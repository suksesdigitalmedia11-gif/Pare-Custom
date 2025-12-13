<?php

namespace App\Http\Controllers\KepalaToko;

use App\Http\Controllers\Controller;
use App\Models\AdvertisementPerformance;
use App\Models\PurchaseOrder;
use App\Models\SalesOrderItem;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdvertisementPerformanceController extends Controller
{
    public function index()
    {
        $today = today()->format('Y-m-d');
        
        // Data untuk hari ini (EXCLUDE record validasi kosong)
        $todayData = AdvertisementPerformance::today()
            ->where('description', '!=', 'Tidak ada aktivitas hari ini')
            ->select('type', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('type')
            ->get()
            ->keyBy('type');
    
        // Data untuk chart (7 hari terakhir) - EXCLUDE record validasi kosong
        $chartData = AdvertisementPerformance::where('date', '>=', today()->subDays(7))
            ->where('description', '!=', 'Tidak ada aktivitas hari ini')
            ->select('date', 'type', DB::raw('COUNT(*) as count'))
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get();
    
        // Format chart data
        $formattedChartData = [];
        $dates = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i)->format('Y-m-d');
            $dates[] = $date;
            $formattedChartData[$date] = [
                'chat' => 0,
                'followup' => 0,
                'closing' => 0
            ];
        }
    
        foreach ($chartData as $data) {
            $date = $data->date->format('Y-m-d');
            if (isset($formattedChartData[$date])) {
                $formattedChartData[$date][$data->type] = $data->count;
            }
        }
    
        // === TARGET CONVERSION & OMSEt ===
        $currentMonth = now()->format('Y-m');
        $daysInMonth = now()->daysInMonth;
        $currentDay = now()->day;
        
        // Data bulan ini untuk iklan (EXCLUDE record validasi kosong)
        $monthlyData = AdvertisementPerformance::where('date', 'like', "{$currentMonth}%")
            ->where('description', '!=', 'Tidak ada aktivitas hari ini')
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
        
        $grossProfit = $monthlySales - $monthlyHpp;
        $targetGrossProfit = 30000000;
        $grossProfitProgress = $targetGrossProfit > 0
            ? max(0, min(100, ($grossProfit / $targetGrossProfit) * 100))
            : 0;
        $grossProfitShortfall = max(0, $targetGrossProfit - $grossProfit);
    
        // === TAMBAH INI ===
        $monthlyProfit = $grossProfit;
        $daysLeft = $daysInMonth - $currentDay; // Define variable yang missing
    
        // Conversion Rate
        $conversionRate = $chatCount > 0 ? ($closingCount / $chatCount) * 100 : 0;
        
        // Target Settings untuk iklan
        $conversionTarget = 50; // 50%
        $omsetTarget = 30000000; // 30jt
        $profitTarget = $targetGrossProfit; // KEEP THIS FOR BACKWARD COMPATIBILITY
        
        // Progress Calculation untuk iklan
        $conversionProgress = $conversionTarget > 0 ? min(100, ($conversionRate / $conversionTarget) * 100) : 0;
        $omsetProgress = $omsetTarget > 0 ? min(100, ($monthlyOmset / $omsetTarget) * 100) : 0;
        $profitProgress = $profitTarget > 0 ? min(100, ($monthlyProfit / $profitTarget) * 100) : 0; // KEEP THIS
    
        // Monthly projection untuk omset
        $projectedOmset = $currentDay > 0 ? ($monthlyOmset / $currentDay) * $daysInMonth : 0;
        $projectedProfit = $projectedOmset; // KEEP THIS FOR BACKWARD COMPATIBILITY
        $isOnTrackOmset = $projectedOmset >= $omsetTarget;
        $isOnTrack = $isOnTrackOmset; // KEEP THIS
    
        // === TARGET INVOICE (JUMLAH NOTA PEMBAYARAN) ===
        $previousMonth = now()->subMonth()->format('Y-m');
        
        // Jumlah nota yang tercetak bulan lalu (count Payment berdasarkan paid_at, bukan SalesOrder)
        // Setiap Payment = 1 nota (bisa DP, pelunasan, dll)
        $previousMonthInvoiceCount = \App\Models\Payment::where('paid_at', 'like', "{$previousMonth}%")
            ->count();
        
        // Target bulan ini = jumlah nota bulan lalu + 100% (jadi 200% dari bulan lalu)
        // Contoh: bulan lalu 2 nota, target = 2 + (2 × 100%) = 2 + 2 = 4 nota
        $invoiceTarget = $previousMonthInvoiceCount * 2.0; // 200% = 2.0
        
        // Realisasi invoice bulan ini (jumlah nota yang tercetak bulan ini berdasarkan paid_at)
        $currentMonthInvoiceCount = \App\Models\Payment::where('paid_at', 'like', "{$currentMonth}%")
            ->count();
        
        // Progress calculation untuk invoice (berdasarkan jumlah nota)
        $invoiceProgress = $invoiceTarget > 0 ? min(100, ($currentMonthInvoiceCount / $invoiceTarget) * 100) : 0;
        
        // Additional stats untuk invoice
        $remainingInvoiceTarget = max(0, $invoiceTarget - $currentMonthInvoiceCount);
        $remainingTarget = $remainingInvoiceTarget; // Define variable yang missing di view
        $dailyInvoiceTargetNeeded = $daysLeft > 0 ? $remainingInvoiceTarget / $daysLeft : $remainingInvoiceTarget;
        $dailyTargetNeeded = $dailyInvoiceTargetNeeded; // Define variable yang missing di view
        
        // Untuk backward compatibility (jika ada view yang masih pakai variabel lama)
        $previousMonthInvoices = $previousMonthInvoiceCount;
        $currentMonthPayments = $currentMonthInvoiceCount;
        
        // Data detail inputan hari ini (untuk history) - TAMPILKAN SEMUA termasuk validasi kosong
        $todayDetails = AdvertisementPerformance::today()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('type');
        
        // Cek apakah user yang login punya shift aktif
        $userActiveShift = Shift::where('user_id', Auth::id())
            ->whereNull('end_time')
            ->with('user')
            ->first();
    
        return view('kepala-toko.advertisement.index', compact(
            'todayData',
            'formattedChartData',
            'dates',
            'today',
            
            // Conversion & Omset Target
            'chatCount', 
            'closingCount', 
            'monthlyOmset', 
            'monthlyProfit', // SEKARANG SUDAH ADA
            'monthlySales',
            'monthlyHpp',
            'grossProfit',
            'grossProfitProgress',
            'grossProfitShortfall',
            'targetGrossProfit',
            'conversionRate',
            'conversionTarget', 
            'omsetTarget', 
            'profitTarget',
            'conversionProgress', 
            'omsetProgress',
            'profitProgress',
            'projectedOmset',
            'projectedProfit',
            'isOnTrackOmset',
            'isOnTrack',
            'currentDay',
            'daysInMonth',
            'daysLeft', // SEKARANG SUDAH ADA
            
            // Invoice Target (berdasarkan jumlah nota)
            'previousMonthInvoices', // Jumlah nota bulan lalu (untuk backward compatibility)
            'previousMonthInvoiceCount', // Jumlah nota bulan lalu (baru)
            'invoiceTarget', // Target jumlah nota bulan ini
            'currentMonthPayments', // Realisasi jumlah nota bulan ini (untuk backward compatibility)
            'currentMonthInvoiceCount', // Realisasi jumlah nota bulan ini (baru)
            'invoiceProgress', // Progress persentase
            'remainingInvoiceTarget', // Sisa target yang perlu dicapai
            'remainingTarget', // Alias untuk remainingInvoiceTarget
            'dailyInvoiceTargetNeeded', // Target harian yang perlu dicapai
            'dailyTargetNeeded', // Alias untuk dailyInvoiceTargetNeeded
            
            // History inputan hari ini
            'todayDetails',
            
            // Shift status
            'userActiveShift'
        ));
    }

    public function create()
    {
        // Cek apakah user yang login punya shift aktif
        $userActiveShift = Shift::where('user_id', Auth::id())
            ->whereNull('end_time')
            ->first();
        
        if (!$userActiveShift) {
            return redirect()->route('kepala-toko.advertisement.index')
                ->withErrors([
                    'error' => 'Anda belum membuka shift hari ini. Buka shift terlebih dahulu sebelum input data iklan.'
                ]);
        }
        
        return view('kepala-toko.advertisement.create');
    }

    public function store(Request $request)
    {
        // Cek apakah user yang login punya shift aktif
        $userActiveShift = Shift::where('user_id', Auth::id())
            ->whereNull('end_time')
            ->first();
        
        if (!$userActiveShift) {
            return back()->withErrors([
                'error' => 'Anda belum membuka shift hari ini. Buka shift terlebih dahulu sebelum input data iklan.'
            ])->withInput();
        }
        
        $validated = $request->validate([
            'type' => 'required|in:chat,followup,closing',
            'description' => 'required|string|max:255',
            'amount' => $request->type === 'closing' ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
        ], [
            'amount.required' => 'Nominal wajib diisi untuk closing',
        ]);
    
        AdvertisementPerformance::create([
            'date' => today(),
            'user_id' => auth()->id(),
            'type' => $validated['type'],
            'description' => $validated['description'],
            'amount' => $validated['amount'] ?? 0,
        ]);
    
        return redirect()->route('kepala-toko.advertisement.index')
            ->with('success', 'Data iklan berhasil disimpan!');
    }

    public function getDescriptions(Request $request)
    {
        $type = $request->get('type');
        $descriptions = AdvertisementPerformance::getDefaultDescriptions($type);
        
        return response()->json($descriptions);
    }
}

