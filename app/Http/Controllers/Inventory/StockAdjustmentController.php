<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockAdjustmentController extends Controller
{

    public function index(Request $request)
    {
        $query = StockAdjustment::with(['user:id,name', 'items.product'])->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('adjustment_number', 'like', "%{$search}%")
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
        }

        if ($request->has('type') && in_array($request->type, ['in', 'out'])) {
            $query->where('type', $request->type);
        }

        $adjustments = $query->paginate(15);

        $prefix = $this->getViewPrefix();
        // Fallback untuk view yang mungkin belum ada di semua role, kita gunakan shared view atau copy nanti
        // Untuk sekarang asumsikan struktur folder standar
        return view("{$prefix}.inventory.stock-adjustments.index", compact('adjustments'));
    }

    public function create()
    {
        if (request()->is('finance/*')) {
            abort(403, 'Finance hanya boleh melihat laporan.');
        }

        $prefix = $this->getViewPrefix();
        return view("{$prefix}.inventory.stock-adjustments.create");
    }

    public function store(Request $request)
    {
        if (request()->is('finance/*'))
            abort(403);

        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:in,out',
            'reason' => 'required|string|max:100',
            'notes' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.current_stock' => 'required|integer', // Untuk validasi backend
        ]);

        try {
            DB::beginTransaction();

            // 1. Generate Number
            $prefix = $validated['type'] === 'in' ? 'ADJ-IN' : 'ADJ-OUT'; // ADJ-IN/20241222/001
            $dateCode = date('Ymd');
            $lastAdj = StockAdjustment::where('adjustment_number', 'like', "{$prefix}/{$dateCode}/%")
                ->orderBy('id', 'desc')
                ->first();

            $number = 1;
            if ($lastAdj) {
                $lastNum = explode('/', $lastAdj->adjustment_number);
                $number = intval(end($lastNum)) + 1;
            }
            $docNumber = sprintf("%s/%s/%03d", $prefix, $dateCode, $number);

            // 2. Create Header
            $adjustment = StockAdjustment::create([
                'adjustment_number' => $docNumber,
                'date' => $validated['date'],
                'type' => $validated['type'],
                'reason' => $validated['reason'],
                'notes' => $validated['notes'],
                'user_id' => auth()->id()
            ]);

            // 3. Process Items & Update Stock
            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                // Validasi Stok Masih Cukup (khusus OUT)
                if ($validated['type'] === 'out' && $product->stock_qty < $item['qty']) {
                    throw new \Exception("Stok produk {$product->name} tidak mencukupi. Sistem: {$product->stock_qty}, Permintaan: {$item['qty']}");
                }

                // Create Item Detail
                $adjustment->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'qty' => $item['qty']
                ]);

                // Update Master Stock
                $oldQty = $product->stock_qty;
                $finalQty = $oldQty;

                if ($validated['type'] === 'in') {
                    $product->increment('stock_qty', $item['qty']);
                    $finalQty = $oldQty + $item['qty'];
                    $qtyIn = $item['qty'];
                    $qtyOut = 0;
                } else {
                    $product->decrement('stock_qty', $item['qty']); // Sudah divalidasi diatas
                    $finalQty = $oldQty - $item['qty'];
                    $qtyIn = 0;
                    $qtyOut = $item['qty'];
                }

                // Record Movement Log
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'adjustment',
                    'initial_qty' => $oldQty,
                    'qty_in' => $qtyIn,
                    'qty_out' => $qtyOut,
                    'final_qty' => $finalQty,
                    'ref_code' => $docNumber,
                    'notes' => "Adjustment ({$validated['type']}) - {$validated['reason']}",
                    'user_id' => auth()->id(),
                    'moved_at' => now()
                ]);
            }

            DB::commit();

            $routeIdx = $this->getRoutePrefix() . '.inventory.stock-adjustments.index';
            return redirect()->route($routeIdx)->with('success', "Penyesuaian stok {$docNumber} berhasil disimpan.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock Adjustment Fail: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $adjustment = StockAdjustment::with(['items.product', 'user'])->findOrFail($id);
        $prefix = $this->getViewPrefix();
        return view("{$prefix}.inventory.stock-adjustments.show", compact('adjustment'));
    }

    // Helper untuk Dynamic View/Route berdasarkan Role
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

    protected function getRoutePrefix()
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
}
