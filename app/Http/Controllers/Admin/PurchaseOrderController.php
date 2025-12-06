<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Owner\PurchaseOrderController as BaseController;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLog;
use App\Exports\PurchaseOrderExport;
use App\Exports\PurchaseOrderTemplateExport;
use App\Imports\PurchaseOrderImport;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\PurchaseOrderItem;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;


class PurchaseOrderController extends BaseController
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
        // Authorization untuk admin
        if (!in_array(auth()->user()->usertype, ['admin', 'owner'])) {
            abort(403, 'Akses ditolak untuk admin');
        }

        $q = $request->get('q');
        $status = $request->get('status');
        $group = $request->get('group');
        $type = $request->get('type');

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

        return view('admin.purchases.index', compact('purchases','q','status','group','type'));
    }

    /**
     * Form import purchase order (admin)
     */
    public function importForm(): View
    {
        return view('admin.purchases.import');
    }

    /**
     * Proses import purchase order dari Excel
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:2048',
        ]);

        try {
            $import = new PurchaseOrderImport();
            Excel::import($import, $request->file('file'));

            if (!empty($import->errors)) {
                return back()->withErrors(['import_errors' => $import->errors]);
            }

            $message = "Import berhasil! {$import->successCount} purchase order berhasil dibuat (status Draft).";

            return redirect()->route('admin.purchases.index')->with('success', $message);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat import: ' . $e->getMessage()]);
        }
    }

    /**
     * Export data purchase order beserta item ke Excel
     */
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

    /**
     * Download template Excel untuk import purchase order
     */
    public function downloadTemplate()
    {
        $fileName = 'purchase_order_template_' . now()->format('Ymd') . '.xlsx';
        return Excel::download(new PurchaseOrderTemplateExport(), $fileName);
    }

    public function create(): View
    {
        if (!in_array(auth()->user()->usertype, ['admin', 'owner'])) {
            abort(403, 'Akses ditolak untuk admin');
        }

        $suppliers = Supplier::orderBy('name')->get();
        return view('admin.purchases.create', compact('suppliers'));
    }
    public function edit(PurchaseOrder $purchase): View
    {
        $purchase->load(['supplier', 'items']);
        $suppliers = Supplier::orderBy('name')->get();
        return view('admin.purchases.edit', compact('purchase', 'suppliers'));
    }

// UPDATE UPDATE METHOD - FIX LOG YANG LEBIH DETAIL
public function update(Request $request, PurchaseOrder $purchase): RedirectResponse
{
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
        // SIMPAN DATA LAMA SEBELUM UPDATE
        $oldData = $purchase->getOriginal();
        $oldItems = $purchase->items->toArray();
        
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

        // === FIXED LOG UPDATE - DETEKSI SEMUA PERUBAHAN ===
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
if ((float)$oldData['grand_total'] != (float)$grandTotal) {
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
        if ((int)$oldItem['qty'] != (int)$newItem['qty']) {
            $itemChanges[] = "Qty {$newItem['product_name']} dari {$oldItem['qty']} ke {$newItem['qty']}";
        }
        if ((float)$oldItem['cost_price'] != (float)$newItem['cost_price']) {
            $itemChanges[] = "Harga {$newItem['product_name']} dari Rp " . number_format($oldItem['cost_price'], 0, ',', '.') . " ke Rp " . number_format($newItem['cost_price'], 0, ',', '.');
        }
        if ((float)($oldItem['discount'] ?? 0) != (float)($newItem['discount'] ?? 0)) {
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
    $this->logAction($purchase, 'updated', 
        "Purchase order diupdate: " . implode(', ', $allChanges)
    );
} else {
    $this->logAction($purchase, 'updated', 
        "Purchase order diupdate (tidak ada perubahan data)"
    );
}
    });

    return redirect()->route('admin.purchases.show', $purchase)->with('success', 'Purchase order berhasil diupdate.');
}
    
    public function store(Request $request): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['admin', 'owner'])) {
            abort(403, 'Akses ditolak untuk admin');
        }
        $validated = $request->validate([
            'order_date' => ['required','date'],
            'deadline' => ['nullable','date'], // TAMBAH INI
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
                'deadline' => $validated['deadline'] ?? null, // TAMBAH INI
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
            // TAMBAH LOG CREATE
            $this->logAction($po, 'created', 
                "Purchase order dibuat: {$poNumber}, Tipe: {$validated['purchase_type']}, " .
                "Supplier: " . ($po->supplier->name ?? 'Baru') . ", " .
                "Total: Rp " . number_format($grandTotal, 0, ',', '.')
            );
        });
    
        return redirect()->route('admin.purchases.index')->with('success', 'Pembelian tersimpan sebagai draft.');
    }

    public function show(PurchaseOrder $purchase): View
    {
        if (!in_array(auth()->user()->usertype, ['admin', 'owner'])) {
            abort(403, 'Akses ditolak untuk admin');
        }

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
            'logs.user', // TAMBAH INI UNTUK LOAD LOGS
            'salesOrder.customer'
        ]);
        $availableStatuses = $purchase->getNextAvailableStatuses();

        return view('admin.purchases.show', compact('purchase', 'availableStatuses'));
    }

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
        $this->logAction($purchase, 'status_changed', 
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

    public function cancel(PurchaseOrder $purchase): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['admin', 'owner'])) {
            return back()->withErrors(['status' => 'Unauthorized']);
        }

        return parent::cancel($purchase);
    }

    // Method receive untuk admin (jika diperlukan)
    public function receive(PurchaseOrder $purchase): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['admin', 'owner'])) {
            return back()->withErrors(['status' => 'Unauthorized']);
        }

        return parent::receive($purchase);
    }
    public function generatePoNumber(): string
    {
        return DB::transaction(function () {
            $today = now()->format('ymd');
    
            // Ambil PO terakhir HARI INI dengan lock untuk mencegah bentrok
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