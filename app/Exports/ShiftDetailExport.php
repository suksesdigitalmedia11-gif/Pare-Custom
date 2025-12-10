<?php

namespace App\Exports;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Payment;
use App\Models\Shift;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ShiftDetailExport implements FromCollection, WithHeadings, WithTitle
{
    protected $shift;

    public function __construct(Shift $shift)
    {
        $this->shift = $shift->load(['user', 'cashTransfers']);
    }

    public function collection()
    {
        $data = [];
        $shift = $this->shift;

        // Payments dalam rentang shift (mengikuti view show)
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

        // Informasi Shift lengkap
        $data[] = ['Shift Information', '', '', '', ''];
        $data[] = ['User', $shift->user->name, '', '', ''];
        $data[] = ['Start Time', Carbon::parse($shift->start_time)->format('d/m/Y H:i'), '', '', ''];
        $data[] = ['End Time', $shift->end_time ? Carbon::parse($shift->end_time)->format('d/m/Y H:i') : '-', '', '', ''];
        $data[] = ['Status', ucfirst($shift->status), '', '', ''];
        $data[] = ['Initial Cash', (float) $shift->initial_cash, '', '', ''];
        $data[] = ['Cash Total', (float) $shift->cash_total, '', '', ''];
        $data[] = ['Income Manual', (float) $shift->income_total, '', '', ''];
        $data[] = ['Expense Total', (float) $shift->expense_total, '', '', ''];
        $data[] = ['Setor/Tukar Tunai', (float) $totalCashTransfer, '', '', ''];
        $data[] = ['Expected Cash (Tunai)', (float) ($shift->initial_cash + $cashLunas + $cashDp + $cashPelunasan + $shift->income_total - $shift->expense_total - $totalCashTransfer), '', '', ''];
        $data[] = ['Final Cash', $shift->final_cash !== null ? (float) $shift->final_cash : '', '', '', ''];
        $data[] = ['Discrepancy', $shift->discrepancy !== null ? (float) $shift->discrepancy : '', '', '', ''];
        $data[] = ['Total Pendapatan', (float) $totalPendapatan, '', '', ''];
        $data[] = ['Notes', $shift->notes ?? '-', '', '', ''];
        $data[] = ['', '', '', '', ''];

        // Ringkasan Pendapatan
        $data[] = ['Ringkasan Pendapatan', '', '', '', ''];
        $data[] = ['Cash Lunas', (float) $cashLunas, '', '', ''];
        $data[] = ['Cash DP', (float) $cashDp, '', '', ''];
        $data[] = ['Cash Pelunasan', (float) $cashPelunasan, '', '', ''];
        $data[] = ['Transfer Lunas', (float) $transferLunas, '', '', ''];
        $data[] = ['Transfer DP', (float) $transferDp, '', '', ''];
        $data[] = ['Transfer Pelunasan', (float) $transferPelunasan, '', '', ''];
        $data[] = ['', '', '', '', ''];

        // Pembayaran / Sales Order dalam shift
        $data[] = ['Pembayaran Sales Order (dalam shift)', '', '', '', ''];
        if ($payments->isEmpty()) {
            $data[] = ['Tidak ada pembayaran SO dalam shift ini.', '', '', '', ''];
        } else {
            $data[] = ['SO Number', 'Customer', 'Metode', 'Kategori', 'Jumlah (Rp)'];
            foreach ($payments as $payment) {
                $amountText = $payment->method === 'split'
                    ? 'Cash: ' . (float) $payment->cash_amount . ' / Transfer: ' . (float) $payment->transfer_amount
                    : (float) $payment->amount;

                $data[] = [
                    $payment->salesOrder->so_number ?? '-',
                    $payment->salesOrder->customer->name ?? 'Umum',
                    ucfirst($payment->method),
                    $payment->category ?? '-',
                    $amountText,
                ];
            }
        }

        $data[] = ['', '', '', '', ''];

        // Pemasukan Manual
        $data[] = ['Pemasukan Manual', '', '', '', ''];
        $incomes = Income::where('shift_id', $shift->id)->get();
        if ($incomes->isEmpty()) {
            $data[] = ['Tidak ada pemasukan manual.', '', '', '', ''];
        } else {
            $data[] = ['Deskripsi', 'Jumlah (Rp)', 'Waktu', '', ''];
            foreach ($incomes as $income) {
                $data[] = [
                    $income->description,
                    (float) $income->amount,
                    Carbon::parse($income->created_at)->format('d/m/Y H:i'),
                    '',
                    '',
                ];
            }
        }

        $data[] = ['', '', '', '', ''];

        // Pengeluaran
        $data[] = ['Pengeluaran', '', '', '', ''];
        $expenses = Expense::where('shift_id', $shift->id)->get();
        if ($expenses->isEmpty()) {
            $data[] = ['Tidak ada pengeluaran.', '', '', '', ''];
        } else {
            $data[] = ['Deskripsi', 'Jumlah (Rp)', 'Waktu', 'Catatan', ''];
            foreach ($expenses as $expense) {
                $data[] = [
                    $expense->description,
                    (float) $expense->amount,
                    Carbon::parse($expense->created_at)->format('d/m/Y H:i'),
                    $expense->notes ?? '-',
                    '',
                ];
            }
        }

        $data[] = ['', '', '', '', ''];

        // Setor / Tukar Tunai
        $data[] = ['Setor / Tukar Tunai', '', '', '', ''];
        $cashTransfers = $shift->cashTransfers;
        if ($cashTransfers->isEmpty()) {
            $data[] = ['Tidak ada setor/tukar tunai.', '', '', '', ''];
        } else {
            $data[] = ['Deskripsi', 'Jenis', 'Jumlah (Rp)', 'Waktu', 'Catatan'];
            foreach ($cashTransfers as $transfer) {
                $data[] = [
                    $transfer->description,
                    ucfirst($transfer->type),
                    (float) $transfer->amount,
                    Carbon::parse($transfer->created_at)->format('d/m/Y H:i'),
                    $transfer->notes ?? '-',
                ];
            }
        }

        return collect($data);
    }

    public function headings(): array
    {
        return ['Col 1', 'Col 2', 'Col 3', 'Col 4', 'Col 5'];
    }

    public function title(): string
    {
        return 'Shift Detail ' . $this->shift->id;
    }
}

