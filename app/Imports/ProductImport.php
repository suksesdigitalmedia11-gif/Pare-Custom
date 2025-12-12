<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;

class ProductImport implements ToModel, WithHeadingRow, SkipsOnFailure
{
    use SkipsFailures;

    private $rowCount = 0;
    private $processedRows = 0;

    public function model(array $row)
    {
        $row = $this->normalizeRow($row);
        $this->processedRows++;
        $rowNumber = $this->processedRows + 1; // +1 karena heading

        // Validasi minimal: name, cost_price, price harus ada
        if (empty($row['name'])) {
            $this->addFailure($rowNumber, 'name', ['Nama produk wajib diisi'], $row);
            return null;
        }

        $costPrice = $this->convertToFloat($row['cost_price'] ?? null);
        $price = $this->convertToFloat($row['price'] ?? null);

        if ($costPrice === false) {
            $this->addFailure($rowNumber, 'cost_price', ['Harga modal tidak valid'], $row);
            return null;
        }

        if ($price === false) {
            $this->addFailure($rowNumber, 'price', ['Harga jual tidak valid'], $row);
            return null;
        }

        if ($price < $costPrice) {
            $this->addFailure($rowNumber, 'price', ['Harga jual tidak boleh lebih kecil dari harga modal'], $row);
            return null;
        }

        $product = $this->findProduct($row);
        
        if ($product) {
            // Update hanya harga modal & harga jual
            $product->cost_price = $costPrice;
            $product->price = $price;
            $product->save();
        } else {
            // Insert produk baru
            $categoryId = $this->getOrCreateCategory($row['category_name'] ?? null);
            
            $stockQty = $this->convertToInt($row['stock_qty'] ?? 0);
            $isActive = $this->convertToBoolean($row['is_active'] ?? 'Aktif');
            
            $product = Product::create([
                'sku' => $row['sku'] ?? null,
                'barcode' => $row['barcode'] ?? null,
                'name' => $row['name'],
                'category_id' => $categoryId,
                'cost_price' => $costPrice,
                'price' => $price,
                'stock_qty' => $stockQty,
                'is_active' => $isActive,
            ]);
        }

        $this->rowCount++;
        return null; // Tidak membuat entitas baru via ToModel
    }

    /**
     * 🔧 FIX: Konversi format Indonesia ke float
     * Contoh: 
     * - "61998,77" → 61998.77
     * - "17.000" → 17000.00
     * - "17.000,77" → 17000.77
     * - "1.234,56" → 1234.56
     * - "30,000" → 30000 (koma sebagai thousand separator jika diikuti 3 digit)
     * - "30,000.00" → 30000.00
     */
    private function convertToFloat($value)
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = trim(strval($value));
        if ($value === '' || $value === '-' || $value === null) {
            return false;
        }

        // Hapus karakter non digit/koma/titik
        $cleaned = preg_replace('/[^\d,\.]/', '', $value);
        if (!preg_match('/\d/', $cleaned)) {
            return false;
        }

        $hasComma = strpos($cleaned, ',') !== false;
        $hasDot = strpos($cleaned, '.') !== false;

        // Deteksi pola ribuan-koma & desimal-titik: 30,000.00
        if ($hasComma && $hasDot && preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $cleaned)) {
            $cleaned = str_replace(',', '', $cleaned); // buang separator ribuan
            // titik sudah desimal
        }
        // Deteksi pola ribuan-titik & desimal-koma: 30.000,00
        elseif ($hasComma && $hasDot && preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $cleaned)) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        }
        // Hanya koma: cek apakah thousand separator (diikuti 3 digit) atau decimal separator
        elseif ($hasComma && !$hasDot) {
            // Cek pola: koma diikuti tepat 3 digit = thousand separator (30,000)
            if (preg_match('/^\d{1,3}(,\d{3})+$/', $cleaned)) {
                $cleaned = str_replace(',', '', $cleaned); // thousand separator
            } else {
                // Koma sebagai decimal separator (1234,56)
                $cleaned = str_replace(',', '.', $cleaned);
            }
        }
        // Hanya titik: bisa ribuan atau desimal. Jika lebih dari 1 titik, buang semua (17.000)
        elseif ($hasDot && !$hasComma) {
            $parts = explode('.', $cleaned);
            if (count($parts) > 2) {
                $cleaned = str_replace('.', '', $cleaned);
            }
            // jika satu titik, biarkan sebagai decimal separator
        }

        $result = (float) $cleaned;
        return $result < 0 ? false : $result;
    }

    private function convertToInt($value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        $value = trim(strval($value));
        if ($value === '' || $value === '-' || $value === null) {
            return 0;
        }

        // Hapus karakter non digit
        $cleaned = preg_replace('/[^\d]/', '', $value);
        return $cleaned === '' ? 0 : (int) $cleaned;
    }

    private function convertToBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = trim(strtolower(strval($value)));
        
        // Handle berbagai format: "aktif", "1", "true", "yes", "ya"
        if (in_array($value, ['aktif', '1', 'true', 'yes', 'ya', 'y'])) {
            return true;
        }

        return false;
    }

    private function getOrCreateCategory(?string $categoryName): ?int
    {
        if (empty($categoryName)) {
            return null;
        }

        $category = Category::where('name', $categoryName)->first();
        if ($category) {
            return $category->id;
        }

        // Buat kategori baru
        $slug = Category::generateUniqueSlug($categoryName);
        $category = Category::create([
            'name' => $categoryName,
            'slug' => $slug,
            'description' => '',
            'is_active' => true,
        ]);

        return $category->id;
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    private function findProduct(array $row): ?Product
    {
        $sku = $row['sku'] ?? null;
        $name = $row['name'] ?? null;

        if ($sku) {
            $product = Product::where('sku', $sku)->first();
            if ($product) {
                return $product;
            }
        }

        if ($name) {
            return Product::where('name', $name)->first();
        }

        return null;
    }

    private function addFailure(int $row, string $attribute, array $errors, array $values): void
    {
        $failure = new Failure($row, $attribute, $errors, $values);
        $this->onFailure($failure);
    }

    private function normalizeRow(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[$this->normalizeKey($key)] = $value;
        }
        return $normalized;
    }

    private function normalizeKey(string $key): string
    {
        $k = strtolower($key);
        $k = str_replace(['(', ')', '.', ','], ' ', $k);
        $k = preg_replace('/\s+/', ' ', $k);
        $k = trim($k);

        $mapping = [
            'id' => 'id',
            'sku' => 'sku',
            'barcode' => 'barcode',
            'nama produk' => 'name',
            'nama_produk' => 'name',
            'nama' => 'name',
            'kategori' => 'category_name',
            'kategori produk' => 'category_name',
            'kategori_produk' => 'category_name',
            'category' => 'category_name',
            'category name' => 'category_name',
            'harga modal rp' => 'cost_price',
            'harga_modal_rp' => 'cost_price',
            'harga modal' => 'cost_price',
            'cost price' => 'cost_price',
            'harga jual rp' => 'price',
            'harga_jual_rp' => 'price',
            'harga jual' => 'price',
            'price' => 'price',
            'stok' => 'stock_qty',
            'stock' => 'stock_qty',
            'stock qty' => 'stock_qty',
            'status' => 'is_active',
        ];

        return $mapping[$k] ?? $k;
    }
}