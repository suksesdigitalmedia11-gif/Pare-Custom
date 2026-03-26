<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\SalesOrder;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Income;
use App\Models\CashTransfer;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;


class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Ambil filter tanggal dari request
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $activeShift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
        
        // Hitung data shift seperti di ShiftController
        $shiftData = $this->calculateShiftData($activeShift);
        
        // Today's stats - PAKAI FILTER TANGGAL
        $todayStats = $this->getTodayStats();
        
        // Deadline alerts - TIDAK PAKAI FILTER (real-time)
        $overdueOrders = $this->getOverdueOrders(); // 🔴 BARU: Deadline terlewat
        $upcomingOrders = $this->getUpcomingOrders(); // 🟡 BARU: Deadline mendekat
        $overdueCount = $overdueOrders->count();
        $upcomingCount = $upcomingOrders->count();
        
        // Pending payments - TIDAK PAKAI FILTER (real-time)
        $pendingPayments = $this->getPendingPayments();
        $pendingPaymentsCount = $pendingPayments->count();

        // Sales type stats - PAKAI FILTER TANGGAL ✅
        $salesTypeStats = $this->getSalesTypeStats($startDate, $endDate);

        // Gabungkan semua data
        $data = array_merge(
            [
                'activeShift' => $activeShift,
                'todayStats' => $todayStats,
                'overdueOrders' => $overdueOrders,
                'overdueCount' => $overdueCount,
                'upcomingOrders' => $upcomingOrders,
                'upcomingCount' => $upcomingCount,
                'pendingPayments' => $pendingPayments,
                'pendingPaymentsCount' => $pendingPaymentsCount,
                'salesTypeStats' => $salesTypeStats,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ],
            $shiftData
        );

        return view('admin.dashboard', $data);
    }

    private function calculateShiftData($shift)
    {
        // Default values - PASTIKAN SEMUA VARIABLE ADA
        $cashLunas = $cashDp = $cashPelunasan = $transferLunas = $transferDp = $transferPelunasan = 0;
        $pengeluaran = $pemasukanManual = $tunaiDiLaci = $awalLaci = $totalDiharapkan = 0;
        $totalTransactions = $totalInvoices = $totalSales = $totalCustomers = 0;
        $averageTransaction = 0;
        $shiftDuration = '0 jam 0 menit';

        if ($shift) {
            $awalLaci = $shift->initial_cash ?? 0;
            $pengeluaran = $shift->expense_total ?? 0;
            $pemasukanManual = $shift->income_total ?? 0;

            // Ambil semua pembayaran yang dibuat selama shift ini
            $payments = Payment::where('created_by', Auth::id())
                ->where('created_at', '>=', $shift->start_time)
                ->where('created_at', '<=', $shift->end_time ?? now())
                ->with('salesOrder')
                ->get();

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
                        $cashLunas += $payment->cash_amount ?? 0;
                        $transferLunas += $payment->transfer_amount ?? 0;
                    } elseif ($payment->category === 'dp') {
                        $cashDp += $payment->cash_amount ?? 0;
                        $transferDp += $payment->transfer_amount ?? 0;
                    } else {
                        $cashPelunasan += $payment->cash_amount ?? 0;
                        $transferPelunasan += $payment->transfer_amount ?? 0;
                    }
                }
            }

            // Hitung total cash dari pembayaran + pemasukan manual
            $totalCashFromPayments = $cashLunas + $cashDp + $cashPelunasan;
            $totalCashFromAllSources = $totalCashFromPayments + $pemasukanManual;
            
            // Hitung tunai di laci (real calculation)
            $realCashTotal = $this->calculateRealCashTotal($shift);
            $totalCashTransfers = CashTransfer::where('shift_id', $shift->id)->sum('amount');
            $tunaiDiLaci = $shift->initial_cash + $realCashTotal - $shift->expense_total - $totalCashTransfers;
            $totalDiharapkan = $tunaiDiLaci;

            // Calculate statistics
            $transactionsQuery = SalesOrder::whereHas('payments', function($query) use ($shift) {
                    $query->where('created_by', Auth::id())
                          ->where('created_at', '>=', $shift->start_time)
                          ->where('created_at', '<=', $shift->end_time ?? now());
                });

            $totalTransactions = $transactionsQuery->count();
            $totalInvoices = $payments->count();
            $totalSales = $payments->sum('amount');
            $totalCustomers = $transactionsQuery->distinct('customer_id')->count('customer_id');

            // Durasi shift
            $start = Carbon::parse($shift->start_time);
            $end = $shift->end_time ? Carbon::parse($shift->end_time) : now();
            $duration = $start->diff($end);
            $shiftDuration = $duration->h . ' jam ' . $duration->i . ' menit';

            // Rata-rata transaksi
            $averageTransaction = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;
        }

        return [
            'cashLunas' => $cashLunas,
            'cashDp' => $cashDp,
            'cashPelunasan' => $cashPelunasan,
            'transferLunas' => $transferLunas,
            'transferDp' => $transferDp,
            'transferPelunasan' => $transferPelunasan,
            'pengeluaran' => $pengeluaran,
            'pemasukanManual' => $pemasukanManual,
            'tunaiDiLaci' => $tunaiDiLaci,
            'awalLaci' => $awalLaci,
            'totalDiharapkan' => $totalDiharapkan,
            'totalTransactions' => $totalTransactions,
            'totalInvoices' => $totalInvoices,
            'totalSales' => $totalSales,
            'totalCustomers' => $totalCustomers,
            'shiftDuration' => $shiftDuration,
            'averageTransaction' => $averageTransaction,
        ];
    }

    private function calculateRealCashTotal($shift): float
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
                $totalCashFromPayments += $payment->cash_amount ?? 0;
            }
        }

        $totalIncome = Income::where('shift_id', $shift->id)->sum('amount');
        
        return $totalCashFromPayments + $totalIncome;
    }

        // 🔴 METHOD BARU: Deadline Terlewat
    private function getOverdueOrders()
    {
        return SalesOrder::where('deadline', '<', now()->startOfDay()) // SUDAH LEWAT
            ->whereNotIn('status', ['selesai', 'diterima_toko'])
            ->with('customer')
            ->orderBy('deadline', 'asc') // Yang paling lama lewat duluan
            ->limit(5)
            ->get();
    }

        // 🟡 METHOD BARU: Deadline Mendekat  
    private function getUpcomingOrders()
    {
        return SalesOrder::where('deadline', '>=', now()->startOfDay())
            ->where('deadline', '<=', now()->addDays(5)->endOfDay())
            ->whereNotIn('status', ['selesai', 'diterima_toko'])
            ->with('customer')
            ->orderBy('deadline', 'asc')
            ->limit(5)
            ->get();
    }

        // 📊 METHOD BARU: Stats Jenis Transaksi (PAKAI FILTER)
        private function getSalesTypeStats($startDate, $endDate)
        {
            $totalSales = SalesOrder::whereBetween('order_date', [$startDate, $endDate])
                ->whereNotIn('status', ['draft'])
                ->count();
    
            $directSales = SalesOrder::whereBetween('order_date', [$startDate, $endDate])
                ->where('add_to_purchase', false)
                ->whereNotIn('status', ['draft'])
                ->count();
    
            $poSales = SalesOrder::whereBetween('order_date', [$startDate, $endDate])
                ->where('add_to_purchase', true)
                ->whereNotIn('status', ['draft'])
                ->count();
    
            $directPercentage = $totalSales > 0 ? round(($directSales / $totalSales) * 100) : 0;
            $poPercentage = $totalSales > 0 ? round(($poSales / $totalSales) * 100) : 0;
    
            return [
                'total' => $totalSales,
                'direct' => $directSales,
                'po' => $poSales,
                'direct_percentage' => $directPercentage,
                'po_percentage' => $poPercentage,
            ];
        }

    private function getTodayStats()
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();
        
        $transactions = SalesOrder::whereBetween('order_date', [$start, $end])
            ->whereNotIn('status', ['draft'])
            ->count();
            
        $revenue = Payment::whereBetween('paid_at', [$start, $end])
            ->sum('amount');
            
        $customers = SalesOrder::whereBetween('order_date', [$start, $end])
            ->whereNotIn('status', ['draft'])
            ->distinct('customer_id')
            ->count('customer_id');
            
        $avgTransaction = $transactions > 0 ? $revenue / $transactions : 0;

        return [
            'transactions' => $transactions,
            'revenue' => $revenue,
            'customers' => $customers,
            'avg_transaction' => round($avgTransaction)
        ];
    }

    private function getUrgentOrders()
    {
        $urgentOrders = SalesOrder::where('deadline', '>=', now()->startOfDay())
            ->where('deadline', '<=', now()->addDays(5)->endOfDay())
            ->whereNotIn('status', ['selesai', 'diterima_toko'])
            ->with('customer')
            ->orderBy('deadline', 'asc')
            ->limit(5)
            ->get();
    
        // DEBUG: Log data deadline
        \Log::info('Urgent Orders Debug', [
            'count' => $urgentOrders->count(),
            'orders' => $urgentOrders->map(function($order) {
                return [
                    'so_number' => $order->so_number,
                    'deadline' => $order->deadline,
                    'days_left' => \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($order->deadline)->startOfDay(), false),
                    'status' => $order->status
                ];
            })->toArray()
        ]);
    
        return $urgentOrders;
    }

    private function getPendingPayments() 
    {
        $pendingOrders = SalesOrder::where('payment_status', 'dp')
            ->with(['customer', 'payments'])
            ->orderBy('order_date', 'desc')
            ->limit(5)
            ->get();
    
        // Filter yang benar-benar masih ada sisa pembayaran
        return $pendingOrders->filter(function($order) {
            return $order->remaining_amount > 0;
        });
    }
}