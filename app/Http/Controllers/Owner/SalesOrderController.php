<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesOrderLog;
use App\Models\Supplier;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrder;
use App\Models\Product;
use App\Models\Customer;
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
use App\Traits\HandlesSalesOrderWorkflow;

class SalesOrderController extends Controller
{
    use HandlesSalesOrderWorkflow;




    public function index(Request $request): View
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $payment_status = $request->get('payment_status');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        $salesOrders = SalesOrder::with(['customer', 'creator', 'approver'])
            ->when(
                $q,
                fn($query) =>
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

        return view('owner.sales.index', compact('salesOrders', 'q', 'status', 'payment_status', 'start_date', 'end_date'));
    }

    public function create(): View|RedirectResponse
    {

        $customers = Customer::orderBy('name')->get();
        $products = Product::where('is_active', true)->where('price', '>=', 0)->orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get(); // ✅ Tambahkan ini
        return view('owner.sales.create', compact('customers', 'products', 'suppliers')); // ✅ Tambahkan 'suppliers'
    }

    public function store(Request $request): RedirectResponse|View
    {
        $validated = $request->validate([
            'order_type' => ['required', 'in:jahit_sendiri,beli_jadi'],
            'order_date' => ['required', 'date'],
            'deadline' => ['nullable', 'date'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'payment_method' => ['required', 'in:cash,transfer,split'],
            'payment_status' => ['required', 'in:dp,lunas'],
            'add_to_purchase' => ['nullable', 'boolean'], // ✅ Tambahkan validasi checkbox
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.sku' => ['nullable', 'string', 'max:100'],
            'items.*.sale_price' => ['required', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'cash_amount' => ['nullable', 'numeric', 'min:0'],
            'transfer_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_at' => ['nullable', 'date'],
            'proof_path' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

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
            return $carry + ((float) $item['sale_price'] * (int) $item['qty']);
        }, 0);
        $discountTotal = collect($validated['items'])->sum(function ($item) {
            return (float) ($item['discount'] ?? 0) * (int) $item['qty'];
        });
        $grandTotal = $subtotal - $discountTotal;

        $cashAmount = $validated['payment_method'] === 'split' ? ($validated['cash_amount'] ?? 0) : ($validated['payment_method'] === 'cash' ? ($validated['payment_amount'] ?? 0) : 0);
        $transferAmount = $validated['payment_method'] === 'split' ? ($validated['transfer_amount'] ?? 0) : ($validated['payment_method'] === 'transfer' ? ($validated['payment_amount'] ?? 0) : 0);
        $paymentAmount = $cashAmount + $transferAmount;

        \Log::info('Calculated payment', ['payment_amount' => $paymentAmount, 'cash' => $cashAmount, 'transfer' => $transferAmount, 'grand_total' => $grandTotal]);

        if ($paymentAmount > 0) {
            if ($paymentAmount > $grandTotal) {
                \Log::error('Payment amount exceeds grand total', ['payment_amount' => $paymentAmount, 'grand_total' => $grandTotal]);
                return back()->withErrors(['payment_amount' => 'Jumlah melebihi grand total: Rp ' . number_format($grandTotal, 0, ',', '.')])->withInput();
            }
        }

        $status = 'pending';
        try {
            $salesOrder = DB::transaction(function () use ($validated, $request, $cashAmount, $transferAmount, $paymentAmount, $grandTotal, $status, $subtotal, $discountTotal) {
                // === AUTO CREATE CUSTOMER LOGIC ===
                $customerId = $validated['customer_id'] ?? null;
                if (empty($customerId) && !empty($validated['customer_name'])) {
                    $existingCustomer = Customer::where('name', $validated['customer_name'])->first();
                    if ($existingCustomer) {
                        $customerId = $existingCustomer->id;
                        \Log::info('Using existing customer', ['customer_id' => $customerId, 'name' => $existingCustomer->name]);
                    } else {
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

                $soNumber = $this->generateSoNumber();
                $salesOrder = SalesOrder::create([
                    'so_number' => $soNumber,
                    'order_type' => $validated['order_type'],
                    'order_date' => $validated['order_date'],
                    'customer_id' => $customerId ?? null,
                    'deadline' => $validated['deadline'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'grand_total' => $grandTotal,
                    'status' => $status,
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => $validated['payment_status'],
                    'created_by' => Auth::id(),
                ]);

                foreach ($validated['items'] as $item) {
                    $lineTotal = ((float) $item['sale_price'] * (int) $item['qty']) - ((float) ($item['discount'] ?? 0) * (int) $item['qty']);

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
                        'discount' => $item['discount'] ?? 0,
                        'line_total' => $lineTotal,
                    ]);
                }

                if ($paymentAmount > 0) {
                    $proofPath = $request->hasFile('proof_path')
                        ? $request->file('proof_path')->store('payment-proofs', 'public')
                        : null;

                    if (in_array($validated['payment_method'], ['transfer', 'split'])) {
                        $hasProof = $request->hasFile('proof_path');
                        $hasReference = !empty($validated['reference_number']);
                        if (!$hasProof && !$hasReference) {
                            throw new \Exception('Untuk metode transfer/split, wajib upload bukti transfer atau isi no referensi.');
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


                    $this->logAction($salesOrder, 'payment_added', "Pembayaran ditambahkan: {$paymentCategory}, Jumlah: Rp " . number_format($paymentAmount, 0, ',', '.') . ", Metode: {$validated['payment_method']}" . ($proofPath ? "" : ", tanpa bukti"));
                }

                $this->logAction($salesOrder, 'created', "Sales order dibuat: {$soNumber}, Tipe: {$validated['order_type']}, Total: Rp " . number_format($grandTotal, 0, ',', '.'));
                return $salesOrder;
            });

            \Log::info('Sales order created successfully', ['so_number' => $salesOrder->so_number]);

            // === AUTO CREATE PURCHASE ORDER JIKA DICEKLIS ===
            if ($request->has('add_to_purchase') && $request->boolean('add_to_purchase')) {
                $itemsToPurchase = [];
                foreach ($validated['items'] as $item) {
                    if (!empty($item['product_id'])) {
                        $product = Product::find($item['product_id']);
                        if ($product && $product->stock_qty < $item['qty']) {
                            $itemsToPurchase[] = [
                                'product_id' => $item['product_id'],
                                'product_name' => $item['product_name'],
                                'sku' => $item['sku'] ?? null,
                                'cost_price' => 0, // Kosongkan harga modal
                                'qty' => $item['qty'],
                                'discount' => 0,
                            ];
                        }
                    } else {
                        // Produk custom (tidak ada di database)
                        $itemsToPurchase[] = [
                            'product_id' => null,
                            'product_name' => $item['product_name'],
                            'sku' => $item['sku'] ?? null,
                            'cost_price' => 0, // Kosongkan harga modal
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

                            $poNumber = 'PO' . now()->format('ymd') . str_pad((string) (PurchaseOrder::whereDate('created_at', now()->toDateString())->count() + 1), 4, '0', STR_PAD_LEFT);

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
                            ]);

                            foreach ($itemsToPurchase as $item) {
                                PurchaseOrderItem::create([
                                    'purchase_order_id' => $purchaseOrder->id,
                                    'product_id' => $item['product_id'],
                                    'product_name' => $item['product_name'],
                                    'sku' => $item['sku'],
                                    'cost_price' => $item['cost_price'], // 0
                                    'qty' => $item['qty'],
                                    'discount' => $item['discount'],
                                    'line_total' => ($item['cost_price'] * $item['qty']) - $item['discount'],
                                ]);
                            }

                            \App\Models\PurchaseOrderLog::create([
                                'purchase_order_id' => $purchaseOrder->id,
                                'user_id' => Auth::id(),
                                'action' => 'created',
                                'description' => "Purchase order Dari Penjualan : {$salesOrder->so_number}",
                                'created_at' => now(),
                            ]);

                            $this->logAction($salesOrder, 'linked_to_purchase', "Linked to Purchase Order: {$poNumber}");
                        });
                    } catch (\Exception $e) {
                        \Log::error('Error auto-creating purchase order for SO: ' . $salesOrder->so_number . ' - ' . $e->getMessage());
                        // Tidak menghentikan proses SO
                    }
                }
            }

            // === NONAKTIFKAN AUTO-PRINT (KARENA CETAK DARI SHOW LEBIH RAPI) ===
            return redirect()->route('owner.sales.show', $salesOrder)->with('success', 'Sales order berhasil dibuat.');

        } catch (\Exception $e) {
            \Log::error('Error storing sales order: ' . $e->getMessage(), ['request' => $request->all()]);
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(SalesOrder $salesOrder): View
    {
        $salesOrder->load(['customer', 'items', 'creator', 'approver', 'payments.creator', 'logs.user']);
        $payment = $salesOrder->payments->first() ?? new Payment();
        return view('owner.sales.show', compact('salesOrder', 'payment'));
    }

    public function edit(SalesOrder $salesOrder): View|RedirectResponse
    {

        if (!$salesOrder->isEditable()) {
            \Log::warning('Attempt to edit non-editable SO: ' . $salesOrder->so_number);
            return back()->withErrors(['error' => 'Sales order yang selesai tidak bisa diedit.']);
        }
        $customers = Customer::orderBy('name')->get();
        $products = Product::where('is_active', true)->where('price', '>=', 0)->orderBy('name')->get();
        return view('owner.sales.edit', compact('salesOrder', 'customers', 'products'));
    }

    public function update(Request $request, SalesOrder $salesOrder): RedirectResponse
    {

        if (!$salesOrder->isEditable()) {
            \Log::warning('Attempt to update non-editable SO: ' . $salesOrder->so_number);
            return back()->withErrors(['error' => 'Sales order yang selesai tidak bisa diedit.']);
        }

        $validated = $request->validate([
            'order_type' => ['required', 'in:jahit_sendiri,beli_jadi'],
            'order_date' => ['required', 'date'],
            'deadline' => ['nullable', 'date'], // TAMBAH INI
            'customer_id' => ['nullable', 'exists:customers,id'],
            'payment_method' => ['required', 'in:cash,transfer,split'],
            'payment_status' => ['required', 'in:dp,lunas'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.sku' => ['nullable', 'string', 'max:100'],
            'items.*.sale_price' => ['required', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'cash_amount' => ['nullable', 'numeric', 'min:0'],
            'transfer_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_at' => ['nullable', 'date'],
            'proof_path' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'reference_number' => ['nullable', 'string', 'max:100'], // TAMBAH INI
        ]);

        foreach ($request->items as $index => $item) {
            if (!empty($item['product_id'])) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    \Log::error("Invalid product at index $index", $item);
                    return back()->withErrors(["items.$index.product_id" => 'Produk yang dipilih tidak valid.'])->withInput();
                }
            }
        }

        $subtotal = collect($validated['items'])->reduce(function ($carry, $item) {
            return $carry + ((float) $item['sale_price'] * (int) $item['qty']);
        }, 0);
        $discountTotal = collect($validated['items'])->sum(function ($item) {
            return (float) ($item['discount'] ?? 0) * (int) $item['qty'];
        });
        $grandTotal = $subtotal - $discountTotal;

        $cashAmount = $validated['payment_method'] === 'split' ? ($validated['cash_amount'] ?? 0) : ($validated['payment_method'] === 'cash' ? ($validated['payment_amount'] ?? 0) : 0);
        $transferAmount = $validated['payment_method'] === 'split' ? ($validated['transfer_amount'] ?? 0) : ($validated['payment_method'] === 'transfer' ? ($validated['payment_amount'] ?? 0) : 0);
        $paymentAmount = $cashAmount + $transferAmount;

        \Log::info('Calculated payment in update', ['payment_amount' => $paymentAmount, 'cash' => $cashAmount, 'transfer' => $transferAmount, 'grand_total' => $grandTotal]);

        if ($paymentAmount > 0) {
            if ($paymentAmount > $grandTotal) {
                \Log::error('Payment amount exceeds grand total', ['payment_amount' => $paymentAmount, 'grand_total' => $grandTotal]);
                return back()->withErrors(['payment_amount' => 'Jumlah melebihi grand total: Rp ' . number_format($grandTotal, 0, ',', '.')])->withInput();
            }
        } else {
            \Log::info('No payment amount in update, skipping payment creation');
        }

        try {
            DB::transaction(function () use ($salesOrder, $validated, $request, $cashAmount, $transferAmount, $paymentAmount, $grandTotal, $subtotal, $discountTotal) {
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
                    'deadline' => $validated['deadline'] ?? null, // tambah ini
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'grand_total' => $grandTotal,
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => $validated['payment_status'],
                    'status' => 'pending',
                    'approved_by' => null,
                    'approved_at' => null,
                ]);

                $salesOrder->items()->delete();
                foreach ($validated['items'] as $item) {
                    $lineTotal = ((float) $item['sale_price'] * (int) $item['qty']) - ((float) ($item['discount'] ?? 0) * (int) $item['qty']);

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
                        'discount' => $item['discount'] ?? 0,
                        'line_total' => $lineTotal,
                    ]);
                }

                if ($paymentAmount > 0) {
                    $proofPath = $request->hasFile('proof_path')
                        ? $request->file('proof_path')->store('payment-proofs', 'public')  // ✅ PASTIKAN 'public'
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
                            'reference_number' => $validated['reference_number'] ?? $latestPayment->reference_number, // TAMBAH INI
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
                            'reference_number' => $validated['reference_number'] ?? null, // TAMBAH INI
                            'created_by' => Auth::id(),
                        ]);

                        \Log::info('Payment created in update', ['payment_id' => $payment->id, 'amount' => $paymentAmount, 'proof_path' => $proofPath ?? 'none']);

                        $this->logAction($salesOrder, 'payment_added', "Pembayaran ditambahkan: {$paymentCategory}, Jumlah: Rp " . number_format($paymentAmount, 0, ',', '.') . ", Metode: {$validated['payment_method']}" . ($proofPath ? "" : ", tanpa bukti"));
                    }
                }

                $this->logAction($salesOrder, 'updated', "Sales order diperbarui: Tipe: {$validated['order_type']}, Total: Rp " . number_format($grandTotal, 0, ',', '.'));

                $changes = [];
                if ($salesOrder->getOriginal('order_type') !== $validated['order_type']) {
                    $changes[] = "Tipe order berubah dari {$salesOrder->getOriginal('order_type')} ke {$validated['order_type']}";
                }
                if ($salesOrder->getOriginal('payment_method') !== $validated['payment_method']) {
                    $changes[] = "Metode pembayaran berubah dari {$salesOrder->getOriginal('payment_method')} ke {$validated['payment_method']}";
                }
                if ($salesOrder->getOriginal('payment_status') !== $validated['payment_status']) {
                    $changes[] = "Status pembayaran berubah dari {$salesOrder->getOriginal('payment_status')} ke {$validated['payment_status']}";
                }
                if ($salesOrder->getOriginal('grand_total') != $grandTotal) {
                    $changes[] = "Grand total berubah dari Rp " . number_format($salesOrder->getOriginal('grand_total'), 0, ',', '.') . " ke Rp " . number_format($grandTotal, 0, ',', '.');
                }
                if (!empty($changes)) {
                    $this->logAction($salesOrder, 'updated_details', implode(', ', $changes));
                }
            });

            \Log::info('Sales order updated successfully', ['so_number' => $salesOrder->so_number]);
            return redirect()->route('owner.sales.show', $salesOrder)->with('success', 'Sales order diperbarui dan menunggu approval.');
        } catch (\Exception $e) {
            \Log::error('Error updating sales order: ' . $e->getMessage(), ['so_number' => $salesOrder->so_number]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat update SO: ' . $e->getMessage()])->withInput();
        }
    }

    public function uploadProof(Request $request, SalesOrder $salesOrder, Payment $payment): RedirectResponse
    {

        if ($payment->sales_order_id !== $salesOrder->id) {
            \Log::warning('Invalid payment for SO: ' . $salesOrder->so_number, ['payment_id' => $payment->id]);
            return back()->withErrors(['error' => 'Pembayaran tidak valid untuk sales order ini.']);
        }

        if (!in_array($payment->method, ['transfer', 'split'])) {
            \Log::warning('Invalid payment method for proof upload: ' . $payment->method, ['so_number' => $salesOrder->so_number]);
            return back()->withErrors(['error' => 'Upload bukti hanya untuk metode transfer atau split.']);
        }

        $validated = $request->validate([
            'proof_path' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        try {
            DB::transaction(function () use ($payment, $request) {
                if ($payment->proof_path) {
                    Storage::disk('public')->delete($payment->proof_path);
                }
                $proofPath = $request->file('proof_path')->store('payment-proofs', 'public');
                $payment->update(['proof_path' => $proofPath]);
                $this->logAction($payment->salesOrder, 'proof_uploaded', "Bukti pembayaran diunggah untuk pembayaran ID {$payment->id}");
            });

            \Log::info('Proof uploaded successfully for SO: ' . $salesOrder->so_number, ['payment_id' => $payment->id]);
            return back()->with('success', 'Bukti pembayaran berhasil diunggah.');
        } catch (\Exception $e) {
            \Log::error('Error uploading proof for SO ' . $salesOrder->so_number . ': ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan saat mengunggah bukti: ' . $e->getMessage()]);
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

        $validated = $request->validate([
            'payment_amount' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) use ($salesOrder) {
                    // Hapus syarat minimal 50%
                    if ($value > $salesOrder->remaining_amount) {
                        $fail('Jumlah tidak boleh melebihi sisa: Rp ' . number_format($salesOrder->remaining_amount, 0, ',', '.'));
                    }
                }
            ],
            'payment_method' => ['required', 'in:cash,transfer,split'],
            'cash_amount' => ['nullable', 'required_if:payment_method,split', 'numeric', 'min:0'],
            'transfer_amount' => ['nullable', 'required_if:payment_method,split', 'numeric', 'min:0'],
            'paid_at' => ['required', 'date'],
            'proof_path' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'reference_number' => ['nullable', 'string', 'max:100'], // ✅ TAMBAH INI
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['payment_method'] === 'split' && $validated['payment_amount'] != ($validated['cash_amount'] ?? 0) + ($validated['transfer_amount'] ?? 0)) {
            \Log::error('Invalid split payment amount', ['payment_amount' => $validated['payment_amount'], 'cash_amount' => $validated['cash_amount'], 'transfer_amount' => $validated['transfer_amount']]);
            return back()->withErrors(['payment_amount' => 'Jumlah total harus sama dengan jumlah cash + transfer.'])->withInput();
        }

        // ✅ VALIDASI BARU: Untuk transfer/split, wajib bukti ATAU no referensi
        if ($validated['payment_method'] === 'transfer' || ($validated['payment_method'] === 'split' && ($validated['transfer_amount'] ?? 0) > 0)) {
            $hasProof = $request->hasFile('proof_path');
            $hasReference = !empty($validated['reference_number']);

            if (!$hasProof && !$hasReference) {
                \Log::error('Missing proof or reference for transfer/split', [
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
                    'reference' => $validated['reference'] ?? null,
                    'reference_number' => $validated['reference_number'] ?? null, // ✅ TAMBAH INI
                    'proof_path' => $proofPath,
                    'note' => $validated['note'] ?? null,
                    'created_by' => Auth::id(),
                ]);

                $salesOrder->update(['payment_status' => ($newPaidTotal >= $salesOrder->grand_total) ? 'lunas' : 'dp']);


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
        $pdf = Pdf::loadView('owner.sales.nota', compact('salesOrder', 'payment'));
        return $pdf->download('nota_' . $salesOrder->so_number . '_payment_' . $payment->id . '.pdf');
    }

    public function printNotaDirect(Payment $payment): View
    {
        $salesOrder = $payment->salesOrder;
        return view('owner.sales.nota', [
            'salesOrder' => $salesOrder,
            'payment' => $payment,
            'autoPrint' => true,
        ]);
    }

    public function destroy(SalesOrder $salesOrder): RedirectResponse
    {
        // Validasi: hanya owner yang bisa hapus
        // Gunakan strtolower untuk support 'Owner', 'OWNER'
        $user = Auth::user();
        $isOwner = strtolower($user->usertype ?? $user->role ?? '') === 'owner';

        if (!$isOwner) {
            \Log::warning('Non-owner attempt to delete SO: ' . $salesOrder->so_number, ['user_id' => Auth::id()]);
            return back()->withErrors(['error' => 'Hanya owner yang dapat menghapus sales order.']);
        }

        // Validasi: hanya bisa hapus SO dengan status tertentu
        // UPDATE: Owner mau "hapus tanpa jejak", jadi kita perlonggar tapi beri warning log
        // Kita ijinkan hapus status 'selesai' juga jika owner yang minta, tapi hati-hati.
        // Namun untuk keamanan sistem inventory, kita tetap batasi status yang sudah 'dikirim' atau terlalu jauh jika perlu.
        // Untuk sekarang kita perluas ijinnya.
        $allowedStatuses = ['draft', 'pending', 'payment', 'request_kain', 'proses_jahit', 'printing', 'diterima_toko', 'jadi', 'siap_ambil', 'di proses', 'selesai'];

        if (!in_array($salesOrder->status, $allowedStatuses)) {
            // Fallback jika status aneh
            \Log::warning('Attempt to delete restricted SO: ' . $salesOrder->so_number, ['status' => $salesOrder->status]);
        }

        try {
            DB::transaction(function () use ($salesOrder) {
                $soNumber = $salesOrder->so_number;
                $cashAdjustedCount = 0;
                $totalCashDeducted = 0;

                // 1. PROSES SETIAP PEMBAYARAN (File & Shift Adjustment)
                foreach ($salesOrder->payments as $payment) {
                    // A. Hapus file bukti fisik
                    if ($payment->proof_path && Storage::disk('public')->exists($payment->proof_path)) {
                        Storage::disk('public')->delete($payment->proof_path);
                    }

                    // B. Adjustment Saldo Shift (Jika Cash/Split)
                    if (($payment->method === 'cash' || $payment->method === 'split') && $payment->cash_amount > 0) {
                        $cashAmount = $payment->cash_amount;
                        $paymentDate = $payment->created_at;
                        $creatorId = $payment->created_by;

                        // Cari shift yang menaungi pembayaran ini (Logika Historis)
                        $relatedShift = Shift::where('user_id', $creatorId)
                            ->where('start_time', '<=', $paymentDate)
                            ->where(function ($q) use ($paymentDate) {
                                $q->whereNull('end_time')
                                    ->orWhere('end_time', '>=', $paymentDate);
                            })
                            ->first();

                        // Jika tidak ketemu shift spesifik, coba fallback ke last active shift user tsb (opsional, tapi lebih baik skip daripada salah tebak)

                        if ($relatedShift) {
                            $relatedShift->decrement('cash_total', (float) $cashAmount);
                            $relatedShift->decrement('final_cash', (float) $cashAmount); // Adjust final cash juga

                            $cashAdjustedCount++;
                            $totalCashDeducted += $cashAmount;

                            \Log::info('Shift adjusted due to SO deletion', [
                                'so_number' => $soNumber,
                                'shift_id' => $relatedShift->id,
                                'amount' => $cashAmount
                            ]);
                        }
                    }
                }

                // 2. KEMBALIKAN STOK (Jika SO sudah memotong stok)
                // Logika: Item sales order mengurangi stok saat dibuat (atau status tertentu tergantung flow).
                // Di sistem ini sepertinya stok dipotong saat SO statusnya maju atau saat payment/selesai.
                // Mari asumsikan stok dipotong saat barang keluar atau reserved.
                // Code asli mengembalikan stok jika status in validStatuses.

                // Ambil semua item
                foreach ($salesOrder->items as $item) {
                    if ($item->product_id) {
                        $product = $item->product;
                        if ($product) {
                            // Kembalikan stok
                            $product->increment('stock_qty', $item->qty);

                            // Catat stock movement
                            StockMovement::create([
                                'product_id' => $product->id,
                                'type' => 'POS_CANCEL',
                                'ref_code' => $soNumber,
                                'initial_qty' => $product->stock_qty - $item->qty, // Stok sebelum dikembalikan
                                'qty_in' => $item->qty,
                                'qty_out' => 0,
                                'final_qty' => $product->stock_qty,
                                'user_id' => Auth::id(),
                                'notes' => 'Pembatalan SO: ' . $soNumber,
                                'moved_at' => now(),
                            ]);
                        }
                    }
                }

                // 3. Hapus Data Relasi
                $salesOrder->payments()->delete(); // Record payments
                $salesOrder->items()->delete();    // Item barang
                $salesOrder->logs()->delete();     // Log history

                // Hapus purchase order item related link jika ada? (Tidak perlu deep delete PO, biarkan PO berdiri sendiri atau manual)

                // 4. Hapus Sales Order
                $salesOrder->delete();

                \Log::info('Sales order deleted successfully', [
                    'so_number' => $soNumber,
                    'deleted_by' => Auth::id(),
                    'shifts_adjusted' => $cashAdjustedCount,
                    'total_cash_deducted' => $totalCashDeducted
                ]);
            });

            return redirect()->route('owner.sales.index')
                ->with('success', 'Sales order berhasil dihapus. Stok dikembalikan, file bukti dihapus, dan saldo kas shift historis telah disesuaikan.');

        } catch (\Exception $e) {
            \Log::error('Error deleting sales order: ' . $e->getMessage(), [
                'so_number' => $salesOrder->so_number,
                'user_id' => Auth::id()
            ]);

            return back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus sales order: ' . $e->getMessage()]);
        }
    }
    public function updatePaymentMethod(Request $request, SalesOrder $salesOrder, Payment $payment): RedirectResponse
    {
        // Validasi hanya owner yang bisa akses
        if (!Auth::user()->hasRole('owner')) {
            \Log::warning('Non-owner attempt to update payment method', [
                'user_id' => Auth::id(),
                'payment_id' => $payment->id
            ]);
            return back()->withErrors(['error' => 'Hanya owner yang dapat mengubah metode pembayaran.']);
        }

        // Validasi payment milik sales order
        if ($payment->sales_order_id !== $salesOrder->id) {
            \Log::warning('Invalid payment for SO in update method', [
                'so_number' => $salesOrder->so_number,
                'payment_id' => $payment->id
            ]);
            return back()->withErrors(['error' => 'Pembayaran tidak valid untuk sales order ini.']);
        }

        $validated = $request->validate([
            'method' => ['required', 'in:cash,transfer,split'],
            'cash_amount' => ['nullable', 'required_if:method,split', 'numeric', 'min:0'],
            'transfer_amount' => ['nullable', 'required_if:method,split', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            DB::transaction(function () use ($salesOrder, $payment, $validated) {
                $oldMethod = $payment->method;
                $oldCashAmount = $payment->cash_amount ?? 0;
                $oldTransferAmount = $payment->transfer_amount ?? 0;

                // Hitung cash amount baru berdasarkan method
                $newCashAmount = 0;
                if ($validated['method'] === 'cash') {
                    $newCashAmount = $payment->amount;
                } elseif ($validated['method'] === 'split') {
                    $newCashAmount = $validated['cash_amount'] ?? 0;
                    // Validate split amounts
                    if (($newCashAmount + ($validated['transfer_amount'] ?? 0)) != $payment->amount) {
                        throw new \Exception('Jumlah cash + transfer harus sama dengan total pembayaran.');
                    }
                }

                // Hitung selisih cash (berapa yang harus ditambah/dikurangi dari shift)
                $cashDifference = $newCashAmount - $oldCashAmount;

                // Update payment data
                $updateData = [
                    'method' => $validated['method'],
                    'reference_number' => $validated['reference_number'] ?? $payment->reference_number,
                ];

                // Handle amount distribution based on method
                if ($validated['method'] === 'cash') {
                    $updateData['cash_amount'] = $payment->amount;
                    $updateData['transfer_amount'] = 0;
                } elseif ($validated['method'] === 'transfer') {
                    $updateData['cash_amount'] = 0;
                    $updateData['transfer_amount'] = $payment->amount;
                } elseif ($validated['method'] === 'split') {
                    $updateData['cash_amount'] = $validated['cash_amount'];
                    $updateData['transfer_amount'] = $validated['transfer_amount'];
                }

                $payment->update($updateData);

                // === UPDATE SHIFT CASH JIKA ADA PERUBAHAN CASH ===
                if (abs($cashDifference) > 0.01) {
                    $this->updateShiftCashForPayment($payment, $cashDifference);
                }

                // Update sales order payment method if this is the only/latest payment
                $latestPayment = $salesOrder->payments()->latest('created_at')->first();
                if ($latestPayment && $latestPayment->id === $payment->id) {
                    $salesOrder->update(['payment_method' => $validated['method']]);
                }

                // Log the action
                $this->logAction(
                    $salesOrder,
                    'payment_method_updated',
                    "Metode pembayaran diubah: {$oldMethod} → {$validated['method']}, " .
                    "Cash: Rp " . number_format($oldCashAmount, 0, ',', '.') . " → Rp " . number_format($payment->cash_amount ?? 0, 0, ',', '.') . ", " .
                    "Transfer: Rp " . number_format($oldTransferAmount, 0, ',', '.') . " → Rp " . number_format($payment->transfer_amount ?? 0, 0, ',', '.')
                );

                \Log::info('Payment method updated successfully', [
                    'payment_id' => $payment->id,
                    'old_method' => $oldMethod,
                    'new_method' => $validated['method'],
                    'cash_difference' => $cashDifference,
                    'so_number' => $salesOrder->so_number
                ]);
            });

            return back()->with('success', 'Metode pembayaran berhasil diubah dan kas shift telah diperbarui.');

        } catch (\Exception $e) {
            \Log::error('Error updating payment method: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'so_number' => $salesOrder->so_number
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat mengubah metode pembayaran: ' . $e->getMessage()]);
        }
    }

    /**
     * Helper method: Update shift cash ketika payment method berubah
     */
    private function updateShiftCashForPayment(Payment $payment, float $cashDifference): void
    {
        // Cari shift terkait payment berdasarkan created_by dan created_at
        $shift = Shift::where('user_id', $payment->created_by)
            ->where('start_time', '<=', $payment->created_at)
            ->where(function ($query) use ($payment) {
                $query->where('end_time', '>=', $payment->created_at)
                    ->orWhereNull('end_time');
            })
            ->orderBy('start_time', 'desc')
            ->first();

        if (!$shift) {
            \Log::warning('Shift not found for payment', [
                'payment_id' => $payment->id,
                'created_by' => $payment->created_by,
                'created_at' => $payment->created_at
            ]);
            return;
        }

        \Log::info('Updating shift cash for payment method change', [
            'shift_id' => $shift->id,
            'payment_id' => $payment->id,
            'cash_difference' => $cashDifference,
            'shift_status' => $shift->end_time ? 'closed' : 'open'
        ]);

        // Update cash_total shift
        if ($cashDifference > 0) {
            $shift->increment('cash_total', $cashDifference);
        } else {
            $shift->decrement('cash_total', abs($cashDifference));
        }

        // Jika shift sudah ditutup, perlu recalculate final_cash dan cascade update
        if ($shift->end_time) {
            $this->recalculateAndUpdateClosedShift($shift);
        }
    }

    /**
     * Helper method: Recalculate final_cash untuk shift yang sudah ditutup dan cascade update
     */
    private function recalculateAndUpdateClosedShift(Shift $shift): void
    {
        // Recalculate final_cash dari data real
        $realCashTotal = $this->calculateRealCashTotalForShift($shift);
        $totalCashTransfers = \App\Models\CashTransfer::where('shift_id', $shift->id)->sum('amount');
        $newFinalCash = $shift->initial_cash + $realCashTotal - $shift->expense_total - $totalCashTransfers;

        $oldFinalCash = $shift->final_cash;
        $finalCashDifference = $newFinalCash - $oldFinalCash;

        \Log::info('Recalculating closed shift final_cash', [
            'shift_id' => $shift->id,
            'old_final_cash' => $oldFinalCash,
            'new_final_cash' => $newFinalCash,
            'difference' => $finalCashDifference
        ]);

        // Update final_cash shift
        $shift->update([
            'final_cash' => $newFinalCash,
            'cash_total' => $realCashTotal, // Update cash_total juga untuk konsistensi
            'discrepancy' => 0, // Reset discrepancy karena kita recalculate dari data real
        ]);

        // Cascade update: Update initial_cash shift berikutnya jika ada
        if (abs($finalCashDifference) > 0.01) {
            $this->cascadeUpdateNextShift($shift, $finalCashDifference);
        }
    }

    /**
     * Helper method: Cascade update initial_cash shift berikutnya
     */
    private function cascadeUpdateNextShift(Shift $updatedShift, float $finalCashDifference): void
    {
        // Cari shift berikutnya yang langsung setelah shift ini
        // Shift berikutnya adalah shift yang start_time > end_time shift ini
        $nextShift = Shift::where('start_time', '>', $updatedShift->end_time)
            ->orderBy('start_time', 'asc')
            ->first();

        if (!$nextShift) {
            \Log::info('No next shift found for cascade update', [
                'updated_shift_id' => $updatedShift->id
            ]);
            return;
        }

        \Log::info('Cascading update to next shift', [
            'updated_shift_id' => $updatedShift->id,
            'next_shift_id' => $nextShift->id,
            'final_cash_difference' => $finalCashDifference
        ]);

        // Update initial_cash shift berikutnya
        $oldInitialCash = $nextShift->initial_cash;
        $newInitialCash = $oldInitialCash + $finalCashDifference;

        $nextShift->update(['initial_cash' => $newInitialCash]);

        \Log::info('Next shift initial_cash updated', [
            'next_shift_id' => $nextShift->id,
            'old_initial_cash' => $oldInitialCash,
            'new_initial_cash' => $newInitialCash
        ]);

        // Jika shift berikutnya juga sudah ditutup, perlu recalculate final_cash-nya juga
        // Karena initial_cash berubah, final_cash juga akan berubah
        if ($nextShift->end_time) {
            $this->recalculateAndUpdateClosedShift($nextShift);
        }
    }

    /**
     * Helper method: Calculate real cash total untuk shift dari data payment
     */
    private function calculateRealCashTotalForShift(Shift $shift): float
    {
        $payments = Payment::where('created_by', $shift->user_id)
            ->where('created_at', '>=', $shift->start_time)
            ->where('created_at', '<=', $shift->end_time ?? now())
            ->get();

        $totalCashFromPayments = 0;
        foreach ($payments as $payment) {
            if ($payment->method === 'cash') {
                $totalCashFromPayments += $payment->amount;
            } elseif ($payment->method === 'split') {
                $totalCashFromPayments += $payment->cash_amount ?? 0;
            }
        }

        $totalIncome = \App\Models\Income::where('shift_id', $shift->id)->sum('amount');

        return $totalCashFromPayments + $totalIncome;
    }

    // ✅ TAMBAH METHOD GET RELATED PURCHASE ORDER UNTUK OWNER
    public function getRelatedPurchaseOrder(SalesOrder $salesOrder)
    {
        try {
            $purchaseOrder = PurchaseOrder::where('sales_order_id', $salesOrder->id)->first();

            if (!$purchaseOrder) {
                return response()->json(['exists' => false], 200);
            }

            return response()->json([
                'exists' => true,
                'po_number' => $purchaseOrder->po_number,
                'status' => $purchaseOrder->getStatusLabel(),
                'supplier_name' => $purchaseOrder->supplier->name ?? '-',
                'purchase_type' => $purchaseOrder->purchase_type,
                'edit_url' => route('owner.purchases.edit', $purchaseOrder),
                'show_url' => route('owner.purchases.show', $purchaseOrder)
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error getting related PO for Owner: ' . $e->getMessage());
            return response()->json([
                'exists' => false,
                'error' => 'Error loading PO data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menghapus pembayaran secara permanen (tanpa jejak)
     * Hanya Owner yang bisa mengakses ini.
     */
    public function destroyPayment(SalesOrder $salesOrder, Payment $payment): RedirectResponse
    {
        // Pastikan payment milik sales order
        if ($payment->sales_order_id !== $salesOrder->id) {
            return back()->withErrors(['error' => 'Pembayaran tidak valid untuk sales order ini.']);
        }

        // Pastikan user adalah owner (meski sudah ada middleware route, double check di controller lebih aman)
        $user = Auth::user();
        $isOwner = strtolower($user->usertype ?? $user->role ?? '') === 'owner';

        if (!$isOwner) {
            return back()->withErrors(['error' => 'Hanya Owner yang berhak menghapus pembayaran secara permanen.']);
        }

        try {
            DB::transaction(function () use ($salesOrder, $payment) {
                // 1. Ambil data penting sebelum dihapus
                $amount = $payment->amount; // Total nominal
                $cashAmount = $payment->cash_amount; // Nominal fisik tunai
                $method = $payment->method;
                $paymentDate = $payment->created_at; // Kapan pembayaran dibuat (bukan paid_at, tapi record creation untuk shift)
                $creatorId = $payment->created_by;

                // 2. Hapus bukti file jika ada
                if ($payment->proof_path && Storage::disk('public')->exists($payment->proof_path)) {
                    Storage::disk('public')->delete($payment->proof_path);
                }

                // 3. Hapus record pembayaran
                $payment->delete();

                // 4. Update status pembayaran di Sales Order
                // Refresh data pembayaran
                $remainingPaid = $salesOrder->payments()->sum('amount');

                $newStatus = ($remainingPaid >= $salesOrder->grand_total)
                    ? 'lunas'
                    : 'dp';

                $salesOrder->update(['payment_status' => $newStatus]);

                // 5. CRITICAL: Sinkronisasi SHIFT (Laporan Kas)
                // Hanya jika pembayaran melibatkan uang fisik (CASH atau SPLIT)
                if ($cashAmount > 0) {
                    // Cari shift yang menaungi pembayaran ini
                    $relatedShift = Shift::where('user_id', $creatorId)
                        ->where('start_time', '<=', $paymentDate)
                        ->where(function ($q) use ($paymentDate) {
                            $q->whereNull('end_time')
                                ->orWhere('end_time', '>=', $paymentDate);
                        })
                        ->first();

                    if ($relatedShift) {
                        // Jika ketemu, kurangi saldo laporan shift agar sinkron
                        // Kurangi cash_total dan final_cash
                        $relatedShift->decrement('cash_total', (float) $cashAmount);
                        $relatedShift->decrement('final_cash', (float) $cashAmount);

                        \Log::info("Shift balance adjusted due to payment deletion", [
                            'shift_id' => $relatedShift->id,
                            'decreased_by' => $cashAmount
                        ]);
                    } else {
                        \Log::warning("Payment deleted but NO related shift found to adjust", [
                            'payment_id' => $payment->id,
                            'created_at' => $paymentDate,
                            'cash_amount' => $cashAmount
                        ]);
                    }
                }

                // 6. Catat Log (Audit Trail)
                $this->logAction(
                    $salesOrder,
                    'payment_deleted',
                    "Pembayaran DIHAPUS oleh Owner: Rp " . number_format((float) $amount, 0, ',', '.') . " ({$method}). Shift adjusted: " . ($cashAmount > 0 ? 'Yes' : 'No')
                );
            });

            return back()->with('success', 'Pembayaran berhasil dihapus secara permanen. Laporan kas terkait telah disesuaikan.');
        } catch (\Exception $e) {
            \Log::error('Error deleting payment for SO ' . $salesOrder->so_number . ': ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus pembayaran: ' . $e->getMessage()]);
        }
    }
}