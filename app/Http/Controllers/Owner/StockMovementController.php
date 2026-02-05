<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        // Default filter: Hari ini
        $from = $request->input('from', Carbon::today()->toDateString());
        $to = $request->input('to', Carbon::today()->toDateString());
        $search = $request->input('search');

        // Query dasar untuk pengelompokan produk yang memiliki mutasi
        $query = StockMovement::select('product_id')
            ->selectRaw('SUM(qty_in) as total_in')
            ->selectRaw('SUM(qty_out) as total_out')
            ->selectRaw('COUNT(*) as total_records')
            ->whereDate('moved_at', '>=', $from)
            ->whereDate('moved_at', '<=', $to)
            ->groupBy('product_id');

        // Filter Pencarian (Nama Produk / SKU)
        if ($search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $stockMovements = $query->with('product:id,name,sku')
            ->orderBy('total_records', 'desc')
            ->paginate(15)
            ->appends($request->query());

        // Lampirkan Saldo Awal & Saldo Akhir untuk setiap produk
        $stockMovements->getCollection()->transform(function ($item) use ($from, $to) {
            // Saldo Awal: Nilai final_qty terakhir sebelum tanggal 'from'
            $item->opening_balance = StockMovement::where('product_id', $item->product_id)
                ->where('moved_at', '<', $from . ' 00:00:00')
                ->orderByDesc('moved_at')
                ->orderByDesc('id')
                ->value('final_qty') ?? 0;

            // Saldo Akhir: Nilai final_qty terakhir pada/sebelum tanggal 'to'
            $item->closing_balance = StockMovement::where('product_id', $item->product_id)
                ->whereDate('moved_at', '<=', $to)
                ->orderByDesc('moved_at')
                ->orderByDesc('id')
                ->value('final_qty') ?? $item->opening_balance;

            return $item;
        });

        // Hitung Ringkasan (Stats) Global
        $statsQuery = StockMovement::whereDate('moved_at', '>=', $from)
            ->whereDate('moved_at', '<=', $to);

        if ($search) {
            $statsQuery->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $summary = [
            'total_in' => $statsQuery->sum('qty_in'),
            'total_out' => $statsQuery->sum('qty_out'),
            'total_records' => $statsQuery->count(),
            'most_active_product' => $statsQuery->select('product_id', DB::raw('count(*) as total'))
                ->groupBy('product_id')
                ->orderByDesc('total')
                ->with('product:id,name')
                ->first()
        ];

        $prefix = $this->getViewPrefix();
        $view = "{$prefix}.inventory.stock_movements.index";

        return view($view, compact('stockMovements', 'from', 'to', 'search', 'summary'));
    }

    protected function getViewPrefix()
    {
        if (request()->is('admin/*'))
            return 'admin';
        if (request()->is('finance/*'))
            return 'finance';
        if (request()->is('kepala-toko/*'))
            return 'kepala-toko';
        if (request()->is('editor/*'))
            return 'editor';
        return 'owner';
    }

    public function getProductMovements(Request $request, $productId)
    {
        $from = $request->input('from', Carbon::today()->toDateString());
        $to = $request->input('to', Carbon::today()->toDateString());
        $prefix = $this->getViewPrefix();

        $movements = StockMovement::with(['user:id,name'])
            ->where('product_id', $productId)
            ->whereDate('moved_at', '>=', $from)
            ->whereDate('moved_at', '<=', $to)
            ->orderBy('moved_at', 'asc') // Urutan kronologis untuk modal
            ->orderBy('id', 'asc')
            ->get();

        // Attach detail_url for clickable ledger
        $movements->transform(function ($move) use ($prefix) {
            $move->detail_url = $this->getTransactionUrl($move->type, $move->ref_code, $prefix);
            return $move;
        });

        $product = Product::select('id', 'name', 'sku')->find($productId);

        return response()->json([
            'product' => $product,
            'range' => ['from' => $from, 'to' => $to],
            'movements' => $movements
        ]);
    }

    /**
     * Map transaction type + ref_code to a specific detail route
     */
    protected function getTransactionUrl($type, $refCode, $prefix)
    {
        if (!$refCode) return null;

        try {
            switch ($type) {
                case 'OUTGOING':
                case 'OUT':
                case 'POS_SALE':
                case 'POS_CANCEL':
                    $so = \App\Models\SalesOrder::where('so_number', $refCode)->first();
                    return $so ? route($prefix . '.sales.show', $so->id) : null;

                case 'OPNAME':
                    $opname = \App\Models\StockOpname::where('document_number', $refCode)->first();
                    return $opname ? route($prefix . '.inventory.stock-opnames.show', $opname->id) : null;

                case 'adjustment':
                    $adj = \App\Models\StockAdjustment::where('adjustment_number', $refCode)->first();
                    return $adj ? route($prefix . '.inventory.stock-adjustments.show', $adj->id) : null;

                case 'INCOMING':
                case 'IN_PURCHASE':
                    // Incoming linked to Purchase Order via StockIn or direct ref
                    $si = \App\Models\StockIn::where('stock_in_number', $refCode)->first();
                    if ($si && $si->purchase_order_id) {
                        return route($prefix . '.purchases.show', $si->purchase_order_id);
                    }
                    
                    // Possible direct PO number in ref_code for some imports? 
                    // Let's check if refCode matches a po_number
                    $po = \App\Models\PurchaseOrder::where('po_number', $refCode)->first();
                    return $po ? route($prefix . '.purchases.show', $po->id) : null;

                case 'purchase_cancel':
                    $po = \App\Models\PurchaseOrder::where('po_number', $refCode)->first();
                    return $po ? route($prefix . '.purchases.show', $po->id) : null;

                default:
                    return null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }
}