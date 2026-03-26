<?php

namespace App\Http\Controllers\KepalaToko;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\SalesOrder;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Ambil filter tanggal dari request
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $categoryId = $request->get('category_id'); // GET SELECTED CATEGORY ID

        // Status shift aktif
        $activeShift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
        
        // Hitung data shift
        $shiftData = $this->calculateShiftData($activeShift);
        
        // Today's stats
        $todayStats = $this->getTodayStats();
        
        // Deadline alerts - real-time
        $overdueOrders = $this->getOverdueOrders();
        $upcomingOrders = $this->getUpcomingOrders();
        $overdueCount = $overdueOrders->count();
        $upcomingCount = $upcomingOrders->count();
        
        // Pending payments - real-time
        $pendingPayments = $this->getPendingPayments();
        $pendingPaymentsCount = $pendingPayments->count();
        $totalPiutang = $pendingPayments->sum('remaining_amount');

        // Pending approvals
        $pendingStockOpnames = $this->getPendingStockOpnames();
        $pendingPurchaseOrders = $this->getPendingPurchaseOrders();
        $pendingApprovalsCount = $pendingStockOpnames->count() + $pendingPurchaseOrders->count();

        // Low stock products
        $lowStockProducts = $this->getLowStockProducts();
        $lowStockCount = $lowStockProducts->count();

        // Recent transactions
        $recentSales = $this->getRecentSales();

        // Sales type stats
        $salesTypeStats = $this->getSalesTypeStats($startDate, $endDate);

        // === CATEGORY BREAKDOWN (Optimasi: Tarik 1x kueri besar, lalu filter di PHP) ===
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        $topItemsForCategories = \App\Models\SalesOrderItem::query()
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->leftJoin('products', 'sales_order_items.product_id', '=', 'products.id')
            ->whereBetween('sales_orders.created_at', [$start, $end])
            ->where('sales_orders.status', '!=', 'draft')
            ->selectRaw('
                sales_order_items.product_id,
                sales_order_items.product_name,
                products.sku as product_sku,
                SUM(sales_order_items.qty) as total_terjual
            ')
            ->groupBy('sales_order_items.product_id', 'sales_order_items.product_name', 'products.sku')
            ->orderBy('total_terjual', 'desc')
            ->limit(100)
            ->get();

        $filterByKeywords = function($items, $keywords) {
            return $items->filter(function($item) use ($keywords) {
                $name = strtolower($item->product_name);
                if (is_callable($keywords)) return $keywords($name);
                foreach ($keywords as $kw) {
                    if (str_contains($name, strtolower($kw))) return true;
                }
                return false;
            })->take(10)->values();
        };

        $catKaosPolos = $filterByKeywords($topItemsForCategories, function($name) {
            return str_contains($name, 'kaos polos') && (str_contains($name, '20s') || str_contains($name, '24s') || str_contains($name, '30s'));
        });
        $catKaosPolo = $filterByKeywords($topItemsForCategories, function($name) {
            return (str_contains($name, 'kaos polo') && !str_contains($name, 'kaos polos')) || str_contains($name, 'lacos');
        });
        $catJaket = $filterByKeywords($topItemsForCategories, ['Jaket', 'Varsity', 'Hoodie', 'Zipper', 'Sweater', 'Hodpol']);
        $catJersey = $filterByKeywords($topItemsForCategories, ['Jersey', 'Milano', 'Benzema', 'Bintik', 'Emboss', 'Dropnadle', 'Dropneedle', 'Airwalk', 'Rabbit', 'Keramik']);
        $catTopi = $filterByKeywords($topItemsForCategories, ['Topi', 'Jaring', 'Kanvas', 'Baseball']);

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
                'totalPiutang' => $totalPiutang,
                'pendingStockOpnames' => $pendingStockOpnames,
                'pendingPurchaseOrders' => $pendingPurchaseOrders,
                'pendingApprovalsCount' => $pendingApprovalsCount,
                'lowStockProducts' => $lowStockProducts,
                'lowStockCount' => $lowStockCount,
                'recentSales' => $recentSales,
                'salesTypeStats' => $salesTypeStats,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'financialChartData' => $this->getFinancialChartData($startDate, $endDate),
                'bestSellingProducts' => $this->getBestSellingProducts($startDate, $endDate),
                'catKaosPolos' => $catKaosPolos,
                'catKaosPolo' => $catKaosPolo,
                'catJaket' => $catJaket,
                'catJersey' => $catJersey,
                'catTopi' => $catTopi,
            ],
            $shiftData
        );

        return view('kepala-toko.dashboard', $data);
    }

    private function calculateShiftData($shift)
    {
        // Default values
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

            // Hitung tunai di laci
            $realCashTotal = $this->calculateRealCashTotal($shift);
            $totalCashTransfers = \App\Models\CashTransfer::where('shift_id', $shift->id)->sum('amount');
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

        $totalIncome = \App\Models\Income::where('shift_id', $shift->id)->sum('amount');
        
        return $totalCashFromPayments + $totalIncome;
    }

    private function getTodayStats()
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();
        
        $transactions = SalesOrder::whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'draft')
            ->count();
            
        $revenue = Payment::whereBetween('paid_at', [$start, $end])
            ->sum('amount');
            
        $customers = SalesOrder::whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'draft')
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

    private function getOverdueOrders()
    {
        return SalesOrder::where('deadline', '<', now()->startOfDay())
            ->whereNotIn('status', ['selesai', 'diterima_toko'])
            ->with('customer')
            ->orderBy('deadline', 'asc')
            ->limit(5)
            ->get();
    }

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

    private function getPendingPayments() 
    {
        $pendingOrders = SalesOrder::where('payment_status', 'dp')
            ->with(['customer', 'payments'])
            ->orderBy('order_date', 'desc')
            ->limit(5)
            ->get();
    
        return $pendingOrders->filter(function($order) {
            return $order->remaining_amount > 0;
        });
    }

    private function getPendingStockOpnames()
    {
        return StockOpname::where('status', 'pending')
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    private function getPendingPurchaseOrders()
    {
        return PurchaseOrder::where('status', 'pending')
            ->whereNull('approved_by')
            ->with(['supplier', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    private function getLowStockProducts()
    {
        return Product::where('is_active', true)
            ->where('stock_qty', '<', 10)
            ->orderBy('stock_qty', 'asc')
            ->limit(10)
            ->get();
    }

    private function getRecentSales()
    {
        return SalesOrder::whereNotIn('status', ['draft'])
            ->with('customer')
            ->orderBy('order_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

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

    private function getFinancialChartData($startDate, $endDate)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // 1. Get Daily Sales (Omset) & Invoice Count
        $dailySales = SalesOrder::whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'draft')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(grand_total) as omset'),
                DB::raw('COUNT(*) as invoices')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // 2. Get Daily HPP
        $dailyHpp = \App\Models\SalesOrderItem::join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->leftJoin('products', 'sales_order_items.product_id', '=', 'products.id')
            ->whereBetween('sales_orders.created_at', [$start, $end])
            ->where('sales_orders.status', '!=', 'draft')
            ->select(
                DB::raw('DATE(sales_orders.created_at) as date'),
                DB::raw('SUM(COALESCE(sales_order_items.cost_price, products.cost_price, 0) * sales_order_items.qty) as hpp')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // 3. Prepare Chart Data
        $labels = [];
        $omsetData = [];
        $hppData = [];
        $profitData = [];
        $invoiceData = [];

        $current = $start->copy();
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $labels[] = $current->format('d M');

            $daySales = $dailySales[$dateStr] ?? null;
            $omset = $daySales->omset ?? 0;
            $invoices = $daySales->invoices ?? 0;
            $hpp = $dailyHpp[$dateStr]->hpp ?? 0;
            $profit = $omset - $hpp;

            $omsetData[] = (float)$omset;
            $hppData[] = (float)$hpp;
            $profitData[] = (float)$profit;
            $invoiceData[] = (int)$invoices;

            $current->addDay();
        }

        return [
            'labels' => $labels,
            'omset' => $omsetData,
            'hpp' => $hppData,
            'profit' => $profitData,
            'invoices' => $invoiceData,
            'total_omset' => array_sum($omsetData),
            'total_hpp' => array_sum($hppData),
            'total_profit' => array_sum($profitData),
            'total_invoices' => array_sum($invoiceData),
        ];
    }

    private function getBestSellingProducts($startDate, $endDate)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        return \App\Models\SalesOrderItem::join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->select(
                'sales_order_items.product_name',
                DB::raw('SUM(sales_order_items.qty) as total_qty')
            )
            ->whereBetween('sales_orders.created_at', [$start, $end])
            ->where('sales_orders.status', '!=', 'draft')
            // Exclude DTF and Spunbond (case insensitive)
            ->where(function($q) {
                $q->where('sales_order_items.product_name', 'NOT LIKE', '%DTF%')
                  ->where('sales_order_items.product_name', 'NOT LIKE', '%dtf%')
                  ->where('sales_order_items.product_name', 'NOT LIKE', '%Spunbond%')
                  ->where('sales_order_items.product_name', 'NOT LIKE', '%spunbond%')
                  ->where('sales_order_items.product_name', 'NOT LIKE', '%Spunbound%')
                  ->where('sales_order_items.product_name', 'NOT LIKE', '%spunbound%');
            })
            ->groupBy('sales_order_items.product_name')
            ->orderBy('total_qty', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Helper to get best selling products with custom query callback
     */
    private function getBestSellingByKeywordQuery($startDate, $endDate, callable $callback)
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

