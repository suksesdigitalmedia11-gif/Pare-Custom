<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class PurchaseOrderExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithColumnWidths
{
    /**
     * @var \Illuminate\Support\Collection<int,\App\Models\PurchaseOrder>
     */
    protected $purchases;

    public function __construct($purchases)
    {
        $this->purchases = $purchases;
    }

    public function collection()
    {
        return $this->purchases;
    }

    public function map($purchase): array
    {
        $rows = [];

        foreach ($purchase->items as $item) {
            $rows[] = [
                $purchase->po_number,
                optional($purchase->order_date)->format('Y-m-d'),
                $purchase->deadline ? $purchase->deadline->format('Y-m-d') : '',
                $purchase->supplier?->name ?? '',
                $purchase->purchase_type,
                $purchase->status,
                $item->product_name,
                $item->sku ?? '',
                $item->cost_price,
                $item->qty,
                $item->discount,
                $item->line_total,
                $purchase->subtotal,
                $purchase->discount_total,
                $purchase->grand_total,
                optional($purchase->created_at)->format('Y-m-d H:i:s'),
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'PO_NUMBER',
            'ORDER_DATE',
            'DEADLINE',
            'SUPPLIER_NAME',
            'PURCHASE_TYPE',
            'STATUS',
            'PRODUCT_NAME',
            'SKU',
            'COST_PRICE',
            'QTY',
            'DISCOUNT',
            'LINE_TOTAL',
            'SUBTOTAL',
            'DISCOUNT_TOTAL',
            'GRAND_TOTAL',
            'CREATED_AT',
        ];
    }

    public function title(): string
    {
        return 'Purchase Orders';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 12,
            'C' => 12,
            'D' => 22,
            'E' => 14,
            'F' => 12,
            'G' => 28,
            'H' => 12,
            'I' => 12,
            'J' => 8,
            'K' => 12,
            'L' => 14,
            'M' => 14,
            'N' => 14,
            'O' => 14,
            'P' => 18,
        ];
    }
}


