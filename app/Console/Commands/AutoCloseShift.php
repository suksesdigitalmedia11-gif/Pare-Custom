<?php

namespace App\Console\Commands;

use App\Models\Shift;
use App\Models\ShiftAutoClose;
use App\Models\Payment;
use App\Models\Income;
use App\Models\CashTransfer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AutoCloseShift extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shift:auto-close';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-close shifts that were not closed on the previous day';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for unclosed shifts...');
        
        // Cari shift yang masih open (belum ditutup)
        $openShifts = Shift::whereNull('end_time')
            ->where('status', 'open')
            ->get();
        
        if ($openShifts->isEmpty()) {
            $this->info('No unclosed shifts found.');
            return 0;
        }
        
        $this->info("Found {$openShifts->count()} unclosed shift(s). Closing them...");
        
        foreach ($openShifts as $shift) {
            DB::transaction(function () use ($shift) {
                // Hitung end_time = akhir hari sebelumnya (23:59:59)
                $yesterday = Carbon::yesterday();
                $endTime = $yesterday->copy()->endOfDay();
                
                // Hitung real cash total
                $realCashTotal = $this->calculateRealCashTotal($shift, $endTime);
                
                // Hitung real final cash
                $realFinalCash = $this->calculateRealFinalCash($shift, $endTime, $realCashTotal);
                
                // Update shift
                $shift->update([
                    'end_time' => $endTime,
                    'final_cash' => $realFinalCash,
                    'cash_total' => $realCashTotal,
                    'discrepancy' => 0,
                    'status' => 'closed',
                    'notes' => ($shift->notes ? $shift->notes . "\n" : '') . 
                               'Auto-closed: Shift tidak ditutup pada hari sebelumnya (' . now()->format('d/m/Y H:i') . ')',
                ]);
                
                // Buat record di shift_auto_closes
                ShiftAutoClose::create([
                    'shift_id' => $shift->id,
                    'auto_closed_date' => now()->toDateString(),
                    'is_blocked' => true,
                    'notes' => 'Shift di-auto-close karena tidak ditutup pada hari sebelumnya',
                ]);
                
                $this->info("Shift #{$shift->id} (User: {$shift->user->name}) has been auto-closed.");
            });
        }
        
        $this->info('All unclosed shifts have been processed. Admin login is now blocked.');
        return 0;
    }
    
    /**
     * Calculate real cash total from actual data
     */
    private function calculateRealCashTotal(Shift $shift, Carbon $endTime): float
    {
        $payments = Payment::where('created_by', $shift->user_id)
            ->where('created_at', '>=', $shift->start_time)
            ->where('created_at', '<=', $endTime)
            ->get();

        $totalCashFromPayments = 0;
        foreach ($payments as $payment) {
            if ($payment->method === 'cash') {
                $totalCashFromPayments += $payment->amount;
            } elseif ($payment->method === 'split') {
                $totalCashFromPayments += $payment->cash_amount;
            }
        }

        $totalIncome = Income::where('shift_id', $shift->id)
            ->where('created_at', '<=', $endTime)
            ->sum('amount');
        
        return $totalCashFromPayments + $totalIncome;
    }
    
    /**
     * Calculate real final cash
     */
    private function calculateRealFinalCash(Shift $shift, Carbon $endTime, float $realCashTotal): float
    {
        $totalCashTransfers = CashTransfer::where('shift_id', $shift->id)
            ->where('created_at', '<=', $endTime)
            ->sum('amount');
        
        return $shift->initial_cash + $realCashTotal - $shift->expense_total - $totalCashTransfers;
    }
}
