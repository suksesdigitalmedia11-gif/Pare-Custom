<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderLog; // TAMBAH INI
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\StockIn;
use App\Models\StockInItem;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\NumberGenerator;
use Illuminate\View\View;
use Carbon\Carbon;
use App\Exports\PurchaseOrderExport;
use App\Exports\PurchaseOrderTemplateExport;
use App\Imports\PurchaseOrderImport;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseOrderController extends Controller
{
    // TAMBAH METHOD LOG HELPER
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
    public function index(Request $request): View
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $group = $request->get('group');
        $type = $request->get('type'); // tambahan untuk filter tipe

        $purchases = PurchaseOrder::with(['supplier', 'creator', 'approver'])
            ->when($q, function ($query) use ($q) {
                $query->where('po_number', 'like', "%$q%")
                    ->orWhereHas('supplier', fn($qq) => $qq->where('name', 'like', "%$q%"));
            })
            ->when($type, fn($query) => $query->where('purchase_type', $type))
            ->when($group, function ($query) use ($group) {
                return match ($group) {
                    'todo' => $query->whereIn('status', ['draft', 'pending']),
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

        return view('owner.purchases.index', compact('purchases', 'q', 'status', 'group', 'type'));
    }

    public function importForm(): View
    {
        return view('owner.purchases.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:2048',
            'import_mode' => 'required|in:migration,full',
        ]);

        try {
            $mode = $request->input('import_mode', 'migration');
            $import = new PurchaseOrderImport($mode);
            Excel::import($import, $request->file('file'));

            if (!empty($import->errors)) {
                return back()->withErrors(['import_errors' => $import->errors]);
            }

            $message = "Import berhasil! {$import->successCount} purchase order berhasil diproses.";
            if ($mode === 'full') {
                $message .= " Stok produk telah ditambahkan secara otomatis.";
            } else {
                $message .= " (Mode Migrasi: Stok tidak berubah).";
            }

            return redirect()->route('owner.purchases.index')->with('success', $message);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat import: ' . $e->getMessage()]);
        }
    }

    public function export(Request $request)
    {
        $query = PurchaseOrder::with(['items', 'supplier', 'creator']);

        if ($type = $request->get('type')) {
            $query->where('purchase_type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($startDate = $request->get('start_date')) {
            $query->whereDate('order_date', '>=', $startDate);
        }

        if ($endDate = $request->get('end_date')) {
            $query->whereDate('order_date', '<=', $endDate);
        }

        $purchases = $query->orderByDesc('order_date')->get();

        if ($purchases->isEmpty()) {
            return back()->withErrors(['error' => 'Tidak ada data purchase untuk di-export.']);
        }

        $fileName = 'purchase_orders_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new PurchaseOrderExport($purchases), $fileName);
    }

    public function downloadTemplate()
    {
        $fileName = 'purchase_order_template_' . now()->format('Ymd') . '.xlsx';
        return Excel::download(new PurchaseOrderTemplateExport(), $fileName);
    }

    public function create(): View
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('owner.purchases.create', compact('suppliers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_date' => ['required', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:order_date'], // TAMBAH VALIDASI 
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'purchase_type' => ['required', 'in:kain,produk_jadi'], // validasi tipe pembelian
            'is_paid' => ['sometimes', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.sku' => ['nullable', 'string', 'max:100'],
            'items.*.cost_price' => ['required', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
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

            $subtotal = 0;
            $discountTotal = 0;
            $grandTotal = 0;
            foreach ($validated['items'] as $item) {
                $line = ((float) $item['cost_price'] * (int) $item['qty']);
                $disc = (float) ($item['discount'] ?? 0);
                $subtotal += $line;
                $discountTotal += $disc;
            }
            $grandTotal = $subtotal - $discountTotal;

            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'order_date' => $validated['order_date'],
                'supplier_id' => $supplierId,
                'purchase_type' => $validated['purchase_type'], // simpan tipe pembelian
                'deadline' => $validated['deadline'] ?? null, // tambah ini
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'grand_total' => $grandTotal,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'is_paid' => (bool) ($validated['is_paid'] ?? false),
                'created_by' => Auth::id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $line = ((float) $item['cost_price'] * (int) $item['qty']) - (float) ($item['discount'] ?? 0);
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
            // TAMBAH LOG CREATE
            $this->logAction(
                $po,
                'created',
                "Purchase order dibuat: {$poNumber}, Tipe: {$validated['purchase_type']}, " .
                "Supplier: " . ($po->supplier->name ?? 'Baru') . ", " .
                "Total: Rp " . number_format($grandTotal, 0, ',', '.')
            );
        });

        return redirect()->route('owner.purchases.index')->with('success', 'Pembelian tersimpan sebagai draft.');
    }

    public function show(PurchaseOrder $purchase): View
    {
        $purchase->load([
            'salesOrder.customer', // ✅ TAMBAH INI UNTUK LOAD CUSTOMER
            'supplier',
            'items',
            'creator',
            'approver',
            'receiver',
            'paymentProcessor',
            'kainReceiver',
            'printer',
            'tailor',
            'finisher',
            'logs.user' // TAMBAH INI UNTUK LOAD LOGS
        ]);
        return view('owner.purchases.show', compact('purchase'));
    }

    public function edit(PurchaseOrder $purchase): View
    {
        $purchase->load(['supplier', 'items']);
        $suppliers = Supplier::orderBy('name')->get();
        return view('owner.purchases.edit', compact('purchase', 'suppliers'));
    }

    // UPDATE UPDATE METHOD - TAMBAH LOG UPDATE
// UPDATE UPDATE METHOD - FIX LOG YANG LEBIH DETAIL
    public function update(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        $validated = $request->validate([
            'order_date' => ['required', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:order_date'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'purchase_type' => ['required', 'in:kain,produk_jadi'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.sku' => ['nullable', 'string', 'max:100'],
            'items.*.cost_price' => ['required', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
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
            // SIMPAN DATA LAMA SEBELUM UPDATE
            $oldData = $purchase->getOriginal();
            $oldItems = $purchase->items->toArray();

            $subtotal = 0;
            $discountTotal = 0;
            $grandTotal = 0;
            foreach ($validated['items'] as $item) {
                $line = ((float) $item['cost_price'] * (int) $item['qty']);
                $disc = (float) ($item['discount'] ?? 0);
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
                'notes' => $validated['notes'] ?? null,
            ]);

            // Hapus items lama dan buat yang baru
            $purchase->items()->delete();
            foreach ($validated['items'] as $item) {
                $line = ((float) $item['cost_price'] * (int) $item['qty']) - (float) ($item['discount'] ?? 0);
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

            // === FIXED LOG UPDATE - DETEKSI HANYA PERUBAHAN YANG REAL ===
            $changes = [];

            // 1. Deteksi perubahan header - PAKAI FORMAT YANG SAMA
            $oldDate = Carbon::parse($oldData['order_date'])->format('Y-m-d');
            $newDate = Carbon::parse($validated['order_date'])->format('Y-m-d');
            if ($oldDate != $newDate) {
                $changes[] = "Tanggal order dari " . Carbon::parse($oldData['order_date'])->format('d/m/Y') . " ke " . Carbon::parse($validated['order_date'])->format('d/m/Y');
            }

            // Deadline - handle null values
            $oldDeadline = $oldData['deadline'] ? Carbon::parse($oldData['deadline'])->format('Y-m-d') : null;
            $newDeadline = $validated['deadline'] ? Carbon::parse($validated['deadline'])->format('Y-m-d') : null;
            if ($oldDeadline != $newDeadline) {
                if ($oldDeadline && $newDeadline) {
                    $changes[] = "Deadline dari " . Carbon::parse($oldData['deadline'])->format('d/m/Y') . " ke " . Carbon::parse($validated['deadline'])->format('d/m/Y');
                } elseif ($newDeadline) {
                    $changes[] = "Deadline ditambahkan: " . Carbon::parse($validated['deadline'])->format('d/m/Y');
                } elseif ($oldDeadline) {
                    $changes[] = "Deadline dihapus";
                }
            }

            if ($oldData['purchase_type'] != $validated['purchase_type']) {
                $oldType = $purchase->getTypeLabel($oldData['purchase_type']);
                $newType = $purchase->getTypeLabel($validated['purchase_type']);
                $changes[] = "Tipe pembelian dari {$oldType} ke {$newType}";
            }

            // Total - bandingkan numeric value, bukan string
            if ((float) $oldData['grand_total'] != (float) $grandTotal) {
                $changes[] = "Total dari Rp " . number_format($oldData['grand_total'], 0, ',', '.') . " ke Rp " . number_format($grandTotal, 0, ',', '.');
            }

            // 2. Deteksi perubahan items (qty, harga, diskon)
            $itemChanges = [];
            $newItems = $validated['items'];

            // Bandingkan items lama dan baru
            foreach ($newItems as $index => $newItem) {
                $oldItem = $oldItems[$index] ?? null;

                if ($oldItem) {
                    // Item existing - cek perubahan
                    if ((int) $oldItem['qty'] != (int) $newItem['qty']) {
                        $itemChanges[] = "Qty {$newItem['product_name']} dari {$oldItem['qty']} ke {$newItem['qty']}";
                    }
                    if ((float) $oldItem['cost_price'] != (float) $newItem['cost_price']) {
                        $itemChanges[] = "Harga {$newItem['product_name']} dari Rp " . number_format($oldItem['cost_price'], 0, ',', '.') . " ke Rp " . number_format($newItem['cost_price'], 0, ',', '.');
                    }
                    if ((float) ($oldItem['discount'] ?? 0) != (float) ($newItem['discount'] ?? 0)) {
                        $oldDisc = number_format($oldItem['discount'] ?? 0, 0, ',', '.');
                        $newDisc = number_format($newItem['discount'] ?? 0, 0, ',', '.');
                        $itemChanges[] = "Diskon {$newItem['product_name']} dari Rp {$oldDisc} ke Rp {$newDisc}";
                    }
                } else {
                    // Item baru
                    $itemChanges[] = "Item baru: {$newItem['product_name']} (Qty: {$newItem['qty']})";
                }
            }

            // Cek item yang dihapus
            if (count($oldItems) > count($newItems)) {
                for ($i = count($newItems); $i < count($oldItems); $i++) {
                    $itemChanges[] = "Item dihapus: {$oldItems[$i]['product_name']}";
                }
            }

            // Gabungkan semua perubahan
            $allChanges = array_merge($changes, $itemChanges);

            if (!empty($allChanges)) {
                $this->logAction(
                    $purchase,
                    'updated',
                    "Purchase order diupdate: " . implode(', ', $allChanges)
                );
            } else {
                $this->logAction(
                    $purchase,
                    'updated',
                    "Purchase order diupdate (tidak ada perubahan data)"
                );
            }
        });

        return redirect()->route('owner.purchases.show', $purchase)->with('success', 'Purchase order berhasil diupdate.');
    }
    // UPDATE SUBMIT METHOD - TAMBAH LOG
    public function submit(PurchaseOrder $purchase): RedirectResponse
    {
        if ($purchase->status !== PurchaseOrder::STATUS_DRAFT) {
            return back()->withErrors(['status' => 'Hanya draft yang bisa diajukan.']);
        }

        $purchase->status = PurchaseOrder::STATUS_PENDING;
        $purchase->save();

        // TAMBAH LOG
        $this->logAction($purchase, 'submitted', 'Purchase order diajukan untuk approval');

        return back()->with('success', 'Pembelian diajukan untuk approval.');
    }

    // UPDATE APPROVE METHOD - TAMBAH LOG
    public function approve(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        if ($purchase->status !== PurchaseOrder::STATUS_PENDING) {
            return back()->withErrors(['status' => 'Hanya pending yang bisa di-approve.']);
        }

        $purchase->update([
            'status' => PurchaseOrder::STATUS_REQUEST_KAIN,
            'approved_by' => Auth::id(),
            'approved_at' => Carbon::now(),
        ]);

        // TAMBAH LOG
        $this->logAction($purchase, 'approved', 'Purchase order di-approve oleh ' . Auth::user()->name);

        return back()->with('success', 'Pembelian telah di-approve.');
    }
    /**
     * ✅ WORKFLOW BARU: payment_proof_file dibuat opsional
     * Bisa diisi nanti, bahkan sampai status selesai
     */
    public function payment(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        if ($purchase->status !== PurchaseOrder::STATUS_REQUEST_KAIN) {
            return back()->withErrors(['status' => 'Hanya request kain yang bisa diproses pembayaran.']);
        }

        $validated = $request->validate([
            'invoice_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048', // ✅ Opsional
            'payment_proof_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048', // ✅ Opsional
        ]);

        $invoicePath = $request->hasFile('invoice_file')
            ? $request->file('invoice_file')->store('purchase_orders/invoices', 'public')
            : $purchase->invoice_file; // Keep existing if not uploaded

        $paymentProofPath = $request->hasFile('payment_proof_file')
            ? $request->file('payment_proof_file')->store('purchase_orders/payments', 'public')
            : $purchase->payment_proof_file; // Keep existing if not uploaded

        $purchase->update([
            'status' => PurchaseOrder::STATUS_PAYMENT,
            'payment_by' => Auth::id(),
            'payment_at' => Carbon::now(),
            'invoice_file' => $invoicePath,
            'payment_proof_file' => $paymentProofPath,
        ]);

        // TAMBAH LOG
        $this->logAction($purchase, 'payment_processed', 'Pembayaran diproses' . ($invoicePath ? ' dengan invoice' : '') . ($paymentProofPath ? ' dan bukti pembayaran' : ''));

        return back()->with('success', 'Pembayaran telah diproses.' . ($invoicePath ? ' Invoice tersimpan.' : '') . ($paymentProofPath ? ' Bukti pembayaran tersimpan.' : ''));
    }

    // Method baru untuk update status workflow
    public function updateWorkflowStatus(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        $validated = $request->validate([
            'new_status' => 'required|string',
        ]);

        $oldStatus = $purchase->status;
        $success = $purchase->updateStatus($validated['new_status'], Auth::id());

        if (!$success) {
            return back()->withErrors(['status' => 'Status tidak valid atau tidak bisa diupdate.']);
        }

        // TAMBAH LOG STATUS CHANGE
        $this->logAction(
            $purchase,
            'status_changed',
            "Status diubah dari {$oldStatus} ke {$validated['new_status']} oleh " . Auth::user()->name
        );

        // Handle khusus untuk selesai - update stock untuk kedua tipe
        if ($validated['new_status'] === PurchaseOrder::STATUS_SELESAI) {
            if ($purchase->isKainType()) {
                $this->handleKainSelesai($purchase);
            } elseif ($purchase->isProdukJadiType()) {
                $this->handleProdukJadiSelesai($purchase);
            }
        }

        $statusLabel = $purchase->getStatusLabel();
        return back()->with('success', "Status berhasil diupdate ke: {$statusLabel}");
    }

    public function handleKainSelesai(PurchaseOrder $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            // Create Stock In untuk kain yang sudah selesai (printing + jahit)
            $stockIn = StockIn::create([
                'stock_in_number' => $this->generateStockInNumber(),
                'purchase_order_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'received_date' => Carbon::now()->toDateString(),
                'notes' => 'Produk kain selesai dari PO: ' . $purchase->po_number,
                'status' => 'posted',
                'received_by' => Auth::id(),
            ]);

            foreach ($purchase->items as $item) {
                StockInItem::create([
                    'stock_in_id' => $stockIn->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'sku' => $item->sku,
                    'qty' => $item->qty,
                ]);

                if ($item->product_id) {
                    $product = $item->product;
                    $initial = $product->stock_qty ?? 0;
                    $product->stock_qty = $initial + $item->qty;
                    $product->save();

                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'INCOMING',
                        'ref_code' => $stockIn->stock_in_number,
                        'initial_qty' => $initial,
                        'qty_in' => $item->qty,
                        'qty_out' => 0,
                        'final_qty' => $product->stock_qty,
                        'user_id' => Auth::id(),
                        'notes' => 'Produk kain selesai (PO: ' . $purchase->po_number . ')',
                        'moved_at' => Carbon::now(),
                    ]);
                }
            }
        });
    }

    public function handleProdukJadiSelesai(PurchaseOrder $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            // Create Stock In untuk produk jadi
            $stockIn = StockIn::create([
                'stock_in_number' => $this->generateStockInNumber(),
                'purchase_order_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'received_date' => Carbon::now()->toDateString(),
                'notes' => 'Produk jadi diterima dari PO: ' . $purchase->po_number,
                'status' => 'posted',
                'received_by' => Auth::id(),
            ]);

            foreach ($purchase->items as $item) {
                StockInItem::create([
                    'stock_in_id' => $stockIn->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'sku' => $item->sku,
                    'qty' => $item->qty,
                ]);

                if ($item->product_id) {
                    $product = $item->product;
                    $initial = $product->stock_qty ?? 0;
                    $product->stock_qty = $initial + $item->qty;
                    $product->save();

                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'INCOMING',
                        'ref_code' => $stockIn->stock_in_number,
                        'initial_qty' => $initial,
                        'qty_in' => $item->qty,
                        'qty_out' => 0,
                        'final_qty' => $product->stock_qty,
                        'user_id' => Auth::id(),
                        'notes' => 'Produk jadi diterima (PO: ' . $purchase->po_number . ')',
                        'moved_at' => Carbon::now(),
                    ]);
                }
            }
        });
    }

    // Method lama tetap dipakai untuk backward compatibility
    public function receive(PurchaseOrder $purchase): RedirectResponse
    {
        if (!in_array($purchase->status, ['request_kain', 'pending'])) {
            return back()->withErrors(['status' => 'Hanya pending/request kain yang bisa diterima.']);
        }

        DB::transaction(function () use ($purchase) {
            $purchase->update([
                'status' => 'received',
                'received_at' => Carbon::now(),
                'received_by' => Auth::id(),
            ]);

            $stockIn = StockIn::create([
                'stock_in_number' => $this->generateStockInNumber(),
                'purchase_order_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'received_date' => Carbon::now()->toDateString(),
                'notes' => 'No. Pembelian: ' . $purchase->po_number,
                'status' => 'posted',
                'received_by' => Auth::id(),
            ]);

            foreach ($purchase->items as $item) {
                StockInItem::create([
                    'stock_in_id' => $stockIn->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'sku' => $item->sku,
                    'qty' => $item->qty,
                ]);

                if ($item->product_id) {
                    $product = $item->product;
                    $initial = $product->stock_qty ?? 0;
                    $product->stock_qty = $initial + $item->qty;
                    $product->save();

                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'INCOMING',
                        'ref_code' => $stockIn->stock_in_number,
                        'initial_qty' => $initial,
                        'qty_in' => $item->qty,
                        'qty_out' => 0,
                        'final_qty' => $product->stock_qty,
                        'user_id' => Auth::id(),
                        'notes' => 'Pembelian diterima (PO: ' . $purchase->po_number . ')',
                        'moved_at' => Carbon::now(),
                    ]);
                }
            }
        });

        return back()->with('success', 'Barang diterima, stok produk diperbarui, dan pergerakan stok dicatat.');
    }

    public function cancel(PurchaseOrder $purchase): RedirectResponse
    {
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

        return back()->with('success', 'Pembelian berhasil dibatalkan.');
    }

    public function return(Request $request, PurchaseOrder $purchase): \Illuminate\Http\JsonResponse
    {
        if (!in_array($purchase->status, [PurchaseOrder::STATUS_SELESAI, 'received'])) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pembelian yang sudah selesai yang bisa diretur.'
            ], 400);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            // Buat purchase return
            $return = PurchaseReturn::create([
                'purchase_order_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'return_date' => now(),
                'reason' => $validated['reason'],
                'created_by' => Auth::id(),
            ]);

            $totalAmount = 0;

            // Process return items
            foreach ($validated['items'] as $itemData) {
                $purchaseItem = $purchase->items()
                    ->where('product_id', $itemData['product_id'])
                    ->first();

                if (!$purchaseItem || $itemData['quantity'] > $purchaseItem->qty) {
                    throw new \Exception('Quantity retur tidak valid untuk produk: ' . $itemData['product_id']);
                }

                $returnItem = PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'product_id' => $itemData['product_id'],
                    'qty' => $itemData['quantity'],
                    'price' => $purchaseItem->cost_price,
                    'total' => $purchaseItem->cost_price * $itemData['quantity']
                ]);

                $totalAmount += $returnItem->total;

                // Kurangi stok
                $product = Product::find($itemData['product_id']);
                $initial = $product->stock_qty;
                $product->stock_qty -= $itemData['quantity'];
                $product->save();

                // Catat stock movement
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'OUTGOING',
                    'ref_code' => 'RET-' . $return->id,
                    'initial_qty' => $initial,
                    'qty_in' => 0,
                    'qty_out' => $itemData['quantity'],
                    'final_qty' => $product->stock_qty,
                    'user_id' => Auth::id(),
                    'notes' => 'Retur pembelian: ' . $purchase->po_number . ' - ' . $validated['reason'],
                    'moved_at' => now(),
                ]);
            }

            $purchase->update(['status' => 'returned']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Retur pembelian berhasil diproses'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses retur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getItems(PurchaseOrder $purchase)
    {
        $items = $purchase->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'quantity' => $item->qty,
                'cost_price' => $item->cost_price
            ];
        });

        return response()->json($items);
    }

    private function generatePoNumber(): string
    {
        return app(NumberGenerator::class)->generatePurchaseOrderNumber();
    }

    // ROLLBACK FEATURE - CRITICAL FOR FIXING ACCIDENTAL 'SELESAI'
    public function rollbackCompletion(PurchaseOrder $purchase): RedirectResponse
    {
        if ($purchase->status !== PurchaseOrder::STATUS_SELESAI) {
            return back()->withErrors(['status' => 'Hanya pembelian status Selesai yang bisa di-rollback.']);
        }

        try {
            DB::transaction(function () use ($purchase) {
                // 1. Cari Dokumen Stock In yang terkait
                $stockIn = StockIn::where('purchase_order_id', $purchase->id)->first();

                if ($stockIn) {
                    // 2. Kembalikan Stok (Deduct)
                    foreach ($stockIn->items as $item) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $initial = $product->stock_qty;
                            $final = $initial - $item->qty;

                            // MODIFIED: Izinkan stok minus untuk keperluan rollback admin (Override Safety)
                            $product->update(['stock_qty' => $final]);

                            StockMovement::where('ref_code', $stockIn->stock_in_number)
                                ->where('type', 'INCOMING')
                                ->delete();
                        }
                    }

                    // 3. Hapus Stock In
                    $stockIn->items()->delete();
                    $stockIn->delete();
                }

                // 4. Kembalikan Status PO
                // Logika mundur: Selesai -> Printing
                $purchase->update([
                    'status' => PurchaseOrder::STATUS_PRINTING,
                    'received_at' => null,
                    'received_by' => null,
                ]);

                $this->logAction($purchase, 'rollback', 'Status dikembalikan dari Selesai ke Printing (Koreksi Admin/Owner)');
            });

            return back()->with('success', 'Status berhasil dikembalikan ke tahap Printing. Stok otomatis ditarik kembali.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function generateStockInNumber(): string
    {
        $date = Carbon::now()->format('ymd');
        $seq = str_pad((string) (StockIn::whereDate('created_at', Carbon::today())->count() + 1), 4, '0', STR_PAD_LEFT);
        return 'IN' . $date . $seq;
    }
}