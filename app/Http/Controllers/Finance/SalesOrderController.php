<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesOrderLog;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\StockMovement;
use App\Models\Payment;
use App\Models\Shift;
use App\Models\PurchaseOrder;
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
use App\Exports\SalesOrderExport;
use Maatwebsite\Excel\Facades\Excel;

class SalesOrderController extends Controller
{

    private function logAction(SalesOrder $salesOrder, string $action, string $description): void
    {
        SalesOrderLog::create([
            'sales_order_id' => $salesOrder->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'created_at' => now(),
        ]);
    }

    /**
     * Helper method untuk cek apakah user bisa melakukan aksi tertentu
     */
    private function canPerformAction(string $action): bool
    {
        $user = Auth::user();
        $userType = strtolower($user->usertype ?? $user->role ?? '');
        
        return match($action) {
            'pending_to_request_kain' => in_array($userType, ['owner', 'kepala_toko', 'finance']),
            'request_kain_to_payment' => $userType === 'finance',
            'payment_to_proses_jahit' => in_array($userType, ['admin', 'finance', 'kepala_toko']),
            'proses_jahit_to_printing' => in_array($userType, ['admin', 'finance', 'kepala_toko']),
            'printing_to_diterima_toko' => in_array($userType, ['admin', 'finance', 'kepala_toko']),
            'diterima_toko_to_selesai' => in_array($userType, ['admin', 'finance', 'kepala_toko']),
            default => false,
        };
    }

    private function updateStockOnPayment(SalesOrder $salesOrder)
    {
        DB::transaction(function () use ($salesOrder) {
            foreach ($salesOrder->items as $item) {
                if ($item->product_id) {
                    $product = $item->product;
                    $initialStock = $product->stock_qty;
                    $newStock = $initialStock - $item->qty;
                    if ($newStock < 0) {
                        \Log::warning('Negative stock for product ' . $product->id . ' on SO ' . $salesOrder->so_number . ': New stock ' . $newStock);
                    }
                    $product->stock_qty = $newStock;
                    $product->save();

                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'OUTGOING',
                        'ref_code' => $salesOrder->so_number,
                        'initial_qty' => $initialStock,
                        'qty_in' => 0,
                        'qty_out' => $item->qty,
                        'final_qty' => $product->stock_qty,
                        'user_id' => Auth::id(),
                        'notes' => 'Pembayaran SO: ' . $salesOrder->so_number,
                        'moved_at' => Carbon::now(),
                    ]);
                }
            }
        });
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

        return view('finance.sales.index', compact('salesOrders', 'q', 'status', 'payment_status', 'start_date', 'end_date'));
    }

    /**
     * Export sales orders to Excel
     */
    public function export(Request $request)
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $payment_status = $request->get('payment_status');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        $salesOrders = SalesOrder::with(['customer', 'items', 'creator'])
            ->when($q, fn($query) =>
                $query->where('so_number', 'like', "%$q%")
                    ->orWhereHas('customer', fn($qq) => $qq->where('name', 'like', "%$q%"))
            )
            ->when($status, fn($query) => $query->where('status', $status))
            ->when($payment_status && $payment_status !== 'all', fn($query) => $query->where('payment_status', $payment_status))
            ->when($start_date, fn($query) => $query->whereDate('order_date', '>=', $start_date))
            ->when($end_date, fn($query) => $query->whereDate('order_date', '<=', $end_date))
            ->orderByDesc('order_date')
            ->get();

        $filename = 'sales-orders-' . date('Y-m-d-H-i') . '.xlsx';

        return Excel::download(new SalesOrderExport($salesOrders), $filename);
    }

    public function create(): View
    {
        // AMBIL SHIFT AKTIF GLOBAL (tanpa validasi user)
        $activeShift = Shift::getActiveShift();
        
        $customers = Customer::orderBy('name')->get();
        $products = Product::where('is_active', true)->where('price', '>', 0)->orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        
        return view('finance.sales.create', compact('customers', 'products', 'activeShift', 'suppliers'));
    }

    public function store(Request $request): RedirectResponse|View
    {
        \Log::info('Store request received', $request->all());
    
        // AMBIL SHIFT AKTIF GLOBAL
        $activeShift = Shift::getActiveShift();
                // Tentukan status dari input (draft atau pending)
                $status = $request->input('status', 'pending');
    
        $validated = $request->validate([
            'order_type' => ['required', 'in:jahit_sendiri,beli_jadi'],
            'order_date' => ['required', 'date'],
            'deadline' => ['nullable','date'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'payment_method' => $status === 'draft' ? ['nullable', 'in:cash,transfer,split'] : ['required', 'in:cash,transfer,split'],
            'payment_status' => $status === 'draft' ? ['nullable', 'in:dp,lunas'] : ['required', 'in:dp,lunas'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.sku' => ['nullable', 'string', 'max:100'],
            'items.*.sale_price' => ['required', 'numeric', 'min:0.01'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'cash_amount' => ['nullable', 'numeric', 'min:0'],
            'transfer_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_at' => ['nullable', 'date'],
            'proof_path' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'], // ✅ TAMBAH INI
        ]);

        \Log::info('Validated data', $validated);

        foreach ($request->items as $index => $item) {
            if (!empty($item['product_id'])) {
                $product = Product::find($item['product_id']);
                if (!$product || $product->price <= 0) {
                    \Log::error("Invalid product at index $index", $item);
                    return back()->withErrors(["items.$index.product_id" => 'Produk tidak valid atau harga kosong.'])->withInput();
                }
            }
        }

        $subtotal = collect($validated['items'])->reduce(function ($carry, $item) {
            return $carry + ((float)$item['sale_price'] * (int)$item['qty']);
        }, 0);
        $discountTotal = (float)($validated['discount_total'] ?? 0);
        $shippingCost = (float)($validated['shipping_cost'] ?? 0); // ✅ TAMBAH INI
        $grandTotal = $subtotal - $discountTotal + $shippingCost; // ✅ UPDATE INI

        $cashAmount = $validated['payment_method'] === 'split' ? ($validated['cash_amount'] ?? 0) : ($validated['payment_method'] === 'cash' ? ($validated['payment_amount'] ?? 0) : 0);
        $transferAmount = $validated['payment_method'] === 'split' ? ($validated['transfer_amount'] ?? 0) : ($validated['payment_method'] === 'transfer' ? ($validated['payment_amount'] ?? 0) : 0);
        $paymentAmount = $cashAmount + $transferAmount;

        \Log::info('Calculated payment', ['payment_amount' => $paymentAmount, 'cash' => $cashAmount, 'transfer' => $transferAmount, 'grand_total' => $grandTotal]);

        if ($paymentAmount > 0) {
            if ($validated['payment_status'] === 'dp' && $paymentAmount < $grandTotal * 0.5) {
                \Log::error('Payment amount below 50% DP', ['payment_amount' => $paymentAmount, 'grand_total' => $grandTotal]);
                return back()->withErrors(['payment_amount' => 'DP minimal 50%: Rp ' . number_format($grandTotal * 0.5, 0, ',', '.')])->withInput();
            }
            if ($paymentAmount > $grandTotal) {
                \Log::error('Payment amount exceeds grand total', ['payment_amount' => $paymentAmount, 'grand_total' => $grandTotal]);
                return back()->withErrors(['payment_amount' => 'Jumlah melebihi grand total: Rp ' . number_format($grandTotal, 0, ',', '.')])->withInput();
            }
        } else {
            \Log::info('No payment amount, skipping payment creation');
        }

        $status = 'pending';

        try {
            $salesOrder = DB::transaction(function () use ($validated, $request, $cashAmount, $transferAmount, $paymentAmount, $grandTotal, $activeShift, $status, $subtotal, $discountTotal, $shippingCost) {
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
                    'deadline' => $validated['deadline'] ?? null,
                    'customer_id' => $customerId ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'shipping_cost' => $shippingCost, // ✅ TAMBAH INI
                    'grand_total' => $grandTotal,
                    'status' => $status,
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => $validated['payment_status'],
                    'created_by' => Auth::id(),
                ]);

                foreach ($validated['items'] as $item) {
                    $lineTotal = ((float)$item['sale_price'] * (int)$item['qty']) - ((float)($item['discount'] ?? 0) * (int)$item['qty']);
                    SalesOrderItem::create([
                        'sales_order_id' => $salesOrder->id,
                        'product_id' => $item['product_id'] ?? null,
                        'product_name' => $item['product_name'],
                        'sku' => $item['sku'] ?? null,
                        'sale_price' => $item['sale_price'],
                        'qty' => $item['qty'],
                        'discount' => $item['discount'] ?? 0,
                        'line_total' => $lineTotal,
                    ]);
                }

                if ($paymentAmount > 0) {
                    $proofPath = $request->hasFile('proof_path')
                        ? $request->file('proof_path')->store('payment-proofs', 'public')
                        : null;
    
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
                        'created_by' => Auth::id(),
                    ]);
    
                    \Log::info('Payment created', ['payment_id' => $payment->id, 'amount' => $paymentAmount, 'proof_path' => $proofPath ?? 'none']);
    
                    // ✅ UPDATE SHIFT CASH JIKA ADA CASH AMOUNT
                    if ($activeShift && $cashAmount > 0) {
                        $activeShift->increment('cash_total', $cashAmount);
                        \Log::info('Shift cash updated by Finance', ['shift_id' => $activeShift->id, 'cash_amount' => $cashAmount]);
                    }
    
                    $this->logAction($salesOrder, 'payment_added', "Pembayaran ditambahkan: {$paymentCategory}, Jumlah: Rp " . number_format($paymentAmount, 0, ',', '.') . ", Metode: {$validated['payment_method']}" . ($proofPath ? "" : ", tanpa bukti"));
                }
    
                $this->logAction($salesOrder, 'created', "Sales order dibuat: {$soNumber}, Tipe: {$validated['order_type']}, Total: Rp " . number_format($grandTotal, 0, ',', '.'));
    
                return $salesOrder;
            });

            \Log::info('Sales order created successfully', ['so_number' => $salesOrder->so_number]);

            if ($paymentAmount > 0) {
                $salesOrder->load('payments');
                $payment = $salesOrder->payments->first();
                return view('finance.sales.nota', [
                    'salesOrder' => $salesOrder,
                    'payment' => $payment,
                    'autoPrint' => true,
                ]);
            }

            return redirect()->route('finance.sales.show', $salesOrder)->with('success', 'Sales order dibuat.');
        } catch (\Exception $e) {
            \Log::error('Error storing sales order: ' . $e->getMessage(), ['request' => $request->all()]);
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(SalesOrder $salesOrder): View
    {
        $salesOrder->load(['customer', 'items', 'creator', 'approver', 'payments.creator', 'logs.user']);
        $payment = $salesOrder->payments->first() ?? new Payment();
        $activeShift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
        return view('finance.sales.show', compact('salesOrder', 'payment', 'activeShift'));
    }

    public function edit(SalesOrder $salesOrder): View|RedirectResponse
    {

        if (!$salesOrder->isEditable()) {
            \Log::warning('Attempt to edit non-editable SO: ' . $salesOrder->so_number);
            return back()->withErrors(['error' => 'Sales order yang selesai tidak bisa diedit.']);
        }
        $customers = Customer::orderBy('name')->get();
        $products = Product::where('is_active', true)->where('price', '>', 0)->orderBy('name')->get();
        $activeShift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
        return view('finance.sales.edit', compact('salesOrder', 'customers', 'products', 'activeShift'));
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
            'items.*.sale_price' => ['required', 'numeric', 'min:0.01'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'], // ✅ TAMBAH INI
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'cash_amount' => ['nullable', 'numeric', 'min:0'],
            'transfer_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_at' => ['nullable', 'date'],
            'proof_path' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        foreach ($request->items as $index => $item) {
            if (!empty($item['product_id'])) {
                $product = Product::find($item['product_id']);
                if (!$product || $product->price <= 0) {
                    \Log::error("Invalid product at index $index", $item);
                    return back()->withErrors(["items.$index.product_id" => 'Produk yang dipilih tidak memiliki harga valid.'])->withInput();
                }
            }
        }

        $subtotal = collect($validated['items'])->reduce(function ($carry, $item) {
            return $carry + ((float)$item['sale_price'] * (int)$item['qty']);
        }, 0);
        $discountTotal = (float)($validated['discount_total'] ?? 0);
        $shippingCost = (float)($validated['shipping_cost'] ?? 0); // ✅ TAMBAH INI
        $grandTotal = $subtotal - $discountTotal + $shippingCost; // ✅ UPDATE INI

        $cashAmount = $validated['payment_method'] === 'split' ? ($validated['cash_amount'] ?? 0) : ($validated['payment_method'] === 'cash' ? ($validated['payment_amount'] ?? 0) : 0);
        $transferAmount = $validated['payment_method'] === 'split' ? ($validated['transfer_amount'] ?? 0) : ($validated['payment_method'] === 'transfer' ? ($validated['payment_amount'] ?? 0) : 0);
        $paymentAmount = $cashAmount + $transferAmount;

        \Log::info('Calculated payment in update', ['payment_amount' => $paymentAmount, 'cash' => $cashAmount, 'transfer' => $transferAmount, 'grand_total' => $grandTotal]);

        if ($paymentAmount > 0) {
            if ($validated['payment_status'] === 'dp' && $paymentAmount < $grandTotal * 0.5) {
                \Log::error('Payment amount below 50% DP', ['payment_amount' => $paymentAmount, 'grand_total' => $grandTotal]);
                return back()->withErrors(['payment_amount' => 'DP minimal 50%: Rp ' . number_format($grandTotal * 0.5, 0, ',', '.')])->withInput();
            }
            if ($paymentAmount > $grandTotal) {
                \Log::error('Payment amount exceeds grand total', ['payment_amount' => $paymentAmount, 'grand_total' => $grandTotal]);
                return back()->withErrors(['payment_amount' => 'Jumlah melebihi grand total: Rp ' . number_format($grandTotal, 0, ',', '.')])->withInput();
            }
        } else {
            \Log::info('No payment amount in update, skipping payment creation');
        }

        try {
            DB::transaction(function () use ($salesOrder, $validated, $request, $cashAmount, $transferAmount, $paymentAmount, $grandTotal, $subtotal, $discountTotal, $shippingCost) {
                $salesOrder->update([
                    'order_type' => $validated['order_type'],
                    'order_date' => $validated['order_date'],
                    'deadline' => $validated['deadline'] ?? null, // TAMBAH INI
                    'customer_id' => $validated['customer_id'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'shipping_cost' => $shippingCost, // ✅ TAMBAH INI
                    'grand_total' => $grandTotal,
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => $validated['payment_status'],
                    'status' => 'pending',
                    'approved_by' => null,
                    'approved_at' => null,
                ]);

                $salesOrder->items()->delete();
                foreach ($validated['items'] as $item) {
                    $lineTotal = ((float)$item['sale_price'] * (int)$item['qty']) - ((float)($item['discount'] ?? 0) * (int)$item['qty']);
                    SalesOrderItem::create([
                        'sales_order_id' => $salesOrder->id,
                        'product_id' => $item['product_id'] ?? null,
                        'product_name' => $item['product_name'],
                        'sku' => $item['sku'] ?? null,
                        'sale_price' => $item['sale_price'],
                        'qty' => $item['qty'],
                        'discount' => $item['discount'] ?? 0,
                        'line_total' => $lineTotal,
                    ]);
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
            return redirect()->route('finance.sales.show', $salesOrder)->with('success', 'Sales order diperbarui dan menunggu approval.');
        } catch (\Exception $e) {
            \Log::error('Error updating sales order: ' . $e->getMessage(), ['so_number' => $salesOrder->so_number]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat update SO: ' . $e->getMessage()])->withInput();
        }
    }

    public function addPayment(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $validated = $request->validate([
            'payment_amount' => ['required', 'numeric', 'min:0', function ($attribute, $value, $fail) use ($salesOrder) {
                if ($salesOrder->paid_total == 0 && $value < $salesOrder->grand_total * 0.5) {
                    $fail('DP minimal 50% dari grand total: Rp ' . number_format($salesOrder->grand_total * 0.5, 0, ',', '.'));
                }
                if ($value > $salesOrder->remaining_amount) {
                    $fail('Jumlah tidak boleh melebihi sisa: Rp ' . number_format($salesOrder->remaining_amount, 0, ',', '.'));
                }
            }],
            'payment_method' => ['required', 'in:cash,transfer,split'],
            'cash_amount' => ['nullable', 'required_if:payment_method,split', 'numeric', 'min:0'],
            'transfer_amount' => ['nullable', 'required_if:payment_method,split', 'numeric', 'min:0'],
            'paid_at' => ['required', 'date'],
            'proof_path' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['payment_method'] === 'split' && $validated['payment_amount'] != ($validated['cash_amount'] ?? 0) + ($validated['transfer_amount'] ?? 0)) {
            \Log::error('Invalid split payment amount', ['payment_amount' => $validated['payment_amount'], 'cash_amount' => $validated['cash_amount'], 'transfer_amount' => $validated['transfer_amount']]);
            return back()->withErrors(['payment_amount' => 'Jumlah total harus sama dengan jumlah cash + transfer.'])->withInput();
        }

        if (($validated['payment_method'] === 'transfer' || ($validated['payment_method'] === 'split' && ($validated['transfer_amount'] ?? 0) > 0)) && !$request->hasFile('proof_path')) {
            \Log::error('Missing proof of payment for transfer/split', ['payment_method' => $validated['payment_method'], 'transfer_amount' => $validated['transfer_amount']]);
            return back()->withErrors(['proof_path' => 'Bukti wajib untuk metode transfer atau split dengan jumlah transfer.'])->withInput();
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
                    'proof_path' => $proofPath,
                    'note' => $validated['note'] ?? null,
                    'created_by' => Auth::id(),
                ]);
    
                $salesOrder->update(['payment_status' => ($newPaidTotal >= $salesOrder->grand_total) ? 'lunas' : 'dp']);
    
                // ✅ UPDATE SHIFT CASH JIKA ADA CASH AMOUNT
                $activeShift = Shift::getActiveShift();
                if ($activeShift && $cashAmount > 0) {
                    $activeShift->increment('cash_total', $cashAmount);
                    \Log::info('Shift cash updated by Finance in addPayment', ['shift_id' => $activeShift->id, 'cash_amount' => $cashAmount]);
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

    private function generateSoNumber(): string
    {
        return app(NumberGenerator::class)->generateSalesOrderNumber();
    }
    public function printNotaDirect(Payment $payment): View
    {
        $salesOrder = $payment->salesOrder;
        return view('finance.sales.nota', [
            'salesOrder' => $salesOrder,
            'payment' => $payment,
            'autoPrint' => true,
        ]);
    }
    public function printNota(Payment $payment): \Illuminate\Http\Response
    {
        $salesOrder = $payment->salesOrder;
        $pdf = Pdf::loadView('finance.sales.nota', compact('salesOrder', 'payment'));
        return $pdf->download('nota_' . $salesOrder->so_number . '_payment_' . $payment->id . '.pdf');
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
// Tambahkan method ini di class Finance SalesOrderController
public function search(Request $request)
{
    $query = $request->get('q');
    
    $products = Product::where('is_active', true)
        ->where('price', '>', 0)
        ->where(function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('sku', 'like', "%{$query}%")
              ->orWhere('barcode', 'like', "%{$query}%");
        })
        ->select('id', 'name', 'sku', 'barcode', 'price', 'stock_qty')
        ->orderBy('name')
        ->limit(10)
        ->get();
    
    return response()->json($products);
}

    /**
     * ✅ WORKFLOW BARU: pending → request_kain (untuk SO dengan PO)
     * Hanya Owner, Kepala Toko, Finance yang bisa
     */
    public function moveToRequestKain(SalesOrder $salesOrder): RedirectResponse
    {
        // Validasi role
        if (!$this->canPerformAction('pending_to_request_kain')) {
            return back()->withErrors(['error' => 'Anda tidak memiliki izin untuk melakukan aksi ini.']);
        }

        // Validasi status dan PO
        if ($salesOrder->status !== 'pending') {
            return back()->withErrors(['status' => 'Hanya status pending yang bisa dipindah ke request_kain.']);
        }

        if (!$salesOrder->hasRelatedPO()) {
            return back()->withErrors(['error' => 'Sales order ini tidak memiliki Purchase Order terkait.']);
        }

        if ($salesOrder->approved_by === null) {
            return back()->withErrors(['status' => 'Sales order harus di-approve terlebih dahulu.']);
        }

        if ($salesOrder->paid_total <= 0) {
            return back()->withErrors(['payment' => 'Harus ada pembayaran untuk mulai proses.']);
        }

        // Validasi pembayaran transfer/split
        if (in_array($salesOrder->payment_method, ['transfer', 'split'])) {
            $invalidPayments = $salesOrder->payments()
                ->whereNull('proof_path')
                ->where(function($q) {
                    $q->whereNull('reference_number')
                      ->orWhere('reference_number', '')
                      ->orWhere('reference_number', ' ')
                      ->orWhere('reference_number', 'null')
                      ->orWhere('reference_number', 'NULL');
                })
                ->count();
            
            if ($invalidPayments > 0) {
                return back()->withErrors(['payment' => 'Semua pembayaran transfer/split harus memiliki bukti pembayaran ATAU no referensi yang valid.']);
            }
        }

        try {
            DB::transaction(function () use ($salesOrder) {
                $this->updateStockOnPayment($salesOrder);
                $salesOrder->update(['status' => 'request_kain']);
                $this->logAction($salesOrder, 'moved_to_request_kain', 'Status berubah ke request_kain');
            });
            
            $salesOrder->refresh();
            SalesPurchaseSyncService::syncPurchaseFromSales($salesOrder);
            return back()->with('success', 'Status berhasil diubah ke request_kain.');
        } catch (\Exception $e) {
            \Log::error('Error moving to request_kain: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * ✅ WORKFLOW BARU: request_kain → payment
     * Hanya Finance yang bisa
     */
    public function moveToPayment(SalesOrder $salesOrder): RedirectResponse
    {
        // Validasi role
        if (!$this->canPerformAction('request_kain_to_payment')) {
            return back()->withErrors(['error' => 'Hanya Finance yang dapat mengubah status dari request_kain ke payment.']);
        }

        if ($salesOrder->status !== 'request_kain') {
            return back()->withErrors(['status' => 'Hanya status request_kain yang bisa dipindah ke payment.']);
        }

        if (!$salesOrder->hasRelatedPO()) {
            return back()->withErrors(['error' => 'Sales order ini tidak memiliki Purchase Order terkait.']);
        }

        try {
            $salesOrder->update(['status' => 'payment']);
            $this->logAction($salesOrder, 'moved_to_payment', 'Status berubah ke payment');
            
            $salesOrder->refresh();
            SalesPurchaseSyncService::syncPurchaseFromSales($salesOrder);
            return back()->with('success', 'Status berhasil diubah ke payment.');
        } catch (\Exception $e) {
            \Log::error('Error moving to payment: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
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
        // Validasi role
        if (!$this->canPerformAction('payment_to_proses_jahit')) {
            return back()->withErrors(['error' => 'Anda tidak memiliki izin untuk melakukan aksi ini.']);
        }

        if ($salesOrder->status !== 'payment') {
            return back()->withErrors(['status' => 'Hanya status payment yang bisa dipindah ke proses_jahit.']);
        }

        if ($salesOrder->order_type !== 'jahit_sendiri') {
            return back()->withErrors(['status' => 'Hanya order jahit_sendiri yang bisa diproses jahit.']);
        }

        if (!$salesOrder->hasRelatedPO()) {
            return back()->withErrors(['error' => 'Sales order ini tidak memiliki Purchase Order terkait.']);
        }

        try {
            $salesOrder->update(['status' => 'proses_jahit']);
            $this->logAction($salesOrder, 'jahit_processed', 'Proses jahit dimulai: Status berubah ke proses_jahit');
            
            $salesOrder->refresh();
            SalesPurchaseSyncService::syncPurchaseFromSales($salesOrder);
            return back()->with('success', 'Proses jahit dimulai.');
        } catch (\Exception $e) {
            \Log::error('Error processing jahit: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * ✅ WORKFLOW BARU: proses_jahit → printing
     * Admin, Finance, Kepala Toko bisa
     */
    public function markAsJadi(SalesOrder $salesOrder): RedirectResponse
    {
        // Validasi role
        if (!$this->canPerformAction('proses_jahit_to_printing')) {
            return back()->withErrors(['error' => 'Anda tidak memiliki izin untuk melakukan aksi ini.']);
        }

        if ($salesOrder->status !== 'proses_jahit') {
            return back()->withErrors(['status' => 'Hanya status proses_jahit yang bisa dipindah ke printing.']);
        }

        if ($salesOrder->order_type !== 'jahit_sendiri') {
            return back()->withErrors(['status' => 'Hanya order jahit_sendiri yang bisa ditandai printing.']);
        }

        if (!$salesOrder->hasRelatedPO()) {
            return back()->withErrors(['error' => 'Sales order ini tidak memiliki Purchase Order terkait.']);
        }

        try {
            $salesOrder->update(['status' => 'printing']);
            $this->logAction($salesOrder, 'marked_jadi', 'Produk selesai dijahit: Status berubah ke printing');
            
            $salesOrder->refresh();
            SalesPurchaseSyncService::syncPurchaseFromSales($salesOrder);
            return back()->with('success', 'Produk selesai dijahit.');
        } catch (\Exception $e) {
            \Log::error('Error marking as jadi: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * ✅ WORKFLOW BARU: printing → diterima_toko (untuk jahit_sendiri) atau payment → diterima_toko (untuk beli_jadi)
     * Admin, Finance, Kepala Toko bisa
     */
    public function markAsDiterimaToko(SalesOrder $salesOrder): RedirectResponse
    {
        // Validasi role
        if (!$this->canPerformAction('printing_to_diterima_toko')) {
            return back()->withErrors(['error' => 'Anda tidak memiliki izin untuk melakukan aksi ini.']);
        }

        // Validasi status: printing (untuk jahit_sendiri) atau payment (untuk beli_jadi)
        $validStatuses = $salesOrder->order_type === 'jahit_sendiri' ? ['printing'] : ['payment'];
        if (!in_array($salesOrder->status, $validStatuses)) {
            return back()->withErrors(['status' => 'Hanya status ' . implode(' atau ', $validStatuses) . ' yang bisa ditandai diterima toko.']);
        }

        if (!$salesOrder->hasRelatedPO()) {
            return back()->withErrors(['error' => 'Sales order ini tidak memiliki Purchase Order terkait.']);
        }

        try {
            $salesOrder->update(['status' => 'diterima_toko']);
            $this->logAction($salesOrder, 'marked_diterima_toko', 'Produk diterima toko: Status berubah ke diterima_toko');
            
            $salesOrder->refresh();
            // ✅ Ketika SO diterima_toko, PO harus selesai
            SalesPurchaseSyncService::syncPurchaseFromSales($salesOrder);
            return back()->with('success', 'Produk diterima toko.');
        } catch (\Exception $e) {
            \Log::error('Error marking as diterima toko: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * ✅ WORKFLOW BARU: diterima_toko → selesai
     * Admin, Finance, Kepala Toko bisa
     */
    public function complete(SalesOrder $salesOrder): RedirectResponse
    {
        // Validasi role
        if (!$this->canPerformAction('diterima_toko_to_selesai')) {
            return back()->withErrors(['error' => 'Anda tidak memiliki izin untuk melakukan aksi ini.']);
        }

        if ($salesOrder->status !== 'diterima_toko') {
            return back()->withErrors(['status' => 'Hanya status diterima_toko yang bisa diselesaikan.']);
        }

        if ($salesOrder->remaining_amount > 0) {
            return back()->withErrors(['payment' => 'Pembayaran harus lunas untuk menyelesaikan.']);
        }

        try {
            $salesOrder->update(['status' => 'selesai', 'completed_at' => Carbon::now()]);
            $this->logAction($salesOrder, 'completed', 'Sales order selesai: Status berubah ke selesai');
            
            $salesOrder->refresh();
            SalesPurchaseSyncService::syncPurchaseFromSales($salesOrder);
            return back()->with('success', 'Sales order selesai.');
        } catch (\Exception $e) {
            \Log::error('Error completing sales order: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    // ✅ TAMBAH METHOD GET RELATED PURCHASE ORDER UNTUK FINANCE
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
                'show_url' => route('finance.purchases.show', $purchaseOrder)
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error getting related PO for Finance: ' . $e->getMessage());
            return response()->json([
                'exists' => false,
                'error' => 'Error loading PO data: ' . $e->getMessage()
            ], 500);
        }
}
}