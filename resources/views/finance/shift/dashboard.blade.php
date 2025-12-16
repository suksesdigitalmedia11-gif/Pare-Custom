<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Finance Shift Monitoring - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
</head>
<body class="bg-gray-50">
<div class="flex">
    <x-navbar-finance />
    <div class="flex-1 lg:w-5/6">
        <x-navbar-top-finance />
        
        <div class="p-4 lg:p-8">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-6 rounded-xl shadow-lg mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold mb-2">🏪 Shift Monitoring</h1>
                        <p class="opacity-90">Monitor semua shift aktif & riwayat shift</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm opacity-80">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
                        <p class="text-sm opacity-80">Update: {{ now()->format('d M Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Date Range Filter -->
            <div class="bg-white p-4 rounded-xl shadow mb-6">
                <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                            <input type="date" name="start_date" value="{{ $startDate }}" 
                                   class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                            <input type="date" name="end_date" value="{{ $endDate }}" 
                                   class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <button type="submit" 
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors font-medium">
                            Terapkan Filter
                        </button>
                    </div>
                </form>
                
                <!-- Quick Date Buttons -->
                <div class="flex gap-2 mt-3">
                    <a href="?start_date={{ now()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}" 
                       class="bg-blue-100 text-blue-700 px-3 py-1 rounded-lg text-sm hover:bg-blue-200 transition-colors">
                        Hari Ini
                    </a>
                    <a href="?start_date={{ now()->subDays(6)->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}" 
                       class="bg-green-100 text-green-700 px-3 py-1 rounded-lg text-sm hover:bg-green-200 transition-colors">
                        7 Hari
                    </a>
                    <a href="?start_date={{ now()->startOfMonth()->format('Y-m-d') }}&end_date={{ now()->endOfMonth()->format('Y-m-d') }}" 
                       class="bg-purple-100 text-purple-700 px-3 py-1 rounded-lg text-sm hover:bg-purple-200 transition-colors">
                        Bulan Ini
                    </a>
                </div>
            </div>

            <!-- SHIFT SUMMARY CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <!-- Total Shift -->
                <div class="bg-white p-6 rounded-xl shadow border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Shift</p>
                            <p class="text-2xl font-bold text-blue-600">{{ $totalShifts }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-full">
                            <i class="bi bi-clock-history text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-600 mt-2">Dalam periode</p>
                </div>

                <!-- Shift Aktif -->
                <div class="bg-white p-6 rounded-xl shadow border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Shift Aktif</p>
                            <p class="text-2xl font-bold text-green-600">{{ $activeShifts }}</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full">
                            <i class="bi bi-play-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-600 mt-2">Sedang berjalan</p>
                </div>

                <!-- Shift Selesai -->
                <div class="bg-white p-6 rounded-xl shadow border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Shift Selesai</p>
                            <p class="text-2xl font-bold text-purple-600">{{ $closedShifts }}</p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-full">
                            <i class="bi bi-check-circle text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-600 mt-2">Telah ditutup</p>
                </div>

                <!-- Total Kas Akhir -->
                <div class="bg-white p-6 rounded-xl shadow border-l-4 border-orange-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Kas Akhir</p>
                            <p class="text-2xl font-bold text-orange-600">
                                Rp {{ number_format($totalFinalCash, 0, ',', '.') }}
                            </p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-full">
                            <i class="bi bi-cash-coin text-orange-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-600 mt-2">Akumulasi semua shift</p>
                </div>
            </div>

            <!-- TWO COLUMN LAYOUT -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- FINANCIAL SUMMARY -->
                <div class="bg-white p-6 rounded-xl shadow">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">💰 Ringkasan Keuangan</h2>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="bi bi-cash-coin text-green-500 mr-3"></i>
                                <span>Total Kas Awal</span>
                            </div>
                            <span class="font-semibold text-green-600">
                                Rp {{ number_format($totalInitialCash, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="bi bi-arrow-down-circle text-blue-500 mr-3"></i>
                                <span>Pemasukan Cash</span>
                            </div>
                            <span class="font-semibold text-blue-600">
                                Rp {{ number_format($totalCashIncome, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="bi bi-arrow-up-circle text-red-500 mr-3"></i>
                                <span>Total Pengeluaran</span>
                            </div>
                            <span class="font-semibold text-red-600">
                                Rp {{ number_format($totalExpenses, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="bi bi-arrow-left-right text-purple-500 mr-3"></i>
                                <span>Setor/Tukar Tunai</span>
                            </div>
                            <span class="font-semibold text-purple-600">
                                Rp {{ number_format($totalCashTransfer, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg border border-green-200">
                            <div class="flex items-center">
                                <i class="bi bi-wallet2 text-green-600 mr-3"></i>
                                <span class="font-medium">Total Kas Akhir</span>
                            </div>
                            <span class="font-bold text-green-700">
                                Rp {{ number_format($totalFinalCash, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- PAYMENT BREAKDOWN -->
                <div class="bg-white p-6 rounded-xl shadow">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">💳 Breakdown Pembayaran</h2>
                    
                    <div class="space-y-4">
                        <!-- CASH -->
                        <div class="border rounded-lg p-4">
                            <h3 class="font-semibold text-green-600 mb-3 flex items-center">
                                <i class="bi bi-cash-coin mr-2"></i> Cash
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span>Lunas Sekali Bayar:</span>
                                    <span class="font-medium">Rp {{ number_format($paymentBreakdown['cash']['lunas'], 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>DP Cash:</span>
                                    <span class="font-medium">Rp {{ number_format($paymentBreakdown['cash']['dp'], 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Pelunasan Cash:</span>
                                    <span class="font-medium">Rp {{ number_format($paymentBreakdown['cash']['pelunasan'], 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between border-t pt-2 font-semibold">
                                    <span>Total Cash:</span>
                                    <span class="text-green-600">
                                        Rp {{ number_format($paymentBreakdown['cash']['lunas'] + $paymentBreakdown['cash']['dp'] + $paymentBreakdown['cash']['pelunasan'], 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- TRANSFER -->
                        <div class="border rounded-lg p-4">
                            <h3 class="font-semibold text-blue-600 mb-3 flex items-center">
                                <i class="bi bi-bank mr-2"></i> Transfer
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span>Lunas Sekali Bayar:</span>
                                    <span class="font-medium">Rp {{ number_format($paymentBreakdown['transfer']['lunas'], 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>DP Transfer:</span>
                                    <span class="font-medium">Rp {{ number_format($paymentBreakdown['transfer']['dp'], 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Pelunasan Transfer:</span>
                                    <span class="font-medium">Rp {{ number_format($paymentBreakdown['transfer']['pelunasan'], 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between border-t pt-2 font-semibold">
                                    <span>Total Transfer:</span>
                                    <span class="text-blue-600">
                                        Rp {{ number_format($paymentBreakdown['transfer']['lunas'] + $paymentBreakdown['transfer']['dp'] + $paymentBreakdown['transfer']['pelunasan'], 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SHIFT LIST -->
            <div class="bg-white p-6 rounded-xl shadow mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">📋 Daftar Shift</h2>
                    <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm">
                        {{ $shifts->count() }} shift
                    </span>
                </div>

                @if($shifts->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-gray-50">
                                <th class="text-left py-3 px-4">User</th>
                                <th class="text-left py-3 px-4">Status</th>
                                <th class="text-left py-3 px-4">Waktu</th>
                                <th class="text-right py-3 px-4">Kas Awal</th>
                                <th class="text-right py-3 px-4">Kas Akhir</th>
                                <th class="text-right py-3 px-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($shifts as $shift)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <div class="font-medium">{{ $shift->user->name }}</div>
                                    <div class="text-xs text-gray-500">ID: {{ $shift->id }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    @if($shift->status === 'open')
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                            <i class="bi bi-play-fill mr-1"></i> Aktif
                                        </span>
                                    @else
                                        <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-medium">
                                            <i class="bi bi-check-circle mr-1"></i> Selesai
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <div class="text-sm">{{ $shift->start_time->format('d M Y H:i') }}</div>
                                    <div class="text-xs text-gray-500">
                                        @if($shift->end_time)
                                            {{ $shift->end_time->format('H:i') }}
                                        @else
                                            Masih berjalan
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <span class="font-medium">Rp {{ number_format($shift->initial_cash, 0, ',', '.') }}</span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    @if($shift->final_cash)
                                        <span class="font-medium text-green-600">Rp {{ number_format($shift->final_cash, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('finance.shift.show', $shift) }}" 
                                       class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs inline-flex items-center">
                                        <i class="bi bi-eye mr-1"></i> Detail
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8 text-gray-500">
                    <i class="bi bi-clock-history text-3xl text-gray-400 mb-2"></i>
                    <p>Tidak ada shift dalam periode ini</p>
                </div>
                @endif
            </div>

            <!-- QUICK ACTIONS -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('finance.shift.history') }}" 
                   class="bg-white p-4 rounded-xl shadow border hover:border-blue-500 transition-colors text-center group">
                    <i class="bi bi-clock-history text-blue-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <p class="font-semibold">Riwayat Lengkap</p>
                    <p class="text-xs text-gray-600">Lihat semua shift dengan filter</p>
                </a>

                <a href="{{ route('finance.shift.export') }}" 
                   class="bg-white p-4 rounded-xl shadow border hover:border-green-500 transition-colors text-center group">
                    <i class="bi bi-file-earmark-excel text-green-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <p class="font-semibold">Export Data</p>
                    <p class="text-xs text-gray-600">Download Excel report</p>
                </a>

                <a href="{{ route('finance.dashboard') }}" 
                   class="bg-white p-4 rounded-xl shadow border hover:border-purple-500 transition-colors text-center group">
                    <i class="bi bi-graph-up text-purple-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <p class="font-semibold">Finance Dashboard</p>
                    <p class="text-xs text-gray-600">Kembali ke dashboard utama</p>
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>