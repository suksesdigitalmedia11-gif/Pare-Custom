<?php

namespace App\Http\Controllers\Finance;

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
use Illuminate\Support\Arr;

class ProductFinanceController extends Controller implements FromArray, WithHeadings
{
    public function index(Request $request): View
    {
        $q = $request->get('q');
        $categoryId = $request->get('category_id');

        $products = Product::with('category')
            ->when($q, function($query) use ($q) {
                $query->where(function($subQuery) use ($q) {
                    $subQuery->where('name', 'like', "%$q%")
                             ->orWhere('sku', 'like', "%$q%")
                             ->orWhere('barcode', 'like', "%$q%");
                });
            })
            ->when($categoryId, fn($query) => $query->where('category_id', $categoryId))
            ->orderByDesc('id')
            ->paginate(15); // Reduced from 50 to 15 for better performance

        $categories = Category::orderBy('name')->get();

        return view('finance.product.index', compact('products', 'categories', 'q', 'categoryId'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        return view('finance.product.create', compact('categories'));
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
        if ($validated['cost_price'] !== null && (float)$validated['price'] < (float)$validated['cost_price']) {
            return back()->withErrors(['price' => 'Harga jual tidak boleh lebih kecil dari harga beli.'])->withInput();
        }
    
        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }
    
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        Product::create($validated);
    
        return redirect()->route('finance.product.index')->with('success', 'Produk berhasil ditambahkan');
    }

    public function show(Product $product): View
    {
        $categories = Category::orderBy('name')->get();
        return view('finance.product.show', compact('product', 'categories'));
    }

    public function edit(Product $product): View
    {
        $categories = Category::orderBy('name')->get();
        return view('finance.product.edit', compact('product', 'categories'));
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
        if ($validated['cost_price'] !== null && (float)$validated['price'] < (float)$validated['cost_price']) {
            return back()->withErrors(['price' => 'Harga jual tidak boleh lebih kecil dari harga beli.'])->withInput();
        }
    
        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }
    
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $product->update($validated);
    
        return redirect()->route('finance.product.index')->with('success', 'Produk berhasil diperbarui');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();
        return redirect()->route('finance.product.index')->with('success', 'Produk berhasil dihapus');
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        
        $products = Product::where('is_active', true)
            ->where('price', '>', 0)
            ->where(function($q) use ($query) {
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
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        try {
            $import = new ProductImport();
            Excel::import($import, $request->file('file'));

            $importedCount = $import->getRowCount();
            $skippedCount = count($import->failures());
            $errors = [];

            foreach ($import->failures() as $failure) {
                $errors[] = "Baris " . $failure->row() . ": " . implode(', ', $failure->errors());
            }

            $message = "Import selesai! {$importedCount} produk berhasil diimport.";
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} produk dilewati karena error.";
            }

            if (!empty($errors)) {
                session()->flash('import_errors', $errors);
            }

            return redirect()->route('finance.product.index')->with('success', $message);

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
}