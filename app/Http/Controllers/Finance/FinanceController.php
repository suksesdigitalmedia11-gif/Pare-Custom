<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\PurchaseOrder;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\AdvertisementPerformance;
use App\Models\ShiftAutoClose;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    /**
     * Dashboard Utama Finance - Income Statement
     */
    public function dashboard(Request $request): View
    {
        // 1. TANGGAL - PASTIKAN SELALU ADA
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        
        // Ambil filter bulan untuk iklan (default: bulan ini)
        $selectedMonth = $request->get('advertisement_month', now()->format('Y-m'));
        
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
    
        // 2. HITUNG OMSET - Total Penjualan + Pemasukan Manual (EXCLUDE DRAFT)
        $totalSales = SalesOrder::whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'draft') // ✅ EXCLUDE DRAFT
            ->sum('grand_total') ?? 0;
            
        $manualIncome = Income::whereBetween('created_at', [$start, $end])->sum('amount') ?? 0;
        $omset = $totalSales + $manualIncome;
    
        // 3. HITUNG HPP - Total Pembelian SELESAI
        $hpp = PurchaseOrder::whereBetween('created_at', [$start, $end])
            ->where('status', 'selesai')
            ->sum('grand_total') ?? 0;

        // 3b. HPP TERKAIT PENJUALAN (untuk Gross Profit khusus)
        // Menghitung hanya purchase order yang terhubung ke sales (punya sales_order_id)
        // dan status selesai, dengan patokan tanggal purchase order (order_date).
        $linkedHpp = PurchaseOrder::whereNotNull('sales_order_id')
            ->where('status', 'selesai')
            ->whereBetween('order_date', [$startDate, $endDate])
            ->sum('grand_total') ?? 0;
        $grossProfitLinked = $totalSales - $linkedHpp;
    
        // 4. HITUNG OPERASIONAL - Pengeluaran Manual
        $operasional = Expense::whereBetween('created_at', [$start, $end])->sum('amount') ?? 0;
    
        // 5. HITUNG PROFIT
        $profit = $omset - $hpp - $operasional;
    
        // 6. DATA TAMBAHAN 
        $salesByPaymentMethod = Payment::whereBetween('paid_at', [$start, $end])
            ->selectRaw('method, SUM(amount) as total_amount, COUNT(*) as transaction_count')
            ->groupBy('method')
            ->get();
    
        // ✅ FIX: Recent Sales EXCLUDE DRAFT
        $recentSales = SalesOrder::with(['customer', 'payments'])
            ->whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'draft') // ✅ EXCLUDE DRAFT
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    
        // ✅ NEW: BREAKDOWN STATUS PEMBAYARAN
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
    
        // ✅ NEW: PELUNASAN (Bayar Bertahap)
        $pelunasanData = Payment::whereBetween('paid_at', [$start, $end])
            ->where('category', 'pelunasan')
            ->selectRaw('COUNT(*) as count, SUM(amount) as amount')
            ->first();
    
        // === PRODUK TERLARIS - SIMPLE VERSION ===
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
    
        $omsetGrowth = 0; // Sementara 0 dulu
        
        // Ambil data iklan menggunakan method helper dengan filter bulan
        $advertisementData = $this->getAdvertisementData($selectedMonth);
        
        // === ADVERTISEMENT PERFORMANCE DATA (pakai filter tanggal utama) ===
        $advertisementPerformanceData = $this->getAdvertisementPerformanceData($startDate, $endDate);
    
        // 7. KIRIM SEMUA DATA KE VIEW
        return view('finance.dashboard', array_merge([
            'omset' => $omset,
            'totalSales' => $totalSales,
            'manualIncome' => $manualIncome,
            'hpp' => $hpp,
            'linkedHpp' => $linkedHpp,
            'grossProfitLinked' => $grossProfitLinked,
            'operasional' => $operasional,
            'profit' => $profit,
            'salesByPaymentMethod' => $salesByPaymentMethod,
            'recentSales' => $recentSales,
            'omsetGrowth' => $omsetGrowth,
            'startDate' => $startDate,
            'bestSellingProducts' => $bestSellingProducts,
            'endDate' => $endDate,
            'start' => $start,
            'end' => $end,
            // ✅ NEW DATA:
            'salesBreakdown' => $salesBreakdown,
            'pelunasanData' => $pelunasanData,
            'selectedMonth' => $selectedMonth,
            'availableMonths' => $this->getAvailableMonths(),
        ], $advertisementData, $advertisementPerformanceData));
    }

    /**
     * Index page - redirect to dashboard
     */
    public function index(): View
    {
        return $this->dashboard(new Request());
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
        // Jumlah nota yang tercetak bulan sebelumnya dari bulan yang dipilih
        $previousMonthInvoiceCount = Payment::where('paid_at', 'like', "{$previousMonth}%")
            ->count();
        
        // Target bulan yang dipilih = jumlah nota bulan sebelumnya + 100% (jadi 200% dari bulan sebelumnya)
        $invoiceTarget = $previousMonthInvoiceCount * 2.0; // 200% = 2.0
        
        // Realisasi invoice bulan yang dipilih
        $currentMonthInvoiceCount = Payment::where('paid_at', 'like', "{$currentMonth}%")
            ->count();
        
        // Progress calculation untuk invoice
        $invoiceProgress = $invoiceTarget > 0 ? min(100, ($currentMonthInvoiceCount / $invoiceTarget) * 100) : 0;
        
        // Additional stats untuk invoice
        $remainingInvoiceTarget = max(0, $invoiceTarget - $currentMonthInvoiceCount);
        $remainingTarget = $remainingInvoiceTarget;
        $dailyInvoiceTargetNeeded = $daysLeft > 0 ? $remainingInvoiceTarget / $daysLeft : $remainingInvoiceTarget;
        $dailyTargetNeeded = $dailyInvoiceTargetNeeded;
        
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

    /**
     * Show list of auto-closed shifts that need approval
     */
    public function shiftAutoCloses(): View
    {
        $autoCloses = ShiftAutoClose::with(['shift.user', 'approver'])
            ->orderBy('auto_closed_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('finance.shift-auto-closes', compact('autoCloses'));
    }

    /**
     * Approve auto-closed shift (unblock admin login)
     */
    public function approveShiftAutoClose($id): RedirectResponse
    {
        $autoClose = ShiftAutoClose::findOrFail($id);
        
        if (!$autoClose->is_blocked) {
            return back()->with('error', 'Shift ini sudah di-approve sebelumnya.');
        }
        
        $autoClose->update([
            'is_blocked' => false,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
        
        return back()->with('success', 'Shift auto-close telah di-approve. Login admin telah diaktifkan kembali.');
    }
}