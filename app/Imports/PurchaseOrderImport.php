<?php

namespace App\Imports;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Product; // ✅ Tambah ini
use App\Models\StockMovement; // ✅ Tambah ini
use App\Models\PurchaseOrderLog; // ✅ Tambah ini
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Services\NumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PurchaseOrderImport implements ToCollection, WithHeadingRow
{
    /** @var array<int,string> */
    public $errors = [];

    /** @var int */
    public $successCount = 0;

    /** @var string */
    public $mode; // 'migration' or 'full'

    public function __construct(string $mode = 'migration')
    {
        $this->mode = $mode;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            $this->errors[] = 'File kosong atau tidak ada baris data yang terbaca.';
            return;
        }

        // Kelompokkan berdasarkan SUPPLIER + TANGGAL + TIPE
        // Agar baris-baris yang "seharusnya" satu nota bisa bergabung
        $grouped = $rows->groupBy(function ($row, $index) {
            $supplier = trim(strtolower((string) ($row['supplier_name'] ?? '')));
            $date = trim((string) ($row['order_date'] ?? ''));
            $type = trim(strtolower((string) ($row['purchase_type'] ?? '')));

            // Jika data kuncinya kosong, biarkan jadi grup sendiri (nanti akan gagal validasi di loop)
            if ($supplier === '' || $date === '') {
                return 'INVALID_ROW_' . $index;
            }

            // Key unik gabungan
            return "{$supplier}|{$date}|{$type}";
        });

        foreach ($grouped as $key => $group) {
            $firstRow = $group->first();
            // Estimasi baris excel (hanya indikasi untuk error message)
            $rowIndex = $group->keys()->first() + 2;

            // Cari nilai ORDER_DATE & DEADLINE pertama yang tidak kosong
            $orderDateRaw = $firstRow['order_date'];
            $deadlineRaw = $firstRow['deadline'] ?? null;
            $statusRaw = $firstRow['status'] ?? null;

            // Siapkan data header untuk validasi
            $headerData = $firstRow->toArray();

            // Validasi header
            $validator = Validator::make($headerData, [
                'order_date' => ['required'],
                'deadline' => ['nullable'],
                'supplier_name' => ['required', 'string', 'max:255'],
                'purchase_type' => ['required', 'in:kain,produk_jadi'],
                'status' => ['nullable', 'in:draft,pending,request_kain,payment,proses_jahit,printing,selesai,canceled'],
            ], [], [
                'order_date' => 'ORDER_DATE',
                'deadline' => 'DEADLINE',
                'supplier_name' => 'SUPPLIER_NAME',
                'purchase_type' => 'PURCHASE_TYPE',
                'status' => 'STATUS',
            ]);

            if ($validator->fails()) {
                $this->errors[] = "Error di baris {$rowIndex}: " . $validator->errors()->first();
                continue;
            }

            // Validasi item per baris
            foreach ($group as $index => $row) {
                $lineValidator = Validator::make($row->toArray(), [
                    'product_name' => ['required', 'string', 'max:255'],
                    'sku' => ['nullable', 'string', 'max:100'],
                    'cost_price' => ['required', 'numeric', 'min:0'],
                    'qty' => ['required', 'integer', 'min:1'],
                    'discount' => ['nullable', 'numeric', 'min:0'],
                ], [], [
                    'product_name' => 'PRODUCT_NAME',
                    'cost_price' => 'COST_PRICE',
                    'qty' => 'QTY',
                    'discount' => 'DISCOUNT',
                ]);

                if ($lineValidator->fails()) {
                    $this->errors[] = "Error baris Excel " . ($rowIndex + $index) . ': ' . $lineValidator->errors()->first();
                    continue 2; // skip entire group
                }
            }

            try {
                DB::transaction(function () use ($group, $firstRow, $key, $rowIndex, $orderDateRaw, $deadlineRaw, $statusRaw) {
                    // 1. Supplier (Find or Create)
                    $supplierName = trim((string) ($firstRow['supplier_name'] ?? ''));
                    $supplier = Supplier::firstOrCreate(
                        ['name' => $supplierName],
                        ['is_active' => true]
                    );

                    // 2. Generate PO Number Otomatis (SELALU BARU)
                    $poNumber = app(NumberGenerator::class)->generatePurchaseOrderNumber();

                    // 3. Hitung total
                    $subtotal = 0;
                    $discountTotal = 0;
                    foreach ($group as $row) {
                        $line = (float) ($row['cost_price']) * (int) ($row['qty']);
                        $disc = (float) ($row['discount'] ?? 0);
                        $subtotal += $line;
                        $discountTotal += $disc;
                    }
                    $grandTotal = $subtotal - $discountTotal;

                    // 4. Parse Dates
                    try {
                        $orderDate = $this->parseDate($orderDateRaw, false);
                    } catch (\Exception $e) {
                        $this->errors[] = "PO (baris {$rowIndex}): Tanggal order tidak valid.";
                        return;
                    }

                    $deadlineDate = null;
                    if (!empty($deadlineRaw)) {
                        try {
                            $deadlineDate = $this->parseDate($deadlineRaw, false);
                        } catch (\Exception $e) { /* ignore deadline error, make null */
                        }
                    }

                    // 5. Tentukan status
                    $allowedStatuses = [
                        PurchaseOrder::STATUS_DRAFT,
                        PurchaseOrder::STATUS_PENDING,
                        PurchaseOrder::STATUS_REQUEST_KAIN,
                        PurchaseOrder::STATUS_PAYMENT,
                        PurchaseOrder::STATUS_PROSES_JAHIT,
                        PurchaseOrder::STATUS_PRINTING,
                        PurchaseOrder::STATUS_SELESAI,
                        PurchaseOrder::STATUS_CANCELLED,
                        PurchaseOrder::STATUS_RETURNED,
                        PurchaseOrder::STATUS_PARTIALLY_RETURNED,
                    ];

                    $status = PurchaseOrder::STATUS_DRAFT;
                    if ($statusRaw && in_array(strtolower($statusRaw), $allowedStatuses, true)) {
                        $status = strtolower($statusRaw);
                    }

                    /** @var \App\Models\PurchaseOrder $purchase */
                    $purchase = PurchaseOrder::create([
                        'po_number' => $poNumber,
                        'order_date' => $orderDate,
                        'deadline' => $deadlineDate,
                        'supplier_id' => $supplier->id,
                        'purchase_type' => strtolower($firstRow['purchase_type']),
                        'subtotal' => $subtotal,
                        'discount_total' => $discountTotal,
                        'grand_total' => $grandTotal,
                        'status' => $status,
                        'is_paid' => false,
                        'created_by' => Auth::id(),
                    ]);

                    // 6. Create Items
                    foreach ($group as $row) {
                        $lineTotal = ((float) $row['cost_price'] * (int) $row['qty']) - (float) ($row['discount'] ?? 0);

                        // Cari Produk
                        $product = null;
                        if (!empty($row['sku'])) {
                            $product = Product::where('sku', trim($row['sku']))->first();
                        }
                        if (!$product) {
                            $product = Product::where('name', trim($row['product_name']))->first();
                        }

                        // Update Stok (Mode Full)
                        if ($this->mode === 'full' && $product) {
                            $oldQty = $product->stock_qty;
                            $qtyIn = (int) $row['qty'];

                            $product->increment('stock_qty', $qtyIn);

                            StockMovement::create([
                                'product_id' => $product->id,
                                'type' => 'IN_PURCHASE',
                                'ref_code' => $poNumber,
                                'initial_qty' => $oldQty,
                                'qty_in' => $qtyIn,
                                'qty_out' => 0,
                                'final_qty' => $oldQty + $qtyIn,
                                'user_id' => Auth::id(),
                                'notes' => "Import {$poNumber}",
                                'moved_at' => now(),
                            ]);
                        }

                        PurchaseOrderItem::create([
                            'purchase_order_id' => $purchase->id,
                            'product_id' => $product ? $product->id : null,
                            'product_name' => $row['product_name'],
                            'sku' => $row['sku'] ?? ($product ? $product->sku : null),
                            'cost_price' => $row['cost_price'],
                            'qty' => $row['qty'],
                            'discount' => $row['discount'] ?? 0,
                            'line_total' => $lineTotal,
                        ]);
                    }

                    $this->successCount++;
                });
            } catch (\Exception $e) {
                $this->errors[] = "Error processing PO at row {$rowIndex}: " . $e->getMessage();
            }
        }
    }
    /**
     * Parse tanggal dari berbagai format Excel / string.
     * Diadaptasi dari SalesOrderImport::parseDate supaya konsisten.
     */
    /**
     * Parse tanggal dari berbagai format Excel / string menggunakan library standar.
     */
    private function parseDate($dateValue, bool $includeTime = true): \Carbon\Carbon
    {
        if ($dateValue === null || $dateValue === '') {
            throw new \InvalidArgumentException('Tanggal kosong');
        }

        // 1. Jika Numeric (Excel Serial Date), gunakan library PhpSpreadsheet
        // Ini lebih akurat menangani bug tahun kabisat 1900 daripada hitung manual
        if (is_numeric($dateValue)) {
            try {
                $dateTimeObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue);
                // Convert ke Carbon
                $carbon = \Carbon\Carbon::instance($dateTimeObj);

                if (!$includeTime) {
                    $carbon->startOfDay();
                }
                return $carbon;
            } catch (\Exception $e) {
                // Fallback jika gagal convert
            }
        }

        $dateString = trim((string) $dateValue);

        // 2. Coba format Indonesia dd/mm/yyyy atau dd-mm-yyyy
        // Format: Tgl-Bln-Thn (Separators: / - .)
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/i', $dateString, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = (int) $m[3];
            return \Carbon\Carbon::create($year, $month, $day, 0, 0, 0);
        }

        // 3. Coba format ISO yyyy-mm-dd (Separators: / - .)
        if (preg_match('/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})/i', $dateString, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            $day = (int) $m[3];
            return \Carbon\Carbon::create($year, $month, $day, 0, 0, 0);
        }

        // 4. Fallback terakhir: biarkan Carbon coba menebak
        try {
            $parsed = \Carbon\Carbon::parse($dateString);
            if (!$includeTime) {
                $parsed->startOfDay();
            }
            return $parsed;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Format tanggal tidak dikenali: $dateString");
        }
    }
}
