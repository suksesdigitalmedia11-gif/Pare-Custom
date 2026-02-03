<?php

namespace App\Http\Controllers\KepalaToko;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesOrderLog;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\Payment;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\NumberGenerator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\SalesPurchaseSyncService;
use Illuminate\Support\Facades\Validator;
use App\Traits\HandlesSalesOrderWorkflow;
use App\Traits\ManagesPayments;

class SalesOrderController extends Controller
{
    use HandlesSalesOrderWorkflow, ManagesPayments;
    private function checkActiveShift(): bool|RedirectResponse
    {
        $activeShift = Shift::getActiveShift(); // PAKAI METHOD BARU
        if (!$activeShift) {
            \Log::warning('No active shift found');
            return redirect()->route('kepala-toko.shift.dashboard')->with('error', 'Silakan mulai shift terlebih dahulu untuk melakukan aksi ini.');
        }
        return true;
    }




    public function index(Request $request): View
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $payment_status = $request->get('payment_status');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        $salesOrders = SalesOrder::with(['customer', 'creator', 'approver'])
            ->when($q, fn($query) =>
                $query->where('so_number', 'like', "%$q%")
                    ->orWhereHas('customer', fn($qq) => $qq->where('name', 'like', "%$q%"))
            )
            ->when($status, fn($query) => $query->where('status', $status))
            ->when($payment_status && $payment_status !== 'all', fn($query) => $query->where('payment_status', $payment_status))
            // ✅ FILTER TANGGAL ORDER (ORDER_DATE BUKAN CREATED_AT)
            ->when($start_date, fn($query) => $query->whereDate('order_date', '>=', $start_date))
            ->when($end_date, fn($query) => $query->whereDate('order_date', '<=', $end_date))
            // ✅ UBAH SORTING: order_date DESC bukan id
            ->orderByDesc('order_date')
            ->paginate(15);

        return view('kepala-toko.sales.index', compact('salesOrders', 'q', 'status', 'payment_status', 'start_date', 'end_date'));
    }

    public function create(): View|RedirectResponse
    {
        $activeShift = Shift::getActiveShift(); // PAKAI METHOD BARU
        if (!$activeShift) {
            return redirect()->route('kepala-toko.shift.dashboard')->with('error', 'Silakan mulai shift dan masukkan kas awal terlebih dahulu.');
        }
        $customers = Customer::orderBy('name')->get();
        $products = Product::where('is_active', true)->where('price', '>=', 0)->orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get(); // ✅ Tambahkan ini
        return view('kepala-toko.sales.create', compact('customers', 'products', 'activeShift', 'suppliers')); // ✅ Tambahkan 'suppliers'
    }

    public function store(Request $request): RedirectResponse|View
    {
        \Log::info('Store request received', $request->all());
        $activeShift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
        if (!$activeShift) {
            \Log::error('No active shift found for user: ' . Auth::id());
            return back()->withErrors(['error' => 'Tidak ada shift aktif. Silakan mulai shift terlebih dahulu.'])->withInput();
        }
    
        // Tentukan status dari input (draft atau pending)
        $status = $request->input('status', 'pending');
    
        // Validasi dasar (selalu wajib)
        $validated = $request->validate([
            'order_type' => ['required', 'in:jahit_sendiri,beli_jadi'],
            'order_date' => ['required', 'date'],
            'deadline' => ['nullable', 'date'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'], 
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'payment_method' => $status === 'draft' ? ['nullable', 'in:cash,transfer,split'] : ['required', 'in:cash,transfer,split'],
            'payment_status' => $status === 'draft' ? ['nullable', 'in:dp,lunas'] : ['required', 'in:dp,lunas'],
            'add_to_purchase' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.sku' => ['nullable', 'string', 'max:100'],
            'items.*.sale_price' => ['required', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'payment_amount' => $status === 'draft' ? ['nullable'] : ['nullable', 'numeric', 'min:0'],
            'cash_amount' => $status === 'draft' ? ['nullable'] : ['nullable', 'numeric', 'min:0'],
            'transfer_amount' => $status === 'draft' ? ['nullable'] : ['nullable', 'numeric', 'min:0'],
            'paid_at' => $status === 'draft' ? ['nullable'] : ['nullable', 'date'],
            'proof_path' => $status === 'draft' ? ['nullable'] : ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'reference_number' => $status === 'draft' ? ['nullable'] : ['nullable', 'string', 'max:100'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'], // ✅ TAMBAH INI
        ]);
    
        // === VALIDASI CUSTOMER - TAMBAH INI ===
    
        \Log::info('Validated data', $validated);
    
        foreach ($request->items as $index => $item) {
            if (!empty($item['product_id'])) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    \Log::error("Invalid product at index $index", $item);
                    return back()->withErrors(["items.$index.product_id" => 'Produk tidak valid.'])->withInput();
                }
            }
        }
    
        $subtotal = collect($validated['items'])->reduce(function ($carry, $item) {
            return $carry + ((float)$item['sale_price'] * (int)$item['qty']);
        }, 0);
        $discountTotal = (float)($validated['discount_total'] ?? 0);
        $shippingCost = (float)($validated['shipping_cost'] ?? 0); // ✅ TAMBAH INI
        $grandTotal = $subtotal - $discountTotal + $shippingCost; // ✅ UPDATE INI
    
        $cashAmount = 0;
        $transferAmount = 0;
        $paymentAmount = 0;
    
        if ($status !== 'draft') {
            $cashAmount = $validated['payment_method'] === 'split' ? ($validated['cash_amount'] ?? 0) : ($validated['payment_method'] === 'cash' ? ($validated['payment_amount'] ?? 0) : 0);
            $transferAmount = $validated['payment_method'] === 'split' ? ($validated['transfer_amount'] ?? 0) : ($validated['payment_method'] === 'transfer' ? ($validated['payment_amount'] ?? 0) : 0);
            $paymentAmount = $cashAmount + $transferAmount;
    
            \Log::info('Calculated payment', ['payment_amount' => $paymentAmount, 'cash' => $cashAmount, 'transfer' => $transferAmount, 'grand_total' => $grandTotal]);
    
            // ✅ HANYA CEK JIKA BUKAN DRAFT
            if ($paymentAmount > 0) {
                // ❌ HAPUS CEK 50% DP
                if ($paymentAmount > $grandTotal) {
                    \Log::error('Payment amount exceeds grand total', ['payment_amount' => $paymentAmount, 'grand_total' => $grandTotal]);
                    return back()->withErrors(['payment_amount' => 'Jumlah melebihi grand total: Rp ' . number_format($grandTotal, 0, ',', '.')])->withInput();
                }
            }
        }
    
        try {
            $salesOrder = DB::transaction(function () use ($validated, $request, $cashAmount, $transferAmount, $paymentAmount, $grandTotal, $activeShift, $status, $subtotal, $discountTotal, $shippingCost) {
                // === AUTO CREATE CUSTOMER LOGIC - PERBAIKI INI ===
$customerId = $validated['customer_id'] ?? null;

if (empty($customerId) && !empty($validated['customer_name'])) {
    // Cek dulu apakah customer dengan nama yang sama sudah ada
    $existingCustomer = Customer::where('name', $validated['customer_name'])->first();
    
    if ($existingCustomer) {
        // Gunakan customer yang sudah ada
        $customerId = $existingCustomer->id;
        \Log::info('Using existing customer', ['customer_id' => $customerId, 'name' => $existingCustomer->name]);
    } else {
        // Buat customer baru
        $customer = Customer::create([
            'name' => $validated['customer_name'],
            'phone' => $validated['customer_phone'] ?? null,
            'email' => null,
            'address' => null,
            'notes' => 'Auto-created from sales order',
            'is_active' => true,
        ]);
        $customerId = $customer->id;
        \Log::info('Auto-created customer', ['customer_id' => $customerId, 'name' => $customer->name, 'phone' => $customer->phone]);
    }
}
                // === END AUTO CREATE CUSTOMER ===
    
                $soNumber = $this->generateSoNumber();
    
                $salesOrder = SalesOrder::create([
                    'so_number' => $soNumber,
                    'order_type' => $validated['order_type'],
                    'order_date' => $validated['order_date'],
                    'customer_id' => $customerId ?? null,
                    'deadline' => $validated['deadline'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'shipping_cost' => $shippingCost, // ✅ TAMBAH INI
                    'grand_total' => $grandTotal,
                    'status' => $status, // ✅ BISA 'draft' ATAU 'pending'
                    'payment_method' => $validated['payment_method'] ?? null,
                    'payment_status' => $validated['payment_status'] ?? null,
                    'created_by' => Auth::id(),
                    'add_to_purchase' => (bool) ($request->input('add_to_purchase') ?? false),
                ]);
    
                foreach ($validated['items'] as $item) {
                    $lineTotal = (float)$item['sale_price'] * (int)$item['qty'];
                    
                    // ✅ Ambil cost_price dari product saat ini (snapshot)
                    $costPrice = 0;
                    if (!empty($item['product_id'])) {
                        $product = Product::find($item['product_id']);
                        if ($product) {
                            $costPrice = $product->cost_price ?? 0;
                        }
                    }
                    
                    SalesOrderItem::create([
                        'sales_order_id' => $salesOrder->id,
                        'product_id' => $item['product_id'] ?? null,
                        'product_name' => $item['product_name'],
                        'sku' => $item['sku'] ?? null,
                        'sale_price' => $item['sale_price'],
                        'cost_price' => $costPrice, // ✅ Snapshot harga modal
                        'qty' => $item['qty'],
                        'discount' => 0, // SET 0 karena diskon sekarang di level order
                        'line_total' => $lineTotal,
                    ]);
                }
    
                // ✅ HANYA PROSES PEMBAYARAN JIKA BUKAN DRAFT
                if ($status !== 'draft' && $paymentAmount > 0) {
                    $proofPath = $request->hasFile('proof_path')
                        ? $request->file('proof_path')->store('payment-proofs', 'public')
                        : null;
    
                        if (in_array($validated['payment_method'], ['transfer', 'split'])) {
                            $hasProof = $request->hasFile('proof_path');
                            $hasReference = !empty($validated['reference_number']);
                            
                            if (!$hasProof && !$hasReference) {
                                return back()->withErrors([
                                    'proof_path' => 'Untuk metode transfer/split, wajib upload bukti transfer atau isi no referensi.'
                                ])->withInput();
                            }
                        }
    
                    $paymentCategory = ($paymentAmount >= $grandTotal) ? 'pelunasan' : 'dp';
                    $payment = Payment::create([
                        'sales_order_id' => $salesOrder->id,
                        'method' => $validated['payment_method'],
                        'status' => $validated['payment_status'],
                        'category' => $paymentCategory,
                        'amount' => $paymentAmount,
                        'cash_amount' => $cashAmount,
                        'transfer_amount' => $transferAmount,
                        'paid_at' => $validated['paid_at'] ?? now(),
                        'proof_path' => $proofPath,
                        'reference_number' => $validated['reference_number'] ?? null,
                        'created_by' => Auth::id(),
                    ]);
    
                    \Log::info('Payment created', ['payment_id' => $payment->id, 'amount' => $paymentAmount, 'proof_path' => $proofPath ?? 'none']);
    
                    if ($cashAmount > 0 && $activeShift) {
                        $activeShift->increment('cash_total', $cashAmount);
                    }
    
                    $this->logAction($salesOrder, 'payment_added', "Pembayaran ditambahkan: {$paymentCategory}, Jumlah: Rp " . number_format($paymentAmount, 0, ',', '.') . ", Metode: {$validated['payment_method']}" . ($proofPath ? "" : ", tanpa bukti"));
                }
    
                $this->logAction($salesOrder, 'created', "Sales order dibuat: {$soNumber}, Tipe: {$validated['order_type']}, Total: Rp " . number_format($grandTotal, 0, ',', '.'));
    
                return $salesOrder;
            });
    
            \Log::info('Sales order created successfully', ['so_number' => $salesOrder->so_number]);
    
            // === AUTO CREATE PURCHASE ORDER JIKA DICEKLIS ===
            if ($status !== 'draft' && $request->has('add_to_purchase') && $request->boolean('add_to_purchase')) {
                $itemsToPurchase = [];
                foreach ($validated['items'] as $item) {
                    if (!empty($item['product_id'])) {
                        $product = Product::find($item['product_id']);
                        if ($product && $product->stock_qty < $item['qty']) {
                            $itemsToPurchase[] = [
                                'product_id' => $item['product_id'],
                                'product_name' => $item['product_name'],
                                'sku' => $item['sku'] ?? null,
                                'cost_price' => 0,
                                'qty' => $item['qty'],
                                'discount' => 0,
                            ];
                        }
                    } else {
                        $itemsToPurchase[] = [
                            'product_id' => null,
                            'product_name' => $item['product_name'],
                            'sku' => $item['sku'] ?? null,
                            'cost_price' => 0,
                            'qty' => $item['qty'],
                            'discount' => 0,
                        ];
                    }
                }
    
                if (!empty($itemsToPurchase)) {
                    try {
                        DB::transaction(function () use ($salesOrder, $itemsToPurchase, $request) {
                            $supplierId = $request->input('supplier_id');
                            $supplierName = $request->input('supplier_name');
                            if ($supplierId) {
                                $supplier = Supplier::findOrFail($supplierId);
                            } elseif ($supplierName) {
                                $supplier = Supplier::firstOrCreate(
                                    ['name' => $supplierName],
                                    ['is_active' => true]
                                );
                            } else {
                                $supplier = Supplier::firstOrCreate(
                                    ['name' => 'Pre-order Customer'],
                                    ['is_active' => true]
                                );
                            }
    
                            $poNumber = app(\App\Http\Controllers\Admin\PurchaseOrderController::class)->generatePoNumber();
    
                            $subtotalPo = collect($itemsToPurchase)->sum(fn($i) => $i['cost_price'] * $i['qty']);
                            $discountTotalPo = collect($itemsToPurchase)->sum(fn($i) => $i['discount']);
                            $grandTotalPo = $subtotalPo - $discountTotalPo;
    
                            $purchaseOrder = PurchaseOrder::create([
                                'po_number' => $poNumber,
                                'order_date' => now(),
                                'supplier_id' => $supplier->id,
                                'purchase_type' => $salesOrder->order_type === 'jahit_sendiri' ? 'kain' : 'produk_jadi',
                                'deadline' => $salesOrder->deadline,
                                'subtotal' => $subtotalPo,
                                'discount_total' => $discountTotalPo,
                                'grand_total' => $grandTotalPo,
                                'status' => PurchaseOrder::STATUS_DRAFT,
                                'is_paid' => false,
                                'created_by' => Auth::id(),
                                'sales_order_id' => $salesOrder->id,
                            ]);
    
                            foreach ($itemsToPurchase as $item) {
                                PurchaseOrderItem::create([
                                    'purchase_order_id' => $purchaseOrder->id,
                                    'product_id' => $item['product_id'],
                                    'product_name' => $item['product_name'],
                                    'sku' => $item['sku'],
                                    'cost_price' => $item['cost_price'],
                                    'qty' => $item['qty'],
                                    'discount' => $item['discount'],
                                    'line_total' => ($item['cost_price'] * $item['qty']) - $item['discount'],
                                ]);
                            }
    
                            \App\Models\PurchaseOrderLog::create([
                                'purchase_order_id' => $purchaseOrder->id,
                                'user_id' => Auth::id(),
                                'action' => 'created',
                                'description' => "Purchase order dibuat dari Sales Order: {$salesOrder->so_number} - Customer: " . ($salesOrder->customer->name ?? 'Unknown'),
                                'created_at' => now(),
                            ]);
    
                            $this->logAction($salesOrder, 'linked_to_purchase', "Linked to Purchase Order: {$poNumber}");
                        });
                    } catch (\Exception $e) {
                        \Log::error('Error auto-creating purchase order for SO: ' . $salesOrder->so_number . ' - ' . $e->getMessage());
                    }
                }
            }
    
            return redirect()->route('kepala-toko.sales.show', $salesOrder)->with('success', 'Sales order berhasil dibuat.');
        } catch (\Exception $e) {
            \Log::error('Error storing sales order: ' . $e->getMessage(), ['request' => $request->all()]);
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(SalesOrder $salesOrder): View
    {
        $salesOrder->load(['customer', 'items', 'creator', 'approver', 'payments.creator', 'logs.user']);
        $payment = $salesOrder->payments->first() ?? new Payment();
        $activeShift = Shift::getActiveShift(); // ✅ PAKAI METHOD BARU UNTUK KONSISTENSI
        return view('kepala-toko.sales.show', compact('salesOrder', 'payment', 'activeShift'));
    }

    public function edit(SalesOrder $salesOrder): View|RedirectResponse
    {
        $shiftCheck = $this->checkActiveShift();
        if ($shiftCheck !== true) {
            return $shiftCheck;
        }
    
        if (!$salesOrder->isEditable()) {
            \Log::warning('Attempt to edit non-editable SO: ' . $salesOrder->so_number);
            return back()->withErrors(['error' => 'Sales order yang selesai tidak bisa diedit.']);
        }
        
        $customers = Customer::orderBy('name')->get();
        $products = Product::where('is_active', true)->where('price', '>=', 0)->orderBy('name')->get();
        $activeShift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
        
        // ✅ TAMBAH INI: Cari PO terkait dan ambil supplier data
        $relatedPurchaseOrder = PurchaseOrder::where('sales_order_id', $salesOrder->id)->first();
        $selectedSupplier = null;
        $supplierName = 'Pre-order Customer';
        
        if ($relatedPurchaseOrder && $relatedPurchaseOrder->supplier) {
            $selectedSupplier = $relatedPurchaseOrder->supplier;
            $supplierName = $selectedSupplier->name;
        }
        
        $suppliers = Supplier::orderBy('name')->get();
    
        return view('kepala-toko.sales.edit', compact(
            'salesOrder', 
            'customers', 
            'products', 
            'activeShift', 
            'suppliers',
            'selectedSupplier', // ✅ KIRIM DATA SUPPLIER YANG DIPILIH
            'supplierName'      // ✅ KIRIM NAMA SUPPLIER
        ));
    }

    public function update(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        \Log::info('=== SALES ORDER UPDATE START ===', [
            'so_number' => $salesOrder->so_number,
            'user_id' => Auth::id(),
            'request_data' => $request->all()
        ]);
    
        $shiftCheck = $this->checkActiveShift();
        if ($shiftCheck !== true) {
            \Log::warning('Shift check failed for SO update: ' . $salesOrder->so_number);
            return $shiftCheck;
        }
        
        if (!$salesOrder->isEditable()) {
            \Log::warning('Attempt to update non-editable SO: ' . $salesOrder->so_number);
            return back()->withErrors(['error' => 'Sales order yang selesai tidak bisa diedit.']);
        }
        
        \Log::info('Validation starting for SO: ' . $salesOrder->so_number);
        
        $status = $request->input('status', $salesOrder->status);
        
        $validated = $request->validate([
            'order_type' => ['required', 'in:jahit_sendiri,beli_jadi'],
            'order_date' => ['required', 'date'],
            'deadline' => ['nullable', 'date'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'payment_method' => $status === 'draft' ? ['nullable', 'in:cash,transfer,split'] : ['required', 'in:cash,transfer,split'],
            'payment_status' => $status === 'draft' ? ['nullable', 'in:dp,lunas'] : ['required', 'in:dp,lunas'],
            'add_to_purchase' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.sku' => ['nullable', 'string', 'max:100'],
            'items.*.sale_price' => ['required', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
        ]);
    
        \Log::info('Validation passed', ['validated_data' => $validated]);
    
        $items = $validated['items'] ?? [];
        if (!is_array($items)) {
            $items = [];
        }
    
        foreach ($items as $index => $item) {
            if (!empty($item['product_id'])) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    \Log::error("Invalid product at index $index", $item);
                    return back()->withErrors(["items.$index.product_id" => 'Produk yang dipilih tidak valid.'])->withInput();
                }
            }
        }
    
        $subtotal = collect($items)->reduce(function ($carry, $item) {
            return $carry + ((float)$item['sale_price'] * (int)$item['qty']);
        }, 0);
        
        $discountTotal = (float)($validated['discount_total'] ?? 0);
        $shippingCost = (float)($validated['shipping_cost'] ?? 0);
        $grandTotal = $subtotal - $discountTotal + $shippingCost;

        try {
            DB::transaction(function () use ($salesOrder, $validated, $request, $grandTotal, $subtotal, $discountTotal, $shippingCost, $status, $items) {
                \Log::info('Transaction started for SO update: ' . $salesOrder->so_number);
                
                $customerId = $validated['customer_id'] ?? null;
    
                if (empty($customerId) && !empty($validated['customer_name'])) {
                    // Cek dulu apakah customer dengan nama yang sama sudah ada
                    $existingCustomer = Customer::where('name', $validated['customer_name'])->first();
                    
                    if ($existingCustomer) {
                        // Gunakan customer yang sudah ada
                        $customerId = $existingCustomer->id;
                        \Log::info('Using existing customer', ['customer_id' => $customerId, 'name' => $existingCustomer->name]);
                    } else {
                        // Buat customer baru
                        $customer = Customer::create([
                            'name' => $validated['customer_name'],
                            'phone' => $validated['customer_phone'] ?? null,
                            'email' => null,
                            'address' => null,
                            'notes' => 'Auto-created from sales order edit',
                            'is_active' => true,
                        ]);
                        $customerId = $customer->id;
                        \Log::info('Auto-created customer in update', ['customer_id' => $customerId, 'name' => $customer->name, 'phone' => $customer->phone]);
                    }
                }
                
                $salesOrder->update([
                    'order_type' => $validated['order_type'],
                    'order_date' => $validated['order_date'],
                    'customer_id' => $customerId ?? null,
                    'deadline' => $validated['deadline'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'shipping_cost' => $shippingCost,
                    'grand_total' => $grandTotal,
                    'payment_method' => $validated['payment_method'] ?? null,
                    'payment_status' => $validated['payment_status'] ?? null,
                    'status' => $status,
                    'add_to_purchase' => (bool) ($request->input('add_to_purchase') ?? false),
                ]);

                $salesOrder->items()->delete();
                foreach ($items as $item) {
                    $lineTotal = (float)$item['sale_price'] * (int)$item['qty'];
                    
                    // ✅ Ambil cost_price dari product saat ini (snapshot)
                    $costPrice = 0;
                    if (!empty($item['product_id'])) {
                        $product = Product::find($item['product_id']);
                        if ($product) {
                            $costPrice = $product->cost_price ?? 0;
                        }
                    }
                    
                    SalesOrderItem::create([
                        'sales_order_id' => $salesOrder->id,
                        'product_id' => $item['product_id'] ?? null,
                        'product_name' => $item['product_name'],
                        'sku' => $item['sku'] ?? null,
                        'sale_price' => $item['sale_price'],
                        'cost_price' => $costPrice, // ✅ Snapshot harga modal
                        'qty' => $item['qty'],
                        'discount' => 0, // SET 0 karena diskon sekarang di level order
                        'line_total' => $lineTotal,
                    ]);
                }

                // ✅ HANYA PROSES PEMBAYARAN JIKA BUKAN DRAFT DAN ADA PAYMENT
                if ($status !== 'draft' && !empty($validated['payment_method'])) {
                    $cashAmount = $validated['payment_method'] === 'split' ? ($validated['cash_amount'] ?? 0) : ($validated['payment_method'] === 'cash' ? ($validated['payment_amount'] ?? 0) : 0);
                    $transferAmount = $validated['payment_method'] === 'split' ? ($validated['transfer_amount'] ?? 0) : ($validated['payment_method'] === 'transfer' ? ($validated['payment_amount'] ?? 0) : 0);
                    $paymentAmount = $cashAmount + $transferAmount;

                    if ($paymentAmount > 0) {
                        $proofPath = $request->hasFile('proof_path')
                            ? $request->file('proof_path')->store('payment-proofs', 'public')
                            : null;
                    
                        // VALIDASI: Untuk transfer/split, wajib bukti ATAU no referensi
                        if (in_array($validated['payment_method'], ['transfer', 'split'])) {
                            $hasProof = $request->hasFile('proof_path');
                            $hasReference = !empty($validated['reference_number']);
                            
                            if (!$hasProof && !$hasReference) {
                                return back()->withErrors([
                                    'proof_path' => 'Untuk metode transfer/split, wajib upload bukti transfer atau isi no referensi.'
                                ])->withInput();
                            }
                        }
                    
                        $paymentCategory = ($paymentAmount >= $grandTotal) ? 'pelunasan' : 'dp';
                    
                        $latestPayment = Payment::where('sales_order_id', $salesOrder->id)->latest('created_at')->first();
                    
                        if ($latestPayment) {
                            $latestPayment->update([
                                'method' => $validated['payment_method'],
                                'status' => $validated['payment_status'],
                                'category' => $paymentCategory,
                                'amount' => $paymentAmount,
                                'cash_amount' => $cashAmount,
                                'transfer_amount' => $transferAmount,
                                'paid_at' => $validated['paid_at'] ?? now(),
                                'proof_path' => $proofPath ?? $latestPayment->proof_path,
                                'reference_number' => $validated['reference_number'] ?? $latestPayment->reference_number,
                                'created_by' => Auth::id(),
                            ]);
                        } else {
                            $payment = Payment::create([
                                'sales_order_id' => $salesOrder->id,
                                'method' => $validated['payment_method'],
                                'status' => $validated['payment_status'],
                                'category' => $paymentCategory,
                                'amount' => $paymentAmount,
                                'cash_amount' => $cashAmount,
                                'transfer_amount' => $transferAmount,
                                'paid_at' => $validated['paid_at'] ?? now(),
                                'proof_path' => $proofPath,
                                'reference_number' => $validated['reference_number'] ?? null,
                                'created_by' => Auth::id(),
                            ]);

                            \Log::info('Payment created in update', ['payment_id' => $payment->id, 'amount' => $paymentAmount, 'proof_path' => $proofPath ?? 'none']);

                            $this->logAction($salesOrder, 'payment_added', "Pembayaran ditambahkan: {$paymentCategory}, Jumlah: Rp " . number_format($paymentAmount, 0, ',', '.') . ", Metode: {$validated['payment_method']}" . ($proofPath ? "" : ", tanpa bukti"));
                        }

                        $activeShift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
                        if ($activeShift && $cashAmount > 0) {
                            $activeShift->increment('cash_total', $cashAmount);
                        }
                    }
                }

                $changes = [];
                if ($salesOrder->getOriginal('order_type') !== $validated['order_type']) {
                    $changes[] = "Tipe order berubah dari {$salesOrder->getOriginal('order_type')} ke {$validated['order_type']}";
                }
                if (($salesOrder->getOriginal('payment_method') ?? null) !== ($validated['payment_method'] ?? null)) {
                    $changes[] = "Metode pembayaran berubah dari " . ($salesOrder->getOriginal('payment_method') ?? 'null') . " ke " . ($validated['payment_method'] ?? 'null');
                }
                if (($salesOrder->getOriginal('payment_status') ?? null) !== ($validated['payment_status'] ?? null)) {
                    $changes[] = "Status pembayaran berubah dari " . ($salesOrder->getOriginal('payment_status') ?? 'null') . " ke " . ($validated['payment_status'] ?? 'null');
                }
                if ($salesOrder->getOriginal('grand_total') != $grandTotal) {
                    $changes[] = "Grand total berubah dari Rp " . number_format($salesOrder->getOriginal('grand_total'), 0, ',', '.') . " ke Rp " . number_format($grandTotal, 0, ',', '.');
                }
                // Sinkronkan PO terkait jika ada
                $this->syncPurchaseOrder($salesOrder);

                if (!empty($changes)) {
                    $this->logAction($salesOrder, 'updated_details', implode(', ', $changes));
                }
            });

            \Log::info('Sales order updated successfully', ['so_number' => $salesOrder->so_number]);
            return redirect()->route('kepala-toko.sales.show', $salesOrder)->with('success', 'Sales order berhasil diperbarui.');
        } catch (\Exception $e) {
            \Log::error('Error updating sales order: ' . $e->getMessage(), ['so_number' => $salesOrder->so_number]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat update SO: ' . $e->getMessage()])->withInput();
        }
    }

    public function uploadProof(Request $request, SalesOrder $salesOrder, Payment $payment)
    {
        $request->validate([
            'proof_path' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'], // Tetap required karena khusus untuk upload bukti
        ]);
    
        try {
            $proofPath = $request->file('proof_path')->store('payment-proofs', 'public');
            
            $payment->update([
                'proof_path' => $proofPath
            ]);
    
            $this->logAction($salesOrder, 'proof_uploaded', "Bukti pembayaran diupload untuk payment ID: {$payment->id}");
    
            return back()->with('success', 'Bukti pembayaran berhasil diupload.');
        } catch (\Exception $e) {
            \Log::error('Error uploading proof: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal upload bukti: ' . $e->getMessage()]);
        }
    }

    /**
     * Sinkronkan Purchase Order terkait ketika Sales Order diupdate (detail item/total).
     */
    private function syncPurchaseOrder(SalesOrder $salesOrder): void
    {
        $purchaseOrder = PurchaseOrder::where('sales_order_id', $salesOrder->id)->first();
        if (!$purchaseOrder) {
            return; // Tidak ada PO terkait
        }

        try {
            DB::transaction(function () use ($salesOrder, $purchaseOrder) {
                // Hapus items PO lama
                $purchaseOrder->items()->delete();

                // Bangun ulang items PO dari items SO
                foreach ($salesOrder->items as $soItem) {
                    $costPrice = 0;
                    if ($soItem->product_id) {
                        $product = Product::find($soItem->product_id);
                        if ($product) {
                            $costPrice = $product->cost_price ?? 0;
                        }
                    }

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'product_id' => $soItem->product_id,
                        'product_name' => $soItem->product_name,
                        'sku' => $soItem->sku,
                        'cost_price' => $costPrice,
                        'qty' => $soItem->qty,
                        'discount' => 0,
                        'line_total' => $costPrice * $soItem->qty,
                    ]);
                }

                // Hitung ulang total PO
                $subtotalPo = $purchaseOrder->items()->sum('line_total');
                $purchaseOrder->update([
                    'subtotal' => $subtotalPo,
                    'grand_total' => $subtotalPo,
                ]);

                // Log di PO & SO
                \App\Models\PurchaseOrderLog::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'user_id' => Auth::id(),
                    'action' => 'updated',
                    'description' => "Purchase order di-update dari Sales Order: {$salesOrder->so_number}",
                    'created_at' => now(),
                ]);

                $this->logAction($salesOrder, 'purchase_order_updated', "Purchase Order terkait di-update: {$purchaseOrder->po_number}");
            });
        } catch (\Exception $e) {
            \Log::error('Error syncing purchase order for SO ' . $salesOrder->so_number . ': ' . $e->getMessage());
        }
    }

    public function approve(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        if ($salesOrder->status !== 'pending') {
            \Log::warning('Attempt to approve non-pending SO: ' . $salesOrder->so_number);
            return back()->withErrors(['status' => 'Hanya pending yang bisa di-approve.']);
        }

        try {
            $salesOrder->update(['approved_by' => Auth::id(), 'approved_at' => Carbon::now()]);
            $this->logAction($salesOrder, 'approved', 'Sales order di-approve oleh ' . Auth::user()->name);
            \Log::info('Sales order approved', ['so_number' => $salesOrder->so_number]);
            return back()->with('success', 'Sales order di-approve.');
        } catch (\Exception $e) {
            \Log::error('Error approving sales order: ' . $e->getMessage(), ['so_number' => $salesOrder->so_number]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat approve: ' . $e->getMessage()]);
        }
    }

    public function addPayment(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $shiftCheck = $this->checkActiveShift();
        if ($shiftCheck !== true) {
            return $shiftCheck;
        }

        $validated = $request->validate([
'payment_amount' => ['required', 'numeric', 'min:1', function ($attribute, $value, $fail) use ($salesOrder) {
    // ✅ UPDATE: Hapus syarat minimal 50% DP
    if ($value > $salesOrder->remaining_amount) {
        $fail('Jumlah tidak boleh melebihi sisa: Rp ' . number_format($salesOrder->remaining_amount, 0, ',', '.'));
    }
}],
            'payment_method' => ['required', 'in:cash,transfer,split'],
            'cash_amount' => ['nullable', 'required_if:payment_method,split', 'numeric', 'min:0'],
            'transfer_amount' => ['nullable', 'required_if:payment_method,split', 'numeric', 'min:0'],
            'paid_at' => ['required', 'date'],
            'proof_path' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'reference_number' => ['nullable', 'string', 'max:100'], // nomor referensi transfer
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['payment_method'] === 'split' && $validated['payment_amount'] != ($validated['cash_amount'] ?? 0) + ($validated['transfer_amount'] ?? 0)) {
            \Log::error('Invalid split payment amount', ['payment_amount' => $validated['payment_amount'], 'cash_amount' => $validated['cash_amount'], 'transfer_amount' => $validated['transfer_amount']]);
            return back()->withErrors(['payment_amount' => 'Jumlah total harus sama dengan jumlah cash + transfer.'])->withInput();
        }

// === PERBAIKAN: Validasi yang benar - bukti ATAU no referensi ===
        if (in_array($validated['payment_method'], ['transfer', 'split'])) {
            $hasProof = $request->hasFile('proof_path');
            $hasReference = !empty($validated['reference_number']);
            
            if (!$hasProof && !$hasReference) {
                \Log::error('Missing proof OR reference for transfer/split payment', [
                    'payment_method' => $validated['payment_method'], 
                    'has_proof' => $hasProof,
                    'has_reference' => $hasReference
                ]);
                return back()->withErrors([
                    'proof_path' => 'Untuk metode transfer/split, wajib upload bukti transfer ATAU isi no referensi.'
                ])->withInput();
            }
        }
        try {
            DB::transaction(function () use ($salesOrder, $validated, $request) {
                $proofPath = $request->hasFile('proof_path')
                    ? $request->file('proof_path')->store('payment-proofs', 'public')
                    : null;

                $cashAmount = $validated['payment_method'] === 'cash' ? $validated['payment_amount'] : ($validated['payment_method'] === 'split' ? ($validated['cash_amount'] ?? 0) : 0);
                $transferAmount = $validated['payment_method'] == 'transfer' ? $validated['payment_amount'] : ($validated['payment_method'] === 'split' ? ($validated['transfer_amount'] ?? 0) : 0);

                $paidBefore = $salesOrder->payments()->sum('amount');
                $newPaidTotal = $paidBefore + $validated['payment_amount'];
                $paymentCategory = ($newPaidTotal >= $salesOrder->grand_total) ? 'pelunasan' : 'dp';

                $payment = Payment::create([
                    'sales_order_id' => $salesOrder->id,
                    'method' => $validated['payment_method'],
                    'status' => ($newPaidTotal >= $salesOrder->grand_total) ? 'lunas' : 'dp',
                    'category' => $paymentCategory,
                    'amount' => $validated['payment_amount'],
                    'cash_amount' => $cashAmount,
                    'transfer_amount' => $transferAmount,
                    'paid_at' => $validated['paid_at'],
                    'reference_number' => $validated['reference_number'] ?? null, // PASTIKAN INI
                    'proof_path' => $proofPath,
                    'note' => $validated['note'] ?? null,
                    'created_by' => Auth::id(),
                ]);

                $salesOrder->update(['payment_status' => ($newPaidTotal >= $salesOrder->grand_total) ? 'lunas' : 'dp']);

                $activeShift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
                if ($activeShift && $cashAmount > 0) {
                    $activeShift->increment('cash_total', $cashAmount);
                }

                $this->logAction($salesOrder, 'payment_added', "Pembayaran ditambahkan: {$paymentCategory}, Jumlah: Rp " . number_format($validated['payment_amount'], 0, ',', '.') . ", Metode: {$validated['payment_method']}");
            });

            \Log::info('Payment added successfully', ['so_number' => $salesOrder->so_number]);
            return back()->with('success', 'Pembayaran ditambahkan.');
        } catch (\Exception $e) {
            \Log::error('Error adding payment for SO ' . $salesOrder->so_number . ': ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menambah pembayaran: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * ✅ WORKFLOW BARU: pending → request_kain (untuk SO dengan PO)
     * Hanya Owner, Kepala Toko, Finance yang bisa
     * ✅ TIDAK PERLU SHIFT CHECK - aksi administratif
     */
        public function moveToRequestKain(SalesOrder $salesOrder): RedirectResponse
    {
        return $this->performMoveToRequestKain($salesOrder);
    }

    /**
     * ✅ WORKFLOW BARU: request_kain → payment
     * Hanya Finance yang bisa
     */
    public function moveToPayment(SalesOrder $salesOrder): RedirectResponse
    {
        return $this->performMoveToPayment($salesOrder);
    }

    /**
     * ✅ WORKFLOW BARU: SO tanpa PO - pending → selesai (setelah approved)
     * ✅ TIDAK PERLU SHIFT CHECK - aksi administratif
     */
    public function completeWithoutPO(SalesOrder $salesOrder): RedirectResponse
    {
        if ($salesOrder->status !== 'pending') {
            return back()->withErrors(['status' => 'Hanya status pending yang bisa diselesaikan.']);
        }

        if ($salesOrder->hasRelatedPO()) {
            return back()->withErrors(['error' => 'Sales order ini memiliki Purchase Order terkait. Gunakan workflow yang sesuai.']);
        }

        if ($salesOrder->approved_by === null) {
            return back()->withErrors(['status' => 'Sales order harus di-approve terlebih dahulu.']);
        }

        // ✅ BUG FIX: Validasi pembayaran harus lunas
        if ($salesOrder->remaining_amount > 0) {
            return back()->withErrors(['payment' => 'Pembayaran harus lunas untuk menyelesaikan sales order.']);
        }

        try {
            DB::transaction(function () use ($salesOrder) {
                $this->updateStockOnPayment($salesOrder);
                $salesOrder->update(['status' => 'selesai', 'completed_at' => Carbon::now()]);
                $this->logAction($salesOrder, 'completed', 'Sales order selesai (tanpa PO)');
            });
            
            return back()->with('success', 'Sales order selesai.');
        } catch (\Exception $e) {
            \Log::error('Error completing SO without PO: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * ✅ WORKFLOW BARU: payment → proses_jahit (untuk jahit_sendiri)
     * Admin, Finance, Kepala Toko bisa
     */
    public function processJahit(SalesOrder $salesOrder): RedirectResponse
    {
        return $this->performMoveToGeneric($salesOrder, 'proses_jahit', 'payment_to_proses_jahit');
    }

    /**
     * ✅ WORKFLOW BARU: proses_jahit → printing
     * Admin, Finance, Kepala Toko bisa
     */
    public function markAsJadi(SalesOrder $salesOrder): RedirectResponse
    {
        return $this->performMoveToGeneric($salesOrder, 'printing', 'proses_jahit_to_printing');
    }

    /**
     * ✅ WORKFLOW BARU: printing → diterima_toko (untuk jahit_sendiri) atau payment → diterima_toko (untuk beli_jadi)
     * Admin, Finance, Kepala Toko bisa
     */
    public function markAsDiterimaToko(SalesOrder $salesOrder): RedirectResponse
    {
        return $this->performMoveToGeneric($salesOrder, 'diterima_toko', 'printing_to_diterima_toko');
    }

    /**
     * ✅ WORKFLOW BARU: diterima_toko → selesai
     * Admin, Finance, Kepala Toko bisa
     */
    public function complete(SalesOrder $salesOrder): RedirectResponse
    {
        return $this->performMoveToGeneric($salesOrder, 'selesai', 'diterima_toko_to_selesai');
    }

    public function printNota(Payment $payment): \Illuminate\Http\Response
    {
        $salesOrder = $payment->salesOrder;
        $pdf = Pdf::loadView('kepala-toko.sales.nota', compact('salesOrder', 'payment'));
        return $pdf->download('nota_' . $salesOrder->so_number . '_payment_' . $payment->id . '.pdf');
    }

    public function printNotaDirect(Payment $payment): View
    {
        $salesOrder = $payment->salesOrder;
        return view('kepala-toko.sales.nota', [
            'salesOrder' => $salesOrder,
            'payment' => $payment,
            'autoPrint' => true,
        ]);
    }

    public function searchCustomers(Request $request)
    {
        $query = $request->get('q');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        $customers = Customer::where('name', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->where('is_active', true)
            ->limit(10)
            ->get(['id', 'name', 'phone']);
        
        return response()->json($customers);
    }

    public function searchSuppliers(Request $request)
    {
        $query = $request->get('q');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        $suppliers = Supplier::where('name', 'like', "%{$query}%")
            ->orWhere('contact_name', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->where('is_active', true)
            ->limit(10)
            ->get(['id', 'name', 'contact_name', 'phone']);
        
        return response()->json($suppliers);
    }

    public function linkToPurchaseOrder(SalesOrder $salesOrder, Request $request): \Illuminate\Http\JsonResponse
    {
        $shiftCheck = $this->checkActiveShift();
        if ($shiftCheck !== true) {
            return response()->json(['error' => 'Shift check failed'], 403);
        }
    
        \Log::info('Manual linking SO to PO', [
            'so_number' => $salesOrder->so_number,
            'user_id' => Auth::id()
        ]);
    
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'supplier_id' => ['nullable', 'exists:suppliers,id'],
                'supplier_name' => ['nullable', 'string', 'max:255'],
                'items_mode' => ['nullable', 'in:all,selected'],
                'selected_items' => ['nullable', 'array'],
                'selected_items.*' => ['integer'],
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 422);
            }

            $validated = $validator->validated();
            $itemsMode = $validated['items_mode'] ?? 'all';
            $selectedIds = collect($validated['selected_items'] ?? [])
                ->filter(fn($id) => !empty($id))
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values();

            DB::transaction(function () use ($salesOrder, $validated, $itemsMode, $selectedIds) {
                // Cek apakah sudah ada PO terkait
                $existingPO = PurchaseOrder::where('sales_order_id', $salesOrder->id)->first();
                if ($existingPO) {
                    throw new \Exception('Sales order ini sudah terkait dengan PO: ' . $existingPO->po_number);
                }
    
                // Buat PO baru berdasarkan SO
                $supplierId = $validated['supplier_id'] ?? null;
                $supplierName = $validated['supplier_name'] ?? null;
                
                if ($supplierId) {
                    $supplier = Supplier::findOrFail($supplierId);
                } elseif ($supplierName) {
                    $supplier = Supplier::firstOrCreate(
                        ['name' => $supplierName],
                        ['is_active' => true]
                    );
                } else {
                    $supplier = Supplier::firstOrCreate(
                        ['name' => 'Pre-order Customer'],
                        ['is_active' => true]
                    );
                }
    
                $poNumber = app(\App\Http\Controllers\Admin\PurchaseOrderController::class)->generatePoNumber();

                $purchaseOrder = PurchaseOrder::create([
                    'po_number' => $poNumber,
                    'order_date' => now(),
                    'supplier_id' => $supplier->id,
                    'purchase_type' => $salesOrder->order_type === 'jahit_sendiri' ? 'kain' : 'produk_jadi',
                    'deadline' => $salesOrder->deadline,
                    'subtotal' => 0,
                    'discount_total' => 0,
                    'grand_total' => 0,
                    'status' => PurchaseOrder::STATUS_DRAFT,
                    'is_paid' => false,
                    'created_by' => Auth::id(),
                    'sales_order_id' => $salesOrder->id,
                    'notes' => 'Customer: ' . ($salesOrder->customer->name ?? 'N/A'),
                ]);
    
                // Tentukan items SO yang akan dimasukkan ke PO
                $itemsQuery = $salesOrder->items();
                if ($itemsMode === 'selected') {
                    if ($selectedIds->isEmpty()) {
                        throw new \Exception('Pilih minimal satu produk untuk membuat Purchase Order.');
                    }
                    $itemsQuery->whereIn('id', $selectedIds);
                }

                $itemsForPurchase = $itemsQuery->get();

                if ($itemsForPurchase->isEmpty()) {
                    throw new \Exception('Tidak ada produk yang valid untuk dimasukkan ke Purchase Order.');
                }

                // Buat items PO berdasarkan pilihan
                foreach ($itemsForPurchase as $soItem) {
                    $costPrice = 0;
                    if ($soItem->product_id) {
                        $product = Product::find($soItem->product_id);
                        if ($product) {
                            $costPrice = $product->cost_price ?? 0;
                        }
                    }
                    
                    $lineTotal = $costPrice * $soItem->qty;
                    
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'product_id' => $soItem->product_id,
                        'product_name' => $soItem->product_name,
                        'sku' => $soItem->sku,
                        'cost_price' => $costPrice,
                        'qty' => $soItem->qty,
                        'discount' => 0,
                        'line_total' => $lineTotal,
                    ]);
                }
    
                // Update totals PO
                $subtotalPo = $purchaseOrder->items()->sum('line_total');
                $purchaseOrder->update([
                    'subtotal' => $subtotalPo,
                    'grand_total' => $subtotalPo,
                ]);
    
                // Buat log PO
                \App\Models\PurchaseOrderLog::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'user_id' => Auth::id(),
                    'action' => 'created',
                    'description' => "Purchase order dibuat manual dari Sales Order: {$salesOrder->so_number}",
                    'created_at' => now(),
                ]);
    
                // Update SO flag dan buat log
                $salesOrder->update(['add_to_purchase' => true]);
                $this->logAction($salesOrder, 'linked_to_purchase', "Manual linked to Purchase Order: {$poNumber}");
    
                \Log::info('Manual PO creation successful', [
                    'so_number' => $salesOrder->so_number,
                    'po_number' => $poNumber
                ]);
            });
    
            return response()->json(['success' => true, 'message' => 'Berhasil membuat Purchase Order terkait.']);
    
        } catch (\Exception $e) {
            \Log::error('Error manual linking SO to PO: ' . $e->getMessage(), [
                'so_number' => $salesOrder->so_number
            ]);
            return response()->json(['error' => 'Gagal membuat Purchase Order: ' . $e->getMessage()], 500);
        }
    }

    public function unlinkFromPurchaseOrder(SalesOrder $salesOrder): \Illuminate\Http\JsonResponse
    {
        $shiftCheck = $this->checkActiveShift();
        if ($shiftCheck !== true) {
            return response()->json(['error' => 'Shift check failed'], 403);
        }
    
        \Log::info('Manual unlinking SO from PO', [
            'so_number' => $salesOrder->so_number,
            'user_id' => Auth::id()
        ]);
    
        try {
            DB::transaction(function () use ($salesOrder) {
                // Cari PO terkait
                $purchaseOrder = PurchaseOrder::where('sales_order_id', $salesOrder->id)->first();
                
                if (!$purchaseOrder) {
                    throw new \Exception('Tidak ada Purchase Order terkait untuk SO ini.');
                }
    
                $poNumber = $purchaseOrder->po_number;
                
                // ✅ HAPUS PO (BUKAN HANYA UNLINK)
                // Hapus items PO terlebih dahulu
                $purchaseOrder->items()->delete();
                
                // Hapus logs PO
                $purchaseOrder->logs()->delete();
                
                // Hapus PO itu sendiri
                $purchaseOrder->delete();
    
                // Update SO flag dan buat log
                $salesOrder->update(['add_to_purchase' => false]);
                $this->logAction($salesOrder, 'unlinked_from_purchase', "Unlinked and DELETED Purchase Order: {$poNumber}");
    
                \Log::info('Manual PO deletion successful', [
                    'so_number' => $salesOrder->so_number,
                    'po_number' => $poNumber
                ]);
            });
    
            return response()->json(['success' => true, 'message' => 'Berhasil memutus hubungan dan menghapus Purchase Order.']);
    
        } catch (\Exception $e) {
            \Log::error('Error manual unlinking SO from PO: ' . $e->getMessage(), [
                'so_number' => $salesOrder->so_number
            ]);
            return response()->json(['error' => 'Gagal memutus hubungan: ' . $e->getMessage()], 500);
        }
    }

    // ✅ METHOD UNTUK CEK PO TERKAIT
    public function getRelatedPurchaseOrder(SalesOrder $salesOrder)
    {
        try {
            $purchaseOrder = PurchaseOrder::with('supplier')->where('sales_order_id', $salesOrder->id)->first();
            
            if (!$purchaseOrder) {
                return response()->json(['exists' => false]);
            }

            // Pastikan supplier ter-load
            if (!$purchaseOrder->relationLoaded('supplier')) {
                $purchaseOrder->load('supplier');
            }

            // Generate route dengan parameter yang benar
            $editUrl = route('kepala-toko.purchases.edit', ['purchase' => $purchaseOrder->id]);
            $showUrl = route('kepala-toko.purchases.show', ['purchase' => $purchaseOrder->id]);

            return response()->json([
                'exists' => true,
                'po_number' => $purchaseOrder->po_number,
                'status' => $purchaseOrder->getStatusLabel(),
                'supplier_name' => $purchaseOrder->supplier->name ?? '-',
                'purchase_type' => $purchaseOrder->purchase_type,
                'edit_url' => $editUrl,
                'show_url' => $showUrl
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getRelatedPurchaseOrder: ' . $e->getMessage(), [
                'sales_order_id' => $salesOrder->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error memuat informasi PO terkait: ' . $e->getMessage()], 500);
        }
    }
}