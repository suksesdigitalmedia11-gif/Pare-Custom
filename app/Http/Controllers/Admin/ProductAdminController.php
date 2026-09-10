<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use League\Csv\Reader;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Imports\ProductImport;
use App\Exports\ProductExport;
use App\Exports\ProductPriceUpdateExport;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductAdminController extends Controller implements FromArray, WithHeadings
{
    public function index(Request $request): View
    {
        $q = $request->get('q');
        $categoryId = $request->get('category_id');
        $sortBy = $request->get('sort_by', 'id');
        $direction = $request->get('direction', 'desc');

        // Whitelist allowed sort columns
        $allowedSorts = ['name', 'price', 'stock_qty', 'id'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
            $direction = 'desc';
        }

        $products = Product::with('category')
            ->when($q, function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('name', 'like', "%$q%")
                        ->orWhere('sku', 'like', "%$q%")
                        ->orWhere('barcode', 'like', "%$q%");
                });
            })
            ->when($categoryId, fn($query) => $query->where('category_id', $categoryId))
            ->orderBy($sortBy, $direction)
            ->paginate(15);

        $categories = Category::orderBy('name')->get();

        return view('admin.product.index', compact('products', 'categories', 'q', 'categoryId', 'sortBy', 'direction'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.product.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'cost_price' => ['nullable', 'numeric', 'min:0'], // <-- BOLEH NULL / 0
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:10240'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // Cek perbandingan hanya jika cost_price diisi
        if ($validated['cost_price'] !== null && (float) $validated['price'] < (float) $validated['cost_price']) {
            return back()->withErrors(['price' => 'Harga jual tidak boleh lebih kecil dari harga beli.'])->withInput();
        }

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }

        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $product = Product::create($validated);

        if (!empty($validated['cost_price']) && (float) $validated['cost_price'] > 0) {
            \App\Models\ProductPriceLog::create([
                'product_id' => $product->id,
                'old_cost_price' => null,
                'new_cost_price' => (float) $validated['cost_price'],
                'changed_by' => auth()->id(),
                'changed_at' => now(),
                'source' => 'manual',
            ]);
        }

        return redirect()->route('admin.product.index')->with('success', 'Produk berhasil ditambahkan');
    }

    public function show(Product $product): View
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.product.show', compact('product', 'categories'));
    }

    public function edit(Product $product): View
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.product.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku,' . $product->id],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'cost_price' => ['nullable', 'numeric', 'min:0'], // <-- BOLEH NULL / 0
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:10240'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // Cek perbandingan hanya jika cost_price diisi
        if ($validated['cost_price'] !== null && (float) $validated['price'] < (float) $validated['cost_price']) {
            return back()->withErrors(['price' => 'Harga jual tidak boleh lebih kecil dari harga beli.'])->withInput();
        }

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }

        $oldCost = (float) ($product->cost_price ?? 0);
        $newCost = $validated['cost_price'] !== null ? (float) $validated['cost_price'] : null;

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $product->update($validated);

        if ($newCost !== null && abs($oldCost - $newCost) > 0.001) {
            \App\Models\ProductPriceLog::create([
                'product_id' => $product->id,
                'old_cost_price' => $oldCost > 0 ? $oldCost : null,
                'new_cost_price' => $newCost,
                'changed_by' => auth()->id(),
                'changed_at' => now(),
                'source' => 'manual',
            ]);
        }

        return redirect()->route('admin.product.index')->with('success', 'Produk berhasil diperbarui');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();
        return redirect()->route('admin.product.index')->with('success', 'Produk berhasil dihapus');
    }

    public function search(Request $request)
    {
        $query = $request->get('q');

        $products = Product::where('is_active', true)
            ->where('price', '>=', 0)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->orWhere('barcode', 'like', "%{$query}%");
            })
            ->select('id', 'name', 'sku', 'barcode', 'price', 'cost_price', 'stock_qty')
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json($products);
    }

    public function import(Request $request): RedirectResponse
    {
        // Two-step import: if token provided, use stored file
        $importToken = $request->get('import_token');
        $file = null;

        if ($importToken && session('import_file_token') === $importToken) {
            $ext = session('import_file_ext', 'xlsx');
            $storedPath = storage_path('app/imports/' . $importToken . '.' . $ext);
            if (file_exists($storedPath)) {
                $file = $storedPath;
                // Clean up session
                session()->forget(['import_file_token', 'import_file_ext']);
            }
        }

        if (!$file) {
            $request->validate([
                'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
            ]);
            $file = $request->file('file');
        }

        try {
            $import = new ProductImport();
            Excel::import($import, $file);

            $importedCount = $import->getRowCount();
            $updatedCount = $import->getUpdatedCount();
            $insertedCount = $import->getInsertedCount();
            $unchangedCount = $import->getUnchangedCount();
            $skippedCount = count($import->failures());
            $errors = [];

            foreach ($import->failures() as $failure) {
                $errors[] = "Baris " . $failure->row() . ": " . implode(', ', $failure->errors());
            }

            $message = "Import selesai! {$importedCount} produk diproses ({$updatedCount} berhasil diupdate, {$insertedCount} produk baru).";
            if ($unchangedCount > 0) {
                $message .= " {$unchangedCount} produk tidak ada perubahan.";
            }
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} produk dilewati karena error.";
            }

            if (!empty($errors)) {
                session()->flash('import_errors', $errors);
            }

            if (!empty($import->getUpdatedProducts())) {
                session()->flash('import_updated_products', $import->getUpdatedProducts());
            }

            return redirect()->route('admin.product.index')->with('success', $message);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = "Baris " . $failure->row() . ": " . implode(', ', $failure->errors());
            }
            return redirect()->back()->withErrors(['file' => $errors]);
        } catch (\Exception $e) {
            \Log::error('Import error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['file' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    public function headings(): array
    {
        return ['sku', 'name', 'category_name', 'cost_price', 'price', 'stock_qty', 'is_active'];
    }

    public function array(): array
    {
        return [
            ['SKU001', 'Kaos Polos Putih', '', 100000, 150000, 50, 1],
            ['SKU002', 'Kaos Polos Hitam', 'Kaos', 120000, 180000, 30, 1],
            ['SKU003', 'Kemeja Formal Navy', 'Kemeja', 200000, 300000, 25, 1],
            ['SKU004', 'Celana Jeans Slim Fit', 'Celana', 150000, 250000, 40, 1],
            ['SKU005', 'Dress Casual Pink', '', 180000, 280000, 15, 0],
        ];
    }

    public function downloadTemplate()
    {
        return Excel::download($this, 'product_template.xlsx');
    }

    /**
     * Preview import — tampilkan data yang akan berubah sebelum commit.
     */
    public function previewImport(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        try {
            $file = $request->file('file');
            $tempPath = $file->store('temp', 'local');
            $absolutePath = storage_path('app/' . $tempPath);

            $previewData = ProductImport::getPreview($absolutePath);

            // Clean up temp file
            \Illuminate\Support\Facades\Storage::disk('local')->delete($tempPath);

            // Store file temporarily for actual import
            $importToken = bin2hex(random_bytes(16));
            $file->storeAs('imports', $importToken . '.' . $file->getClientOriginalExtension(), 'local');
            session(['import_file_token' => $importToken, 'import_file_ext' => $file->getClientOriginalExtension()]);

            return response()->json([
                'success' => true,
                'preview' => $previewData['rows'] ?? [],
                'import_token' => $importToken,
                'summary' => $previewData['summary'] ?? [],
                'total_changes' => $previewData['summary']['total_changes'] ?? 0,
                'total_inserts' => $previewData['summary']['total_inserts'] ?? 0,
                'total_updates' => $previewData['summary']['total_updates'] ?? 0,
                'total_unchanged' => $previewData['summary']['total_unchanged'] ?? 0,
                'total_errors' => $previewData['summary']['total_errors'] ?? 0,
            ]);
        } catch (\Exception $e) {
            \Log::error('Preview import error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca file: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Export template ringan untuk update harga.
     * Hanya berisi: SKU, Nama Produk, Harga Modal Lama, Harga Modal Baru (kosong), Harga Jual.
     */
    public function exportPriceUpdate(Request $request): BinaryFileResponse
    {
        $q = $request->get('q');
        $categoryId = $request->get('category_id');

        $products = Product::with('category')
            ->when($q, function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('name', 'like', "%$q%")
                        ->orWhere('sku', 'like', "%$q%")
                        ->orWhere('barcode', 'like', "%$q%");
                });
            })
            ->when($categoryId, fn($query) => $query->where('category_id', $categoryId))
            ->orderBy('name')
            ->get();

        $filename = 'update_harga_' . date('Y-m-d_His') . '.xlsx';

        $export = new ProductPriceUpdateExport($products);

        return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Export produk ke Excel/CSV
     * Mendukung filter berdasarkan query dan kategori
     */
    public function export(Request $request): BinaryFileResponse
    {
        $q = $request->get('q');
        $categoryId = $request->get('category_id');
        $format = $request->get('format', 'xlsx'); // xlsx, csv

        // Query produk dengan filter yang sama seperti di index
        $products = Product::with('category')
            ->when($q, function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('name', 'like', "%$q%")
                        ->orWhere('sku', 'like', "%$q%")
                        ->orWhere('barcode', 'like', "%$q%");
                });
            })
            ->when($categoryId, fn($query) => $query->where('category_id', $categoryId))
            ->orderByDesc('id')
            ->get();

        // Generate filename dengan timestamp dan filter info
        $filename = 'produk_export_' . date('Y-m-d_His');
        if ($q) {
            $filename .= '_search-' . substr($q, 0, 10);
        }
        if ($categoryId) {
            $category = \App\Models\Category::find($categoryId);
            if ($category) {
                $filename .= '_kategori-' . $category->name;
            }
        }
        $filename .= '.' . $format;

        $export = new ProductExport($products);

        if ($format === 'csv') {
            return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX);
    }
}