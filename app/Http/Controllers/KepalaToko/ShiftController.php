<?php

namespace App\Http\Controllers\KepalaToko;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\Payment;
use App\Models\SalesOrder;
use App\Models\Expense;
use App\Models\Income;
use App\Models\AdvertisementPerformance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ShiftHistoryExport;
use App\Exports\ShiftDetailExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShiftController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        // CEK APAKAH SUDAH ADA SHIFT AKTIF DI TOKO INI
        $existingShift = Shift::whereNull('end_time')->first();
        
        if ($existingShift) {
            return back()->withErrors([
                'error' => 'Tidak bisa mulai shift. Shift aktif sedang berjalan oleh ' . 
                          $existingShift->user->name . 
                          ' sejak ' . $existingShift->start_time->format('H:i')
            ]);
        }
    
        // CEK APAKAH USER INI SUDAH PUNYA SHIFT AKTIF
        $userActiveShift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
        if ($userActiveShift) {
            return back()->withErrors(['error' => 'Anda sudah memiliki shift aktif. Akhiri shift sebelumnya terlebih dahulu.']);
        }
    
        // === LOGIC AUTO KAS AWAL DENGAN SANITY CHECK ===
        $latestClosedShift = Shift::whereNotNull('end_time')->latest('end_time')->first();
        
        if ($latestClosedShift) {
            // SANITY CHECK: final_cash tidak boleh lebih dari 3x initial_cash
            $maxAllowed = $latestClosedShift->initial_cash * 3;
            if ($latestClosedShift->final_cash > $maxAllowed) {
                \Log::error("Suspicious final_cash detected. Shift: {$latestClosedShift->id}, Final: {$latestClosedShift->final_cash}, Initial: {$latestClosedShift->initial_cash}");
                
                // Fallback: hitung ulang dari data real
                $realFinalCash = $this->calculateRealFinalCashForShift($latestClosedShift); // ✅ PAKAI METHOD BARU
                $initialCash = $realFinalCash;
                
                \Log::info("Auto-corrected final_cash from {$latestClosedShift->final_cash} to {$realFinalCash}");
            } else {
                $initialCash = $latestClosedShift->final_cash;
            }
            $message = 'Shift dimulai dengan kas awal otomatis: Rp ' . number_format($initialCash, 0, ',', '.');
        } else {
            // FIRST TIME: manual input required
            $validated = $request->validate([
                'initial_cash' => ['required', 'numeric', 'min:0', 'max:100000000'],
            ]);
            $initialCash = $validated['initial_cash'];
            $message = 'Shift pertama dimulai dengan kas awal: Rp ' . number_format($initialCash, 0, ',', '.');
        }
    
        Shift::create([
            'user_id' => Auth::id(),
            'initial_cash' => $initialCash,
            'start_time' => now(),
            'status' => 'open',
        ]);
    
        return redirect()->route('kepala-toko.shift.dashboard')->with('success', $message);
    }

    public function end(Request $request): Response|RedirectResponse|BinaryFileResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'print_summary' => ['nullable', 'boolean'],
        ]);
    
        $shift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
        if (!$shift) {
            return back()->withErrors(['error' => 'Anda tidak memiliki shift aktif. Mulai shift terlebih dahulu.']);
        }
    
        // === VALIDASI: Cek apakah USER YANG LOGIN sudah input iklan hari ini ===
        $userTodayAdvertisementCount = AdvertisementPerformance::where('date', today())
            ->where('user_id', Auth::id())
            ->count();
        
        if ($userTodayAdvertisementCount === 0) {
            return back()->withErrors([
                'advertisement_required' => 'Data iklan hari ini masih kosong. Harap input data iklan terlebih dahulu atau validasi bahwa hari ini memang tidak ada aktivitas iklan.'
            ])->withInput();
        }
    
        // === HITUNG REAL-TIME DARI DATA ASLI (JANGAN PERCAYA cash_total) ===
        $realFinalCash = $this->calculateRealFinalCash($shift);
        
        // Update dengan nilai yang benar
        $shift->update([
            'final_cash' => $realFinalCash,
            'cash_total' => $this->calculateRealCashTotal($shift), // Perbaiki cash_total yang korup
            'discrepancy' => 0,
            'end_time' => now(),
            'notes' => $validated['notes'] ?? null,
            'status' => 'closed',
        ]);
    
        // SELALU generate PDF untuk print
        $pdfPath = $this->printShiftSummary($shift->id);
    
        if ($request->has('print_summary')) {
            return $this->downloadSummary($shift->id);
        }
    
        return redirect()->route('kepala-toko.shift.history')->with('success', 
            'Shift diakhiri. ' .
            'Kas akhir: Rp ' . number_format($realFinalCash, 0, ',', '.') . '. ' .
            'Tidak ada selisih karena perhitungan sistem.'
        );
    }

    // METHOD BARU: HITUNG REAL CASH TOTAL DARI DATA ASLI
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

    // METHOD BARU: HITUNG REAL FINAL CASH DARI DATA ASLI
    private function calculateRealFinalCash(Shift $shift): float
    {
        $realCashTotal = $this->calculateRealCashTotal($shift);
        $totalCashTransfers = \App\Models\CashTransfer::where('shift_id', $shift->id)->sum('amount');
        
        return $shift->initial_cash + $realCashTotal - $shift->expense_total - $totalCashTransfers;
    }

    // METHOD BARU: Hitung real final cash untuk shift lain (bukan shift aktif)
    private function calculateRealFinalCashForShift(Shift $shift): float
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
        $totalCashTransfers = \App\Models\CashTransfer::where('shift_id', $shift->id)->sum('amount');
        
        return $shift->initial_cash + $totalCashFromPayments + $totalIncome - $shift->expense_total - $totalCashTransfers;
    }
// Method untuk print summary (struk thermal)
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

    $pdf = PDF::loadView('kepala-toko.shift.closing_summary', compact('shift', 'incomes', 'expenses', 'payments'));
    
    // Set paper size dan margin
    $pdf->setPaper('a4', 'portrait'); // Default A4
    $pdf->setOption('margin-top', 0);
    $pdf->setOption('margin-right', 0);
    $pdf->setOption('margin-bottom', 0);
    $pdf->setOption('margin-left', 0);
    
    return $pdf->stream('closing_summary_shift_' . $shift->id . '.pdf');
}

// Tambahkan method untuk print preview
public function printPreview($id)
{
    $shift = Shift::with('user')->findOrFail($id);
    return view('kepala-toko.shift.print_preview', compact('shift'));
}

    public function printShiftSummary($id)
    {
        $shift = Shift::with('user')->findOrFail($id);
        $incomes = Income::where('shift_id', $id)->get();
        $expenses = Expense::where('shift_id', $id)->get();
        $salesOrders = SalesOrder::whereHas('payments', function ($query) use ($shift) {
            $query->where('created_by', $shift->user_id)
                  ->where('created_at', '>=', $shift->start_time)
                  ->where('created_at', '<=', $shift->end_time ?? now());
        })->with(['customer', 'payments'])->get();
    
        $pdf = PDF::loadView('kepala-toko.shift.closing_summary', compact('shift', 'incomes', 'expenses', 'salesOrders'));
        $pdfPath = 'pdfs/closing_summary_' . $shift->id . '.pdf';
        Storage::put($pdfPath, $pdf->output());
        return $pdfPath;
    }

    public function income(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'income_amount' => ['required', 'numeric', 'min:1000', 'max:10000000'], // BATASI MAX!
            'income_description' => ['required', 'string', 'max:255'],
        ]);

        // PAKAI DB TRANSACTION UNTUK HINDARI RACE CONDITION
        return DB::transaction(function () use ($validated) {
            $shift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
            
            if (!$shift) {
                return back()->withErrors(['error' => 'Anda tidak memiliki shift aktif. Mulai shift terlebih dahulu.']);
            }

            // CEK DUPLIKAT 5 MENIT TERAKHIR
            $recentIncome = Income::where('shift_id', $shift->id)
                ->where('description', $validated['income_description'])
                ->where('amount', $validated['income_amount'])
                ->where('created_at', '>=', now()->subMinutes(5))
                ->first();
                
            if ($recentIncome) {
                return back()->withErrors(['error' => 'Pemasukan serupa sudah ditambahkan 5 menit yang lalu.']);
            }

            Income::create([
                'shift_id' => $shift->id,
                'amount' => $validated['income_amount'],
                'description' => $validated['income_description'],
            ]);

            $shift->increment('income_total', $validated['income_amount']);
            $shift->increment('cash_total', $validated['income_amount']);

            \Log::info('Pemasukan ditambahkan: ' . $validated['income_description'] . ' - Rp ' . number_format($validated['income_amount'], 0, ',', '.'));

            return back()->with('success', 'Pemasukan berhasil ditambahkan.');
        });
    }

    public function expense(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_amount' => ['required', 'numeric', 'min:1000', 'max:10000000'], // BATASI MAX!
            'expense_description' => ['required', 'string', 'max:255'],
        ]);

        // PAKAI DB TRANSACTION UNTUK HINDARI RACE CONDITION
        return DB::transaction(function () use ($validated) {
            $shift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
            
            if (!$shift) {
                return back()->withErrors(['error' => 'Anda tidak memiliki shift aktif. Mulai shift terlebih dahulu.']);
            }

            // CEK DUPLIKAT 5 MENIT TERAKHIR
            $recentExpense = Expense::where('shift_id', $shift->id)
                ->where('description', $validated['expense_description'])
                ->where('amount', $validated['expense_amount'])
                ->where('created_at', '>=', now()->subMinutes(5))
                ->first();
                
            if ($recentExpense) {
                return back()->withErrors(['error' => 'Pengeluaran serupa sudah ditambahkan 5 menit yang lalu.']);
            }

            Expense::create([
                'shift_id' => $shift->id,
                'amount' => $validated['expense_amount'],
                'description' => $validated['expense_description'],
                'created_at' => now(),
            ]);

            $shift->increment('expense_total', $validated['expense_amount']);

            \Log::info('Pengeluaran ditambahkan: ' . $validated['expense_description'] . ' - Rp ' . number_format($validated['expense_amount'], 0, ',', '.'));

            return back()->with('success', 'Pengeluaran berhasil ditambahkan.');
        });
    }

    public function cashTransfer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transfer_amount' => ['required', 'numeric', 'min:1', 'gt:0', 'max:10000000'],
            'transfer_description' => ['required', 'string', 'max:255'],
            'transfer_type' => ['required', 'in:setor,tukar,other'],
            'transfer_notes' => ['nullable', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($validated) {
            $shift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
            
            if (!$shift) {
                return back()->withErrors(['error' => 'Anda tidak memiliki shift aktif. Mulai shift terlebih dahulu.']);
            }

            // CEK APAKAH KAS LACI CUKUP
            $realFinalCash = $this->calculateRealFinalCash($shift);
            if ($validated['transfer_amount'] > $realFinalCash) {
                return back()->withErrors([
                    'transfer_amount' => 'Jumlah melebihi kas di laci. Kas tersedia: Rp ' . number_format($realFinalCash, 0, ',', '.')
                ])->withInput();
            }

            // CEK DUPLIKAT 5 MENIT TERAKHIR
            $recentTransfer = \App\Models\CashTransfer::where('shift_id', $shift->id)
                ->where('description', $validated['transfer_description'])
                ->where('amount', $validated['transfer_amount'])
                ->where('created_at', '>=', now()->subMinutes(5))
                ->first();
                
            if ($recentTransfer) {
                return back()->withErrors(['error' => 'Transfer tunai serupa sudah ditambahkan 5 menit yang lalu.']);
            }

            // CREATE CASH TRANSFER
            \App\Models\CashTransfer::create([
                'shift_id' => $shift->id,
                'amount' => $validated['transfer_amount'],
                'description' => $validated['transfer_description'],
                'type' => $validated['transfer_type'],
                'notes' => $validated['transfer_notes'] ?? null,
            ]);

            \Log::info('Cash transfer ditambahkan: ' . $validated['transfer_description'] . ' - Rp ' . number_format($validated['transfer_amount'], 0, ',', '.'));

            return back()->with('success', 'Setor/Tukar tunai berhasil dicatat.');
        });
    }

    /**
     * Auto-input iklan default 0 (untuk validasi hari kosong)
     */
    public function validateEmptyAdvertisement(): RedirectResponse
    {
        $shift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
        
        if (!$shift) {
            return back()->withErrors(['error' => 'Anda tidak memiliki shift aktif.']);
        }
        
        // Cek apakah USER YANG LOGIN sudah input iklan hari ini
        $userTodayAdvertisementCount = AdvertisementPerformance::where('date', today())
            ->where('user_id', Auth::id())
            ->count();
        
        if ($userTodayAdvertisementCount > 0) {
            return back()->withErrors(['error' => 'Anda sudah menginput data iklan hari ini. Tidak perlu validasi kosong.']);
        }
        
        // Buat 3 record dengan nilai 0
        $today = today();
        $userId = Auth::id();
        $description = 'Tidak ada aktivitas hari ini';
        
        AdvertisementPerformance::create([
            'date' => $today,
            'user_id' => $userId,
            'type' => 'chat',
            'description' => $description,
            'amount' => 0,
        ]);
        
        AdvertisementPerformance::create([
            'date' => $today,
            'user_id' => $userId,
            'type' => 'followup',
            'description' => $description,
            'amount' => 0,
        ]);
        
        AdvertisementPerformance::create([
            'date' => $today,
            'user_id' => $userId,
            'type' => 'closing',
            'description' => $description,
            'amount' => 0,
        ]);
        
        return back()->with('success', 'Data iklan hari ini telah divalidasi sebagai kosong (0 untuk semua jenis).');
    }

    public function dashboard(): View
    {
        $shift = Shift::where('user_id', Auth::id())->whereNull('end_time')->first();
    
        // Inisialisasi variabel dengan nilai default
        $cashLunas = 0;
        $cashDp = 0;
        $cashPelunasan = 0;
        $transferLunas = 0;
        $transferDp = 0;
        $transferPelunasan = 0;
        $pengeluaran = 0;
        $pemasukanManual = 0;
        $tunaiDiLaci = 0;
        $awalLaci = 0;
        $totalDiharapkan = 0;
    
        // === VARIABEL STATISTICS BARU ===
        $totalTransactions = 0;
        $totalInvoices = 0;
        $totalSales = 0;
        $totalCustomers = 0;
        $shiftDuration = '0 jam 0 menit';
        $averageTransaction = 0;
    
        if ($shift) {
            $awalLaci = $shift->initial_cash;
            $pengeluaran = $shift->expense_total;
            $pemasukanManual = $shift->income_total;
    
            // Ambil semua pembayaran yang dibuat selama shift ini, berdasarkan Payment.created_at
            $payments = Payment::where('created_by', Auth::id())
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
    
            // Hitung total cash dari pembayaran + pemasukan manual
            $totalCashFromPayments = $cashLunas + $cashDp + $cashPelunasan;
            $totalCashFromAllSources = $totalCashFromPayments + $pemasukanManual;
            
            // Hitung total cash transfer
            $totalCashTransfers = \App\Models\CashTransfer::where('shift_id', $shift->id)->sum('amount');
            
            // Hitung tunai di laci (dikurangi cash transfer)
            $tunaiDiLaci = $shift->initial_cash + $totalCashFromAllSources - $shift->expense_total - $totalCashTransfers;
            $totalDiharapkan = $shift->initial_cash + $totalCashFromAllSources - $shift->expense_total - $totalCashTransfers;
    
            // === CALCULATE STATISTICS - FIXED ===
            // 1. Total transaksi = Jumlah UNIQUE sales order yang ada payment di shift ini
            $salesOrdersInShift = SalesOrder::whereHas('payments', function($query) use ($shift) {
                    $query->where('created_by', Auth::id())
                          ->where('created_at', '>=', $shift->start_time)
                          ->where('created_at', '<=', $shift->end_time ?? now());
                })
                ->get();
    
            $totalTransactions = $salesOrdersInShift->count();
    
            // 2. Total invoices = Jumlah payment di shift ini
            $totalInvoices = $payments->count();
    
            // 3. Total penjualan = SUM dari amount semua payment di shift ini
            $totalSales = $payments->sum('amount');
    
            // 4. Total customer = Jumlah Sales Order (setiap SO = 1 customer, termasuk "Umum")
            $totalCustomers = $salesOrdersInShift->count();
    
            // Durasi shift
            $start = Carbon::parse($shift->start_time);
            $end = $shift->end_time ? Carbon::parse($shift->end_time) : now();
            $duration = $start->diff($end);
            $shiftDuration = $duration->h . ' jam ' . $duration->i . ' menit';
    
            // Rata-rata transaksi = Total sales / Total transactions
            $averageTransaction = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;
        }
    
        return view('kepala-toko.shift.dashboard', compact(
            'shift',
            'cashLunas',
            'cashDp',
            'cashPelunasan',
            'transferLunas',
            'transferDp',
            'transferPelunasan',
            'pengeluaran',
            'pemasukanManual',
            'tunaiDiLaci',
            'awalLaci',
            'totalDiharapkan',
            // Statistics baru
            'totalTransactions',
            'totalInvoices', 
            'totalSales',
            'totalCustomers',
            'shiftDuration',
            'averageTransaction'
        ));
    }

    public function history()
    {
        $shifts = Shift::with('user')->latest()->paginate(10);
        collect($shifts->items())->map(function ($shift) {
            $payments = Payment::where('created_by', $shift->user_id)
                ->where('created_at', '>=', $shift->start_time)
                ->where('created_at', '<=', $shift->end_time ?? now())
                ->with('salesOrder')
                ->get();
            
            $shift->cashLunas = 0;
            $shift->cashDp = 0;
            $shift->cashPelunasan = 0;
            $shift->transferTotal = 0;
        
            foreach ($payments as $payment) {
                $so = $payment->salesOrder;
                $isLunasSekaliBayar = ($payment->category === 'pelunasan' && $so->payments->count() === 1);
                if ($payment->method === 'cash') {
                    if ($isLunasSekaliBayar) {
                        $shift->cashLunas += $payment->amount;
                    } elseif ($payment->category === 'dp') {
                        $shift->cashDp += $payment->amount;
                    } else {
                        $shift->cashPelunasan += $payment->amount;
                    }
                } elseif ($payment->method === 'transfer') {
                    if ($isLunasSekaliBayar) {
                        $shift->transferTotal += $payment->amount;
                    } elseif ($payment->category === 'dp') {
                        $shift->transferTotal += $payment->amount;
                    } else {
                        $shift->transferTotal += $payment->amount;
                    }
                } elseif ($payment->method === 'split') {
                    if ($isLunasSekaliBayar) {
                        $shift->cashLunas += $payment->cash_amount;
                        $shift->transferTotal += $payment->transfer_amount;
                    } elseif ($payment->category === 'dp') {
                        $shift->cashDp += $payment->cash_amount;
                        $shift->transferTotal += $payment->transfer_amount;
                    } else {
                        $shift->cashPelunasan += $payment->cash_amount;
                        $shift->transferTotal += $payment->transfer_amount;
                    }
                }
            }
        
            return $shift;
        });
        
        return view('kepala-toko.shift.history', compact('shifts'));
    }

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
    
        return view('kepala-toko.shift.show', compact('shift', 'incomes', 'expenses', 'salesOrders', 'cashLunas', 'cashDp', 'cashPelunasan', 'transferLunas', 'transferDp', 'transferPelunasan', 'totalPendapatan'));
    }

    public function export()
    {
        return Excel::download(new ShiftHistoryExport, 'shift_history_' . date('Ymd_His') . '.xlsx');
    }

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

        $pdf = Pdf::loadView('kepala-toko.shift.detail_pdf', compact('shift', 'salesOrders', 'expenses', 'incomes'));
        return $pdf->download('shift_detail_' . $shift->id . '_' . date('Ymd_His') . '.pdf');
    }

    public function exportPdf()
    {
        $shifts = Shift::with('user')->orderBy('start_time', 'desc')->get();
        
        $pdf = Pdf::loadView('kepala-toko.shift.pdf', compact('shifts'));
        return $pdf->download('laporan_shift_' . date('Ymd_His') . '.pdf');
    }
    public function downloadSummary($id)
{
    try {
        $shift = Shift::findOrFail($id);
        $pdfPath = 'pdfs/closing_summary_' . $shift->id . '.pdf';
        
        // Cek file exists
        if (!Storage::exists($pdfPath)) {
            // Regenerate PDF jika tidak ada
            $pdfPath = $this->printShiftSummary($shift->id);
        }
        
        return response()->download(storage_path('app/' . $pdfPath), 'closing_summary_shift_' . $shift->id . '.pdf');
    } catch (\Exception $e) {
        return back()->withErrors(['error' => 'File tidak ditemukan: ' . $e->getMessage()]);
    }
}
}