<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class SalesOrderTemplateExport implements FromArray, WithHeadings, WithTitle, WithColumnWidths
{
    public function title(): string
    {
        return 'Sales Order Template';
    }
    public function array(): array
    {
        return [
            // ✅ CONTOH 1: SPLIT PAYMENT (Cash + Transfer)
            [
                'SAL2501010001',      // SO_NUMBER
                '2025-01-15',         // ORDER_DATE
                '2025-01-20',         // DEADLINE
                'Budi Santoso',       // CUSTOMER_NAME
                '081234567890',       // CUSTOMER_PHONE
                'beli_jadi',          // ORDER_TYPE
                'split',              // PAYMENT_METHOD ✅ BISA: cash/transfer/split
                'lunas',              // PAYMENT_STATUS ✅ BISA: dp/lunas
                150000,               // CASH_AMOUNT_TOTAL ✅ NEW
                200000,               // TRANSFER_AMOUNT_TOTAL ✅ NEW
                'TRF-001',            // REFERENCE_NUMBER ✅ NEW
                '2025-01-15 10:30:00',// PAID_AT ✅ NEW
                15000,                // SHIPPING_COST ✅ NEW
                'Kaos Polo Lengan Pendek', // PRODUCT_NAME
                'POLO001',            // SKU
                75000,                // SALE_PRICE
                2,                    // QTY
                5000                  // DISCOUNT
            ],
            [
                'SAL2501010001',      // SAME SO NUMBER
                '2025-01-15',         // SAME DATE
                '2025-01-20',         // SAME DEADLINE  
                'Budi Santoso',       // SAME CUSTOMER
                '081234567890',       // SAME PHONE
                'beli_jadi',          // SAME ORDER TYPE
                'split',              // SAME PAYMENT METHOD
                'lunas',              // SAME PAYMENT STATUS
                150000,               // SAME CASH AMOUNT
                200000,               // SAME TRANSFER AMOUNT
                'TRF-001',            // SAME REFERENCE
                '2025-01-15 10:30:00',// SAME PAID_AT
                15000,                // SAME SHIPPING_COST
                'Celana Chino',       // DIFFERENT PRODUCT
                'CHINO001',           // DIFFERENT SKU
                120000,               // DIFFERENT PRICE
                1,                    // DIFFERENT QTY
                0                     // DIFFERENT DISCOUNT
            ],
            
            // ✅ CONTOH 2: CASH ONLY
            [
                'SAL2501010002',
                '2025-01-16', 
                '2025-01-25',
                'Siti Rahayu',
                '081298765432', 
                'jahit_sendiri',
                'cash',               // ✅ CASH ONLY
                'lunas',
                350000,               // ✅ CASH_AMOUNT_TOTAL
                0,                    // ✅ TRANSFER_AMOUNT_TOTAL = 0
                '',                   // No reference for cash
                '2025-01-16 14:20:00',
                0,                    // No shipping
                'Kemeja Linen Custom',
                'LINEN001',
                150000,
                1,
                10000
            ],
            
            // ✅ CONTOH 3: TRANSFER ONLY  
            [
                'SAL2501010003',
                '2025-01-17',
                '2025-01-22',
                'Ahmad Wijaya', 
                '081377788899',
                'beli_jadi',
                'transfer',           // ✅ TRANSFER ONLY
                'dp',                 // DP saja
                0,                    // ✅ CASH_AMOUNT_TOTAL = 0
                150000,               // ✅ TRANSFER_AMOUNT_TOTAL
                'BANK-789',           // Reference number
                '2025-01-17 09:15:00',
                20000,                // With shipping
                'Jaket Denim',
                'DENIM001', 
                250000,
                1,
                0
            ]
        ];
    }
    
    public function headings(): array
    {
        return [
            'SO_NUMBER',
            'ORDER_DATE',
            'DEADLINE', 
            'CUSTOMER_NAME',
            'CUSTOMER_PHONE',
            'ORDER_TYPE',
            'PAYMENT_METHOD',       // cash/transfer/split
            'PAYMENT_STATUS',       // dp/lunas  
            'CASH_AMOUNT_TOTAL',    // ✅ Nominal cash (isi 0 jika tidak ada)
            'TRANSFER_AMOUNT_TOTAL', // ✅ Nominal transfer (isi 0 jika tidak ada)
            'REFERENCE_NUMBER',     // ✅ No referensi transfer
            'PAID_AT',              // ✅ Tanggal bayar (YYYY-MM-DD HH:MM:SS)
            'SHIPPING_COST',        // ✅ Ongkos kirim
            'PRODUCT_NAME',
            'SKU', 
            'SALE_PRICE',
            'QTY',
            'DISCOUNT'
        ];
    }
    
    public function columnWidths(): array
    {
        return [
            'A' => 15, 'B' => 12, 'C' => 12, 'D' => 20, 'E' => 15,
            'F' => 12, 'G' => 12, 'H' => 12, 'I' => 15, 'J' => 15, // ✅ Adjusted widths
            'K' => 15, 'L' => 18, 'M' => 12, 'N' => 25, 'O' => 12,
            'P' => 12, 'Q' => 8, 'R' => 10
        ];
    }
}