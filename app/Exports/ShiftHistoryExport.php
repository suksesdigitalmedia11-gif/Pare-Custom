<?php

namespace App\Exports;

use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ShiftHistoryExport implements FromCollection, WithHeadings
{
    private ?Carbon $startDate;
    private ?Carbon $endDate;

    public function __construct(?string $startDate = null, ?string $endDate = null)
    {
        // Normalize to full-day window so exported data matches on-screen filters.
        $this->startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $this->endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : null;
    }

    public function collection(): Collection
    {
        $query = Shift::with('user');

        if ($this->startDate && $this->endDate) {
            $start = $this->startDate;
            $end = $this->endDate;

            $query->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_time', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_time', '<=', $end)
                            ->where(function ($q2) use ($start) {
                                $q2->whereNull('end_time')
                                    ->orWhere('end_time', '>=', $start);
                            });
                    });
            });
        }

        return $query->orderBy('start_time', 'desc')->get()->map(function ($shift) {
            $kasPenjualan = $shift->cash_total - $shift->income_total;

            return [
                'Kasir' => $shift->user->name,
                'Tanggal' => $shift->start_time->format('d/m/Y'),
                'Waktu Mulai' => $shift->start_time->format('H:i'),
                'Waktu Selesai' => $shift->end_time ? $shift->end_time->format('H:i') : '-',
                'Kas Awal' => $shift->initial_cash,
                'Kas dari Penjualan' => $kasPenjualan,
                'Pemasukan Manual' => $shift->income_total,
                'Total Kas Masuk' => $shift->cash_total,
                'Total Pengeluaran' => $shift->expense_total,
                'Kas Diharapkan' => $shift->initial_cash + $shift->cash_total - $shift->expense_total,
                'Kas Aktual' => $shift->final_cash ?? 0,
                'Selisih' => $shift->discrepancy ?? 0,
                'Status' => ucfirst($shift->status),
                'Catatan' => $shift->notes ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Kasir',
            'Tanggal',
            'Waktu Mulai',
            'Waktu Selesai',
            'Kas Awal (Rp)',
            'Kas dari Penjualan (Rp)',
            'Pemasukan Manual (Rp)',
            'Total Kas Masuk (Rp)',
            'Total Pengeluaran (Rp)',
            'Kas Diharapkan (Rp)',
            'Kas Aktual (Rp)',
            'Selisih (Rp)',
            'Status',
            'Catatan',
        ];
    }
}