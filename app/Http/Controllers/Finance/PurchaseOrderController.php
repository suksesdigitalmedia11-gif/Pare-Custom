<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Owner\PurchaseOrderController as BaseController;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLog;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PurchaseOrderController extends BaseController
{
    private function logAction(PurchaseOrder $purchaseOrder, string $action, string $description): void
    {
        PurchaseOrderLog::create([
            'purchase_order_id' => $purchaseOrder->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
    public function show(PurchaseOrder $purchase): View
    {
        // Authorization untuk finance
        if (!in_array(auth()->user()->usertype, ['finance', 'owner'])) {
            abort(403, 'Akses ditolak untuk finance');
        }

        // Load relationships yang diperlukan
        $purchase->load([
            'items', 
            'supplier', 
            'creator', 
            'approver', 
            'paymentProcessor',
            'kainReceiver',
            'printer',
            'tailor',
            'finisher',
            'logs.user', // ✅ TAMBAH INI UNTUK LOAD LOGS
            'salesOrder.customer'
        ]);

        return view('finance.purchases.show', compact('purchase'));
    }
    public function index(Request $request): View
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $group = $request->get('group');
        $type = $request->get('type'); // tambahan untuk filter tipe

        $purchases = PurchaseOrder::with(['supplier','creator','approver'])
            ->when($q, function ($query) use ($q) {
                $query->where('po_number', 'like', "%$q%")
                      ->orWhereHas('supplier', fn($qq) => $qq->where('name', 'like', "%$q%"));
            })
            ->when($type, fn($query) => $query->where('purchase_type', $type))
            ->when($group, function ($query) use ($group) {
                return match ($group) {
                    'todo' => $query->whereIn('status', ['draft','pending']),
                    'request_kain' => $query->where('status', 'request_kain'),
                    'in_progress' => $query->whereIn('status', ['payment', 'proses_jahit', 'printing']),
                    'completed' => $query->where('status', 'selesai'),
                    'cancelled' => $query->where('status', 'canceled'),
                    default => $query,
                };
            })
            ->when($status, fn($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15);

        return view('finance.purchases.index', compact('purchases','q','status','group','type'));
    }
    public function create(): View
    {
        if (!in_array(auth()->user()->usertype, ['finance', 'owner'])) {
            abort(403, 'Akses ditolak untuk finance');
        }

        $suppliers = Supplier::orderBy('name')->get();
        return view('finance.purchases.create', compact('suppliers'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['finance', 'owner'])) {
            abort(403, 'Akses ditolak untuk finance');
        }

        $validated = $request->validate([
            'order_date' => ['required','date'],
            'deadline' => ['nullable','date'],
            'supplier_id' => ['nullable','exists:suppliers,id'],
            'supplier_name' => ['nullable','string','max:255'],
            'purchase_type' => ['required','in:kain,produk_jadi'],
            'is_paid' => ['sometimes','boolean'],
            'items' => ['required','array','min:1'],
            'items.*.product_id' => ['nullable','exists:products,id'],
            'items.*.product_name' => ['required','string','max:255'],
            'items.*.sku' => ['nullable','string','max:100'],
            'items.*.cost_price' => ['required','numeric','min:0'],
            'items.*.qty' => ['required','integer','min:1'],
            'items.*.discount' => ['nullable','numeric','min:0'],
        ]);

        $supplierId = $validated['supplier_id'] ?? null;
        if (!$supplierId) {
            if (!empty($validated['supplier_name'])) {
                $supplier = Supplier::firstOrCreate(
                    ['name' => $validated['supplier_name']],
                    ['is_active' => true]
                );
                $supplierId = $supplier->id;
            } else {
                return back()->withErrors(['supplier_id' => 'Pilih supplier atau isi nama supplier.'])->withInput();
            }
        }

        DB::transaction(function () use ($validated, $supplierId) {
            $poNumber = $this->generatePoNumber();

            $subtotal = 0; $discountTotal = 0; $grandTotal = 0;
            foreach ($validated['items'] as $item) {
                $line = ((float)$item['cost_price'] * (int)$item['qty']);
                $disc = (float)($item['discount'] ?? 0);
                $subtotal += $line;
                $discountTotal += $disc;
            }
            $grandTotal = $subtotal - $discountTotal;

            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'order_date' => $validated['order_date'],
                'deadline' => $validated['deadline'] ?? null,
                'supplier_id' => $supplierId,
                'purchase_type' => $validated['purchase_type'],
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'grand_total' => $grandTotal,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'is_paid' => (bool)($validated['is_paid'] ?? false),
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $line = ((float)$item['cost_price'] * (int)$item['qty']) - (float)($item['discount'] ?? 0);
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'sku' => $item['sku'] ?? null,
                    'cost_price' => $item['cost_price'],
                    'qty' => $item['qty'],
                    'discount' => $item['discount'] ?? 0,
                    'line_total' => $line,
                ]);
            }

            $this->logAction($po, 'created', 
                "Purchase order dibuat: {$poNumber}, Tipe: {$validated['purchase_type']}, " .
                "Supplier: " . ($po->supplier->name ?? 'Baru') . ", " .
                "Total: Rp " . number_format($grandTotal, 0, ',', '.')
            );
        });

        return redirect()->route('finance.purchases.index')->with('success', 'Pembelian tersimpan sebagai draft.');
    }
    public function approve(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['finance', 'owner'])) {
            return back()->withErrors(['status' => 'Unauthorized']);
        }
        return parent::approve($request, $purchase);
    }

    public function payment(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['finance', 'owner'])) {
            return back()->withErrors(['status' => 'Unauthorized']);
        }
        return parent::payment($request, $purchase);
    }

    public function edit(PurchaseOrder $purchase): View
    {
        if (!in_array(auth()->user()->usertype, ['finance', 'owner'])) {
            abort(403, 'Akses ditolak untuk finance');
        }

        $purchase->load(['supplier', 'items']);
        $suppliers = Supplier::orderBy('name')->get();
        return view('finance.purchases.edit', compact('purchase', 'suppliers'));
    }

    public function update(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['finance', 'owner'])) {
            abort(403, 'Akses ditolak untuk finance');
        }

        $validated = $request->validate([
            'order_date' => ['required','date'],
            'deadline' => ['nullable','date'],
            'supplier_id' => ['nullable','exists:suppliers,id'],
            'supplier_name' => ['nullable','string','max:255'],
            'purchase_type' => ['required','in:kain,produk_jadi'],
            'items' => ['required','array','min:1'],
            'items.*.product_id' => ['nullable','exists:products,id'],
            'items.*.product_name' => ['required','string','max:255'],
            'items.*.sku' => ['nullable','string','max:100'],
            'items.*.cost_price' => ['required','numeric','min:0'],
            'items.*.qty' => ['required','integer','min:1'],
            'items.*.discount' => ['nullable','numeric','min:0'],
        ]);

        $supplierId = $validated['supplier_id'] ?? null;
        if (!$supplierId) {
            if (!empty($validated['supplier_name'])) {
                $supplier = Supplier::firstOrCreate(
                    ['name' => $validated['supplier_name']],
                    ['is_active' => true]
                );
                $supplierId = $supplier->id;
            } else {
                return back()->withErrors(['supplier_id' => 'Pilih supplier atau isi nama supplier.'])->withInput();
            }
        }

        DB::transaction(function () use ($purchase, $validated, $supplierId) {
            $subtotal = 0; $discountTotal = 0; $grandTotal = 0;
            foreach ($validated['items'] as $item) {
                $line = ((float)$item['cost_price'] * (int)$item['qty']);
                $disc = (float)($item['discount'] ?? 0);
                $subtotal += $line;
                $discountTotal += $disc;
            }
            $grandTotal = $subtotal - $discountTotal;

            $purchase->update([
                'order_date' => $validated['order_date'],
                'deadline' => $validated['deadline'] ?? null,
                'supplier_id' => $supplierId,
                'purchase_type' => $validated['purchase_type'],
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'grand_total' => $grandTotal,
            ]);

            // Hapus items lama dan buat yang baru
            $purchase->items()->delete();
            foreach ($validated['items'] as $item) {
                $line = ((float)$item['cost_price'] * (int)$item['qty']) - (float)($item['discount'] ?? 0);
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchase->id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'sku' => $item['sku'] ?? null,
                    'cost_price' => $item['cost_price'],
                    'qty' => $item['qty'],
                    'discount' => $item['discount'] ?? 0,
                    'line_total' => $line,
                ]);
            }

            $this->logAction($purchase, 'updated', 
                "Purchase order diupdate: {$purchase->po_number}, " .
                "Total: Rp " . number_format($grandTotal, 0, ',', '.')
            );
        });

        return redirect()->route('finance.purchases.show', $purchase)->with('success', 'Pembelian berhasil diupdate.');
    }

    public function uploadProof(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['finance', 'owner'])) {
            return back()->withErrors(['status' => 'Unauthorized']);
        }

        $validated = $request->validate([
            'invoice_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'payment_proof_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        try {
            DB::transaction(function () use ($purchase, $request, $validated) {
                if ($request->hasFile('invoice_file')) {
                    if ($purchase->invoice_file) {
                        Storage::disk('public')->delete($purchase->invoice_file);
                    }
                    $invoicePath = $request->file('invoice_file')->store('purchase_orders/invoices', 'public');
                    $purchase->update(['invoice_file' => $invoicePath]);
                }

                if ($request->hasFile('payment_proof_file')) {
                    if ($purchase->payment_proof_file) {
                        Storage::disk('public')->delete($purchase->payment_proof_file);
                    }
                    $paymentProofPath = $request->file('payment_proof_file')->store('purchase_orders/payments', 'public');
                    $purchase->update(['payment_proof_file' => $paymentProofPath]);
                }

                $this->logAction($purchase, 'proof_uploaded', 
                    'Bukti pembayaran/invoice diunggah oleh ' . Auth::user()->name
                );
            });

            return back()->with('success', 'File berhasil diunggah.');
        } catch (\Exception $e) {
            \Log::error('Error uploading proof: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan saat mengunggah file: ' . $e->getMessage()]);
        }
    }

    public function cancel(PurchaseOrder $purchase): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['finance', 'owner'])) {
            return back()->withErrors(['status' => 'Unauthorized']);
        }
        
        if ($purchase->status === PurchaseOrder::STATUS_CANCELLED) {
            return back()->with('error', 'Pembelian sudah dibatalkan.');
        }

        // Tidak bisa cancel jika sudah masuk ke production workflow
        $productionStatuses = [
            PurchaseOrder::STATUS_PAYMENT,
            PurchaseOrder::STATUS_PROSES_JAHIT,
            PurchaseOrder::STATUS_PRINTING,
            PurchaseOrder::STATUS_SELESAI
        ];

        if (in_array($purchase->status, $productionStatuses)) {
            return back()->with('error', 'Tidak bisa membatalkan pembelian yang sudah masuk ke proses produksi.');
        }

        $purchase->cancel();
        $this->logAction($purchase, 'cancelled', 'Purchase order dibatalkan oleh ' . Auth::user()->name);
        
        return back()->with('success', 'Pembelian telah dibatalkan.');
    }

    public function updateWorkflowStatus(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        $validated = $request->validate(['new_status' => 'required|string']);
        if ($validated['new_status'] !== 'selesai' && auth()->user()->usertype !== 'owner') {
            return back()->withErrors(['status' => 'Finance hanya bisa update ke selesai.']);
        }
        return parent::updateWorkflowStatus($request, $purchase);
    }

    public function generatePoNumber(): string
    {
        return DB::transaction(function () {
            $today = now()->format('ymd');

            $lastPo = DB::table('purchase_orders')
                ->whereDate('created_at', today())
                ->lockForUpdate()
                ->orderBy('po_number', 'desc')
                ->first();

            if ($lastPo) {
                $lastSeq = (int) substr($lastPo->po_number, -4);
                $newSeq = $lastSeq + 1;
            } else {
                $newSeq = 1;
            }

            return 'PO' . $today . str_pad($newSeq, 4, '0', STR_PAD_LEFT);
        });
    }
}