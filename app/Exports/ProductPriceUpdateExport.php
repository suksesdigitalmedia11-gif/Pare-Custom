<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ProductPriceUpdateExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithColumnWidths, WithStyles, ShouldAutoSize
{
    protected $products;

    public function __construct($products)
    {
        $this->products = $products;
    }

    public function collection()
    {
        return $this->products;
    }

    public function map($product): array
    {
        return [
            $product->sku ?? '-',
            $product->name,
            $product->cost_price ?? 0,
            '', // Harga Modal Baru — kosong untuk diisi user
            $product->price ?? 0,
            '', // Harga Jual Baru — kosong untuk diisi user
        ];
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Nama Produk',
            'Harga Modal Lama',
            'Harga Modal Baru',
            'Harga Jual Lama',
            'Harga Jual Baru',
        ];
    }

    public function title(): string
    {
        return 'Update Harga';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,  // SKU
            'B' => 40,  // Nama Produk
            'C' => 20,  // Harga Modal Lama
            'D' => 22,  // Harga Modal Baru (Input)
            'E' => 20,  // Harga Jual Lama
            'F' => 22,  // Harga Jual Baru (Input)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style umum untuk header baris 1
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Warna header kolom informasi (A-C & E)
        $sheet->getStyle('A1:C1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2C3E50'],
            ],
        ]);
        $sheet->getStyle('E1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2C3E50'],
            ],
        ]);

        // Warna header kolom input Harga Modal Baru (D1) -> Orange / Amber
        $sheet->getStyle('D1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D97706'], // Amber-600
            ],
        ]);

        // Warna header kolom input Harga Jual Baru (F1) -> Emerald Green
        $sheet->getStyle('F1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669'], // Emerald-600
            ],
        ]);

        // Set tinggi baris header
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Style untuk data rows
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle('A2:F' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Alternating row colors untuk data referensi
            for ($row = 2; $row <= $lastRow; $row++) {
                if ($row % 2 == 0) {
                    $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8FAFC'],
                        ],
                    ]);
                    $sheet->getStyle('E' . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8FAFC'],
                        ],
                    ]);
                }
            }

            // Align numeric columns and format numbers
            $sheet->getStyle('C2:F' . $lastRow)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
                'numberFormat' => [
                    'formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
                ],
            ]);

            // Highlight kolom input:
            // Kolom D (Harga Modal Baru) -> Soft Yellow
            $sheet->getStyle('D2:D' . $lastRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEF9C3'], // Yellow-100
                ],
            ]);

            // Kolom F (Harga Jual Baru) -> Soft Green
            $sheet->getStyle('F2:F' . $lastRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DCFCE7'], // Green-100
                ],
            ]);
        }

        // Freeze first row
        $sheet->freezePane('A2');

        return $sheet;
    }
}
