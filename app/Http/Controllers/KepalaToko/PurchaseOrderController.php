<?php

namespace App\Http\Controllers\KepalaToko;

use App\Http\Controllers\Owner\PurchaseOrderController as BaseController;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\PurchaseOrderItem;
use Carbon\Carbon;


class PurchaseOrderController extends BaseController
{
    public function index(Request $request): View
    {
        // Authorization untuk kepala-toko
        if (!in_array(auth()->user()->usertype, ['kepala_toko', 'owner'])) {
            abort(403, 'Akses ditolak untuk kepala-toko');
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

        return view('kepala-toko.purchases.index', compact('purchases','q','status','group','type'));
    }

    public function create(): View
    {
        if (!in_array(auth()->user()->usertype, ['kepala_toko', 'owner'])) {
            abort(403, 'Akses ditolak untuk kepala-toko');
        }

        $suppliers = Supplier::orderBy('name')->get();
        return view('kepala-toko.purchases.create', compact('suppliers'));
    }
    
    public function store(Request $request): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['kepala_toko', 'owner'])) {
            abort(403, 'Akses ditolak untuk kepala-toko');
        }
        $validated = $request->validate([
            'order_date' => ['required','date'],
            'supplier_id' => ['nullable','exists:suppliers,id'],
            'supplier_name' => ['nullable','string','max:255'],
            'purchase_type' => ['required','in:kain,produk_jadi'], // validasi tipe pembelian
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
                'supplier_id' => $supplierId,
                'purchase_type' => $validated['purchase_type'], // simpan tipe pembelian
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
        });

        return redirect()->route('kepala-toko.purchases.index')->with('success', 'Pembelian tersimpan sebagai draft.');
    }

    public function show(PurchaseOrder $purchase): View
    {
        if (!in_array(auth()->user()->usertype, ['kepala_toko', 'owner'])) {
            abort(403, 'Akses ditolak untuk kepala-toko');
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
            'finisher'
        ]);

        return view('kepala-toko.purchases.show', compact('purchase'));
    }

    public function submit(PurchaseOrder $purchase): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['kepala_toko', 'owner'])) {
            return back()->withErrors(['status' => 'Unauthorized']);
        }

        return parent::submit($purchase);
    }

    public function approve(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['kepala_toko', 'owner'])) {
            return back()->withErrors(['status' => 'Unauthorized']);
        }

        return parent::approve($request, $purchase);
    }

    public function payment(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['kepala_toko', 'owner'])) {
            return back()->withErrors(['status' => 'Unauthorized']);
        }

        return parent::payment($request, $purchase);
    }

    public function updateWorkflowStatus(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['kepala_toko', 'owner'])) {
            return back()->withErrors(['status' => 'Unauthorized']);
        }

        $validated = $request->validate(['new_status' => 'required|string']);
        
        // kepala-toko hanya bisa update ke tahap produksi setelah pembayaran
        if (!in_array($validated['new_status'], ['proses_jahit', 'printing', 'selesai'])) {
            return back()->withErrors(['status' => 'kepala toko hanya bisa update ke proses jahit, printing, atau selesai.']);
        }

        return parent::updateWorkflowStatus($request, $purchase);
    }

    public function cancel(PurchaseOrder $purchase): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['kepala_toko', 'owner'])) {
            return back()->withErrors(['status' => 'Unauthorized']);
        }

        return parent::cancel($purchase);
    }

    // Method receive untuk kepala-toko (jika diperlukan)
    public function receive(PurchaseOrder $purchase): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['kepala_toko', 'owner'])) {
            return back()->withErrors(['status' => 'Unauthorized']);
        }

        return parent::receive($purchase);
    }
    private function generatePoNumber(): string
    {
        $date = Carbon::now()->format('ymd');
        $seq = str_pad((string) (PurchaseOrder::whereDate('created_at', Carbon::today())->count() + 1), 4, '0', STR_PAD_LEFT);
        return 'PO'.$date.$seq;
    }
}