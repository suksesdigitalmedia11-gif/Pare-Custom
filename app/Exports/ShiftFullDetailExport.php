<?php

namespace App\Exports;

use App\Models\Payment;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ShiftFullDetailExport implements FromCollection, WithHeadings
{
    private ?Carbon $startDate;
    private ?Carbon $endDate;

    public function __construct(?string $startDate = null, ?string $endDate = null)
    {
        $this->startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $this->endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : null;
    }

    public function collection(): Collection
    {
        $query = Shift::with(['user', 'cashTransfers', 'incomes', 'expenses']);

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

        return $query->orderBy('start_time', 'desc')->get()->map(function (Shift $shift) {
            // Ambil pembayaran dalam rentang shift
            $payments = Payment::where('created_by', $shift->user_id)
                ->where('created_at', '>=', $shift->start_time)
                ->where('created_at', '<=', $shift->end_time ?? now())
                ->with('salesOrder')
                ->get();

            $cashLunas = $cashDp = $cashPelunasan = 0;
            $transferLunas = $transferDp = $transferPelunasan = 0;

            foreach ($payments as $payment) {
                $so = $payment->salesOrder;
                $isLunasSekaliBayar = ($payment->category === 'pelunasan' && $so && $so->payments->count() === 1);

                if ($payment->method === 'cash') {
                    if ($isLunasSekaliBayar) {
                        $cashLunas += $payment->amount;
                    } elseif ($payment->category === 'dp') {
                        $cashDp += $payment->amount;
                    } else {
                        $cashPelunasan += $payment->amount;
                    }
                } elseif ($payment->method === 'transfer') {
                    if ($isLunasSekaliBayar) {
                        $transferLunas += $payment->amount;
                    } elseif ($payment->category === 'dp') {
                        $transferDp += $payment->amount;
                    } else {
                        $transferPelunasan += $payment->amount;
                    }
                } elseif ($payment->method === 'split') {
                    if ($isLunasSekaliBayar) {
                        $cashLunas += $payment->cash_amount;
                        $transferLunas += $payment->transfer_amount;
                    } elseif ($payment->category === 'dp') {
                        $cashDp += $payment->cash_amount;
                        $transferDp += $payment->transfer_amount;
                    } else {
                        $cashPelunasan += $payment->cash_amount;
                        $transferPelunasan += $payment->transfer_amount;
                    }
                }
            }

            $totalPendapatan = $cashLunas + $cashDp + $cashPelunasan + $transferLunas + $transferDp + $transferPelunasan;
            $totalCashTransfer = $shift->cashTransfers->sum('amount');

            return [
                'Shift ID' => $shift->id,
                'Kasir' => $shift->user->name,
                'Tanggal' => $shift->start_time->format('d/m/Y'),
                'Waktu Mulai' => $shift->start_time->format('H:i'),
                'Waktu Selesai' => $shift->end_time ? $shift->end_time->format('H:i') : '-',
                'Status' => ucfirst($shift->status),
                'Kas Awal' => $shift->initial_cash,
                'Cash Lunas' => $cashLunas,
                'Cash DP' => $cashDp,
                'Cash Pelunasan' => $cashPelunasan,
                'Transfer Lunas' => $transferLunas,
                'Transfer DP' => $transferDp,
                'Transfer Pelunasan' => $transferPelunasan,
                'Pemasukan Manual' => $shift->income_total,
                'Total Kas Masuk' => $shift->cash_total,
                'Total Pengeluaran' => $shift->expense_total,
                'Setor/Tukar Tunai' => $totalCashTransfer,
                'Kas Diharapkan (Tunai)' => $shift->initial_cash + $cashLunas + $cashDp + $cashPelunasan + $shift->income_total - $shift->expense_total - $totalCashTransfer,
                'Kas Aktual' => $shift->final_cash ?? 0,
                'Selisih' => $shift->discrepancy ?? 0,
                'Total Pendapatan' => $totalPendapatan,
                'Jumlah Sales Order (dibayar di shift)' => $payments->pluck('sales_order_id')->filter()->unique()->count(),
                'Jumlah Pemasukan Manual' => $shift->incomes->count(),
                'Jumlah Pengeluaran' => $shift->expenses->count(),
                'Jumlah Setor/Tukar Tunai' => $shift->cashTransfers->count(),
                'Catatan' => $shift->notes ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Shift ID',
            'Kasir',
            'Tanggal',
            'Waktu Mulai',
            'Waktu Selesai',
            'Status',
            'Kas Awal (Rp)',
            'Cash Lunas (Rp)',
            'Cash DP (Rp)',
            'Cash Pelunasan (Rp)',
            'Transfer Lunas (Rp)',
            'Transfer DP (Rp)',
            'Transfer Pelunasan (Rp)',
            'Pemasukan Manual (Rp)',
            'Total Kas Masuk (Rp)',
            'Total Pengeluaran (Rp)',
            'Setor/Tukar Tunai (Rp)',
            'Kas Diharapkan (Tunai) (Rp)',
            'Kas Aktual (Rp)',
            'Selisih (Rp)',
            'Total Pendapatan (Rp)',
            'Jumlah Sales Order (dibayar di shift)',
            'Jumlah Pemasukan Manual',
            'Jumlah Pengeluaran',
            'Jumlah Setor/Tukar Tunai',
            'Catatan',
        ];
    }
}

