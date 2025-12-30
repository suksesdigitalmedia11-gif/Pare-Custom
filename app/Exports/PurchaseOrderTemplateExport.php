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
            // Contoh 1: Pembelian kain dengan 2 item (Satu Supplier = Satu PO Otomatis)
            [
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
                '2025-01-15',          // Tanggal harus sama agar jadi satu PO
                '2025-01-20',
                'Toko Kain Makmur',    // Supplier sama agar jadi satu PO
                'kain',
                'request_kain',
                'Kain Linen Halus',
                'LIN001',
                65000,
                30,
                0,
            ],

            // Contoh 2: PO Berbeda (karena beda supplier / tanggal)
            [
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
            'A' => 12, // ORDER_DATE
            'B' => 12, // DEADLINE
            'C' => 22, // SUPPLIER_NAME
            'D' => 14, // PURCHASE_TYPE
            'E' => 14, // STATUS
            'F' => 28, // PRODUCT_NAME
            'G' => 12, // SKU
            'H' => 14, // COST_PRICE
            'I' => 8,  // QTY
            'J' => 12, // DISCOUNT
        ];
    }
}


