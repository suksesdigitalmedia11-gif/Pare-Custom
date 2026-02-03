<?php

namespace App\Traits;

use App\Models\SalesOrder;
use App\Models\Payment;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

trait ManagesPayments
{
    /**
     * Update payment details (Method, Amount, Date, Reference Number, Note)
     */
    public function updatePaymentMethod(Request $request, SalesOrder $salesOrder, Payment $payment): RedirectResponse
    {
        $user = Auth::user();
        $userType = strtolower($user->usertype ?? $user->role ?? '');

        // Validasi akses: Owner, Finance, Kepala Toko
        if (!in_array($userType, ['owner', 'finance', 'kepala_toko'])) {
            Log::warning('Unauthorized attempt to update payment method', [
                'user_id' => $user->id,
                'user_type' => $userType,
                'payment_id' => $payment->id
            ]);
            return back()->withErrors(['error' => 'Anda tidak memiliki otoritas untuk mengubah data pembayaran.']);
        }

        // Validasi payment milik sales order
        if ($payment->sales_order_id !== $salesOrder->id) {
            return back()->withErrors(['error' => 'Pembayaran tidak valid untuk sales order ini.']);
        }

        $validated = $request->validate([
            'method' => ['required', 'in:cash,transfer,split'],
            'amount' => ['required', 'numeric', 'min:0'],
            'cash_amount' => ['nullable', 'required_if:method,split', 'numeric', 'min:0'],
            'transfer_amount' => ['nullable', 'required_if:method,split', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            DB::transaction(function () use ($salesOrder, $payment, $validated) {
                $oldMethod = $payment->method;
                $oldAmount = (float) $payment->amount;
                $oldCashAmount = (float) ($payment->cash_amount ?? 0);
                $oldPaidAt = $payment->paid_at;
                $newAmount = (float) $validated['amount'];

                // 1. Validasi Split Amount jika metode split
                if ($validated['method'] === 'split') {
                    $newCash = (float) ($validated['cash_amount'] ?? 0);
                    $newTransfer = (float) ($validated['transfer_amount'] ?? 0);
                    if (abs(($newCash + $newTransfer) - $newAmount) > 0.01) {
                        throw new \Exception('Jumlah cash + transfer (Rp ' . number_format($newCash + $newTransfer) . ') harus sama dengan total pembayaran (Rp ' . number_format($newAmount) . ').');
                    }
                }

                // 2. Hitung New Cash Amount
                $newCashAmount = 0;
                if ($validated['method'] === 'cash') {
                    $newCashAmount = $newAmount;
                } elseif ($validated['method'] === 'split') {
                    $newCashAmount = (float) $validated['cash_amount'];
                }

                // 3. Update Payment Data
                $updateData = [
                    'method' => $validated['method'],
                    'amount' => $newAmount,
                    'paid_at' => $validated['paid_at'],
                    'reference_number' => $validated['reference_number'],
                    'note' => $validated['note'],
                ];

                if ($validated['method'] === 'cash') {
                    $updateData['cash_amount'] = $newAmount;
                    $updateData['transfer_amount'] = 0;
                } elseif ($validated['method'] === 'transfer') {
                    $updateData['cash_amount'] = 0;
                    $updateData['transfer_amount'] = $newAmount;
                } else {
                    $updateData['cash_amount'] = $validated['cash_amount'];
                    $updateData['transfer_amount'] = $validated['transfer_amount'];
                }

                $payment->update($updateData);

                // Recalculate all categories for this SO to ensure consistency (DP/Pelunasan labels)
                $this->recalculatePaymentCategories($salesOrder->fresh());

                // 4. Sinkronisasi Shift Cash
                $cashDifference = $newCashAmount - $oldCashAmount;
                if (abs($cashDifference) > 0.01) {
                    $this->updateShiftCashForPayment($payment, $cashDifference);
                }

                // 5. Update Sales Order Payment Status & Method (re-sync after recalculation)
                $salesOrder->refresh(); // Ambil data krusial terbaru
                $totalPaid = $salesOrder->payments()->sum('amount');
                $newPaymentStatus = ($totalPaid >= $salesOrder->grand_total) ? 'lunas' : 'dp';
                if ($totalPaid <= 0) $newPaymentStatus = 'belum_bayar';

                $soUpdateData = ['payment_status' => $newPaymentStatus];
                
                // Jika ini pembayaran terbaru/terakhir, update default payment method di SO
                $latestPayment = $salesOrder->payments()->orderBy('paid_at', 'desc')->first();
                if ($latestPayment && $latestPayment->id === $payment->id) {
                    $soUpdateData['payment_method'] = $validated['method'];
                }
                
                $salesOrder->update($soUpdateData);

                // 6. Log Detail Perubahan (Audit Trail)
                $changes = [];
                if ($oldMethod !== $validated['method']) $changes[] = "Metode: {$oldMethod} → {$validated['method']}";
                if (abs($oldAmount - $newAmount) > 0.01) $changes[] = "Nominal: Rp " . number_format($oldAmount) . " → Rp " . number_format($newAmount);
                if (Carbon::parse($oldPaidAt)->format('Y-m-d H:i') !== Carbon::parse($validated['paid_at'])->format('Y-m-d H:i')) {
                    $changes[] = "Tgl: " . Carbon::parse($oldPaidAt)->format('d/m/Y H:i') . " → " . Carbon::parse($validated['paid_at'])->format('d/m/Y H:i');
                }

                $this->logAction(
                    $salesOrder,
                    'payment_correction',
                    "Koreksi pembayaran oleh " . Auth::user()->name . ": " . implode(", ", $changes)
                );
            });

            return back()->with('success', 'Data pembayaran berhasil dikoreksi dan laporan kas telah disesuaikan.');
        } catch (\Exception $e) {
            Log::error('Error correcting payment: ' . $e->getMessage(), ['payment_id' => $payment->id]);
            return back()->withErrors(['error' => 'Gagal mengoreksi pembayaran: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete payment record and adjust shift cash
     */
    public function destroyPayment(SalesOrder $salesOrder, Payment $payment): RedirectResponse
    {
        $user = Auth::user();
        $userType = strtolower($user->usertype ?? $user->role ?? '');

        // Otoritas: Owner, Finance, Kepala Toko
        if (!in_array($userType, ['owner', 'finance', 'kepala_toko'])) {
            return back()->withErrors(['error' => 'Anda tidak memiliki otoritas untuk menghapus pembayaran.']);
        }

        if ($payment->sales_order_id !== $salesOrder->id) {
            return back()->withErrors(['error' => 'Pembayaran tidak valid.']);
        }

        try {
            DB::transaction(function () use ($salesOrder, $payment) {
                $amount = (float) $payment->amount;
                $cashAmount = (float) ($payment->cash_amount ?? 0);
                $method = $payment->method;
                
                // Hapus bukti transfer jika ada
                if ($payment->proof_path && Storage::disk('public')->exists($payment->proof_path)) {
                    Storage::disk('public')->delete($payment->proof_path);
                }

                $payment->delete();

                // 1. Recalculate all categories for remaining payments
                $this->recalculatePaymentCategories($salesOrder->fresh());

                // 2. Update SO Status
                $totalPaid = $salesOrder->payments()->sum('amount');
                $newStatus = ($totalPaid >= $salesOrder->grand_total) ? 'lunas' : (($totalPaid > 0) ? 'dp' : 'belum_bayar');
                $salesOrder->update(['payment_status' => $newStatus]);

                // 3. Adjust Shift
                if ($cashAmount > 0) {
                    $this->updateShiftCashForPayment($payment, -$cashAmount);
                }

                // 3. Log
                $this->logAction($salesOrder, 'payment_deleted', "Pembayaran Rp " . number_format($amount) . " ({$method}) dihapus oleh " . Auth::user()->name);
            });

            return back()->with('success', 'Pembayaran berhasil dihapus dan laporan kas telah disesuaikan.');
        } catch (\Exception $e) {
            Log::error('Error deleting payment: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal menghapus pembayaran: ' . $e->getMessage()]);
        }
    }

    /**
     * Sync payment changes with Shift cash report
     */
    protected function updateShiftCashForPayment(Payment $payment, float $cashDifference): void
    {
        // Cari shift berdasarkan creator dan waktu record dibuat
        $shift = Shift::where('user_id', $payment->created_by)
            ->where('start_time', '<=', $payment->created_at)
            ->where(function ($query) use ($payment) {
                $query->where('end_time', '>=', $payment->created_at)
                    ->orWhereNull('end_time');
            })
            ->orderBy('start_time', 'desc')
            ->first();

        if (!$shift) {
            Log::warning('Shift not found for payment adjustment', ['payment_id' => $payment->id]);
            return;
        }

        // Update saldo cash di shift
        if ($cashDifference > 0) {
            $shift->increment('cash_total', $cashDifference);
            // Jika shift belum tutup, update final_cash juga agar sinkron saat tutup nanti
            if (!$shift->end_time) {
                $shift->increment('final_cash', $cashDifference);
            }
        } else {
            $absDiff = abs($cashDifference);
            $shift->decrement('cash_total', $absDiff);
            if (!$shift->end_time) {
                $shift->decrement('final_cash', $absDiff);
            }
        }

        // Jika shift SUDAH ditutup, jalankan cascade recalculation
        if ($shift->end_time) {
            $shift->increment('final_cash', $cashDifference); // Tetap update final_cash-nya meski sudah tutup
            $this->recalculateAndUpdateClosedShift($shift);
        }
    }

    protected function recalculateAndUpdateClosedShift(Shift $shift): void
    {
        // Recalculate cash_total from payments + incomes
        $realCash = $this->calculateRealCashTotalForShift($shift);
        $oldFinalCash = $shift->final_cash;
        $newFinalCash = $shift->initial_cash + $realCash - $shift->expense_total;
        
        $shift->update([
            'cash_total' => $realCash,
            'final_cash' => $newFinalCash
        ]);

        $difference = $newFinalCash - $oldFinalCash;

        if (abs($difference) > 0.01) {
            $this->cascadeUpdateNextShift($shift, $difference);
        }
    }

    protected function cascadeUpdateNextShift(Shift $updatedShift, float $finalCashDifference): void
    {
        $nextShift = Shift::where('start_time', '>', $updatedShift->end_time)
            ->orderBy('start_time', 'asc')
            ->first();

        if ($nextShift) {
            $nextShift->increment('initial_cash', $finalCashDifference);
            if ($nextShift->end_time) {
                $this->recalculateAndUpdateClosedShift($nextShift);
            }
        }
    }

    protected function calculateRealCashTotalForShift(Shift $shift): float
    {
        $totalCashPayments = Payment::where('created_by', $shift->user_id)
            ->where('created_at', '>=', $shift->start_time)
            ->where('created_at', '<=', $shift->end_time ?? now())
            ->where(function($q) {
                $q->where('method', 'cash')
                  ->orWhere('method', 'split');
            })
            ->get()
            ->sum(function($p) {
                return $p->method === 'cash' ? $p->amount : $p->cash_amount;
            });

        $totalIncome = \App\Models\Income::where('shift_id', $shift->id)->sum('amount');

        return (float)($totalCashPayments + $totalIncome);
    }

    /**
     * Recalculate 'category' for all payments of a SalesOrder to ensure consistency.
     * This ensures that payments are correctly labeled as 'dp' or 'pelunasan'
     * based on the current grand total and payment sequence.
     */
    public function recalculatePaymentCategories(SalesOrder $salesOrder): void
    {
        $cumulative = 0;
        // Gunakan reorder() untuk menghapus order default (DESC) dari relasi di model SalesOrder
        $payments = $salesOrder->payments()
            ->reorder() 
            ->orderBy('paid_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();
            
        $grandTotal = (float) $salesOrder->grand_total;
        
        foreach ($payments as $p) {
            $cumulative += (float) $p->amount;
            
            // Menggunakan toleransi kecil untuk perbandingan float
            $newCategory = ($cumulative >= ($grandTotal - 0.01)) ? 'pelunasan' : 'dp';
            
            if ($p->category !== $newCategory) {
                $p->update(['category' => $newCategory]);
            }
        }
    }
}
