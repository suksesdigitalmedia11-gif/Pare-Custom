<?php

namespace App\Imports;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            $this->errors[] = 'File kosong atau tidak ada baris data yang terbaca.';
            return;
        }

        // Kelompokkan berdasarkan PO_NUMBER (boleh kosong, nanti akan digenerate)
        $grouped = $rows->groupBy(function ($row, $index) {
            $poNumber = trim((string)($row['po_number'] ?? ''));
            if ($poNumber === '') {
                // Gunakan key unik sementara berdasarkan index
                return 'AUTO_' . ($index + 1);
            }
            return strtoupper($poNumber);
        });

        foreach ($grouped as $key => $group) {
            $firstRow = $group->first();
            $rowIndex = $group->keys()->first() + 2; // +2 karena heading row

            // Cari nilai ORDER_DATE & DEADLINE pertama yang tidak kosong di dalam 1 group
            $orderDateRaw = null;
            $deadlineRaw = null;
            $statusRaw = null;

            foreach ($group as $row) {
                if ($orderDateRaw === null && isset($row['order_date']) && $row['order_date'] !== '') {
                    $orderDateRaw = $row['order_date'];
                }
                if ($deadlineRaw === null && isset($row['deadline']) && $row['deadline'] !== '') {
                    $deadlineRaw = $row['deadline'];
                }
                if ($statusRaw === null && isset($row['status']) && $row['status'] !== '') {
                    $statusRaw = strtolower(trim((string) $row['status']));
                }
            }

            // Siapkan data header untuk validasi
            $headerData = $firstRow->toArray();
            $headerData['order_date'] = $orderDateRaw;
            $headerData['deadline'] = $deadlineRaw;
            $headerData['status'] = $statusRaw;

            // Validasi basic per-group (header PO)
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
                $this->errors[] = "PO di baris {$rowIndex}: " . $validator->errors()->first();
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
                    $this->errors[] = "PO {$key} - baris Excel " . ($index + 2) . ': ' . $lineValidator->errors()->first();
                    continue 2; // skip entire group
                }
            }

            try {
                DB::transaction(function () use ($group, $firstRow, $key, $rowIndex, $orderDateRaw, $deadlineRaw, $statusRaw) {
                    // Supplier
                    $supplierName = trim((string)($firstRow['supplier_name'] ?? ''));
                    $supplier = Supplier::firstOrCreate(
                        ['name' => $supplierName],
                        ['is_active' => true]
                    );

                    // Tentukan / generate PO number
                    $rawPoNumber = trim((string)($firstRow['po_number'] ?? ''));
                    if ($rawPoNumber !== '' && !str_starts_with(strtoupper($rawPoNumber), 'PO')) {
                        $poNumber = 'PO' . strtoupper($rawPoNumber);
                    } elseif ($rawPoNumber !== '') {
                        $poNumber = strtoupper($rawPoNumber);
                    } else {
                        // generate otomatis dengan helper dari controller admin
                        $poNumber = app(\App\Http\Controllers\Admin\PurchaseOrderController::class)->generatePoNumber();
                    }

                    // Cek duplikasi PO_NUMBER
                    if (PurchaseOrder::where('po_number', $poNumber)->exists()) {
                        $this->errors[] = "PO Number {$poNumber} sudah ada di sistem (baris {$rowIndex}).";
                        return;
                    }

                    // Hitung total
                    $subtotal = 0;
                    $discountTotal = 0;
                    foreach ($group as $row) {
                        $line = (float)($row['cost_price']) * (int)($row['qty']);
                        $disc = (float)($row['discount'] ?? 0);
                        $subtotal += $line;
                        $discountTotal += $disc;
                    }
                    $grandTotal = $subtotal - $discountTotal;

                    // Parse tanggal order & deadline dengan helper yang support berbagai format Excel
                    try {
                        $orderDate = $this->parseDate($orderDateRaw, false);
                    } catch (\Exception $e) {
                        $this->errors[] = "PO {$poNumber} (baris {$rowIndex}): ORDER_DATE tidak bisa diparsing. Gunakan format YYYY-MM-DD atau tanggal standar Excel.";
                        return;
                    }

                    $deadlineDate = null;
                    if ($deadlineRaw !== null && $deadlineRaw !== '') {
                        try {
                            $deadlineDate = $this->parseDate($deadlineRaw, false);
                        } catch (\Exception $e) {
                            $this->errors[] = "PO {$poNumber} (baris {$rowIndex}): DEADLINE tidak bisa diparsing. Gunakan format YYYY-MM-DD atau kosongkan.";
                            return;
                        }
                    }

                    // Tentukan status (opsional)
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
                    if ($statusRaw && in_array($statusRaw, $allowedStatuses, true)) {
                        $status = $statusRaw;
                    }

                    /** @var \App\Models\PurchaseOrder $purchase */
                    $purchase = PurchaseOrder::create([
                        'po_number' => $poNumber,
                        'order_date' => $orderDate,
                        'deadline' => $deadlineDate,
                        'supplier_id' => $supplier->id,
                        'purchase_type' => $firstRow['purchase_type'],
                        'subtotal' => $subtotal,
                        'discount_total' => $discountTotal,
                        'grand_total' => $grandTotal,
                        'status' => $status,
                        'is_paid' => false,
                        'created_by' => Auth::id(),
                    ]);

                    foreach ($group as $row) {
                        $lineTotal = ((float)$row['cost_price'] * (int)$row['qty']) - (float)($row['discount'] ?? 0);

                        PurchaseOrderItem::create([
                            'purchase_order_id' => $purchase->id,
                            'product_id' => null,
                            'product_name' => $row['product_name'],
                            'sku' => $row['sku'] ?? null,
                            'cost_price' => $row['cost_price'],
                            'qty' => $row['qty'],
                            'discount' => $row['discount'] ?? 0,
                            'line_total' => $lineTotal,
                        ]);
                    }

                    $this->successCount++;
                });
            } catch (\Exception $e) {
                $this->errors[] = "PO group {$key} (baris {$rowIndex}): " . $e->getMessage();
            }
        }
    }

    /**
     * Parse tanggal dari berbagai format Excel / string.
     * Diadaptasi dari SalesOrderImport::parseDate supaya konsisten.
     */
    private function parseDate($dateValue, bool $includeTime = true): \Carbon\Carbon
    {
        // Jika kosong, lempar exception supaya caller bisa handle
        if ($dateValue === null || $dateValue === '') {
            throw new \InvalidArgumentException('Tanggal kosong');
        }

        // Gunakan Carbon helper dari Laravel
        $carbonClass = class_exists(\Carbon\Carbon::class) ? \Carbon\Carbon::class : null;
        if ($carbonClass === null) {
            throw new \RuntimeException('Carbon tidak tersedia');
        }

        /** @var \Carbon\Carbon $carbon */
        $carbon = $carbonClass;

        // Numeric → Excel serial date
        if (is_numeric($dateValue)) {
            $excelDate = (float) $dateValue;

            if ($excelDate >= 60) {
                $excelDate = $excelDate - 2;
            } elseif ($excelDate >= 1) {
                $excelDate = $excelDate - 1;
            }

            $excelEpoch = $carbon::create(1899, 12, 30, 0, 0, 0);
            $parsedDate = $excelEpoch->copy()->addDays((int) $excelDate);

            $decimalPart = $excelDate - (int) $excelDate;
            if ($decimalPart > 0 && $includeTime) {
                $totalSeconds = (int) round($decimalPart * 86400);
                $hours = (int) floor($totalSeconds / 3600);
                $minutes = (int) floor(($totalSeconds % 3600) / 60);
                $seconds = $totalSeconds % 60;
                $parsedDate->setTime($hours, $minutes, $seconds);
            }

            if (!$includeTime) {
                $parsedDate->setTime(0, 0, 0);
            }

            return $parsedDate;
        }

        $dateString = trim((string) $dateValue);

        // Coba format Indonesia dd/mm/yyyy atau dd-mm-yyyy
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/i', $dateString, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = (int) $m[3];

            return $carbon::create($year, $month, $day, 0, 0, 0);
        }

        // Coba format ISO yyyy-mm-dd atau yyyy/mm/dd
        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/i', $dateString, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            $day = (int) $m[3];

            return $carbon::create($year, $month, $day, 0, 0, 0);
        }

        // Fallback: biarkan Carbon coba parse
        $parsed = $carbon::parse($dateString);
        if (!$includeTime) {
            $parsed->setTime(0, 0, 0);
        }

        return $parsed;
    }
}
