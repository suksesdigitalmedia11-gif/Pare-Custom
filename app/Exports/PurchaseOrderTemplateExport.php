<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class PurchaseOrderTemplateExport implements FromArray, WithHeadings, WithTitle, WithColumnWidths
{
    public function title(): string
    {
        return 'Purchase Order Template';
    }

    public function headings(): array
    {
        return [
            'PO_NUMBER',        // Opsional, kosongkan jika ingin nomor otomatis
            'ORDER_DATE',       // Tanggal pembelian (YYYY-MM-DD)
            'DEADLINE',         // Opsional: target selesai (YYYY-MM-DD)
            'SUPPLIER_NAME',    // Nama supplier (akan dibuat otomatis jika belum ada)
            'PURCHASE_TYPE',    // kain / produk_jadi
            'STATUS',           // Opsional: draft, pending, request_kain, payment, proses_jahit, printing, selesai, canceled
            'PRODUCT_NAME',     // Nama produk/bahan
            'SKU',              // Opsional
            'COST_PRICE',       // Harga beli per unit
            'QTY',              // Qty
            'DISCOUNT',         // Diskon per baris (opsional)
        ];
    }

    public function array(): array
    {
        return [
            // Contoh 1: Pembelian kain dengan 2 item (PO_NUMBER sama)
            [
                'PO2501010001',        // PO_NUMBER (boleh dikosongkan)
                '2025-01-15',          // ORDER_DATE
                '2025-01-20',          // DEADLINE
                'Toko Kain Makmur',    // SUPPLIER_NAME
                'kain',                // PURCHASE_TYPE
                'request_kain',        // STATUS
                'Kain Katun Premium',  // PRODUCT_NAME
                'KTN001',              // SKU
                45000,                 // COST_PRICE
                50,                    // QTY
                0,                     // DISCOUNT
            ],
            [
                'PO2501010001',        // PO_NUMBER sama
                '2025-01-15',
                '2025-01-20',
                'Toko Kain Makmur',
                'kain',
                'request_kain',
                'Kain Linen Halus',
                'LIN001',
                65000,
                30,
                0,
            ],

            // Contoh 2: Pembelian produk jadi 1 item
            [
                'PO2501010002',
                '2025-01-18',
                '',
                'Konveksi Jaya Abadi',
                'produk_jadi',
                'selesai',
                'Kemeja Putih Lengan Panjang',
                'KMJ001',
                90000,
                20,
                10000,
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // PO_NUMBER
            'B' => 12, // ORDER_DATE
            'C' => 12, // DEADLINE
            'D' => 22, // SUPPLIER_NAME
            'E' => 14, // PURCHASE_TYPE
            'F' => 14, // STATUS
            'G' => 28, // PRODUCT_NAME
            'H' => 12, // SKU
            'I' => 14, // COST_PRICE
            'J' => 8,  // QTY
            'K' => 12, // DISCOUNT
        ];
    }
}


