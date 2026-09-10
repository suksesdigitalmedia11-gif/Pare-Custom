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
    private $updatedCount = 0;
    private $insertedCount = 0;
    private $unchangedCount = 0;
    private $updatedProducts = [];
    private $insertedProducts = [];

    public function model(array $row)
    {
        $row = $this->normalizeRow($row);
        $this->processedRows++;
        $rowNumber = $this->processedRows + 1; // +1 karena heading row

        // Validasi minimal: nama produk wajib ada
        if (empty($row['name'])) {
            $this->addFailure($rowNumber, 'name', ['Nama produk wajib diisi'], $row);
            return null;
        }

        $costPrice = $this->convertToFloat($row['cost_price'] ?? null);
        $price = $this->convertToFloat($row['price'] ?? null);

        $rawSku = $row['sku'] ?? null;
        if ($rawSku !== null) {
            $rawSku = trim(strval($rawSku));
            if ($rawSku === '-' || $rawSku === '') {
                $rawSku = null;
            }
        }

        $product = $this->findProduct($row);

        if ($product) {
            // === PRODUK EXISTING ===
            $oldCostPrice = (float) ($product->cost_price ?? 0);
            $oldPrice = (float) ($product->price ?? 0);

            $costChanged = false;
            $priceChanged = false;
            $skuChanged = false;

            // Update/koreksi SKU jika ada SKU di Excel dan berbeda dari SKU saat ini
            $oldSku = $product->sku;
            if ($rawSku && $oldSku !== $rawSku) {
                $skuInUse = Product::where('sku', $rawSku)->where('id', '!=', $product->id)->exists();
                if ($skuInUse) {
                    $this->addFailure($rowNumber, 'sku', ["SKU '{$rawSku}' sudah digunakan oleh produk lain."], $row);
                    return null;
                }
                $product->sku = $rawSku;
                $skuChanged = true;
            }

            // Jika KEDUANYA (harga modal baru dan harga jual baru) kosong
            if ($costPrice === false && $price === false) {
                if ($skuChanged) {
                    $product->save();
                    $this->rowCount++;
                    $this->updatedCount++;
                    $this->updatedProducts[] = [
                        'name' => $product->name,
                        'sku' => $product->sku ?? '-',
                        'change' => $oldSku ? "SKU diperbarui: {$oldSku} -> {$rawSku}" : "SKU diperbarui: {$rawSku}"
                    ];
                } else {
                    $this->unchangedCount++;
                }
                return null;
            }

            // Tentukan target harga setelah update
            $targetCostPrice = ($costPrice !== false) ? $costPrice : $oldCostPrice;
            $targetPrice = ($price !== false) ? $price : $oldPrice;

            // Validasi: harga jual tidak boleh lebih kecil dari harga modal
            if ($targetPrice < $targetCostPrice) {
                $costFormatted = 'Rp ' . number_format($targetCostPrice, 0, ',', '.');
                $priceFormatted = 'Rp ' . number_format($targetPrice, 0, ',', '.');
                $this->addFailure(
                    $rowNumber,
                    'price',
                    ["Harga jual ({$priceFormatted}) tidak boleh lebih kecil dari harga modal ({$costFormatted}) untuk produk '{$product->name}'."],
                    $row
                );
                return null;
            }

            if ($costPrice !== false && abs($oldCostPrice - $costPrice) > 0.001) {
                $product->cost_price = $costPrice;
                $costChanged = true;
            }

            if ($price !== false && abs($oldPrice - $price) > 0.001) {
                $product->price = $price;
                $priceChanged = true;
            }

            if ($costChanged || $priceChanged || $skuChanged) {
                $product->save();
                $this->rowCount++;
                $this->updatedCount++;

                // Log audit trail perubahan harga modal
                if ($costChanged) {
                    \App\Models\ProductPriceLog::create([
                        'product_id' => $product->id,
                        'old_cost_price' => $oldCostPrice,
                        'new_cost_price' => $costPrice,
                        'changed_by' => auth()->check() ? auth()->id() : null,
                        'changed_at' => now(),
                        'source' => 'import',
                    ]);
                }

                $descChanges = [];
                if ($skuChanged) $descChanges[] = $oldSku ? "SKU: {$oldSku} -> {$rawSku}" : "SKU: {$rawSku}";
                if ($costChanged) $descChanges[] = "Modal: Rp " . number_format($oldCostPrice, 0, ',', '.') . " -> Rp " . number_format($costPrice, 0, ',', '.');
                if ($priceChanged) $descChanges[] = "Jual: Rp " . number_format($oldPrice, 0, ',', '.') . " -> Rp " . number_format($price, 0, ',', '.');

                $this->updatedProducts[] = [
                    'name' => $product->name,
                    'sku' => $product->sku ?? '-',
                    'change' => implode(', ', $descChanges)
                ];
            } else {
                $this->unchangedCount++;
            }

            return null;

        } else {
            // === PRODUK BARU ===
            if ($costPrice === false) {
                $this->addFailure($rowNumber, 'cost_price', ["Harga modal wajib diisi untuk produk baru '{$row['name']}'"], $row);
                return null;
            }

            if ($price === false) {
                $this->addFailure($rowNumber, 'price', ["Harga jual wajib diisi untuk produk baru '{$row['name']}'"], $row);
                return null;
            }

            if ($price < $costPrice) {
                $costFormatted = 'Rp ' . number_format($costPrice, 0, ',', '.');
                $priceFormatted = 'Rp ' . number_format($price, 0, ',', '.');
                $this->addFailure($rowNumber, 'price', ["Harga jual ({$priceFormatted}) tidak boleh lebih kecil dari harga modal ({$costFormatted})"], $row);
                return null;
            }

            $categoryId = $this->getOrCreateCategory($row['category_name'] ?? null);
            $stockQty = $this->convertToInt($row['stock_qty'] ?? 0);
            $isActive = $this->convertToBoolean($row['is_active'] ?? 'Aktif');

            $product = Product::create([
                'sku' => $rawSku,
                'barcode' => $row['barcode'] ?? null,
                'name' => trim($row['name']),
                'category_id' => $categoryId,
                'cost_price' => $costPrice,
                'price' => $price,
                'stock_qty' => $stockQty,
                'is_active' => $isActive,
            ]);

            // Log harga modal awal
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

            $this->rowCount++;
            $this->insertedCount++;
            $this->insertedProducts[] = [
                'name' => $product->name,
                'sku' => $rawSku ?? '-',
                'cost' => $costPrice,
                'price' => $price
            ];

            return $product;
        }
    }

    /**
     * Konversi berbagai format angka/mata uang Indonesia dan standar ke float
     */
    public function convertToFloat($value)
    {
        if ($value === null || $value === '' || $value === '-') {
            return false;
        }

        if (is_int($value) || is_float($value)) {
            return $value < 0 ? false : (float) $value;
        }

        $value = trim(strval($value));
        if ($value === '' || $value === '-') {
            return false;
        }

        // Hapus karakter non digit, koma, titik
        $cleaned = preg_replace('/[^\d,\.]/', '', $value);
        if (!preg_match('/\d/', $cleaned)) {
            return false;
        }

        $hasComma = strpos($cleaned, ',') !== false;
        $hasDot = strpos($cleaned, '.') !== false;

        // Deteksi pola ribuan-koma & desimal-titik: 30,000.00
        if ($hasComma && $hasDot && preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $cleaned)) {
            $cleaned = str_replace(',', '', $cleaned);
        }
        // Deteksi pola ribuan-titik & desimal-koma: 30.000,00
        elseif ($hasComma && $hasDot && preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $cleaned)) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        }
        // Hanya koma
        elseif ($hasComma && !$hasDot) {
            // Jika koma diikuti kelipatan 3 digit (misal: 30,000 atau 1,500,000)
            if (preg_match('/^\d{1,3}(,\d{3})+$/', $cleaned)) {
                $cleaned = str_replace(',', '', $cleaned);
            } else {
                // Koma desimal: 1234,50
                $cleaned = str_replace(',', '.', $cleaned);
            }
        }
        // Hanya titik
        elseif ($hasDot && !$hasComma) {
            // Format ribuan Indonesia: 50.000 atau 150.000 atau 1.500.000 (titik diikuti 3 digit)
            if (preg_match('/^\d{1,3}(\.\d{3})+$/', $cleaned)) {
                $cleaned = str_replace('.', '', $cleaned);
            } else {
                $parts = explode('.', $cleaned);
                if (count($parts) > 2) {
                    $cleaned = str_replace('.', '', $cleaned);
                }
                // Jika satu titik bukan kelipatan 3 digit (misal 12.5), biarkan desimal
            }
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

        $cleaned = preg_replace('/[^\d]/', '', $value);
        return $cleaned === '' ? 0 : (int) $cleaned;
    }

    private function convertToBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = trim(strtolower(strval($value)));
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

        $slug = Category::generateUniqueSlug($categoryName);
        $category = Category::create([
            'name' => $categoryName,
            'slug' => $slug,
            'description' => '',
            'is_active' => true,
        ]);

        return $category->id;
    }

    public function getRowCount(): int { return $this->rowCount; }
    public function getProcessedRows(): int { return $this->processedRows; }
    public function getUpdatedCount(): int { return $this->updatedCount; }
    public function getInsertedCount(): int { return $this->insertedCount; }
    public function getUnchangedCount(): int { return $this->unchangedCount; }
    public function getUpdatedProducts(): array { return $this->updatedProducts; }
    public function getInsertedProducts(): array { return $this->insertedProducts; }

    public function findProduct(array $row): ?Product
    {
        $sku = $row['sku'] ?? null;
        if ($sku !== null) {
            $sku = trim(strval($sku));
            if ($sku === '-' || $sku === '') {
                $sku = null;
            }
        }

        $name = $row['name'] ?? null;
        if ($name !== null) {
            $name = trim(strval($name));
        }

        // Cari berdasarkan SKU terlebih dahulu (hanya jika SKU bukan kosong / '-')
        if ($sku) {
            $product = Product::where('sku', $sku)->first();
            if ($product) {
                return $product;
            }
        }

        // Cari berdasarkan Nama Produk
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
     * Returns array of preview data and full-file summary statistics.
     */
    public static function getPreview(string $filePath): array
    {
        $import = new static();
        $rows = \Maatwebsite\Excel\Facades\Excel::toCollection($import, $filePath);
        
        $previewRows = [];
        $rowNumber = 2; // Row 1 = header
        $totalInserts = 0;
        $totalUpdates = 0;
        $totalUnchanged = 0;
        $totalErrors = 0;
        
        $sheetRows = $rows->first() ?? collect();

        foreach ($sheetRows as $row) {
            if ($row->filter()->isEmpty()) {
                continue; // Skip empty rows
            }
            
            $rowData = [];
            foreach ($row as $key => $value) {
                $normalizedKey = $import->normalizeKey(strval($key));
                $rowData[$normalizedKey] = $value;
            }
            
            // Skip baris tanpa nama produk
            if (empty($rowData['name'])) {
                continue;
            }
            
            $rawSku = $rowData['sku'] ?? null;
            if ($rawSku !== null) {
                $rawSku = trim(strval($rawSku));
                if ($rawSku === '-' || $rawSku === '') {
                    $rawSku = null;
                }
            }

            $name = trim(strval($rowData['name']));
            $newCostPrice = $import->convertToFloat($rowData['cost_price'] ?? null);
            $newPrice = $import->convertToFloat($rowData['price'] ?? null);
            
            $product = $import->findProduct($rowData);
            
            $actionType = 'insert';
            $actionLabel = 'Produk Baru';
            $oldCostPrice = null;
            $oldPrice = null;
            $oldSku = null;
            $newSku = null;
            $productName = $name;
            $errorMessage = null;

            if ($product) {
                $actionType = 'update';
                $oldCostPrice = (float) ($product->cost_price ?? 0);
                $oldPrice = (float) ($product->price ?? 0);
                $oldSku = $product->sku;
                $productName = $product->name;
                
                $skuChanged = false;
                $newSku = null;
                $skuError = false;
                if (!empty($rawSku) && $oldSku !== $rawSku) {
                    $skuInUse = Product::where('sku', $rawSku)->where('id', '!=', $product->id)->exists();
                    if ($skuInUse) {
                        $actionType = 'error';
                        $errorMessage = "SKU '{$rawSku}' sudah digunakan oleh produk lain";
                        $actionLabel = 'Error: SKU Duplikat';
                        $totalErrors++;
                        $skuError = true;
                    } else {
                        $skuChanged = true;
                        $newSku = $rawSku;
                    }
                }

                if (!$skuError) {
                    $targetCost = ($newCostPrice !== false) ? $newCostPrice : $oldCostPrice;
                    $targetPrice = ($newPrice !== false) ? $newPrice : $oldPrice;

                    // Cek error jika jual < modal
                    if (($newCostPrice !== false || $newPrice !== false) && $targetPrice < $targetCost) {
                        $actionType = 'error';
                        $errorMessage = 'Harga jual (' . number_format($targetPrice, 0, ',', '.') . ') < modal (' . number_format($targetCost, 0, ',', '.') . ')';
                        $actionLabel = 'Error: Jual < Modal';
                        $totalErrors++;
                    } else {
                        $costChanged = $newCostPrice !== false && abs($oldCostPrice - $newCostPrice) > 0.001;
                        $priceChanged = $newPrice !== false && abs($oldPrice - $newPrice) > 0.001;
                        
                        $changes = [];
                        if ($skuChanged) $changes[] = $oldSku ? "SKU ({$oldSku}->{$rawSku})" : "SKU ({$rawSku})";
                        if ($costChanged) $changes[] = 'Modal';
                        if ($priceChanged) $changes[] = 'Jual';
                        
                        if (empty($changes)) {
                            $actionType = 'no_change';
                            $actionLabel = 'Tidak berubah';
                            $totalUnchanged++;
                        } else {
                            $actionLabel = 'Update: ' . implode(' + ', $changes);
                            $totalUpdates++;
                        }
                    }
                }
            } else {
                if ($newCostPrice === false || $newPrice === false) {
                    $actionType = 'error';
                    $errorMessage = 'Modal & jual wajib diisi untuk produk baru';
                    $actionLabel = 'Error: Belum Lengkap';
                    $totalErrors++;
                } elseif ($newPrice < $newCostPrice) {
                    $actionType = 'error';
                    $errorMessage = 'Harga jual < modal';
                    $actionLabel = 'Error: Jual < Modal';
                    $totalErrors++;
                } else {
                    $totalInserts++;
                }
            }
            
            // Simpan sample rows hingga 100 baris untuk preview tabel di UI
            if (count($previewRows) < 100) {
                $previewRows[] = [
                    'row' => $rowNumber,
                    'sku' => $rawSku ?? '-',
                    'product_name' => $productName,
                    'action' => $actionLabel,
                    'action_type' => $actionType,
                    'error_message' => $errorMessage,
                    'old_sku' => $oldSku,
                    'new_sku' => $newSku,
                    'old_cost_price' => $oldCostPrice,
                    'new_cost_price' => $newCostPrice === false ? null : $newCostPrice,
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice === false ? null : $newPrice,
                ];
            }
            
            $rowNumber++;
        }
        
        return [
            'rows' => $previewRows,
            'summary' => [
                'total_rows' => $rowNumber - 2,
                'total_inserts' => $totalInserts,
                'total_updates' => $totalUpdates,
                'total_unchanged' => $totalUnchanged,
                'total_errors' => $totalErrors,
                'total_changes' => $totalInserts + $totalUpdates,
            ]
        ];
    }

    private function normalizeRow(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[$this->normalizeKey($key)] = $value;
        }
        return $normalized;
    }

    public function normalizeKey(string $key): string
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

            // Harga Modal Baru (diambil untuk update cost_price)
            'harga modal baru' => 'cost_price',
            'harga_modal_baru' => 'cost_price',
            'harga modal baru rp' => 'cost_price',
            'harga modal baru idr' => 'cost_price',
            'modal baru' => 'cost_price',

            // Harga Modal Lama (hanya referensi)
            'harga modal lama' => 'old_cost_price_ref',
            'harga_modal_lama' => 'old_cost_price_ref',
            'harga modal lama rp' => 'old_cost_price_ref',
            'modal lama' => 'old_cost_price_ref',

            // Harga Modal Standar / Template Lama
            'harga modal rp' => 'cost_price',
            'harga_modal_rp' => 'cost_price',
            'harga modal' => 'cost_price',
            'cost price' => 'cost_price',
            'cost_price' => 'cost_price',

            // Harga Jual Baru (diambil untuk update price)
            'harga jual baru' => 'price',
            'harga_jual_baru' => 'price',
            'harga jual baru rp' => 'price',
            'harga jual baru idr' => 'price',
            'jual baru' => 'price',

            // Harga Jual Lama (hanya referensi)
            'harga jual lama' => 'old_price_ref',
            'harga_jual_lama' => 'old_price_ref',
            'harga jual lama rp' => 'old_price_ref',
            'jual lama' => 'old_price_ref',

            // Harga Jual Standar / Template Lama
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