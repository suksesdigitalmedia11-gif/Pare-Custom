<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\PurchaseOrder;
use App\Models\Payment;
use App\Models\SalesOrder;
use App\Models\Expense;
use App\Models\Income;
use App\Models\CashTransfer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ShiftHistoryExport;
use App\Exports\ShiftFullDetailExport;
use App\Exports\ShiftDetailExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;


class ShiftController extends Controller
{
    /**
     * Dashboard Supervisor Finance - Monitor ALL Active Shifts
     */
/**
 * Dashboard Finance - Focus on Financial Reporting & Analysis
 */
public function dashboard(Request $request): View
{
    // DATE RANGE - Default to current month
    $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
    $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
    
    $start = Carbon::parse($startDate);
    $end = Carbon::parse($endDate);

    // === GET ALL SHIFTS dalam periode ===
    $shifts = Shift::with('user')
        ->where(function($query) use ($start, $end) {
            // Shift yang start_time dalam range, atau yang overlap dengan range
            $query->whereBetween('start_time', [$start, $end])
                  ->orWhere(function($q) use ($start, $end) {
                      $q->where('start_time', '<=', $end)
                        ->where(function($q2) use ($start) {
                            $q2->whereNull('end_time')
                               ->orWhere('end_time', '>=', $start);
                        });
                  });
        })
        ->orderBy('start_time', 'desc')
        ->get();

    // === CALCULATE TOTALS dari semua shift ===
    $totalShifts = $shifts->count();
    $activeShifts = $shifts->where('status', 'open')->count();
    $closedShifts = $shifts->where('status', 'closed')->count();

    // Total dari semua shift
    $totalInitialCash = $shifts->sum('initial_cash');
    $totalCashIncome = $shifts->sum('cash_total');
    $totalExpenses = $shifts->sum('expense_total');
    $totalFinalCash = $shifts->sum('final_cash');
    $totalDiscrepancy = $shifts->sum('discrepancy');

    // === DETAILED PAYMENT BREAKDOWN dari semua shift ===
    $allPayments = Payment::whereBetween('paid_at', [$start, $end])->get();
    
    $paymentBreakdown = [
        'cash' => [
            'lunas' => $allPayments->where('method', 'cash')
                ->filter(function($payment) {
                    $so = $payment->salesOrder;
                    return $so && $payment->category === 'pelunasan' && $so->payments->count() === 1;
                })->sum('amount'),
            'dp' => $allPayments->where('method', 'cash')->where('category', 'dp')->sum('amount'),
            'pelunasan' => $allPayments->where('method', 'cash')
                ->filter(function($payment) {
                    $so = $payment->salesOrder;
                    return $so && $payment->category === 'pelunasan' && $so->payments->count() > 1;
                })->sum('amount'),
        ],
        'transfer' => [
            'lunas' => $allPayments->where('method', 'transfer')
                ->filter(function($payment) {
                    $so = $payment->salesOrder;
                    return $so && $payment->category === 'pelunasan' && $so->payments->count() === 1;
                })->sum('amount'),
            'dp' => $allPayments->where('method', 'transfer')->where('category', 'dp')->sum('amount'),
            'pelunasan' => $allPayments->where('method', 'transfer')
                ->filter(function($payment) {
                    $so = $payment->salesOrder;
                    return $so && $payment->category === 'pelunasan' && $so->payments->count() > 1;
                })->sum('amount'),
        ]
    ];

    // Manual incomes & expenses dari semua shift
    $totalManualIncomes = Income::whereBetween('created_at', [$start, $end])->sum('amount');
    $totalManualExpenses = Expense::whereBetween('created_at', [$start, $end])->sum('amount');

    return view('finance.shift.dashboard', compact(
        'shifts',
        'totalShifts',
        'activeShifts', 
        'closedShifts',
        'totalInitialCash',
        'totalCashIncome',
        'totalExpenses',
        'totalFinalCash',
        'totalDiscrepancy',
        'paymentBreakdown',
        'totalManualIncomes',
        'totalManualExpenses',
        'startDate',
        'endDate'
    ));
}


    /**
     * Calculate payments for a shift (helper function)
     */
    private function calculateShiftPayments(Shift $shift): array
    {
        $cashLunas = $cashDp = $cashPelunasan = $transferLunas = $transferDp = $transferPelunasan = 0;

        $payments = Payment::where('created_by', $shift->user_id)
            ->where('created_at', '>=', $shift->start_time)
            ->where('created_at', '<=', $shift->end_time ?? now())
            ->with('salesOrder')
            ->get();

        foreach ($payments as $payment) {
            $so = $payment->salesOrder;
            $isLunasSekaliBayar = ($payment->category === 'pelunasan' && $so->payments->count() === 1);
            
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

        $cashTotal = $cashLunas + $cashDp + $cashPelunasan;
        $transferTotal = $transferLunas + $transferDp + $transferPelunasan;

        return compact(
            'cashLunas', 'cashDp', 'cashPelunasan',
            'transferLunas', 'transferDp', 'transferPelunasan',
            'cashTotal', 'transferTotal'
        );
    }

    public function printSummary($id)
{
    $shift = Shift::with('user')->findOrFail($id);
    
    $payments = Payment::where('created_by', $shift->user_id)
        ->where('created_at', '>=', $shift->start_time)
        ->where('created_at', '<=', $shift->end_time ?? now())
        ->with('salesOrder')
        ->get();
        
    $incomes = Income::where('shift_id', $id)->get();
    $expenses = Expense::where('shift_id', $id)->get();

    $pdf = PDF::loadView('finance.shift.closing_summary', compact('shift', 'incomes', 'expenses', 'payments'));
    
    // Set paper size dan margin
    $pdf->setPaper('a4', 'portrait'); // Default A4
    $pdf->setOption('margin-top', 0);
    $pdf->setOption('margin-right', 0);
    $pdf->setOption('margin-bottom', 0);
    $pdf->setOption('margin-left', 0);
    
    return $pdf->stream('closing_summary_shift_' . $shift->id . '.pdf');
}
    /**
     * Riwayat Shift untuk Finance - Lihat SEMUA shift
     */
    public function history(Request $request): View
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $shifts = Shift::with(['user', 'cashTransfers'])
            ->where(function($query) use ($start, $end) {
                $query->whereBetween('start_time', [$start, $end])
                      ->orWhere(function($q) use ($start, $end) {
                          $q->where('start_time', '<=', $end)
                            ->where(function($q2) use ($start) {
                                $q2->whereNull('end_time')
                                   ->orWhere('end_time', '>=', $start);
                            });
                      });
            })
            ->orderBy('start_time', 'desc')
            ->paginate(20);

        return view('finance.shift.history', compact('shifts', 'startDate', 'endDate'));
    }

    /**
     * Detail Shift untuk Finance - Lihat detail (read-only)
     */
    public function show(Shift $shift)
    {
        $incomes = Income::where('shift_id', $shift->id)->get();
        $expenses = Expense::where('shift_id', $shift->id)->get();
    
        $salesOrders = SalesOrder::whereHas('payments', function ($query) use ($shift) {
            $query->where('created_by', $shift->user_id)
                  ->where('created_at', '>=', $shift->start_time)
                  ->where('created_at', '<=', $shift->end_time ?? now());
        })->with(['customer', 'payments'])->get();
    
        $payments = Payment::where('created_by', $shift->user_id)
            ->where('created_at', '>=', $shift->start_time)
            ->where('created_at', '<=', $shift->end_time ?? now())
            ->with('salesOrder')
            ->get();
    
        $cashLunas = $cashDp = $cashPelunasan = $transferLunas = $transferDp = $transferPelunasan = 0;
    
        foreach ($payments as $payment) {
            $so = $payment->salesOrder;
            $isLunasSekaliBayar = ($payment->category === 'pelunasan' && $so->payments->count() === 1);
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
    
        return view('finance.shift.show', compact('shift', 'incomes', 'expenses', 'salesOrders', 'cashLunas', 'cashDp', 'cashPelunasan', 'transferLunas', 'transferDp', 'transferPelunasan', 'totalPendapatan'));
    }

    /**
     * Export Excel untuk Finance
     */
    public function export(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        return Excel::download(
            new ShiftHistoryExport($startDate, $endDate),
            'shift_history_' . date('Ymd_His') . '.xlsx'
        );
    }

    /**
     * Export Excel detail lengkap untuk Finance (semua shift dalam rentang)
     */
    public function exportFull(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        return Excel::download(
            new ShiftFullDetailExport($startDate, $endDate),
            'shift_detail_full_' . date('Ymd_His') . '.xlsx'
        );
    }

    /**
     * Export Detail Excel untuk Finance
     */
    public function exportDetail(Shift $shift)
    {
        return Excel::download(new ShiftDetailExport($shift), 'shift_detail_' . $shift->id . '_' . date('Ymd_His') . '.xlsx');
    }

    public function exportDetailPdf(Shift $shift)
    {
        // Ambil SO yang memiliki pembayaran dalam rentang shift
        $salesOrders = SalesOrder::whereHas('payments', function ($query) use ($shift) {
            $query->where('created_by', $shift->user_id)
                  ->where('created_at', '>=', $shift->start_time)
                  ->where('created_at', '<=', $shift->end_time ?? now());
        })->with(['payments' => function ($query) use ($shift) {
            $query->where('created_at', '>=', $shift->start_time)
                  ->where('created_at', '<=', $shift->end_time ?? now());
        }])->get();
    
        $expenses = Expense::where('shift_id', $shift->id)->get();
        $incomes = Income::where('shift_id', $shift->id)->get();

        $pdf = Pdf::loadView('finance.shift.detail_pdf', compact('shift', 'salesOrders', 'expenses', 'incomes'));
        return $pdf->download('shift_detail_' . $shift->id . '_' . date('Ymd_His') . '.pdf');
    }

    /**
     * Hapus pengeluaran dari shift (untuk koreksi jika admin salah input)
     */
    public function deleteExpense(Shift $shift, Expense $expense): RedirectResponse
    {
        // Validasi: expense harus milik shift tersebut
        if ($expense->shift_id !== $shift->id) {
            return back()->withErrors(['error' => 'Pengeluaran tidak ditemukan pada shift ini.']);
        }

        return DB::transaction(function () use ($shift, $expense) {
            $expenseAmount = (float) $expense->amount;
            $expenseDescription = $expense->description;

            // Hapus expense
            $expense->delete();

            // Update expense_total di shift (decrement)
            $shift->decrement('expense_total', $expenseAmount);

            // Jika shift sudah closed, recalculate final_cash dan discrepancy
            // Karena expense dihapus (salah input), berarti uangnya tidak benar-benar keluar
            // Jadi final_cash harus disesuaikan menjadi lebih besar
            if ($shift->status === 'closed' && $shift->end_time) {
                // Simpan final_cash lama untuk hitung selisih
                $oldFinalCash = $shift->final_cash;
                
                // Recalculate final cash berdasarkan data real setelah hapus expense
                // Formula: initial_cash + cash_masuk - expense_total (sudah dikurangi) - cash_transfer
                $newFinalCash = $this->calculateRealFinalCash($shift);
                
                // Hitung selisih perubahan final_cash
                $finalCashDifference = $newFinalCash - $oldFinalCash;
                
                // Update final_cash menjadi lebih besar (karena expense berkurang, kas akhir bertambah)
                // Discrepancy di-set ke 0 karena final_cash sudah disesuaikan dengan perhitungan yang benar
                $shift->update([
                    'final_cash' => $newFinalCash,
                    'discrepancy' => 0, // Tidak ada selisih karena sudah disesuaikan
                ]);
                
                // Cascade update: Update initial_cash shift berikutnya jika ada perubahan
                if (abs($finalCashDifference) > 0.01) {
                    $this->cascadeUpdateNextShift($shift, $finalCashDifference);
                }
            }

            \Log::info('Finance menghapus pengeluaran: ' . $expenseDescription . ' - Rp ' . number_format($expenseAmount, 0, ',', '.') . ' dari shift #' . $shift->id . ' oleh ' . Auth::user()->name);

            return back()->with('success', 'Pengeluaran berhasil dihapus. Perhitungan telah diperbarui.');
        });
    }

    /**
     * Helper method untuk calculate real cash total (sama seperti di Admin controller)
     */
    private function calculateRealCashTotal(Shift $shift): float
    {
        $payments = Payment::where('created_by', $shift->user_id)
            ->where('created_at', '>=', $shift->start_time)
            ->where('created_at', '<=', $shift->end_time ?? now())
            ->get();

        $totalCashFromPayments = 0;
        foreach ($payments as $payment) {
            if ($payment->method === 'cash') {
                $totalCashFromPayments += $payment->amount;
            } elseif ($payment->method === 'split') {
                $totalCashFromPayments += $payment->cash_amount;
            }
        }

        $totalIncome = Income::where('shift_id', $shift->id)->sum('amount');
        
        return $totalCashFromPayments + $totalIncome;
    }

    /**
     * Helper method untuk calculate real final cash (sama seperti di Admin controller)
     */
    private function calculateRealFinalCash(Shift $shift): float
    {
        $realCashTotal = $this->calculateRealCashTotal($shift);
        $totalCashTransfers = \App\Models\CashTransfer::where('shift_id', $shift->id)->sum('amount');
        
        return $shift->initial_cash + $realCashTotal - $shift->expense_total - $totalCashTransfers;
    }

    /**
     * Hapus pemasukan manual dari shift
     */
    public function deleteIncome(Shift $shift, Income $income): RedirectResponse
    {
        if ($income->shift_id !== $shift->id) {
            return back()->withErrors(['error' => 'Pemasukan tidak ditemukan pada shift ini.']);
        }

        return DB::transaction(function () use ($shift, $income) {
            $incomeAmount = (float) $income->amount;
            $incomeDescription = $income->description;

            // Hapus income
            $income->delete();

            // Update total di shift (decrement income_total dan cash_total)
            $shift->decrement('income_total', $incomeAmount);
            $shift->decrement('cash_total', $incomeAmount);

            if ($shift->status === 'closed' && $shift->end_time) {
                $oldFinalCash = $shift->final_cash;
                $newFinalCash = $this->calculateRealFinalCash($shift); // income berkurang → final cash turun
                $finalCashDifference = $newFinalCash - $oldFinalCash;

                $shift->update([
                    'final_cash' => $newFinalCash,
                    'discrepancy' => 0,
                ]);

                if (abs($finalCashDifference) > 0.01) {
                    $this->cascadeUpdateNextShift($shift, $finalCashDifference);
                }
            }

            \Log::info('Finance menghapus pemasukan: ' . $incomeDescription . ' - Rp ' . number_format($incomeAmount, 0, ',', '.') . ' dari shift #' . $shift->id . ' oleh ' . Auth::user()->name);

            return back()->with('success', 'Pemasukan manual berhasil dihapus. Perhitungan telah diperbarui.');
        });
    }

    /**
     * Hapus setor/tukar tunai
     */
    public function deleteCashTransfer(Shift $shift, CashTransfer $cashTransfer): RedirectResponse
    {
        if ($cashTransfer->shift_id !== $shift->id) {
            return back()->withErrors(['error' => 'Setor/Tukar tunai tidak ditemukan pada shift ini.']);
        }

        return DB::transaction(function () use ($shift, $cashTransfer) {
            $amount = (float) $cashTransfer->amount;
            $description = $cashTransfer->description;

            $cashTransfer->delete();

            // Menghapus cash transfer artinya kas akhir bertambah (karena uang tidak keluar)
            if ($shift->status === 'closed' && $shift->end_time) {
                $oldFinalCash = $shift->final_cash;
                $newFinalCash = $this->calculateRealFinalCash($shift); // cash transfer berkurang → final cash naik
                $finalCashDifference = $newFinalCash - $oldFinalCash;

                $shift->update([
                    'final_cash' => $newFinalCash,
                    'discrepancy' => 0,
                ]);

                if (abs($finalCashDifference) > 0.01) {
                    $this->cascadeUpdateNextShift($shift, $finalCashDifference);
                }
            }

            \Log::info('Finance menghapus cash transfer: ' . $description . ' - Rp ' . number_format($amount, 0, ',', '.') . ' dari shift #' . $shift->id . ' oleh ' . Auth::user()->name);

            return back()->with('success', 'Setor/Tukar tunai berhasil dihapus. Perhitungan telah diperbarui.');
        });
    }

    /**
     * Cascade update initial_cash shift berikutnya ketika final_cash shift sebelumnya berubah
     */
    private function cascadeUpdateNextShift(Shift $updatedShift, float $finalCashDifference): void
    {
        // Cari shift berikutnya yang langsung setelah shift ini
        // Shift berikutnya adalah shift yang start_time > end_time shift ini
        $nextShift = Shift::where('start_time', '>', $updatedShift->end_time)
            ->orderBy('start_time', 'asc')
            ->first();

        if (!$nextShift) {
            \Log::info('Finance: No next shift found for cascade update', [
                'updated_shift_id' => $updatedShift->id
            ]);
            return;
        }

        \Log::info('Finance: Cascading update to next shift', [
            'updated_shift_id' => $updatedShift->id,
            'next_shift_id' => $nextShift->id,
            'final_cash_difference' => $finalCashDifference
        ]);

        // Update initial_cash shift berikutnya
        $oldInitialCash = $nextShift->initial_cash;
        $newInitialCash = $oldInitialCash + $finalCashDifference;
        
        $nextShift->update(['initial_cash' => $newInitialCash]);

        \Log::info('Finance: Next shift initial_cash updated', [
            'next_shift_id' => $nextShift->id,
            'old_initial_cash' => $oldInitialCash,
            'new_initial_cash' => $newInitialCash
        ]);

        // Jika shift berikutnya juga sudah ditutup, perlu recalculate final_cash-nya juga
        // Karena initial_cash berubah, final_cash juga akan berubah
        if ($nextShift->status === 'closed' && $nextShift->end_time) {
            $this->recalculateAndUpdateClosedShift($nextShift);
        }
    }

    /**
     * Recalculate dan update shift yang sudah closed (untuk cascade update)
     */
    private function recalculateAndUpdateClosedShift(Shift $shift): void
    {
        // Simpan final_cash lama untuk hitung selisih
        $oldFinalCash = $shift->final_cash;
        
        // Recalculate final_cash dari data real
        $newFinalCash = $this->calculateRealFinalCash($shift);
        
        // Hitung selisih perubahan final_cash
        $finalCashDifference = $newFinalCash - $oldFinalCash;

        \Log::info('Finance: Recalculating closed shift final_cash', [
            'shift_id' => $shift->id,
            'old_final_cash' => $oldFinalCash,
            'new_final_cash' => $newFinalCash,
            'difference' => $finalCashDifference
        ]);

        // Update final_cash shift
        $shift->update([
            'final_cash' => $newFinalCash,
            'discrepancy' => 0, // Reset discrepancy karena kita recalculate dari data real
        ]);

        // Cascade update: Update initial_cash shift berikutnya jika ada perubahan
        if (abs($finalCashDifference) > 0.01) {
            $this->cascadeUpdateNextShift($shift, $finalCashDifference);
        }
    }
}