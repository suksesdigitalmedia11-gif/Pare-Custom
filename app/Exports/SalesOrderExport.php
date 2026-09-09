<?php

namespace App\Exports;

use App\Models\SalesOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class SalesOrderExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithColumnWidths
{
    protected $salesOrders;

    public function __construct($salesOrders)
    {
        $this->salesOrders = $salesOrders;
    }

    public function collection()
    {
        return $this->salesOrders;
    }

    public function map($salesOrder): array
    {
        $rows = [];

        // ✅ Fallback jika Sales Order belum memiliki item (agar order tetap muncul di rekap)
        if ($salesOrder->items->isEmpty()) {
            $rows[] = [
                $salesOrder->so_number,
                $salesOrder->order_date ? $salesOrder->order_date->format('Y-m-d') : '',
                $salesOrder->deadline ? $salesOrder->deadline->format('Y-m-d') : '',
                $salesOrder->customer ? $salesOrder->customer->name : 'Umum',
                $salesOrder->customer ? $salesOrder->customer->phone : '',
                $salesOrder->order_type,
                $salesOrder->payment_method,
                $salesOrder->payment_status,
                '-',                                       // PRODUCT_NAME
                '',                                        // SKU
                0,                                         // SALE_PRICE
                0,                                         // QTY
                0,                                         // LINE_TOTAL
                $salesOrder->discount_total,               // DISCOUNT_TOTAL
                0,                                         // COST_PRICE
                0,                                         // HPP_LINE
                $salesOrder->status,                       // STATUS
                $salesOrder->subtotal,                     // SUBTOTAL
                $salesOrder->shipping_cost,                // SHIPPING_COST
                $salesOrder->grand_total,                  // GRAND_TOTAL
                $salesOrder->total_hpp,                    // TOTAL_HPP
                $salesOrder->est_profit,                   // EST_PROFIT
                $salesOrder->paid_total,                   // TOTAL_DIBAYAR
                $salesOrder->remaining_amount,             // SISA
                $salesOrder->created_at ? $salesOrder->created_at->format('Y-m-d H:i:s') : '',
                $salesOrder->creator->name ?? 'System'
            ];

            return $rows;
        }

        foreach ($salesOrder->items as $index => $item) {
            $isFirstItem = ($index === 0);

            // ✅ Hitung HPP item dengan fallback konsisten dashboard
            $costPrice = $item->cost_price;
            if ($costPrice === null) {
                $costPrice = $item->product ? ($item->product->cost_price ?? 0) : 0;
            }
            $hppLine = (float) $costPrice * (int) $item->qty;

            $rows[] = [
                $salesOrder->so_number,
                $salesOrder->order_date ? $salesOrder->order_date->format('Y-m-d') : '',
                $salesOrder->deadline ? $salesOrder->deadline->format('Y-m-d') : '',
                $salesOrder->customer ? $salesOrder->customer->name : 'Umum',
                $salesOrder->customer ? $salesOrder->customer->phone : '',
                $salesOrder->order_type,
                $salesOrder->payment_method,
                $salesOrder->payment_status,
                $item->product_name,
                $item->sku ?? '',
                $item->sale_price,
                $item->qty,
                $item->line_total,                        // ✅ Subtotal per item (sale_price × qty - diskon item)
                $isFirstItem ? $salesOrder->discount_total : '', // ✅ DISCOUNT_TOTAL (Hanya baris ke-1 per SO)
                $costPrice,                               // ✅ Harga modal item
                $hppLine,                                 // ✅ HPP per item (cost × qty)
                $isFirstItem ? $salesOrder->status : '',  // ✅ STATUS (Hanya baris ke-1 per SO)
                $isFirstItem ? $salesOrder->subtotal : '', // ✅ SUBTOTAL (Hanya baris ke-1 per SO)
                $isFirstItem ? $salesOrder->shipping_cost : '', // ✅ SHIPPING_COST (Hanya baris ke-1 per SO)
                $isFirstItem ? $salesOrder->grand_total : '', // ✅ GRAND_TOTAL (Hanya baris ke-1 per SO)
                $isFirstItem ? $salesOrder->total_hpp : '', // ✅ TOTAL_HPP (Hanya baris ke-1 per SO)
                $isFirstItem ? $salesOrder->est_profit : '', // ✅ EST_PROFIT (Hanya baris ke-1 per SO)
                $isFirstItem ? $salesOrder->paid_total : '', // ✅ TOTAL_DIBAYAR (Hanya baris ke-1 per SO)
                $isFirstItem ? $salesOrder->remaining_amount : '', // ✅ SISA (Hanya baris ke-1 per SO)
                $salesOrder->created_at ? $salesOrder->created_at->format('Y-m-d H:i:s') : '',
                $salesOrder->creator->name ?? 'System'
            ];
        }

        return $rows;
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
            'PAYMENT_METHOD',
            'PAYMENT_STATUS',
            'PRODUCT_NAME',
            'SKU',
            'SALE_PRICE',
            'QTY',
            'LINE_TOTAL',
            'DISCOUNT_TOTAL',
            'COST_PRICE',
            'HPP_LINE',
            'STATUS',
            'SUBTOTAL',
            'SHIPPING_COST',
            'GRAND_TOTAL',
            'TOTAL_HPP',
            'EST_PROFIT',
            'TOTAL_DIBAYAR',
            'SISA',
            'CREATED_AT',
            'CREATED_BY'
        ];
    }

    public function title(): string
    {
        return 'Sales Orders';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, 'B' => 12, 'C' => 12, 'D' => 20, 'E' => 15,
            'F' => 12, 'G' => 15, 'H' => 15, 'I' => 25, 'J' => 12,
            'K' => 12, 'L' => 8, 'M' => 12, 'N' => 14, 'O' => 12,
            'P' => 12, 'Q' => 12, 'R' => 12, 'S' => 13, 'T' => 12,
            'U' => 12, 'V' => 12, 'W' => 12, 'X' => 12, 'Y' => 18,
            'Z' => 15
        ];
    }
}