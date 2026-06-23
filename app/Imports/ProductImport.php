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

        // cost_price kosong: cek apakah produk existing
        if ($costPrice === false) {
            $product = $this->findProduct($row);
            if ($product) {
                // Isi SKU untuk produk yang belum punya SKU (meskipun harga tidak berubah)
                if (empty($product->sku) && !empty($row['sku'])) {
                    $product->sku = $row['sku'];
                    $product->save();
                    $this->rowCount++;
                }
                return null;
            }
            $this->addFailure($rowNumber, 'cost_price', ['Harga modal wajib diisi untuk produk baru'], $row);
            return null;
        }

        // price kosong: jika produk existing, update hanya cost_price; jika baru, error
        if ($price === false) {
            $product = $this->findProduct($row);
            if ($product) {
                // Update hanya cost_price, pertahankan harga jual yang ada
                $oldCostPrice = $product->cost_price;
                $product->cost_price = $costPrice;

                // Isi SKU untuk produk yang belum punya SKU
                if (empty($product->sku) && !empty($row['sku'])) {
                    $product->sku = $row['sku'];
                }

                $product->save();

                if (abs($oldCostPrice - $costPrice) > 0.001) {
                    \App\Models\ProductPriceLog::create([
                        'product_id' => $product->id,
                        'old_cost_price' => $oldCostPrice,
                        'new_cost_price' => $costPrice,
                        'changed_by' => auth()->check() ? auth()->id() : null,
                        'changed_at' => now(),
                        'source' => 'import',
                    ]);
                }

                $this->rowCount++;
                return null;
            }
            $this->addFailure($rowNumber, 'price', ['Harga jual wajib diisi untuk produk baru'], $row);
            return null;
        }

        if ($price < $costPrice) {
            $this->addFailure($rowNumber, 'price', ['Harga jual tidak boleh lebih kecil dari harga modal'], $row);
            return null;
        }

        $product = $this->findProduct($row);
        
        if ($product) {
            // Update harga modal, harga jual, dan SKU (jika kosong)
            $oldCostPrice = $product->cost_price;
            $oldPrice = $product->price;
            $product->cost_price = $costPrice;
            $product->price = $price;

            // Isi SKU untuk produk yang belum punya SKU
            if (empty($product->sku) && !empty($row['sku'])) {
                $product->sku = $row['sku'];
            }

            $product->save();

            // Log perubahan cost_price jika berbeda
            if (abs($oldCostPrice - $costPrice) > 0.001) {
                \App\Models\ProductPriceLog::create([
                    'product_id' => $product->id,
                    'old_cost_price' => $oldCostPrice,
                    'new_cost_price' => $costPrice,
                    'changed_by' => auth()->check() ? auth()->id() : null,
                    'changed_at' => now(),
                    'source' => 'import',
                ]);
            }
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

            // Log harga modal awal untuk produk baru
            if ($costPrice > 0) {
                \App\Models\ProductPriceLog::create([
                    'product_id' => $product->id,
                    'old_cost_price' => null,
                    'new_cost_price' => $costPrice,
                    'changed_by' => auth()->check() ? auth()->id() : null,
                    'changed_at' => now(),
                    'source' => 'import',
                ]);
            }
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

    /**
     * Preview rows from file without saving.
     * Returns array of preview data: what would change.
     */
    public static function getPreview(string $filePath): array
    {
        $import = new static();
        $rows = \Maatwebsite\Excel\Facades\Excel::toCollection($import, $filePath);
        
        $previewRows = [];
        $rowNumber = 2; // Start from row 2 (row 1 = header)
        
        foreach ($rows->first() as $row) {
            if ($row->filter()->isEmpty()) {
                continue; // Skip empty rows
            }
            
            $rowData = [];
            foreach ($row as $key => $value) {
                $normalizedKey = $import->normalizeKey(strval($key));
                $rowData[$normalizedKey] = $value;
            }
            
            // Skip rows with no name
            if (empty($rowData['name'])) {
                continue;
            }
            
            $sku = $rowData['sku'] ?? null;
            $name = $rowData['name'] ?? null;
            $newCostPrice = $import->convertToFloat($rowData['cost_price'] ?? null);
            $newPrice = $import->convertToFloat($rowData['price'] ?? null);
            
            // cost_price atau price kosong = hanya skip kalau KEDUANYA kosong
            // Kalau hanya cost_price yang kosong → produk existing = no_change
            if ($newCostPrice === false && $newPrice === false && empty($sku)) {
                continue; // Skip rows with no values at all
            }
            
            // Find existing product
            $product = $import->findProduct($rowData);
            
            $actionType = 'insert';
            $actionLabel = 'Produk Baru';
            $oldCostPrice = null;
            $oldPrice = null;
            $oldSku = null;
            $newSku = null;
            $productName = $name;
            
            if ($product) {
                $actionType = 'update';
                $oldCostPrice = $product->cost_price;
                $oldPrice = $product->price;
                $oldSku = $product->sku;
                $productName = $product->name;
                
                // Deteksi perubahan SKU: dari kosong → terisi
                $skuChanged = (empty($product->sku) || $product->sku === null) && !empty($sku);
                $newSku = $skuChanged ? $sku : null;
                
                // Only count as change if values differ AND new values are valid
                $costChanged = $newCostPrice !== false && abs($oldCostPrice - $newCostPrice) > 0.001;
                $priceChanged = $newPrice !== false && abs($oldPrice - $newPrice) > 0.001;
                
                // Build enriched action label
                $changes = [];
                if ($skuChanged) $changes[] = 'SKU';
                if ($costChanged || $priceChanged) $changes[] = 'Harga';
                
                if (empty($changes)) {
                    $actionType = 'no_change';
                    $actionLabel = 'Tidak berubah';
                } else {
                    $actionLabel = 'Update: ' . implode(' + ', $changes);
                }
            }
            
            // Skip new products without cost_price in preview
            if (!$product && $newCostPrice === false) {
                $rowNumber++;
                continue;
            }
            
            $previewRows[] = [
                'row' => $rowNumber,
                'sku' => $sku ?? '-',
                'product_name' => $productName,
                'action' => $actionLabel,
                'action_type' => $actionType,
                'old_sku' => $oldSku,
                'new_sku' => $newSku,
                'old_cost_price' => $oldCostPrice,
                'new_cost_price' => $newCostPrice === false ? null : $newCostPrice,
                'old_price' => $oldPrice,
                'new_price' => $newPrice === false ? null : $newPrice,
            ];
            
            $rowNumber++;
            
            if (count($previewRows) >= 20) {
                break;
            }
        }
        
        return $previewRows;
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
            // Specific: hanya "Harga Modal Baru" yang dipakai untuk cost_price
            'harga modal baru' => 'cost_price',
            'harga_modal_baru' => 'cost_price',
            // "Harga Modal Lama" diabaikan (hanya referensi)
            'harga modal lama' => 'old_cost_price_ref',
            'harga_modal_lama' => 'old_cost_price_ref',
            // Backward compat
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