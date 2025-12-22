<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockMovement;
use App\Models\StockOpnameItem;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Imports\StockOpnameImport;
use App\Exports\StockOpnameTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockOpnameController extends Controller
{
    public function index()
    {
        $stockOpnames = StockOpname::with([
            'creator:id,name,email',
            'approver:id,name,email',
            'items.product:id,name'
        ])->latest()->paginate(10);

        $prefix = $this->getViewPrefix();
        $view = "{$prefix}.inventory.stock-opnames.index";
        return view($view, compact('stockOpnames'));
    }

    public function create()
    {
        if (request()->is('finance/*')) {
            return redirect()->route($this->getRoutePrefix() . '.inventory.stock-opnames.index')
                ->with('error', 'Akses ditolak. Anda tidak memiliki izin untuk membuat Stock Opname.');
        }

        // Kita tidak mengirim $products karena akan menggunakan AJAX Search agar ringan
        // Kita juga tidak generate nomor di sini untuk mencegah duplikasi (Race Condition)

        $prefix = $this->getViewPrefix();
        $view = "{$prefix}.inventory.stock-opnames.create";
        return view($view, [
            'routePrefix' => $this->getRoutePrefix()
        ]);
    }

    public function searchProducts(Request $request)
    {
        $query = $request->get('q', '');

        // Return kosong jika query terlalu pendek untuk optimasi
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $products = Product::select('id', 'name', 'sku', 'stock_qty')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->orWhere('barcode', 'like', "%{$query}%");
            })
            ->limit(20) // Batasi hasil pencarian
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku ?? '',
                    'stock_qty' => $product->stock_qty,
                    'formatted_stock' => number_format($product->stock_qty, 0, ',', '.'),
                    'display_name' => "{$product->name} (Stok: {$product->stock_qty})"
                ];
            });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        if (request()->is('finance/*')) {
            return redirect()->route($this->getRoutePrefix() . '.inventory.stock-opnames.index')
                ->with('error', 'Akses ditolak. Anda tidak memiliki izin untuk membuat Stock Opname.');
        }

        $validated = $request->validate([
            'date' => ['required', 'date'], // Hapus validasi 'today' agar fleksibel input data susulan
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_name' => 'required|string', // Validasi nama agar data old() tidak rusak
            'items.*.system_qty' => 'required|numeric',
            'items.*.actual_qty' => 'required|numeric|min:0',
        ], [
            'items.required' => 'Harap tambahkan minimal satu produk.',
            'items.*.actual_qty.required' => 'Qty aktual wajib diisi.',
            'items.*.product_id.required' => 'Produk tidak valid.',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                // Generate Document Number saat Save (Atomic/Critical Section)
                // Ini mencegah nomor ganda jika 2 admin input bersamaan
                $documentNumber = $this->generateDocumentNumber();

                $stockOpname = StockOpname::create([
                    'document_number' => $documentNumber,
                    'date' => $validated['date'],
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'draft',
                    'user_id' => auth()->id()
                ]);

                foreach ($validated['items'] as $item) {
                    // Ambil stok sistem TERBARU saat simpan agar akurat
                    // Meskipun di UI ditampilkan stok saat load, di database kita snapshot yg real-time
                    $product = Product::lockForUpdate()->find($item['product_id']);

                    if (!$product)
                        continue;

                    $systemQty = $product->stock_qty;

                    $stockOpname->items()->create([
                        'product_id' => $item['product_id'],
                        'product_name' => $product->name,
                        'sku' => $product->sku ?? null,
                        'system_qty' => $systemQty,
                        'actual_qty' => $item['actual_qty'],
                        'difference' => $item['actual_qty'] - $systemQty
                    ]);
                }
            });

            $route = $this->getRoutePrefix() . '.inventory.stock-opnames.index';
            return redirect()->route($route)
                ->with('success', 'Stock opname berhasil dibuat.');

        } catch (\Exception $e) {
            // Log error untuk developer
            \Log::error('Stock Opname Error: ' . $e->getMessage());

            // Redirect back dengan input lama & pesan error
            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        if (request()->is('finance/*') || request()->is('editor/*')) {
            return redirect()->route($this->getRoutePrefix() . '.inventory.stock-opnames.index')
                ->with('error', 'Akses ditolak. Anda tidak memiliki izin untuk mengedit Stock Opname.');
        }
        $stockOpname = StockOpname::with(['items.product'])->findOrFail($id);

        if ($stockOpname->status !== 'draft') {
            $route = $this->getRoutePrefix() . '.inventory.stock-opnames.show';
            return redirect()->route($route, $id)
                ->with('error', 'Hanya stock opname dengan status draft yang dapat diedit.');
        }

        $products = Product::select('id', 'name', 'stock_qty')->get();

        $prefix = $this->getViewPrefix();
        $view = "{$prefix}.inventory.stock-opnames.edit";
        return view($view, compact('stockOpname', 'products'));
    }

    public function update(Request $request, $id)
    {
        if (request()->is('finance/*') || request()->is('editor/*')) {
            return redirect()->route($this->getRoutePrefix() . '.inventory.stock-opnames.index')
                ->with('error', 'Akses ditolak. Anda tidak memiliki izin untuk mengedit Stock Opname.');
        }
        $stockOpname = StockOpname::findOrFail($id);

        if ($stockOpname->status !== 'draft') {
            $route = $this->getRoutePrefix() . '.inventory.stock-opnames.show';
            return redirect()->route($route, $id)
                ->with('error', 'Hanya stock opname dengan status draft yang dapat diedit.');
        }

        $validated = $request->validate([
            'document_number' => 'required|unique:stock_opnames,document_number,' . $id,
            'date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    if ($value !== date('Y-m-d')) {
                        $fail('Tanggal harus hari ini.');
                    }
                }
            ],
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.system_qty' => 'required|numeric',
            'items.*.actual_qty' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($stockOpname, $validated) {
            $stockOpname->update([
                'document_number' => $validated['document_number'],
                'date' => $validated['date'],
                'notes' => $validated['notes'],
            ]);

            $stockOpname->items()->delete();

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                $systemQty = $product->stock_qty;

                $stockOpname->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'sku' => $product->sku ?? null,
                    'system_qty' => $systemQty,
                    'actual_qty' => $item['actual_qty'],
                    'difference' => $item['actual_qty'] - $systemQty
                ]);
            }
        });

        $route = $this->getRoutePrefix() . '.inventory.stock-opnames.show';
        return redirect()->route($route, $id)
            ->with('success', 'Stock opname berhasil diperbarui');
    }

    public function approve($id)
    {
        if (!in_array(auth()->user()->usertype, ['finance', 'kepala_toko', 'owner'])) {
            return back()->with('error', 'Akses ditolak.');
        }
        $stockOpname = StockOpname::with('items.product')->findOrFail($id);

        DB::transaction(function () use ($stockOpname) {
            $stockOpname->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            foreach ($stockOpname->items as $item) {
                $product = $item->product;

                if ($product) {
                    $initialQty = $product->stock_qty;
                    $finalQty = $item->actual_qty;

                    $product->update(['stock_qty' => $finalQty]);

                    $qtyIn = $finalQty > $initialQty ? $finalQty - $initialQty : 0;
                    $qtyOut = $finalQty < $initialQty ? $initialQty - $finalQty : 0;

                    StockMovement::record([
                        'product_id' => $product->id,
                        'type' => StockMovement::OPNAME,
                        'ref_code' => $stockOpname->document_number,
                        'qty_in' => $qtyIn,
                        'qty_out' => $qtyOut,
                        'user_id' => auth()->id(),
                        'notes' => 'Stock opname: ' . $stockOpname->document_number,
                        'moved_at' => now(),
                    ]);
                }
            }
        });

        $route = $this->getRoutePrefix() . '.inventory.stock-opnames.index';
        return redirect()->route($route)
            ->with('success', 'Stock Opname berhasil disetujui dan stok produk diperbarui');
    }

    public function destroy($id)
    {
        if (request()->is('admin/*') || request()->is('kepala-toko/*') || request()->is('editor/*')) {
            return redirect()->route($this->getRoutePrefix() . '.inventory.stock-opnames.index')
                ->with('error', 'Akses ditolak. Anda tidak memiliki izin untuk menghapus Stock Opname.');
        }
        $stockOpname = StockOpname::findOrFail($id);

        if ($stockOpname->status === 'approved') {
            return back()->with('error', 'Tidak bisa menghapus Stock Opname yang sudah disetujui');
        }

        DB::transaction(function () use ($stockOpname) {
            $stockOpname->items()->delete();
            $stockOpname->delete();
        });

        $prefix = $this->getRoutePrefix();
        $route = "{$prefix}.inventory.stock-opnames.index";
        return redirect()->route($route)
            ->with('success', 'Stock Opname berhasil dihapus');
    }

    public function show($id)
    {
        $stockOpname = StockOpname::with([
            'creator:id,name,email',
            'approver:id,name,email',
            'items.product:id,name'
        ])->findOrFail($id);

        $products = Product::select('id', 'name', 'stock_qty')->get();

        $prefix = $this->getViewPrefix();
        $view = "{$prefix}.inventory.stock-opnames.show";
        return view($view, compact('stockOpname', 'products'));
    }

    public function exportPdf($id)
    {
        $stockOpname = StockOpname::with([
            'creator:id,name,email',
            'approver:id,name,email',
            'items.product:id,name'
        ])->findOrFail($id);

        if ($stockOpname->status !== 'approved') {
            $route = $this->getRoutePrefix() . '.inventory.stock-opnames.show';
            return redirect()->route($route, $id)
                ->with('error', 'Hanya stock opname yang sudah disetujui yang dapat diekspor ke PDF.');
        }

        $prefix = $this->getViewPrefix();
        $view = "{$prefix}.inventory.stock-opnames.pdf";
        $pdf = Pdf::loadView($view, compact('stockOpname'));
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('margin-top', 10);
        $pdf->setOption('margin-bottom', 10);
        $pdf->setOption('margin-left', 10);
        $pdf->setOption('margin-right', 10);

        return $pdf->download('Stock_Opname_' . $stockOpname->document_number . '.pdf');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx|max:2048',
        ]);

        try {
            Excel::import(new StockOpnameImport, $request->file('file'));
            $route = request()->is('admin/*') ? 'admin.inventory.stock-opnames.index' : 'owner.inventory.stock-opnames.index';
            return redirect()->route($route)
                ->with('success', 'Stock opname berhasil diimpor.');
        } catch (\Exception $e) {
            $route = request()->is('admin/*') ? 'admin.inventory.stock-opnames.index' : 'owner.inventory.stock-opnames.index';
            return redirect()->route($route)
                ->with('error', $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        // Cek permission berdasarkan route current
        $allowedRoles = ['owner', 'admin', 'finance', 'kepala_toko', 'editor'];
        $currentRole = null;

        foreach ($allowedRoles as $role) {
            if (request()->is($role . '/*')) {
                $currentRole = $role;
                break;
            }
        }

        // Jika tidak ada role yang cocok, redirect ke dashboard
        if (!$currentRole) {
            return redirect()->route('dashboard')->with('error', 'Akses tidak diizinkan');
        }

        try {
            return Excel::download(new StockOpnameTemplateExport, 'stock_opname_template.xlsx');
        } catch (\Exception $e) {
            $route = $this->getRoutePrefix() . '.inventory.stock-opnames.index';
            return redirect()->route($route)
                ->with('error', 'Gagal mengunduh template: ' . $e->getMessage());
        }
    }

    private function generateDocumentNumber()
    {
        $prefix = 'SO';
        $datePart = date('Ymd');

        $lastSO = StockOpname::where('document_number', 'like', $prefix . $datePart . '%')
            ->orderBy('document_number', 'desc')
            ->first();

        $number = 1;
        if ($lastSO) {
            $lastNumber = (int) substr($lastSO->document_number, -3);
            $number = $lastNumber + 1;
        }

        return $prefix . $datePart . str_pad($number, 3, '0', STR_PAD_LEFT);
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