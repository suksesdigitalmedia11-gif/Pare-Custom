<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard Shift - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
</head>
<body class="bg-gray-100">
<div class="flex">
    <x-navbar-admin />
    <div class="flex-1 lg:w-5/6">
        <x-navbar-top-admin />
        <div class="p-4 lg:p-8">
            <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                <h1 class="text-2xl font-semibold text-gray-800 mb-4">Dashboard Shift</h1>

                <!-- Notifikasi Shift Aktif -->
@php
    $activeShift = \App\Models\Shift::whereNull('end_time')->first();
@endphp

<!-- STATISTICS DASHBOARD -->
@if($shift)
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <!-- Total Transaksi -->
    <div class="bg-white p-4 rounded-lg shadow border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="bg-blue-100 p-2 rounded-lg mr-3">
                <i class="bi bi-receipt text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total Transaksi</p>
                <p class="text-xl font-bold text-gray-800">{{ $totalTransactions }}</p>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Sales order di shift ini</p>
    </div>

    <!-- Invoice Tercetak -->
    <div class="bg-white p-4 rounded-lg shadow border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="bg-green-100 p-2 rounded-lg mr-3">
                <i class="bi bi-printer text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Invoice</p>
                <p class="text-xl font-bold text-gray-800">{{ $totalInvoices }}</p>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Nota yang tercetak</p>
    </div>

    <!-- Total Penjualan -->
    <div class="bg-white p-4 rounded-lg shadow border-l-4 border-purple-500">
        <div class="flex items-center">
            <div class="bg-purple-100 p-2 rounded-lg mr-3">
                <i class="bi bi-currency-dollar text-purple-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total Penjualan</p>
                <p class="text-xl font-bold text-gray-800">Rp {{ number_format($totalSales, 0, ',', '.') }}</p>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Nilai transaksi</p>
    </div>

    <!-- Customer Dilayani -->
    <div class="bg-white p-4 rounded-lg shadow border-l-4 border-orange-500">
        <div class="flex items-center">
            <div class="bg-orange-100 p-2 rounded-lg mr-3">
                <i class="bi bi-people text-orange-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Customer</p>
                <p class="text-xl font-bold text-gray-800">{{ $totalCustomers }}</p>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Customer dilayani</p>
    </div>
</div>

<!-- ROW 2 STATISTICS -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <!-- Durasi Shift -->
    <div class="bg-white p-4 rounded-lg shadow border-l-4 border-indigo-500">
        <div class="flex items-center">
            <div class="bg-indigo-100 p-2 rounded-lg mr-3">
                <i class="bi bi-clock text-indigo-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Durasi Shift</p>
                <p class="text-xl font-bold text-gray-800">{{ $shiftDuration }}</p>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Mulai: {{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }}</p>
    </div>

    <!-- Rata-rata Transaksi -->
    <div class="bg-white p-4 rounded-lg shadow border-l-4 border-pink-500">
        <div class="flex items-center">
            <div class="bg-pink-100 p-2 rounded-lg mr-3">
                <i class="bi bi-graph-up text-pink-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Rata-rata/Transaksi</p>
                <p class="text-xl font-bold text-gray-800">Rp {{ number_format($averageTransaction, 0, ',', '.') }}</p>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Nilai per transaksi</p>
    </div>
</div>
@endif

@if($activeShift && !$shift)
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
        <div class="flex items-center">
            <i class="bi bi-exclamation-triangle mr-2"></i>
            <strong>Shift sedang berjalan!</strong>
        </div>
        <p class="mt-1 text-sm">
            Shift aktif sedang berjalan oleh <strong>{{ $activeShift->user->name }}</strong> 
            sejak {{ $activeShift->start_time->format('H:i') }}.
            Tunggu hingga shift selesai untuk mulai shift baru.
        </p>
    </div>
@endif

                @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p>{{ session('error') }}</p>
                </div>
                @endif


                <div class="flex flex-wrap gap-4 mb-4">
                    <a href="{{ route('admin.shift.history') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded shadow inline-flex items-center">
                        <i class="bi bi-clock-history mr-2"></i> Lihat Riwayat
                    </a>
                </div>

                @if($shift)
                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4">
                        <p class="text-sm text-green-800">
                            <strong>Shift Aktif</strong> - Dimulai pada {{ \Carbon\Carbon::parse($shift->start_time)->format('d/m/Y H:i') }}
                        </p>
                        <p class="text-sm text-green-800">Kas Awal: Rp {{ number_format($shift->initial_cash, 0, ',', '.') }}</p>
                    </div>
                @else
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                        <p class="text-sm text-red-800">
                            <i class="bi bi-exclamation-triangle"></i> Shift belum dimulai. 
                            Silakan mulai shift untuk mengoperasikan penjualan.
                        </p>
                    </div>
                @endif

                <!-- Grid Stats -->
                @if($shift)
                <div class="grid md:grid-cols-2 gap-6 mt-6">
                    <!-- Kolom Kiri - Pemasukan -->
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h2 class="text-lg font-semibold mb-4 text-green-800">💰 Pemasukan</h2>
                        <table class="w-full table-auto text-sm">
                            <tbody>
                                <tr class="border-b">
                                    <td class="px-3 py-1">Cash Lunas</td>
                                    <td class="px-3 py-1 text-right">Rp {{ number_format($cashLunas, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="px-3 py-1">Cash DP</td>
                                    <td class="px-3 py-1 text-right">Rp {{ number_format($cashDp, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="px-3 py-1">Cash Pelunasan</td>
                                    <td class="px-3 py-1 text-right">Rp {{ number_format($cashPelunasan, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="px-3 py-1">Transfer Lunas</td>
                                    <td class="px-3 py-1 text-right">Rp {{ number_format($transferLunas, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="px-3 py-1">Transfer DP</td>
                                    <td class="px-3 py-1 text-right">Rp {{ number_format($transferDp, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="px-3 py-1">Transfer Pelunasan</td>
                                    <td class="px-3 py-1 text-right">Rp {{ number_format($transferPelunasan, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="px-3 py-1 font-medium">Pemasukan Manual</td>
                                    <td class="px-3 py-1 text-right text-blue-600">Rp {{ number_format($pemasukanManual, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="bg-green-100 font-semibold">
                                    <td class="px-3 py-1">Total Pemasukan</td>
                                    <td class="px-3 py-1 text-right">Rp {{ number_format($cashLunas + $cashDp + $cashPelunasan + $transferLunas + $transferDp + $transferPelunasan + $pemasukanManual, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

<!-- Kolom Kanan - Kas Keluar & Summary -->
<div class="bg-red-50 p-4 rounded-lg">
    <h2 class="text-lg font-semibold mb-4 text-red-800">💸 Kas Keluar & Summary</h2>
    <table class="w-full table-auto text-sm">
        <tbody>
            <tr class="border-b">
                <td class="px-3 py-2 font-medium">Pengeluaran</td>
                <td class="px-3 py-2 text-right">Rp {{ number_format($pengeluaran, 0, ',', '.') }}</td>
            </tr>
            <tr class="border-b">
                <td class="px-3 py-2 font-medium">Setor/Tukar Tunai</td>
                <td class="px-3 py-2 text-right text-purple-600">
                    @php
                        $totalCashTransfer = $shift->cashTransfers->sum('amount') ?? 0;
                    @endphp
                    Rp {{ number_format($totalCashTransfer, 0, ',', '.') }}
                </td>
            </tr>
            <tr class="border-b">
                <td class="px-3 py-2 font-medium">Awal Laci</td>
                <td class="px-3 py-2 text-right">Rp {{ number_format($awalLaci, 0, ',', '.') }}</td>
            </tr>
            <tr class="border-b bg-blue-50">
                <td class="px-3 py-2 font-semibold">Tunai di Laci</td>
                <td class="px-3 py-2 text-right font-semibold">
                    @php
                        $tunaiDiLaci = $shift->initial_cash + ($cashLunas + $cashDp + $cashPelunasan) + $pemasukanManual - $pengeluaran - $totalCashTransfer;
                    @endphp
                    Rp {{ number_format($tunaiDiLaci, 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>
</div>
                </div>

                <!-- Debug Section (untuk memeriksa data pembayaran) -->
                <!-- @if (config('app.env') === 'local')
                <div class="mt-6 bg-gray-100 p-4 rounded-lg">
                    <h2 class="text-lg font-semibold mb-3 text-gray-800">Debug Info (Pembayaran di Shift Ini)</h2>
                    <pre class="bg-gray-800 text-white p-4 rounded-lg overflow-auto text-xs">
                        @php
                            $debugPayments = \App\Models\Payment::where('created_by', Auth::id())
                                ->where('created_at', '>=', $shift->start_time)
                                ->with('salesOrder')
                                ->get();
                            foreach ($debugPayments as $p) {
                                echo "Payment ID: " . $p->id . "\n";
                                echo "SO Number: " . ($p->salesOrder ? $p->salesOrder->so_number : 'N/A') . "\n";
                                echo "Method: " . $p->method . "\n";
                                echo "Status: " . $p->status . "\n";
                                echo "Category: " . $p->category . "\n";
                                echo "Amount: " . $p->amount, 2, '.', '' . "\n";
                                echo "Cash Amount: " .$p->cash_amount, 2, '.', '' . "\n";
                                echo "Transfer Amount: " .$p->transfer_amount, 2, '.', '' . "\n";
                                echo "Paid At: " . $p->paid_at . "\n";
                                echo "Grand Total SO: " . ($p->salesOrder ? number_format($p->salesOrder->grand_total, 2, '.', '') : 'N/A') . "\n";
                                echo "Payments Count in SO: " . ($p->salesOrder ? $p->salesOrder->payments->count() : 'N/A') . "\n";
                                echo "Created By: " . $p->created_by . "\n";
                                echo "------------------------\n";
                            }
                        @endphp
                    </pre>
                </div>
                @endif -->
                @endif

                <!-- Form Input Pemasukan -->
                @if($shift)
                <div class="mt-6 bg-yellow-50 p-4 rounded-lg">
                    <h2 class="text-lg font-semibold mb-3 text-yellow-800">➕ Input Pemasukan Manual</h2>
                    <form action="{{ route('admin.shift.income') }}" method="POST">
                        @csrf
                        <div class="grid md:grid-cols-3 gap-4 mb-3">
                            <div>
                                <label for="income_amount" class="block font-medium mb-1">Jumlah Pemasukan *</label>
                                <input type="number" name="income_amount" id="income_amount" min="1" step="0.01" required 
       class="border rounded px-3 py-2 w-full focus:ring focus:ring-yellow-300"
       placeholder="Contoh: 500, 1000, 2500">
                            </div>
                            <div>
                                <label for="income_description" class="block font-medium mb-1">Keterangan *</label>
                                <input type="text" name="income_description" id="income_description" required 
                                       class="border rounded px-3 py-2 w-full focus:ring focus:ring-yellow-300" 
                                       placeholder="Contoh: Setoran modal, dll">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-2 rounded shadow w-full">
                                    <i class="bi bi-plus-circle"></i> Tambah Pemasukan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                @endif

                <!-- Form Input Pengeluaran -->
                @if($shift)
                <div class="mt-4 bg-red-50 p-4 rounded-lg">
                    <h2 class="text-lg font-semibold mb-3 text-red-800">➖ Input Pengeluaran</h2>
                    <form action="{{ route('admin.shift.expense') }}" method="POST">
                        @csrf
                        <div class="grid md:grid-cols-3 gap-4 mb-3">
                            <div>
                                <label for="expense_amount" class="block font-medium mb-1">Jumlah Pengeluaran *</label>
                                <input type="number" name="expense_amount" id="expense_amount" min="1" step="0.01" required 
                                class="border rounded px-3 py-2 w-full focus:ring focus:ring-red-300"
                                placeholder="Contoh: 500, 1000, 2500">
                            </div>
                            <div>
                                <label for="expense_description" class="block font-medium mb-1">Keterangan *</label>
                                <input type="text" name="expense_description" id="expense_description" required 
                                       class="border rounded px-3 py-2 w-full focus:ring focus:ring-red-300" 
                                       placeholder="Contoh: Beli plastik, dll">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded shadow w-full">
                                    <i class="bi bi-dash-circle"></i> Tambah Pengeluaran
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                @endif

                <!-- Form Setor/Tukar Tunai -->
@if($shift)
<div class="mt-4 bg-purple-50 p-4 rounded-lg">
    <h2 class="text-lg font-semibold mb-3 text-purple-800">💸 Setor/Tukar Tunai</h2>
    <form action="{{ route('admin.shift.cashTransfer') }}" method="POST">
        @csrf
        <div class="grid md:grid-cols-4 gap-4 mb-3">
            <div>
                <label for="transfer_amount" class="block font-medium mb-1">Jumlah *</label>
                <input type="number" name="transfer_amount" id="transfer_amount" min="1" step="0.01" required 
       class="border rounded px-3 py-2 w-full focus:ring focus:ring-purple-300"
       placeholder="Contoh: 500, 1000, 2500">
            </div>
            <div>
                <label for="transfer_description" class="block font-medium mb-1">Keterangan *</label>
                <input type="text" name="transfer_description" id="transfer_description" required 
                       class="border rounded px-3 py-2 w-full focus:ring focus:ring-purple-300" 
                       placeholder="Contoh: Setor ke bank, Tukar uang, dll">
            </div>
            <div>
                <label for="transfer_type" class="block font-medium mb-1">Jenis *</label>
                <select name="transfer_type" id="transfer_type" required 
                        class="border rounded px-3 py-2 w-full focus:ring focus:ring-purple-300">
                    <option value="setor">Setor Bank</option>
                    <option value="tukar">Tukar Uang</option>
                    <option value="other">Lainnya</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded shadow w-full">
                    <i class="bi bi-arrow-left-right"></i> Catat Transfer
                </button>
            </div>
        </div>
        <div class="mb-3">
            <label for="transfer_notes" class="block font-medium mb-1">Catatan (Opsional)</label>
            <textarea name="transfer_notes" id="transfer_notes" rows="2"
                      class="border rounded px-3 py-2 w-full focus:ring focus:ring-purple-300"
                      placeholder="Catatan tambahan..."></textarea>
        </div>
        <p class="text-xs text-purple-600">
            ⚡ <strong>Fitur Baru:</strong> Transfer tunai tidak mempengaruhi laporan profit/operasional
        </p>
    </form>
</div>
@endif

<!-- Form Tutup/Mulai Shift -->
<div class="mt-6">
<!-- Form Tutup Shift -->
@if($shift)
    @php
        $userTodayAdvertisementCount = \App\Models\AdvertisementPerformance::where('date', today())
            ->where('user_id', Auth::id())
            ->count();
    @endphp
    
    <!-- ALERT: Data Iklan Kosong -->
    @if($userTodayAdvertisementCount === 0)
        <div class="bg-orange-50 border-l-4 border-orange-400 p-4 mb-4 rounded-lg shadow">
            <div class="flex items-center gap-3">
                <i class="bi bi-exclamation-triangle-fill text-orange-600 text-xl"></i>
                <div class="flex-1">
                    <p class="font-semibold text-orange-800">Data Iklan Hari Ini Masih Kosong</p>
                    <p class="text-sm text-orange-700 mt-1">
                        Anda belum menginput data iklan hari ini. Harap input data iklan terlebih dahulu atau validasi bahwa hari ini memang tidak ada aktivitas iklan.
                    </p>
                    <div class="mt-3">
                        <form action="{{ route('admin.shift.validate-empty-advertisement') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-medium inline-flex items-center gap-2">
                                <i class="bi bi-check-circle-fill"></i>
                                Validasi Hari Ini Kosong (Input 0 untuk semua jenis)
                            </button>
                        </form>
                        <a href="{{ route('admin.advertisement.create') }}" 
                           class="ml-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium inline-flex items-center gap-2">
                            <i class="bi bi-plus-circle-fill"></i>
                            Input Data Iklan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <!-- ALERT: Error dari Controller -->
    @if(session('error') || (isset($errors) && $errors->has('advertisement_required')))
        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4 rounded-lg shadow">
            <div class="flex items-center gap-3">
                <i class="bi bi-x-circle-fill text-red-600 text-xl"></i>
                <div class="flex-1">
                    <p class="font-semibold text-red-800">Error</p>
                    <p class="text-sm text-red-700 mt-1">
                        @if($errors->has('advertisement_required'))
                            {{ $errors->first('advertisement_required') }}
                        @else
                            {{ session('error') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif
    
    <form action="{{ route('admin.shift.end') }}" method="POST">
        @csrf
        
        <!-- INFO KAS AKHIR OTOMATIS -->
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
            <h3 class="font-semibold text-blue-800 mb-2">💰 Kas Akhir Otomatis</h3>
            <div class="grid md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p><strong>Kas Awal:</strong> Rp {{ number_format($shift->initial_cash, 0, ',', '.') }}</p>
                    <p><strong>Pemasukan Cash:</strong> Rp {{ number_format($cashLunas + $cashDp + $cashPelunasan, 0, ',', '.') }}</p>
                    <p><strong>Pemasukan Transfer:</strong> Rp {{ number_format($transferLunas + $transferDp + $transferPelunasan, 0, ',', '.') }}</p>
                    <p><strong>Pengeluaran:</strong> Rp {{ number_format($pengeluaran, 0, ',', '.') }}</p>
                </div>
                <div class="bg-white p-3 rounded border">
                    <p class="font-semibold text-green-600">
                        <strong>Kas Akhir:</strong> Rp {{ number_format($totalDiharapkan - $totalCashTransfer , 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-600 mt-1">
                        Sistem otomatis menghitung berdasarkan transaksi
                    </p>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <label for="notes" class="block font-medium mb-1">Catatan Penutupan (Opsional)</label>
            <textarea name="notes" id="notes" rows="2"
                      class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                      placeholder="Catatan khusus shift ini"></textarea>
        </div>

        <!-- Checkbox Print -->
        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" name="print_summary" value="1" 
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" checked>
                <span class="ml-2 text-sm text-gray-600">Cetak summary penutupan shift</span>
            </label>
            <p class="text-xs text-gray-500 mt-1">Summary akan otomatis didownload setelah tutup shift</p>
        </div>

        <button type="submit" 
                class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded shadow font-semibold {{ $userTodayAdvertisementCount === 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                {{ $userTodayAdvertisementCount === 0 ? 'disabled' : '' }}>
            <i class="bi bi-lock-fill"></i> Konfirmasi Tutup Shift
        </button>
        
        <p class="text-xs text-gray-600 mt-2">
            💡 <strong>Note:</strong> Kas akhir dihitung otomatis oleh sistem
        </p>
    </form>
    @else
        <!-- TAMPILKAN FORM MULAI SHIFT HANYA JIKA TIDAK ADA SHIFT AKTIF -->
        @if(!$shift)
            @php
                $latestClosedShift = \App\Models\Shift::whereNotNull('end_time')->latest('end_time')->first();
                $isFirstTime = !$latestClosedShift;
            @endphp
            
            @if($isFirstTime)
                <!-- FIRST TIME: Manual input required -->
                <form action="{{ route('admin.shift.start') }}" method="POST">
                    @csrf
                    <div class="max-w-md">
                        <label for="initial_cash" class="block font-medium mb-1">Kas Awal di Laci *</label>
                        <input type="number" name="initial_cash" id="initial_cash" required 
                               class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                               placeholder="Jumlah uang awal di laci">
                        <p class="text-xs text-gray-500 mt-1">Input manual hanya untuk shift pertama kali</p>
                    </div>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow mt-3">
                        <i class="bi bi-play-fill"></i> Mulai Shift Pertama
                    </button>
                </form>
            @else
                <!-- AUTO: Kas awal dari shift sebelumnya -->
                <form action="{{ route('admin.shift.start') }}" method="POST">
                    @csrf
                    <div class="bg-blue-50 p-4 rounded-lg mb-4">
                        <h3 class="font-semibold text-blue-800 mb-2">🔄 Kas Awal Otomatis</h3>
                        <p class="text-sm text-blue-700">
                            Kas awal diambil dari shift sebelumnya: 
                            <strong>Rp {{ number_format($latestClosedShift->final_cash, 0, ',', '.') }}</strong>
                        </p>
                        <p class="text-sm text-blue-600 mt-1">
                            Shift sebelumnya: {{ $latestClosedShift->end_time->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow">
                        <i class="bi bi-play-fill"></i> Mulai Shift Baru
                    </button>
                </form>
            @endif
        @else
            <div class="bg-gray-100 p-4 rounded-lg text-center">
                <p class="text-gray-600">
                    <i class="bi bi-info-circle"></i> 
                    Tidak bisa mulai shift baru karena shift sedang aktif
                </p>
            </div>
        @endif
    @endif
</div>
            </div>
        </div>
    </div>
</div>
</body>
</html>