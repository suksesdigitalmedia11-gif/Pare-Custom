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

        // Query Utama: List Kronologis (Mutasi)
        $query = StockMovement::with(['product', 'user'])
            ->whereDate('moved_at', '>=', $from)
            ->whereDate('moved_at', '<=', $to);

        // Filter Pencarian (Nama Produk / SKU / User)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($subQ) use ($search) {
                    $subQ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                })
                    ->orWhereHas('user', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%");
                    })
                    ->orWhere('ref_code', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        // Urutkan dari yang terbaru
        $stockMovements = $query->orderBy('moved_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->appends($request->query());

        $prefix = $this->getViewPrefix();
        $view = "{$prefix}.inventory.stock_movements.index";

        return view($view, compact('stockMovements', 'from', 'to', 'search'));
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

    public function getProductMovements($productId, $date)
    {
        // Ambil detail pergerakan stok untuk produk tertentu di tanggal tertentu
        $movements = StockMovement::with(['product', 'user'])
            ->where('product_id', $productId)
            ->whereDate('moved_at', $date)
            ->orderBy('moved_at', 'desc')
            ->get();

        $product = Product::find($productId);

        return response()->json([
            'product' => $product->name ?? 'Unknown Product',
            'date' => $date,
            'movements' => $movements
        ]);
    }
}