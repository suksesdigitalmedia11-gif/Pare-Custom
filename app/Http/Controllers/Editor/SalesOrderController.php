<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesOrderLog;
use App\Models\Product;
use App\Models\Customer;
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

class SalesOrderController extends Controller
{
    private function checkActiveShift(): bool|RedirectResponse
    {
        $activeShift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
        if (!$activeShift) {
            \Log::warning('No active shift for user: ' . Auth::id());
            return redirect()->route('editor.shift.dashboard')->with('error', 'Silakan mulai shift terlebih dahulu untuk melakukan aksi ini.');
        }
        return true;
    }

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

    public function index(Request $request): View
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $payment_status = $request->get('payment_status');

        $salesOrders = SalesOrder::with(['customer', 'creator', 'approver'])
            ->when($q, fn($query) =>
                $query->where('so_number', 'like', "%$q%")
                    ->orWhereHas('customer', fn($qq) => $qq->where('name', 'like', "%$q%"))
            )
            ->when($status, fn($query) => $query->where('status', $status))
            ->when($payment_status && $payment_status !== 'all', fn($query) => $query->where('payment_status', $payment_status))
            ->orderByDesc('id')
            ->paginate(15);

        return view('editor.sales.index', compact('salesOrders', 'q', 'status', 'payment_status'));
    }

    public function create(): View|RedirectResponse
    {
        $shiftCheck = $this->checkActiveShift();
        if ($shiftCheck !== true) {
            return $shiftCheck;
        }
        $customers = Customer::orderBy('name')->get();
        $products = Product::where('is_active', true)->where('price', '>=', 0)->orderBy('name')->get();
        $activeShift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
        return view('editor.sales.create', compact('customers', 'products', 'activeShift'));
    }

    public function store(Request $request): RedirectResponse|View
    {
        $shiftCheck = $this->checkActiveShift();
        if ($shiftCheck !== true) {
            return $shiftCheck;
        }

        $validated = $request->validate([
            'order_type' => ['required', 'in:jahit_sendiri,beli_jadi'],
            'order_date' => ['required', 'date'],
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
        ]);

        $subtotal = collect($validated['items'])->reduce(fn($carry, $item) => $carry + ((float)$item['sale_price'] * (int)$item['qty']), 0);
        $discountTotal = collect($validated['items'])->sum(fn($item) => (float)($item['discount'] ?? 0) * (int)$item['qty']);
        $grandTotal = $subtotal - $discountTotal;

        $cashAmount = $validated['payment_method'] === 'split' ? ($validated['cash_amount'] ?? 0) : ($validated['payment_method'] === 'cash' ? ($validated['payment_amount'] ?? 0) : 0);
        $transferAmount = $validated['payment_method'] === 'split' ? ($validated['transfer_amount'] ?? 0) : ($validated['payment_method'] === 'transfer' ? ($validated['payment_amount'] ?? 0) : 0);
        $paymentAmount = $cashAmount + $transferAmount;

        if ($paymentAmount > 0) {
            if ($paymentAmount > $grandTotal) {
                return back()->withErrors(['payment_amount' => 'Jumlah melebihi grand total: Rp ' . number_format($grandTotal, 0, ',', '.')])->withInput();
            }
        }

        try {
            $salesOrder = DB::transaction(function () use ($validated, $request, $cashAmount, $transferAmount, $paymentAmount, $grandTotal, $subtotal, $discountTotal) {
                $soNumber = $this->generateSoNumber();
                $salesOrder = SalesOrder::create([
                    'so_number' => $soNumber,
                    'order_type' => $validated['order_type'],
                    'order_date' => $validated['order_date'],
                    'customer_id' => $validated['customer_id'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'grand_total' => $grandTotal,
                    'status' => 'pending',
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => $validated['payment_status'],
                    'created_by' => Auth::id(),
                ]);

                foreach ($validated['items'] as $item) {
                    $lineTotal = ((float)$item['sale_price'] * (int)$item['qty']) - ((float)($item['discount'] ?? 0) * (int)$item['qty']);
                    
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
                    $proofPath = $request->hasFile('proof_path') ? $request->file('proof_path')->store('payment-proofs', 'public') : null;
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

                    $activeShift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
                    if ($cashAmount > 0 && $activeShift) {
                        $activeShift->increment('cash_total', $cashAmount);
                    }

                    $this->logAction($salesOrder, 'payment_added', "Pembayaran ditambahkan: {$paymentCategory}, Jumlah: Rp " . number_format($paymentAmount, 0, ',', '.') . ", Metode: {$validated['payment_method']}");
                }

                $this->logAction($salesOrder, 'created', "Sales order dibuat: {$soNumber}, Tipe: {$validated['order_type']}, Total: Rp " . number_format($grandTotal, 0, ',', '.'));
                return $salesOrder;
            });

            if ($paymentAmount > 0) {
                $salesOrder->load('payments');
                $payment = $salesOrder->payments->first();
                return view('editor.sales.nota', [
                    'salesOrder' => $salesOrder,
                    'payment' => $payment,
                    'autoPrint' => true,
                ]);
            }

            return redirect()->route('editor.sales.show', $salesOrder)->with('success', 'Sales order dibuat.');
        } catch (\Exception $e) {
            \Log::error('Error storing sales order: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(SalesOrder $salesOrder): View
    {
        $salesOrder->load(['customer', 'items', 'creator', 'approver', 'payments.creator', 'logs.user']);
        $payment = $salesOrder->payments->first() ?? new Payment();
        $activeShift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
        return view('editor.sales.show', compact('salesOrder', 'payment', 'activeShift'));
    }

    /**
     * Read-only detail untuk kebutuhan desain (DTF/Jersey).
     */
    public function showDesign(SalesOrder $salesOrder): View
    {
        $salesOrder->load(['customer', 'items', 'logs.user']);

        $hasDesignItem = $salesOrder->items->contains(function ($item) {
            return $item->requires_design || in_array($item->product_type, ['dtf', 'jersey']);
        });

        if (!$hasDesignItem) {
            abort(404, 'Order ini tidak memiliki item desain.');
        }

        return view('editor.sales.show', [
            'salesOrder' => $salesOrder,
        ]);
    }

    /**
     * Daftar sales order yang membutuhkan desain (DTF/Jersey) - read only.
     */
    public function indexDesign(Request $request): View
    {
        $search = trim($request->get('search', ''));
        $status = $request->get('status', 'all');

        $designCondition = function ($query) {
            $query->where('requires_design', true)
                ->orWhereIn('product_type', ['dtf', 'jersey'])
                ->orWhereRaw('LOWER(product_name) LIKE ?', ['%dtf%'])
                ->orWhereRaw('LOWER(product_name) LIKE ?', ['%jersey%']);
        };

        $orders = SalesOrder::with(['customer', 'items' => function ($q) use ($designCondition) {
                $q->where($designCondition);
            }])
            ->whereHas('items', $designCondition)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('so_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== 'all', function ($q) use ($status, $designCondition) {
                $q->whereHas('items', function ($iq) use ($status, $designCondition) {
                    $iq->where($designCondition)
                       ->where('design_status', $status);
                });
            })
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $statusOptions = ['all' => 'Semua Status'] + SalesOrderItem::designStatusOptions();

        return view('editor.sales.index', [
            'orders' => $orders,
            'statusOptions' => $statusOptions,
            'statusFilter' => $status,
            'search' => $search,
        ]);
    }

    public function edit(SalesOrder $salesOrder): View|RedirectResponse
    {
        $shiftCheck = $this->checkActiveShift();
        if ($shiftCheck !== true) {
            return $shiftCheck;
        }

        if (!$salesOrder->isEditable()) {
            return back()->withErrors(['error' => 'Sales order yang selesai tidak bisa diedit.']);
        }
        $customers = Customer::orderBy('name')->get();
        $products = Product::where('is_active', true)->where('price', '>=', 0)->orderBy('name')->get();
        $activeShift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
        return view('editor.sales.edit', compact('salesOrder', 'customers', 'products', 'activeShift'));
    }

    public function update(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $shiftCheck = $this->checkActiveShift();
        if ($shiftCheck !== true) {
            return $shiftCheck;
        }

        if (!$salesOrder->isEditable()) {
            return back()->withErrors(['error' => 'Sales order yang selesai tidak bisa diedit.']);
        }

        $validated = $request->validate([
            'order_type' => ['required', 'in:jahit_sendiri,beli_jadi'],
            'order_date' => ['required', 'date'],
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
        ]);

        $subtotal = collect($validated['items'])->reduce(fn($carry, $item) => $carry + ((float)$item['sale_price'] * (int)$item['qty']), 0);
        $discountTotal = collect($validated['items'])->sum(fn($item) => (float)($item['discount'] ?? 0) * (int)$item['qty']);
        $grandTotal = $subtotal - $discountTotal;

        $cashAmount = $validated['payment_method'] === 'split' ? ($validated['cash_amount'] ?? 0) : ($validated['payment_method'] === 'cash' ? ($validated['payment_amount'] ?? 0) : 0);
        $transferAmount = $validated['payment_method'] === 'split' ? ($validated['transfer_amount'] ?? 0) : ($validated['payment_method'] === 'transfer' ? ($validated['payment_amount'] ?? 0) : 0);
        $paymentAmount = $cashAmount + $transferAmount;

        if ($paymentAmount > 0) {
            if ($paymentAmount > $grandTotal) {
                return back()->withErrors(['payment_amount' => 'Jumlah melebihi grand total: Rp ' . number_format($grandTotal, 0, ',', '.')])->withInput();
            }
        }

        try {
            DB::transaction(function () use ($salesOrder, $validated, $request, $cashAmount, $transferAmount, $paymentAmount, $grandTotal, $subtotal, $discountTotal) {
                $salesOrder->update([
                    'order_type' => $validated['order_type'],
                    'order_date' => $validated['order_date'],
                    'customer_id' => $validated['customer_id'] ?? null,
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
                    $lineTotal = ((float)$item['sale_price'] * (int)$item['qty']) - ((float)($item['discount'] ?? 0) * (int)$item['qty']);
                    
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
                    $proofPath = $request->hasFile('proof_path') ? $request->file('proof_path')->store('payment-proofs', 'public') : null;
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
                            'created_by' => Auth::id(),
                        ]);

                        $this->logAction($salesOrder, 'payment_updated', "Pembayaran diperbarui: {$paymentCategory}, Jumlah: Rp " . number_format($paymentAmount, 0, ',', '.') . ", Metode: {$validated['payment_method']}");
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
                            'created_by' => Auth::id(),
                        ]);

                        $this->logAction($salesOrder, 'payment_added', "Pembayaran ditambahkan: {$paymentCategory}, Jumlah: Rp " . number_format($paymentAmount, 0, ',', '.') . ", Metode: {$validated['payment_method']}");
                    }

                    $activeShift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
                    if ($activeShift && $cashAmount > 0) {
                        $activeShift->increment('cash_total', $cashAmount);
                    }
                }

                $this->logAction($salesOrder, 'updated', "Sales order diperbarui: Tipe: {$validated['order_type']}, Total: Rp " . number_format($grandTotal, 0, ',', '.'));
            });

            return redirect()->route('editor.sales.show', $salesOrder)->with('success', 'Sales order diperbarui dan menunggu approval.');
        } catch (\Exception $e) {
            \Log::error('Error updating sales order: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan saat update SO: ' . $e->getMessage()])->withInput();
        }
    }

    public function addPayment(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $shiftCheck = $this->checkActiveShift();
        if ($shiftCheck !== true) {
            return $shiftCheck;
        }

        $validated = $request->validate([
            'payment_amount' => ['required', 'numeric', 'min:0', function ($attribute, $value, $fail) use ($salesOrder) {
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
            return back()->withErrors(['payment_amount' => 'Jumlah total harus sama dengan jumlah cash + transfer.'])->withInput();
        }

        if (($validated['payment_method'] === 'transfer' || ($validated['payment_method'] === 'split' && ($validated['transfer_amount'] ?? 0) > 0)) && !$request->hasFile('proof_path')) {
            return back()->withErrors(['proof_path' => 'Bukti wajib untuk metode transfer atau split dengan jumlah transfer.'])->withInput();
        }

        try {
            DB::transaction(function () use ($salesOrder, $validated, $request) {
                $proofPath = $request->hasFile('proof_path') ? $request->file('proof_path')->store('payment-proofs', 'public') : null;
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
                    'proof_path' => $proofPath,
                    'reference' => $validated['reference'] ?? null,
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
}