<?php

namespace App\Imports;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Shift;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesOrderImport implements ToCollection, WithHeadingRow
{
    public $errors = [];
    public $successCount = 0;
    public $importType = 'current'; // 'current' or 'historical'

    public function __construct($importType = 'current')
    {
        $this->importType = $importType;
    }

    public function collection(Collection $rows)
    {
        // ✅ UNTUK DATA HISTORICAL, SKIP SHIFT CHECK
        if ($this->importType === 'current') {
            $activeShift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
            
            if (!$activeShift) {
                $this->errors[] = 'Tidak ada shift aktif. Silakan mulai shift terlebih dahulu.';
                return;
            }
        }

        // ✅ GROUP ROWS BY SO_NUMBER UNTUK HANDLE MULTIPLE ITEMS & MULTIPLE PAYMENTS PER SO
        $soGroups = [];
        foreach ($rows as $index => $row) {
            // Skip row kosong
            if (empty($row['so_number']) && empty($row['product_name']) && empty($row['cash_amount_total']) && empty($row['transfer_amount_total'])) {
                continue;
            }

            $soNumber = $this->normalizeSoNumber($row['so_number'] ?? null);
            if (!$soNumber) {
                // Skip jika tidak ada SO number dan tidak ada product/payment info
                if (empty($row['product_name']) && empty($row['cash_amount_total']) && empty($row['transfer_amount_total'])) {
                    continue;
                }
                $soNumber = 'IMPORT_' . date('Ymd') . '_' . ($index + 1);
            }

            if (!isset($soGroups[$soNumber])) {
                $soGroups[$soNumber] = [
                    'so_number' => $soNumber,
                    'item_rows' => [],
                    'payment_rows' => [],
                    'index' => $index + 2
                ];
            }
            
            // ✅ PILAH ROW: ITEM atau PAYMENT
            if (!empty($row['product_name'])) {
                // Ini adalah item row
                $soGroups[$soNumber]['item_rows'][] = [
                    'data' => $row,
                    'index' => $index + 2
                ];
            } else {
                // Cek apakah ada payment info
                $cashAmount = (float) ($row['cash_amount_total'] ?? 0);
                $transferAmount = (float) ($row['transfer_amount_total'] ?? 0);
                if ($cashAmount > 0 || $transferAmount > 0) {
                    // Ini adalah payment row (baris khusus untuk payment tambahan)
                    $soGroups[$soNumber]['payment_rows'][] = [
                        'data' => $row,
                        'index' => $index + 2
                    ];
                } else {
                    // Jika tidak ada product_name dan tidak ada payment info, anggap sebagai item row kosong
                    // (mungkin payment info ada di row item sebelumnya)
                    if (!empty($soGroups[$soNumber]['item_rows'])) {
                        // Copy payment info dari item row terakhir jika ada
                        $lastItemRow = end($soGroups[$soNumber]['item_rows']);
                        if (!empty($lastItemRow['data']['cash_amount_total']) || !empty($lastItemRow['data']['transfer_amount_total'])) {
                            // Skip, payment sudah ada di item row
                            continue;
                        }
                    }
                    // Jika tidak ada item rows sebelumnya, ini mungkin row kosong
                    continue;
                }
            }
        }

        // ✅ PROCESS SETIAP GROUP SO
        foreach ($soGroups as $soGroup) {
            try {
                DB::transaction(function () use ($soGroup) {
                    $soNumber = $soGroup['so_number'];
                    
                    // ✅ VALIDASI: HARUS ADA MINIMAL 1 ITEM ROW
                    if (empty($soGroup['item_rows'])) {
                        $this->errors[] = "SO Number {$soNumber} (Baris {$soGroup['index']}): Harus ada minimal 1 item (PRODUCT_NAME)";
                        return;
                    }
                    
                    $firstRow = $soGroup['item_rows'][0]['data'];

                    // ✅ CHECK IF SO ALREADY EXISTS
                    $existingSO = SalesOrder::where('so_number', $soNumber)->first();
                    if ($existingSO) {
                        $this->errors[] = "SO Number {$soNumber} sudah ada di sistem (Baris {$soGroup['index']})";
                        return;
                    }

                    // ✅ VALIDASI & PREPARE DATA UNTUK 1 SO
                    $validationResult = $this->validateSoData($firstRow, $soGroup['index']);
                    if (!$validationResult['valid']) {
                        $this->errors[] = $validationResult['error'];
                        return;
                    }

                    // ✅ CREATE/FIND CUSTOMER
                    $customer = $this->getOrCreateCustomer($firstRow);

                    // ✅ CALCULATE TOTALS DARI SEMUA ITEMS
                    $totals = $this->calculateSoTotals($soGroup['item_rows']);

                    // ✅ CREATE SALES ORDER
                    $salesOrder = SalesOrder::create([
                        'so_number' => $soNumber,
                        'order_type' => $firstRow['order_type'] ?? 'beli_jadi',
                        'order_date' => $this->parseDate($firstRow['order_date'])->startOfDay(),
                        'deadline' => !empty($firstRow['deadline']) ? $this->parseDate($firstRow['deadline'])->startOfDay() : null,
                        'customer_id' => $customer->id,
                        'subtotal' => $totals['subtotal'],
                        'discount_total' => $totals['discount_total'],
                        'shipping_cost' => $totals['shipping_cost'],
                        'grand_total' => $totals['grand_total'],
                        'status' => $this->importType === 'historical' ? 'selesai' : 'pending',
                        'payment_method' => $firstRow['payment_method'] ?? 'cash',
                        'payment_status' => $firstRow['payment_status'] ?? 'dp',
                        'created_by' => Auth::id(),
                        'approved_by' => $this->importType === 'historical' ? Auth::id() : null,
                        'approved_at' => $this->importType === 'historical' ? now() : null,
                        'completed_at' => $this->importType === 'historical' ? now() : null,
                    ]);

                    // ✅ CREATE SALES ORDER ITEMS (HANYA DARI ITEM ROWS)
                    foreach ($soGroup['item_rows'] as $itemData) {
                        $row = $itemData['data'];
                        
                        SalesOrderItem::create([
                            'sales_order_id' => $salesOrder->id,
                            'product_id' => null, // Manual product untuk import
                            'product_name' => $row['product_name'],
                            'sku' => $row['sku'] ?? null,
                            'sale_price' => (float) $row['sale_price'],
                            'qty' => (int) $row['qty'],
                            'discount' => (float) ($row['discount'] ?? 0),
                            'line_total' => ((float) $row['sale_price'] * (int) $row['qty']) - (float) ($row['discount'] ?? 0),
                        ]);
                    }

                    // ✅ CREATE PAYMENT(S) - BISA MULTIPLE PAYMENTS!
                    $allPaymentRows = [];
                    $seenPaymentKeys = [];
                    
                    // 1. Payment dari item rows (jika ada payment info di row item)
                    // Hanya ambil dari row pertama yang ada payment info untuk menghindari duplikasi
                    $firstPaymentFromItems = null;
                    foreach ($soGroup['item_rows'] as $itemData) {
                        $row = $itemData['data'];
                        $cashAmount = (float) ($row['cash_amount_total'] ?? 0);
                        $transferAmount = (float) ($row['transfer_amount_total'] ?? 0);
                        $paymentAmount = $cashAmount + $transferAmount;
                        
                        if ($paymentAmount > 0) {
                            if ($firstPaymentFromItems === null) {
                                // Ambil payment info dari row pertama yang ada
                                $firstPaymentFromItems = $row;
                                $paymentKey = ($row['paid_at'] ?? $row['order_date']) . '_' . $paymentAmount . '_' . ($row['payment_method'] ?? 'cash');
                                if (!isset($seenPaymentKeys[$paymentKey])) {
                                    $seenPaymentKeys[$paymentKey] = true;
                                    $allPaymentRows[] = $row;
                                }
                            }
                        }
                    }
                    
                    // 2. Payment dari payment rows (baris khusus payment tambahan)
                    foreach ($soGroup['payment_rows'] as $paymentData) {
                        $row = $paymentData['data'];
                        $cashAmount = (float) ($row['cash_amount_total'] ?? 0);
                        $transferAmount = (float) ($row['transfer_amount_total'] ?? 0);
                        $paymentAmount = $cashAmount + $transferAmount;
                        
                        if ($paymentAmount > 0) {
                            // Buat key unik untuk payment
                            $paymentKey = ($row['paid_at'] ?? $row['order_date'] ?? now()->format('Y-m-d H:i:s')) . '_' . $paymentAmount . '_' . ($row['payment_method'] ?? 'cash');
                            if (!isset($seenPaymentKeys[$paymentKey])) {
                                $seenPaymentKeys[$paymentKey] = true;
                                $allPaymentRows[] = $row;
                            }
                        }
                    }
                    
                    // ✅ CREATE SEMUA PAYMENT RECORDS (SUPPORT MULTIPLE PAYMENTS)
                    $totalPaidAmount = 0;
                    
                    // Sort payment rows by paid_at untuk memastikan urutan kronologis
                    usort($allPaymentRows, function($a, $b) {
                        $dateA = $this->parseDate($a['paid_at'] ?? $a['order_date'] ?? now(), true)->timestamp;
                        $dateB = $this->parseDate($b['paid_at'] ?? $b['order_date'] ?? now(), true)->timestamp;
                        return $dateA <=> $dateB;
                    });
                    
                    foreach ($allPaymentRows as $paymentRow) {
                        try {
                            $this->createPayment($salesOrder, $paymentRow, $totalPaidAmount);
                            // $totalPaidAmount sudah diupdate di dalam createPayment via reference
                        } catch (\Exception $e) {
                            $this->errors[] = "SO {$soNumber}: Error membuat payment - " . $e->getMessage();
                        }
                    }
                    
                    // ✅ UPDATE PAYMENT STATUS BERDASARKAN TOTAL YANG SUDAH DIBAYAR
                    if ($totalPaidAmount > 0) {
                        $finalPaymentStatus = ($totalPaidAmount >= $salesOrder->grand_total) ? 'lunas' : 'dp';
                        $salesOrder->update(['payment_status' => $finalPaymentStatus]);
                    }

                    $this->successCount++;
                    
                    // ✅ CREATE LOG
                    \App\Models\SalesOrderLog::create([
                        'sales_order_id' => $salesOrder->id,
                        'user_id' => Auth::id(),
                        'action' => 'created',
                        'description' => "Sales order diimport dari Excel: {$soNumber} dengan " . count($allPaymentRows) . " pembayaran",
                        'created_at' => now(),
                    ]);
                });

            } catch (\Exception $e) {
                $this->errors[] = "SO {$soGroup['so_number']} (Baris {$soGroup['index']}): " . $e->getMessage();
            }
        }
    }

    // ✅ HELPER METHODS
    private function normalizeSoNumber($soNumber)
    {
        if (empty($soNumber)) {
            return $this->generateSoNumber();
        }
        
        $soNumber = strtoupper(trim($soNumber));
        
        // Jika SO number tidak ada prefix, tambahkan
        if (!preg_match('/^[A-Za-z]/', $soNumber)) {
            $soNumber = 'SAL' . $soNumber;
        }
        
        return $soNumber;
    }

    private function validateSoData($row, $rowIndex)
    {
        $requiredFields = ['order_date', 'customer_name', 'product_name', 'sale_price', 'qty'];
        
        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                return [
                    'valid' => false,
                    'error' => "Baris {$rowIndex}: Field {$field} harus diisi"
                ];
            }
        }

        // Validasi order_type
        if (!empty($row['order_type']) && !in_array($row['order_type'], ['jahit_sendiri', 'beli_jadi'])) {
            return [
                'valid' => false,
                'error' => "Baris {$rowIndex}: ORDER_TYPE harus 'jahit_sendiri' atau 'beli_jadi'"
            ];
        }

        // Validasi numeric fields
        if (!is_numeric($row['sale_price']) || $row['sale_price'] <= 0) {
            return [
                'valid' => false,
                'error' => "Baris {$rowIndex}: SALE_PRICE harus angka positif"
            ];
        }

        if (!is_numeric($row['qty']) || $row['qty'] <= 0) {
            return [
                'valid' => false,
                'error' => "Baris {$rowIndex}: QTY harus angka positif"
            ];
        }
        if (!empty($row['payment_method'])) {
            $cashAmount = (float) ($row['cash_amount_total'] ?? 0);
            $transferAmount = (float) ($row['transfer_amount_total'] ?? 0);
            $paymentAmount = $cashAmount + $transferAmount;
            
            if ($row['payment_method'] === 'split' && $paymentAmount <= 0) {
                return [
                    'valid' => false,
                    'error' => "Baris {$rowIndex}: Split payment harus ada cash_amount_total atau transfer_amount_total"
                ];
            }
            
            if ($row['payment_method'] === 'cash' && $cashAmount <= 0) {
                return [
                    'valid' => false, 
                    'error' => "Baris {$rowIndex}: Cash payment harus ada cash_amount_total"
                ];
            }
            
            if ($row['payment_method'] === 'transfer' && $transferAmount <= 0) {
                return [
                    'valid' => false,
                    'error' => "Baris {$rowIndex}: Transfer payment harus ada transfer_amount_total"
                ];
            }
        }
        
        return ['valid' => true];
    }
    private function getOrCreateCustomer($row)
    {
        $customerName = $row['customer_name'];
        $customerPhone = $row['customer_phone'] ?? null;

        $customer = Customer::where('name', $customerName)->first();
        
        if (!$customer) {
            $customer = Customer::create([
                'name' => $customerName,
                'phone' => $customerPhone,
                'email' => null,
                'address' => null,
                'notes' => 'Auto-created from import',
                'is_active' => true
            ]);
        }

        return $customer;
    }

    private function calculateSoTotals($itemRows)
    {
        $subtotal = 0;
        $discount_total = 0;
        $shipping_cost = 0;

        foreach ($itemRows as $itemData) {
            $row = $itemData['data'];
            $salePrice = (float) ($row['sale_price'] ?? 0);
            $qty = (int) ($row['qty'] ?? 0);
            $discount = (float) ($row['discount'] ?? 0);
            
            $subtotal += ($salePrice * $qty);
            $discount_total += $discount;
        }

        // Ambil shipping cost dari row pertama (jika ada)
        if (!empty($itemRows)) {
            $firstRow = $itemRows[0]['data'];
            $shipping_cost = !empty($firstRow['shipping_cost']) ? (float) $firstRow['shipping_cost'] : 0;
        }

        $grand_total = $subtotal - $discount_total + $shipping_cost;

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discount_total,
            'shipping_cost' => $shipping_cost,
            'grand_total' => $grand_total
        ];
    }

// app/Imports/SalesOrderImport.php - ENHANCED! SUPPORT MULTIPLE PAYMENTS
private function createPayment($salesOrder, $row, &$totalPaidBefore = 0)
{
    $paymentMethod = $row['payment_method'] ?? 'cash';
    
    // ✅ SMART PAYMENT PARSING
    $cashAmount = (float) ($row['cash_amount_total'] ?? 0);
    $transferAmount = (float) ($row['transfer_amount_total'] ?? 0);
    
    // Auto-calculate total amount
    $paymentAmount = $cashAmount + $transferAmount;
    
    // ✅ VALIDASI: PAYMENT HARUS LEBIH DARI 0
    if ($paymentAmount <= 0) {
        return; // Skip jika tidak ada payment
    }
    
    // ✅ VALIDATE PAYMENT TOTAL (TIDAK STRICT UNTUK MULTIPLE PAYMENTS)
    // Untuk multiple payments, tidak perlu strict bahwa satu payment harus = grand_total
    $totalPaidAfter = $totalPaidBefore + $paymentAmount;
    
    // Tentukan category payment
    $paymentCategory = 'dp';
    if ($totalPaidBefore == 0 && $paymentAmount >= $salesOrder->grand_total) {
        // Payment pertama dan sudah lunas sekaligus
        $paymentCategory = 'pelunasan';
    } elseif ($totalPaidBefore > 0 && $totalPaidAfter >= $salesOrder->grand_total) {
        // Payment tambahan yang membuat lunas (pelunasan akhir)
        $paymentCategory = 'pelunasan';
    } elseif ($totalPaidBefore == 0 && $paymentAmount < $salesOrder->grand_total) {
        // Payment pertama tapi belum lunas (DP)
        $paymentCategory = 'dp';
    } elseif ($totalPaidBefore > 0 && $totalPaidAfter < $salesOrder->grand_total) {
        // Payment tambahan tapi belum lunas (pelunasan sebagian)
        $paymentCategory = 'pelunasan';
    }
    
    // Tentukan status payment
    $paymentStatus = ($totalPaidAfter >= $salesOrder->grand_total) ? 'lunas' : 'dp';
    
    // ✅ CREATE PAYMENT RECORD
    Payment::create([
        'sales_order_id' => $salesOrder->id,
        'method' => $paymentMethod,
        'status' => $paymentStatus,
        'category' => $paymentCategory,
        'amount' => $paymentAmount,
        'cash_amount' => $cashAmount,
        'transfer_amount' => $transferAmount,
        'paid_at' => $this->parseDate($row['paid_at'] ?? $row['order_date'] ?? now(), true), // ✅ INCLUDE TIME untuk paid_at
        'reference_number' => $row['reference_number'] ?? null,
        'note' => $row['note'] ?? null,
        'created_by' => Auth::id(),
    ]);
    
    // Update total paid before untuk payment berikutnya
    $totalPaidBefore += $paymentAmount;
}

    /**
     * Parse tanggal/datetime dari berbagai format Excel
     * Support: Excel serial date, format Indonesia (dd/mm/yyyy), format ISO (yyyy-mm-dd), dll
     */
    private function parseDate($dateValue, $includeTime = true)
    {
        // Jika kosong, return sekarang
        if (empty($dateValue)) {
            return now();
        }

        try {
            // ✅ HANDLE EXCEL SERIAL DATE (number seperti 45320)
            // Excel menyimpan tanggal sebagai number: 1 = 1900-01-01
            if (is_numeric($dateValue)) {
                $excelDate = (float) $dateValue;
                
                // Excel serial date dimulai dari 1900-01-01 (hari ke-1)
                // Tapi Excel salah menghitung tahun 1900 sebagai tahun kabisat
                // Jadi perlu adjust: subtract 2 hari untuk tanggal > 28 Feb 1900
                if ($excelDate >= 60) {
                    $excelDate = $excelDate - 2;
                } elseif ($excelDate >= 1) {
                    $excelDate = $excelDate - 1;
                }
                
                // Excel epoch: 1899-12-30 (base date untuk Excel)
                $excelEpoch = Carbon::create(1899, 12, 30, 0, 0, 0);
                
                // Convert Excel serial date ke Carbon
                $parsedDate = $excelEpoch->copy()->addDays((int)$excelDate);
                
                // Jika ada decimal (waktu), tambahkan waktu
                $decimalPart = $excelDate - (int)$excelDate;
                if ($decimalPart > 0 && $includeTime) {
                    $totalSeconds = round($decimalPart * 86400); // 86400 detik dalam sehari
                    $hours = floor($totalSeconds / 3600);
                    $minutes = floor(($totalSeconds % 3600) / 60);
                    $seconds = $totalSeconds % 60;
                    $parsedDate->setTime($hours, $minutes, $seconds);
                }
                
                return $parsedDate;
            }

            // ✅ HANDLE STRING DATE - Normalisasi dulu
            $dateString = trim((string) $dateValue);
            
            // Jika hanya angka (termasuk decimal), coba parse sebagai Excel serial date
            if (preg_match('/^\d+(\.\d+)?$/', $dateString)) {
                $excelDate = (float) $dateString;
                if ($excelDate >= 60) {
                    $excelDate = $excelDate - 2;
                } elseif ($excelDate >= 1) {
                    $excelDate = $excelDate - 1;
                }
                $excelEpoch = Carbon::create(1899, 12, 30, 0, 0, 0);
                $parsedDate = $excelEpoch->copy()->addDays((int)$excelDate);
                
                $decimalPart = $excelDate - (int)$excelDate;
                if ($decimalPart > 0 && $includeTime) {
                    $totalSeconds = round($decimalPart * 86400);
                    $hours = floor($totalSeconds / 3600);
                    $minutes = floor(($totalSeconds % 3600) / 60);
                    $seconds = $totalSeconds % 60;
                    $parsedDate->setTime($hours, $minutes, $seconds);
                }
                
                return $parsedDate;
            }

            // ✅ HANDLE FORMAT INDONESIA: dd/mm/yyyy atau dd-mm-yyyy
            // Format: 15/01/2025 atau 15-01-2025 atau 15/01/2025 10:30:00
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})(\s+(\d{1,2}):(\d{1,2})(:(\d{1,2}))?)?\s*$/i', $dateString, $matches)) {
                $day = (int) $matches[1];
                $month = (int) $matches[2];
                $year = (int) $matches[3];
                $hour = isset($matches[5]) && $includeTime ? (int) $matches[5] : 0;
                $minute = isset($matches[6]) && $includeTime ? (int) $matches[6] : 0;
                $second = isset($matches[8]) && $includeTime ? (int) $matches[8] : 0;
                
                // Validasi: jika day > 31 atau month > 12, mungkin format salah
                if ($day > 31 || $month > 12) {
                    // Mungkin format US (mm/dd/yyyy), coba swap
                    if ($month <= 12 && $day <= 12) {
                        // Ambigu, coba sebagai format Indonesia dulu
                        // Jika tidak valid, akan throw error dan coba format lain
                    }
                }
                
                return Carbon::create($year, $month, $day, $hour, $minute, $second);
            }

            // ✅ HANDLE FORMAT ISO: yyyy-mm-dd atau yyyy/mm/dd
            // Format: 2025-01-15 atau 2025/01/15 atau 2025-01-15 10:30:00
            if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})(\s+(\d{1,2}):(\d{1,2})(:(\d{1,2}))?)?\s*$/i', $dateString, $matches)) {
                $year = (int) $matches[1];
                $month = (int) $matches[2];
                $day = (int) $matches[3];
                $hour = isset($matches[5]) && $includeTime ? (int) $matches[5] : 0;
                $minute = isset($matches[6]) && $includeTime ? (int) $matches[6] : 0;
                $second = isset($matches[8]) && $includeTime ? (int) $matches[8] : 0;
                
                return Carbon::create($year, $month, $day, $hour, $minute, $second);
            }

            // ✅ HANDLE FORMAT US: mm/dd/yyyy (jika terdeteksi)
            // Coba parse sebagai format US jika format sebelumnya gagal
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})(\s+(\d{1,2}):(\d{1,2})(:(\d{1,2}))?)?\s*$/i', $dateString, $matches)) {
                $first = (int) $matches[1];
                $second = (int) $matches[2];
                $third = (int) $matches[3];
                
                // Jika bagian pertama > 12 tapi bagian kedua <= 12, kemungkinan format US
                if ($first > 12 && $second <= 12 && $third >= 1900) {
                    $month = $second;
                    $day = $first;
                    $year = $third;
                    $hour = isset($matches[5]) && $includeTime ? (int) $matches[5] : 0;
                    $minute = isset($matches[6]) && $includeTime ? (int) $matches[6] : 0;
                    $second = isset($matches[8]) && $includeTime ? (int) $matches[8] : 0;
                    
                    return Carbon::create($year, $month, $day, $hour, $minute, $second);
                }
            }

            // ✅ COBA PARSE DENGAN CARBON (untuk format standar lainnya)
            $parsed = Carbon::parse($dateString);
            
            // Jika tidak perlu waktu, set ke 00:00:00
            if (!$includeTime) {
                $parsed->setTime(0, 0, 0);
            }
            
            return $parsed;
            
        } catch (\Exception $e) {
            // Jika semua gagal, log error dan return now
            \Log::warning('Error parsing date: ' . $dateValue . ' - ' . $e->getMessage());
            return now();
        }
    }

    private function generateSoNumber(): string
    {
        $date = Carbon::now()->format('ymd');
        $seq = DB::table('sales_orders')->whereDate('created_at', Carbon::today())->count() + 1;
        return 'SAL' . $date . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }
}