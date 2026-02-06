<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
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
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        $categoryId = $request->get('category_id'); // GET SELECTED CATEGORY ID

        // Ambil filter bulan untuk iklan (default: bulan ini)
        $selectedMonth = $request->get('advertisement_month', now()->format('Y-m'));

        // Pastikan rentang mencakup seluruh hari (00:00 s.d. 23:59)
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // 2. HITUNG OMSET - Total Penjualan + Pemasukan Manual (EXCLUDE DRAFT)
        $totalSales = SalesOrder::whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'draft') // ✅ EXCLUDE DRAFT
            ->sum('grand_total') ?? 0;

        $manualIncome = Income::whereBetween('created_at', [$start, $end])->sum('amount') ?? 0;
        $omset = $totalSales + $manualIncome;

        // 3. HITUNG HPP - Dari harga modal item penjualan (cost_price × qty) - LOCKED/SNAPSHOT
        // HPP = SUM(sales_order_items.cost_price × sales_order_items.qty)
        // Menggunakan cost_price yang disimpan di item (snapshot saat transaksi)
        // Fallback ke products.cost_price jika cost_price NULL (untuk data lama)
        $hpp = SalesOrderItem::join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->leftJoin('products', 'sales_order_items.product_id', '=', 'products.id')
            ->where('sales_orders.status', '!=', 'draft')
            ->whereBetween('sales_orders.created_at', [$start, $end])
            ->sum(DB::raw('COALESCE(sales_order_items.cost_price, products.cost_price, 0) * sales_order_items.qty')) ?? 0;

        // 3b. GROSS PROFIT - Sesuai kartu Omset (Omset = totalSales + manualIncome)
        // Gross Profit = Omset - HPP
        // Gunakan $omset agar pemasukan manual ikut diperhitungkan
        $grossProfit = $omset - $hpp;

        // Hitung progress dan shortfall untuk Target Gross Profit (jika diperlukan)
        // Tapi untuk Target Gross Profit di bagian iklan, tetap pakai yang dari getAdvertisementData

        // 4. HITUNG OPERASIONAL - Pengeluaran Manual
        $operasional = Expense::whereBetween('created_at', [$start, $end])->sum('amount') ?? 0;

        // 4b. RINCIAN OPERASIONAL - Detail pengeluaran dengan nominal dan keterangan
        // 4b. RINCIAN OPERASIONAL - Detail pengeluaran dengan nominal dan keterangan
        $operasionalDetails = Expense::whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')
            ->select(['id', 'amount', 'description', 'created_at'])
            ->paginate(10)
            ->withQueryString();

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
                sales_order_items.product_name,
                products.sku as product_sku,
                SUM(sales_order_items.qty) as total_terjual
            ')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->leftJoin('products', 'sales_order_items.product_id', '=', 'products.id')
            ->whereBetween('sales_orders.created_at', [$start, $end])
            ->where('sales_orders.status', '!=', 'draft')
            ->where(function($q) {
                $q->where('sales_order_items.product_name', 'NOT LIKE', '%DTF%')
                  ->where('sales_order_items.product_name', 'NOT LIKE', '%dtf%')
                  ->where('sales_order_items.product_name', 'NOT LIKE', '%Spunbond%')
                  ->where('sales_order_items.product_name', 'NOT LIKE', '%spunbond%')
                  ->where('sales_order_items.product_name', 'NOT LIKE', '%Spunbound%')
                  ->where('sales_order_items.product_name', 'NOT LIKE', '%spunbound%');
            })
            ->groupBy('product_id', 'sales_order_items.product_name', 'products.sku')
            ->orderBy('total_terjual', 'desc')
            ->limit(10)
            ->get();

        $omsetGrowth = 0; // Sementara 0 dulu

        // Ambil data iklan menggunakan method helper dengan filter bulan
        $advertisementData = $this->getAdvertisementData($selectedMonth);

        // === ADVERTISEMENT PERFORMANCE DATA (pakai filter tanggal utama) ===
        $advertisementPerformanceData = $this->getAdvertisementPerformanceData($startDate, $endDate);

        // === DEADLINE ALERTS ===
        $overdueOrders = SalesOrder::where('deadline', '<', now()->startOfDay())
            ->whereNotIn('status', ['selesai', 'diterima_toko', 'cancel', 'draft'])
            ->with('customer')
            ->orderBy('deadline', 'asc')
            ->limit(5)
            ->get();
        $overdueCount = $overdueOrders->count();

        $upcomingOrders = SalesOrder::where('deadline', '>=', now()->startOfDay())
            ->where('deadline', '<=', now()->addDays(5)->endOfDay())
            ->whereNotIn('status', ['selesai', 'diterima_toko', 'cancel', 'draft'])
            ->with('customer')
            ->orderBy('deadline', 'asc')
            ->limit(5)
            ->get();
        $upcomingCount = $upcomingOrders->count();

        // === 1. KAOS POLOS (Kaos Polos + 20s/24s/30s) ===
        $kaosPolos = $this->getBestSellingByKeywordQuery($startDate, $endDate, function($q) {
            $q->where('sales_order_items.product_name', 'LIKE', '%Kaos Polos%')
              ->where(function($sub) {
                  $sub->where('sales_order_items.product_name', 'LIKE', '%20s%')
                      ->orWhere('sales_order_items.product_name', 'LIKE', '%24s%')
                      ->orWhere('sales_order_items.product_name', 'LIKE', '%30s%');
              });
        });

        // === 2. KAOS POLO (Kaos Polo/Lacos/24s - Exclude 'Polos' to avoid overlap) ===
        $kaosPolo = $this->getBestSellingByKeywordQuery($startDate, $endDate, function($q) {
            $q->where(function($sub) {
                // Logic: (Kaos Polo AND NOT Kaos Polos) OR Lacos
                $sub->where(function($k) {
                    $k->where('sales_order_items.product_name', 'LIKE', '%Kaos Polo%')
                      ->where('sales_order_items.product_name', 'NOT LIKE', '%Kaos Polos%');
                })
                ->orWhere('sales_order_items.product_name', 'LIKE', '%Lacos%');
                // Removed loose '24s' to prevent Kaos Polos 24s from entering here
            });
        });

        // === 3. JAKET (Jaket, Varsity, Hoodie, Zipper, Sweater, Hodpol) ===
        $jaket = $this->getBestSellingByKeywordQuery($startDate, $endDate, function($q) {
            $q->where(function($sub) {
                $keywords = ['Jaket', 'Varsity', 'Hoodie', 'Zipper', 'Sweater', 'Hodpol'];
                foreach ($keywords as $key) {
                    $sub->orWhere('sales_order_items.product_name', 'LIKE', '%' . $key . '%');
                }
            });
        });

        // === 4. JERSEY (Jersey, Milano, Benzema, Bintik, Emboss, Dropnadle, Airwalk, Rabbit, Keramik) ===
        $jersey = $this->getBestSellingByKeywordQuery($startDate, $endDate, function($q) {
            $q->where(function($sub) {
                // Added specific keywords from user request + 'Dropneedle' just in case
                $keywords = ['Jersey', 'Milano', 'Benzema', 'Bintik', 'Emboss', 'Dropnadle', 'Dropneedle', 'Airwalk', 'Rabbit', 'Keramik'];
                foreach ($keywords as $key) {
                    $sub->orWhere('sales_order_items.product_name', 'LIKE', '%' . $key . '%');
                }
            });
        });

        // === 5. TOPI (Topi, Jaring, Kanvas, Baseball) ===
        $topi = $this->getBestSellingByKeywordQuery($startDate, $endDate, function($q) {
            $q->where(function($sub) {
                $keywords = ['Topi', 'Jaring', 'Kanvas', 'Baseball'];
                foreach ($keywords as $key) {
                    $sub->orWhere('sales_order_items.product_name', 'LIKE', '%' . $key . '%');
                }
            });
        });

        // 7. KIRIM SEMUA DATA KE VIEW
        // Pastikan data dasar (omset, hpp, grossProfit) tidak dioverride oleh advertisementData
        // Untuk kartu "GROSS PROFIT" (ringkasan keuangan), pakai $dashboardGrossProfit (dari filter tanggal)
        // Untuk "Target Gross Profit", pakai $grossProfit dari advertisementData (dari closing amount bulan yang dipilih)
        return view('finance.dashboard', array_merge(
            $advertisementData, // ✅ Ini berisi grossProfit, grossProfitProgress, grossProfitShortfall dari monthlySales - monthlyHpp
            $advertisementPerformanceData,
            [
                'omset' => $omset,
                'totalSales' => $totalSales,
                'manualIncome' => $manualIncome,
                'hpp' => $hpp,
                'dashboardGrossProfit' => $grossProfit, // ✅ Gross Profit untuk kartu ringkasan (dari filter tanggal)
                // $grossProfit dari advertisementData tetap digunakan untuk Target Gross Profit
                'operasional' => $operasional,
                'operasionalDetails' => $operasionalDetails,
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
                'categories' => [], // Keep empty or remove if view doesn't break
                'selectedCategoryId' => null, // Remove logic
                // New Categories Variables
                'catKaosPolos' => $kaosPolos,
                'catKaosPolo' => $kaosPolo,
                'catJaket' => $jaket,
                'catJersey' => $jersey,
                'catTopi' => $topi,
                // Deadline Alerts
                'overdueOrders' => $overdueOrders,
                'overdueCount' => $overdueCount,
                'upcomingOrders' => $upcomingOrders,
                'upcomingCount' => $upcomingCount,
            ]
        ));
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

        // HPP dihitung dari cost_price × qty dari sales order items bulan ini - LOCKED/SNAPSHOT
        // Menggunakan cost_price yang disimpan di item (snapshot saat transaksi)
        // Fallback ke products.cost_price jika cost_price NULL (untuk data lama)
        $monthlyHpp = SalesOrderItem::join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->leftJoin('products', 'sales_order_items.product_id', '=', 'products.id')
            ->where('sales_orders.status', '!=', 'draft')
            ->where('sales_orders.created_at', 'like', "{$currentMonth}%")
            ->sum(DB::raw('COALESCE(sales_order_items.cost_price, products.cost_price, 0) * sales_order_items.qty')) ?? 0;

        // REVISI: Hitung Monthly Sales dari Real Data (SalesOrder) agar Gross Profit akurat
        // Sebelumnya mengambil dari advertisement closing yang mungkin tidak lengkap/input manual
        $realSales = SalesOrder::where('status', '!=', 'draft')
            ->where('created_at', 'like', "{$currentMonth}%")
            ->sum('grand_total') ?? 0;

        $realManualIncome = Income::where('created_at', 'like', "{$currentMonth}%")
            ->sum('amount') ?? 0;

        $monthlySales = $realSales + $realManualIncome;

        // Gross Profit = Sales (dari Real Sales) - HPP
        // Pastikan tipe data float untuk perhitungan yang akurat
        $monthlySales = (float) ($monthlySales ?? 0);
        $monthlyHpp = (float) ($monthlyHpp ?? 0);
        $grossProfit = $monthlySales - $monthlyHpp;
        $targetGrossProfit = 30000000.0;

        // Progress: hitung persentase dari target (realisasi / target * 100)
        // Progress dibatasi antara 0-100% untuk tampilan (tidak bisa negatif atau lebih dari 100%)
        // Jika gross profit negatif, progress = 0% (karena tidak mungkin negatif)
        // Jika gross profit positif, hitung persentase dari target
        if ($targetGrossProfit > 0) {
            $calculatedProgress = ($grossProfit / $targetGrossProfit) * 100.0;
            // Batasi progress antara 0-100% untuk tampilan
            $grossProfitProgress = max(0.0, min(100.0, $calculatedProgress));
        } else {
            $grossProfitProgress = 0.0;
        }

        // Shortfall: selisih antara target dengan realisasi
        // Jika gross profit negatif, shortfall = target (karena sudah rugi, berarti shortfall penuh)
        // Jika gross profit positif tapi belum mencapai target, shortfall = target - gross profit
        if ($grossProfit < 0) {
            // Jika rugi, shortfall = target penuh (karena kita sudah rugi, berarti shortfall = target)
            $grossProfitShortfall = $targetGrossProfit;
        } else {
            // Jika untung tapi belum mencapai target
            $grossProfitShortfall = max(0.0, $targetGrossProfit - $grossProfit);
        }

        // Debug log untuk troubleshooting
        \Log::info('Target Gross Profit Calculation', [
            'currentMonth' => $currentMonth,
            'monthlySales' => $monthlySales,
            'monthlyHpp' => $monthlyHpp,
            'grossProfit' => $grossProfit,
            'targetGrossProfit' => $targetGrossProfit,
            'grossProfitProgress' => $grossProfitProgress,
            'grossProfitShortfall' => $grossProfitShortfall,
            'grossProfit_type' => gettype($grossProfit),
            'grossProfit_is_positive' => $grossProfit > 0,
        ]);

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
        $previousMonthInvoiceCount = SalesOrder::where('created_at', 'like', "{$previousMonth}%")
            ->where('status', '!=', 'draft')
            ->count();

        // Target bulan yang dipilih = jumlah nota bulan sebelumnya + 100% (jadi 200% dari bulan sebelumnya)
        $invoiceTarget = $previousMonthInvoiceCount * 2.0; // 200% = 2.0

        // Realisasi invoice bulan yang dipilih
        $currentMonthInvoiceCount = SalesOrder::where('created_at', 'like', "{$currentMonth}%")
            ->where('status', '!=', 'draft')
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

        // Flag data iklan
        $hasActualData = AdvertisementPerformance::whereBetween('date', [$start, $end])
            ->where('description', '!=', 'Tidak ada aktivitas hari ini')
            ->exists();
        $hasValidatedEmpty = AdvertisementPerformance::whereBetween('date', [$start, $end])
            ->where('description', 'Tidak ada aktivitas hari ini')
            ->exists();
        $hasAnyData = AdvertisementPerformance::whereBetween('date', [$start, $end])->exists();

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
            'advertisementHasValidatedEmpty' => $hasValidatedEmpty,
            'advertisementHasAnyData' => $hasAnyData,
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

    /**
     * AJAX Handler for Expenses Pagination
     */
    public function expensesPagination(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $operasionalDetails = Expense::whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')
            ->select(['id', 'amount', 'description', 'created_at'])
            ->paginate(10)
            ->withQueryString();
            
        // Calculate total for footer (always total of filtered range, not just page)
        $operasional = Expense::whereBetween('created_at', [$start, $end])->sum('amount') ?? 0;

        return view('finance.partials.expenses_table', [
            'operasionalDetails' => $operasionalDetails,
            'operasional' => $operasional,
            'showTotal' => true
        ]);
    }

    /**
     * Get best selling products filtered by category
     */
    /**
     * Helper to get best selling products with custom query callback
     */
    protected function getBestSellingByKeywordQuery($startDate, $endDate, callable $callback)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        return \App\Models\SalesOrderItem::selectRaw('
                product_id,
                sales_order_items.product_name,
                products.sku as product_sku,
                SUM(sales_order_items.qty) as total_terjual
            ')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->leftJoin('products', 'sales_order_items.product_id', '=', 'products.id')
            ->whereBetween('sales_orders.created_at', [$start, $end])
            ->where('sales_orders.status', '!=', 'draft')
            ->where($callback) // Apply the specific logic
            ->groupBy('product_id', 'sales_order_items.product_name', 'products.sku')
            ->orderBy('total_terjual', 'desc')
            ->limit(10)
            ->get();
    }
}