<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\AdvertisementPerformance;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Payment;
use App\Models\Income;
use App\Models\Expense;
use App\Models\CashTransfer;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Filter tanggal untuk financial data (default: bulan ini)
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        
        // Filter bulan untuk iklan (default: bulan ini)
        $selectedMonth = $request->get('month', now()->format('Y-m'));
        
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        // === FINANCIAL OVERVIEW (Income Statement) ===
        $totalSales = SalesOrder::whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'draft')
            ->sum('grand_total') ?? 0;
            
        $manualIncome = Income::whereBetween('created_at', [$start, $end])->sum('amount') ?? 0;
        $omset = $totalSales + $manualIncome;
    
        $hpp = PurchaseOrder::whereBetween('created_at', [$start, $end])
            ->where('status', 'selesai')
            ->sum('grand_total') ?? 0;
    
        $operasional = Expense::whereBetween('created_at', [$start, $end])->sum('amount') ?? 0;
        $profit = $omset - $hpp - $operasional;
        
        $totalCashTransfer = CashTransfer::whereBetween('created_at', [$start, $end])->sum('amount') ?? 0;
        
        // === PAYMENT BREAKDOWN ===
        $salesBreakdown = SalesOrder::whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'draft')
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(grand_total) as total_omset,
                SUM(CASE WHEN payment_status = "lunas" THEN 1 ELSE 0 END) as lunas_count,
                SUM(CASE WHEN payment_status = "lunas" THEN grand_total ELSE 0 END) as lunas_amount,
                SUM(CASE WHEN payment_status = "dp" THEN 1 ELSE 0 END) as dp_count,
                SUM(CASE WHEN payment_status = "dp" THEN grand_total ELSE 0 END) as dp_amount,
                SUM(CASE WHEN payment_status IS NULL OR payment_status = "" THEN 1 ELSE 0 END) as belum_bayar_count,
                SUM(CASE WHEN payment_status IS NULL OR payment_status = "" THEN grand_total ELSE 0 END) as belum_bayar_amount
            ')
            ->first();
        
        // === PENDING PAYMENTS (PIUTANG) ===
        $pendingPayments = SalesOrder::where('payment_status', 'dp')
            ->with(['customer', 'payments'])
            ->orderBy('order_date', 'desc')
            ->limit(5)
            ->get()
            ->filter(function($order) {
                return $order->remaining_amount > 0;
            });
        $pendingPaymentsCount = $pendingPayments->count();
        $totalPiutang = $pendingPayments->sum('remaining_amount');
        
        // === PAYMENT METHODS ===
        $salesByPaymentMethod = Payment::whereBetween('paid_at', [$start, $end])
            ->selectRaw('method, SUM(amount) as total_amount, COUNT(*) as transaction_count')
            ->groupBy('method')
            ->get();
        
        // === DEADLINE ALERTS ===
        $overdueOrders = SalesOrder::where('deadline', '<', now()->startOfDay())
            ->whereNotIn('status', ['selesai', 'diterima_toko'])
            ->with('customer')
            ->orderBy('deadline', 'asc')
            ->limit(5)
            ->get();
        $overdueCount = $overdueOrders->count();
        
        $upcomingOrders = SalesOrder::where('deadline', '>=', now()->startOfDay())
            ->where('deadline', '<=', now()->addDays(5)->endOfDay())
            ->whereNotIn('status', ['selesai', 'diterima_toko'])
            ->with('customer')
            ->orderBy('deadline', 'asc')
            ->limit(5)
            ->get();
        $upcomingCount = $upcomingOrders->count();
        
        // === PERFORMANCE METRICS ===
        $todayStats = $this->getTodayStats();
        
        $salesTypeStats = $this->getSalesTypeStats($startDate, $endDate);
        
        // === BUSINESS INSIGHTS ===
        $recentSales = SalesOrder::with(['customer', 'payments'])
            ->whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'draft')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        $bestSellingProducts = \App\Models\SalesOrderItem::selectRaw('
                product_id,
                products.name as product_name,
                products.sku as product_sku,
                SUM(sales_order_items.qty) as total_terjual
            ')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->join('products', 'sales_order_items.product_id', '=', 'products.id')
            ->whereBetween('sales_orders.created_at', [$start, $end])
            ->where('sales_orders.status', 'selesai')
            ->groupBy('product_id', 'products.name', 'products.sku')
            ->orderBy('total_terjual', 'desc')
            ->limit(5)
            ->get();
        
        // Ambil data iklan menggunakan method helper dengan filter bulan
        $advertisementData = $this->getAdvertisementData($selectedMonth);
        
        // === ADVERTISEMENT PERFORMANCE DATA (pakai filter tanggal utama) ===
        $advertisementPerformanceData = $this->getAdvertisementPerformanceData($startDate, $endDate);
        
        return view('owner.dashboard', array_merge($advertisementData, $advertisementPerformanceData, [
            'selectedMonth' => $selectedMonth,
            'availableMonths' => $this->getAvailableMonths(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            // Financial Overview
            'omset' => $omset,
            'totalSales' => $totalSales,
            'manualIncome' => $manualIncome,
            'hpp' => $hpp,
            'operasional' => $operasional,
            'profit' => $profit,
            'totalCashTransfer' => $totalCashTransfer,
            // Payment Breakdown
            'salesBreakdown' => $salesBreakdown,
            // Pending Payments
            'pendingPayments' => $pendingPayments,
            'pendingPaymentsCount' => $pendingPaymentsCount,
            'totalPiutang' => $totalPiutang,
            // Payment Methods
            'salesByPaymentMethod' => $salesByPaymentMethod,
            // Deadline Alerts
            'overdueOrders' => $overdueOrders,
            'overdueCount' => $overdueCount,
            'upcomingOrders' => $upcomingOrders,
            'upcomingCount' => $upcomingCount,
            // Performance
            'todayStats' => $todayStats,
            'salesTypeStats' => $salesTypeStats,
            // Business Insights
            'recentSales' => $recentSales,
            'bestSellingProducts' => $bestSellingProducts,
        ]));
    }

    /**
     * Helper method untuk mengambil semua data iklan dengan filter bulan
     */
    protected function getAdvertisementData($selectedMonth = null)
    {
        if (!$selectedMonth) {
            $selectedMonth = now()->format('Y-m');
        }
        
        $currentMonth = $selectedMonth;
        $selectedDate = Carbon::createFromFormat('Y-m', $currentMonth);
        $daysInMonth = $selectedDate->daysInMonth;
        $currentDay = (now()->format('Y-m') === $currentMonth) ? now()->day : $daysInMonth;
        $previousMonth = $selectedDate->copy()->subMonth()->format('Y-m');
        
        // Data bulan ini untuk iklan
        $monthlyData = AdvertisementPerformance::where('date', 'like', "{$currentMonth}%")
            ->select('type', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('type')
            ->get()
            ->keyBy('type');
    
        $chatCount = $monthlyData['chat']->count ?? 0;
        $closingCount = $monthlyData['closing']->count ?? 0;
        $monthlyOmset = $monthlyData['closing']->total_amount ?? 0;
        $monthlySales = $monthlyOmset;
        // HPP diambil dari SEMUA pembelian (tidak hanya bulan yang sama)
        $monthlyHpp = PurchaseOrder::sum('grand_total');
        $grossProfit = $monthlySales - $monthlyHpp;
        $targetGrossProfit = 30000000;
        $grossProfitProgress = $targetGrossProfit > 0
            ? max(0, min(100, ($grossProfit / $targetGrossProfit) * 100))
            : 0;
        $grossProfitShortfall = max(0, $targetGrossProfit - $grossProfit);
    
        $monthlyProfit = $grossProfit;
        $daysLeft = $daysInMonth - $currentDay;
    
        // Conversion Rate
        $conversionRate = $chatCount > 0 ? ($closingCount / $chatCount) * 100 : 0;
        
        // Target Settings untuk iklan
        $conversionTarget = 50; // 50%
        $omsetTarget = 30000000; // 30jt
        $profitTarget = $targetGrossProfit;
        
        // Progress Calculation untuk iklan
        $conversionProgress = $conversionTarget > 0 ? min(100, ($conversionRate / $conversionTarget) * 100) : 0;
        $omsetProgress = $omsetTarget > 0 ? min(100, ($monthlyOmset / $omsetTarget) * 100) : 0;
        $profitProgress = $profitTarget > 0 ? min(100, ($monthlyProfit / $profitTarget) * 100) : 0;
    
        // Monthly projection untuk omset
        $projectedOmset = $currentDay > 0 ? ($monthlyOmset / $currentDay) * $daysInMonth : 0;
        $projectedProfit = $projectedOmset;
        $isOnTrackOmset = $projectedOmset >= $omsetTarget;
        $isOnTrack = $isOnTrackOmset;
    
        // === TARGET INVOICE (JUMLAH NOTA PEMBAYARAN) ===
        // Untuk bulan yang dipilih, cari bulan sebelumnya
        $previousMonthForSelected = $previousMonth;
        
        // Jumlah nota yang tercetak bulan sebelumnya dari bulan yang dipilih
        $previousMonthInvoiceCount = \App\Models\Payment::where('paid_at', 'like', "{$previousMonthForSelected}%")
            ->count();
        
        // Target bulan yang dipilih = jumlah nota bulan sebelumnya + 100% (jadi 200% dari bulan sebelumnya)
        $invoiceTarget = $previousMonthInvoiceCount * 2.0; // 200% = 2.0
        
        // Realisasi invoice bulan yang dipilih
        $currentMonthInvoiceCount = \App\Models\Payment::where('paid_at', 'like', "{$currentMonth}%")
            ->count();
        
        // Hitung hari untuk bulan yang dipilih
        $selectedDaysInMonth = $selectedDate->daysInMonth;
        $selectedCurrentDay = now()->format('Y-m') === $currentMonth ? now()->day : $selectedDaysInMonth;
        $selectedDaysLeft = $selectedDaysInMonth - $selectedCurrentDay;
        
        // Progress calculation untuk invoice
        $invoiceProgress = $invoiceTarget > 0 ? min(100, ($currentMonthInvoiceCount / $invoiceTarget) * 100) : 0;
        
        // Additional stats untuk invoice
        $remainingInvoiceTarget = max(0, $invoiceTarget - $currentMonthInvoiceCount);
        $remainingTarget = $remainingInvoiceTarget;
        $dailyInvoiceTargetNeeded = $selectedDaysLeft > 0 ? $remainingInvoiceTarget / $selectedDaysLeft : $remainingInvoiceTarget;
        $dailyTargetNeeded = $dailyInvoiceTargetNeeded;
        
        // Update currentDay dan daysInMonth untuk bulan yang dipilih
        $currentDay = $selectedCurrentDay;
        $daysInMonth = $selectedDaysInMonth;
        $daysLeft = $selectedDaysLeft;
        
        // Untuk backward compatibility
        $previousMonthInvoices = $previousMonthInvoiceCount;
        $currentMonthPayments = $currentMonthInvoiceCount;
        
        return [
            // Gross Profit Data
            'monthlySales' => $monthlySales,
            'monthlyHpp' => $monthlyHpp,
            'grossProfit' => $grossProfit,
            'targetGrossProfit' => $targetGrossProfit,
            'grossProfitProgress' => $grossProfitProgress,
            'grossProfitShortfall' => $grossProfitShortfall,
            'currentDay' => $currentDay,
            'daysInMonth' => $daysInMonth,
            'daysLeft' => $daysLeft,
            
            // Invoice Target Data
            'previousMonthInvoiceCount' => $previousMonthInvoiceCount,
            'invoiceTarget' => $invoiceTarget,
            'currentMonthInvoiceCount' => $currentMonthInvoiceCount,
            'invoiceProgress' => $invoiceProgress,
            'remainingInvoiceTarget' => $remainingInvoiceTarget,
            'dailyInvoiceTargetNeeded' => $dailyInvoiceTargetNeeded,
            
            // Additional data
            'chatCount' => $chatCount,
            'closingCount' => $closingCount,
            'monthlyOmset' => $monthlyOmset,
            'conversionRate' => $conversionRate,
        ];
    }

    /**
     * Get available months untuk filter (12 bulan terakhir + bulan depan)
     */
    protected function getAvailableMonths()
    {
        $months = [];
        // Bulan lalu (12 bulan)
        for ($i = 12; $i >= 1; $i--) {
            $months[] = now()->subMonths($i)->format('Y-m');
        }
        // Bulan ini
        $months[] = now()->format('Y-m');
        // Bulan depan
        $months[] = now()->addMonth()->format('Y-m');
        
        return $months;
    }

    /**
     * Get today's performance stats
     */
    protected function getTodayStats()
    {
        $today = now()->format('Y-m-d');
        
        $transactions = SalesOrder::whereDate('order_date', $today)
            ->whereNotIn('status', ['draft'])
            ->count();
            
        $revenue = Payment::whereDate('paid_at', $today)
            ->sum('amount');
            
        $customers = SalesOrder::whereDate('order_date', $today)
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

    /**
     * Get sales type statistics
     */
    protected function getSalesTypeStats($startDate, $endDate)
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

    /**
     * Get advertisement performance data dengan filter tanggal
     */
    protected function getAdvertisementPerformanceData($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        // Data untuk periode yang dipilih (EXCLUDE record validasi kosong)
        $periodData = AdvertisementPerformance::whereBetween('date', [$start, $end])
            ->where('description', '!=', 'Tidak ada aktivitas hari ini')
            ->select('type', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('type')
            ->get()
            ->keyBy('type');
        
        $chatCount = $periodData['chat']->count ?? 0;
        $followupCount = $periodData['followup']->count ?? 0;
        $closingCount = $periodData['closing']->count ?? 0;
        $closingAmount = $periodData['closing']->total_amount ?? 0;
        
        // Data untuk chart (per hari dalam range) - EXCLUDE record validasi kosong
        $chartData = AdvertisementPerformance::whereBetween('date', [$start, $end])
            ->where('description', '!=', 'Tidak ada aktivitas hari ini')
            ->select('date', 'type', DB::raw('COUNT(*) as count'))
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get();
        
        // Format chart data
        $formattedChartData = [];
        $dates = [];
        
        $currentDate = $start->copy();
        while ($currentDate <= $end) {
            $dateStr = $currentDate->format('Y-m-d');
            $dates[] = $dateStr;
            $formattedChartData[$dateStr] = [
                'chat' => 0,
                'followup' => 0,
                'closing' => 0
            ];
            $currentDate->addDay();
        }
        
        foreach ($chartData as $data) {
            $dateStr = $data->date->format('Y-m-d');
            if (isset($formattedChartData[$dateStr])) {
                $formattedChartData[$dateStr][$data->type] = $data->count;
            }
        }
        
        // Cek apakah ada data aktual (bukan hanya validasi kosong) untuk periode ini
        $hasActualData = AdvertisementPerformance::whereBetween('date', [$start, $end])
            ->where('description', '!=', 'Tidak ada aktivitas hari ini')
            ->exists();
        
        return [
            'advertisementStartDate' => $startDate,
            'advertisementEndDate' => $endDate,
            'advertisementChatCount' => $chatCount,
            'advertisementFollowupCount' => $followupCount,
            'advertisementClosingCount' => $closingCount,
            'advertisementClosingAmount' => $closingAmount,
            'advertisementChartData' => $formattedChartData,
            'advertisementChartDates' => $dates,
            'advertisementHasActualData' => $hasActualData,
        ];
    }
}

