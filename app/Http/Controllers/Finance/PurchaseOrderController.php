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