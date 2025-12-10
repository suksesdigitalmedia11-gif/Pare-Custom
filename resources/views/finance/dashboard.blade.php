<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Finance Dashboard - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Raleway', sans-serif; }
    </style>
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
                        <h1 class="text-2xl font-bold mb-2">💰 Finance Dashboard</h1>
                        <p class="opacity-90">Laporan Keuangan & Income Statement</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm opacity-80">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
                    </div>
                </div>
            </div>

            <!-- Date Range Filter -->
            <div class="bg-white p-4 rounded-xl shadow mb-6">
                <form method="GET" id="filterForm" class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rentang Tanggal</label>
                        <input type="text" id="dateRangePicker" name="date_range" 
                               value="{{ $startDate }} to {{ $endDate }}" 
                               placeholder="Pilih rentang tanggal" 
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" readonly>
                        <input type="hidden" name="start_date" id="start_date" value="{{ $startDate }}">
                        <input type="hidden" name="end_date" id="end_date" value="{{ $endDate }}">
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

            <!-- ADVERTISEMENT PERFORMANCE - 3 CARDS COMPACT -->
            @if(isset($advertisementChatCount))
            <div class="bg-white p-4 rounded-xl shadow-lg mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i class="bi bi-megaphone text-blue-600"></i>
                        Data Iklan
                    </h2>
                    <p class="text-xs text-gray-500">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
                </div>
                
                <!-- PEMBERITAHUAN: Tidak ada data aktual -->
                @if(isset($advertisementHasActualData) && !$advertisementHasActualData)
                <div class="bg-gray-50 border-l-4 border-gray-400 p-3 mb-4 rounded-lg">
                    <div class="flex items-center gap-2">
                        <i class="bi bi-info-circle-fill text-gray-600"></i>
                        <p class="text-sm text-gray-700">
                            <strong>Tidak ada data iklan untuk periode ini.</strong> Admin telah memvalidasi bahwa tidak ada aktivitas iklan.
                        </p>
                    </div>
                </div>
                @endif
                
                <!-- 3 CARDS COMPACT -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                    <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="text-xs text-blue-600 font-medium mb-1">Chat Masuk</p>
                                <p class="text-2xl font-bold text-blue-800">{{ $advertisementChatCount ?? 0 }}</p>
                            </div>
                            <i class="bi bi-chat-left-text text-blue-500 text-2xl"></i>
                        </div>
                        <p class="text-xs text-blue-600">Total chat masuk periode ini</p>
                    </div>
                    
                    <div class="bg-orange-50 p-4 rounded-lg border-l-4 border-orange-500">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="text-xs text-orange-600 font-medium mb-1">Follow Up</p>
                                <p class="text-2xl font-bold text-orange-800">{{ $advertisementFollowupCount ?? 0 }}</p>
                            </div>
                            <i class="bi bi-telephone text-orange-500 text-2xl"></i>
                        </div>
                        <p class="text-xs text-orange-600">Total follow up periode ini</p>
                    </div>
                    
                    <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-500">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="text-xs text-green-600 font-medium mb-1">Closing</p>
                                <p class="text-2xl font-bold text-green-800">{{ $advertisementClosingCount ?? 0 }}</p>
                                <p class="text-sm text-green-700 mt-1">Rp {{ number_format($advertisementClosingAmount ?? 0, 0, ',', '.') }}</p>
                            </div>
                            <i class="bi bi-currency-dollar text-green-500 text-2xl"></i>
                        </div>
                        <p class="text-xs text-green-600">Total closing periode ini</p>
                    </div>
                </div>
                
                <!-- GRAFIK COMPACT -->
                <div class="bg-gray-50 p-3 rounded-lg">
                    <h3 class="text-xs font-semibold text-gray-700 mb-2">Grafik Data Iklan (Per Hari)</h3>
                    <canvas id="advertisementChart" height="60"></canvas>
                </div>
            </div>
            @endif

            <!-- INCOME STATEMENT COMPACT -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
                <!-- OMSET -->
                <div class="bg-white p-4 rounded-xl shadow border-l-4 border-green-500">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="text-xs text-gray-500 font-medium">OMSET</p>
                            <p class="text-xl font-bold text-green-600">
                                Rp {{ number_format($omset, 0, ',', '.') }}
                            </p>
                        </div>
                        <i class="bi bi-arrow-down-circle text-green-500 text-lg"></i>
                    </div>
                    <p class="text-xs text-gray-500">Penjualan: Rp {{ number_format($totalSales, 0, ',', '.') }}</p>
                </div>

                <!-- HPP -->
                <div class="bg-white p-4 rounded-xl shadow border-l-4 border-red-500">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="text-xs text-gray-500 font-medium">HPP</p>
                            <p class="text-xl font-bold text-red-600">
                                Rp {{ number_format($hpp, 0, ',', '.') }}
                            </p>
                        </div>
                        <i class="bi bi-box-seam text-red-500 text-lg"></i>
                    </div>
                    <p class="text-xs text-gray-500">Cost of Goods Sold</p>
                </div>

                <!-- GROSS PROFIT (HPP TERKAIT PENJUALAN) -->
                <div class="bg-white p-4 rounded-xl shadow border-l-4 border-teal-500">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="text-xs text-gray-500 font-medium">GROSS PROFIT</p>
                            <p class="text-xl font-bold text-teal-700">
                                Rp {{ number_format($grossProfitLinked ?? 0, 0, ',', '.') }}
                            </p>
                        </div>
                        <i class="bi bi-graph-up-arrow text-teal-500 text-lg"></i>
                    </div>
                    <p class="text-xs text-gray-500">Pendapatan penjualan - HPP dari PO terhubung</p>
                    <p class="text-[11px] text-gray-400 mt-1">
                        Total nya :
                        Rp {{ number_format($linkedHpp ?? 0, 0, ',', '.') }}
                    </p>
                </div>

                <!-- OPERASIONAL -->
                <div class="bg-white p-4 rounded-xl shadow border-l-4 border-orange-500">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="text-xs text-gray-500 font-medium">OPERASIONAL</p>
                            <p class="text-xl font-bold text-orange-600">
                                Rp {{ number_format($operasional, 0, ',', '.') }}
                            </p>
                        </div>
                        <i class="bi bi-tools text-orange-500 text-lg"></i>
                    </div>
                    <p class="text-xs text-gray-500">Operating Expenses</p>
                </div>

                <!-- CASH TRANSFER -->
                <div class="bg-white p-4 rounded-xl shadow border-l-4 border-purple-500">
                    <div class="flex items-center justify-between mb-2">
            <div>
                            <p class="text-xs text-gray-500 font-medium">TRANSFER TUNAI</p>
                            <p class="text-xl font-bold text-purple-600">
                    @php
                        $totalCashTransfer = \App\Models\CashTransfer::whereBetween('created_at', [$start, $end])->sum('amount') ?? 0;
                    @endphp
                    Rp {{ number_format($totalCashTransfer, 0, ',', '.') }}
                </p>
            </div>
                        <i class="bi bi-arrow-left-right text-purple-500 text-lg"></i>
            </div>
                    <p class="text-xs text-gray-500">Setor/Tukar Tunai</p>
    </div>

                <!-- PROFIT -->
                <div class="bg-white p-4 rounded-xl shadow border-l-4 border-blue-500">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="text-xs text-gray-500 font-medium">PROFIT</p>
                            <p class="text-xl font-bold {{ $profit >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                                Rp {{ number_format($profit, 0, ',', '.') }}
                            </p>
                        </div>
                        <i class="bi bi-graph-up {{ $profit >= 0 ? 'text-blue-500' : 'text-red-500' }} text-lg"></i>
                    </div>
                    <p class="text-xs {{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium">
                        {{ $profit >= 0 ? 'Laba' : 'Rugi' }}
                    </p>
                </div>
            </div>

            <!-- BREAKDOWN STATUS PEMBAYARAN COMPACT -->
<div class="bg-white p-4 rounded-xl shadow mb-4">
    <h2 class="text-sm font-semibold text-gray-800 mb-3">📊 Breakdown Status Pembayaran</h2>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
        <!-- TOTAL ORDERS -->
        <div class="bg-blue-50 p-3 rounded-lg border-l-4 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-blue-600 text-xs font-medium">Total Orders</p>
                    <p class="text-xl font-bold text-blue-800">{{ $salesBreakdown->total_orders ?? 0 }}</p>
                </div>
                <i class="bi bi-receipt text-blue-500"></i>
            </div>
        </div>

        <!-- LUNAS -->
        <div class="bg-green-50 p-3 rounded-lg border-l-4 border-green-500">
            <div class="flex justify-between items-center mb-1">
                <div>
                    <p class="text-green-600 text-xs font-medium">Lunas</p>
                    <p class="text-xl font-bold text-green-800">{{ $salesBreakdown->lunas_count ?? 0 }}</p>
                </div>
                <i class="bi bi-check-circle text-green-500"></i>
            </div>
            <p class="text-xs text-green-700">Rp {{ number_format($salesBreakdown->lunas_amount ?? 0, 0, ',', '.') }}</p>
        </div>

        <!-- DP -->
        <div class="bg-yellow-50 p-3 rounded-lg border-l-4 border-yellow-500">
            <div class="flex justify-between items-center mb-1">
                <div>
                    <p class="text-yellow-600 text-xs font-medium">DP</p>
                    <p class="text-xl font-bold text-yellow-800">{{ $salesBreakdown->dp_count ?? 0 }}</p>
                </div>
                <i class="bi bi-currency-dollar text-yellow-500"></i>
            </div>
            <p class="text-xs text-yellow-700">Rp {{ number_format($salesBreakdown->dp_amount ?? 0, 0, ',', '.') }}</p>
        </div>

        <!-- BELUM BAYAR -->
        <div class="bg-red-50 p-3 rounded-lg border-l-4 border-red-500">
            <div class="flex justify-between items-center mb-1">
                <div>
                    <p class="text-red-600 text-xs font-medium">Belum Bayar</p>
                    <p class="text-xl font-bold text-red-800">{{ $salesBreakdown->belum_bayar_count ?? 0 }}</p>
                </div>
                <i class="bi bi-clock text-red-500"></i>
            </div>
            <p class="text-xs text-red-700">Rp {{ number_format($salesBreakdown->belum_bayar_amount ?? 0, 0, ',', '.') }}</p>
        </div>
    </div>

    @if($pelunasanData && $pelunasanData->count > 0)
    <div class="bg-purple-50 p-2 rounded-lg border border-purple-200 text-xs">
        <span class="font-semibold text-purple-800">Pelunasan:</span>
        <span class="text-purple-600">{{ $pelunasanData->count }} transaksi • Rp {{ number_format($pelunasanData->amount, 0, ',', '.') }}</span>
    </div>
    @endif
</div>

<!-- THREE COLUMN LAYOUT COMPACT - SAMA TINGGI -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <!-- PAYMENT METHODS BREAKDOWN -->
    <div class="bg-white p-4 rounded-xl shadow flex flex-col" style="height: 400px;">
        <h2 class="text-sm font-semibold text-gray-800 mb-3">💳 Metode Pembayaran</h2>
        
        <div class="space-y-2 overflow-y-auto flex-1">
            @foreach($salesByPaymentMethod as $method)
            <div class="flex justify-between items-center p-2 bg-gray-50 rounded text-xs">
                <div class="flex items-center gap-2">
                    @if($method->method === 'cash')
                        <i class="bi bi-cash-coin text-green-500"></i>
                    @elseif($method->method === 'transfer')
                        <i class="bi bi-bank text-blue-500"></i>
                    @else
                        <i class="bi bi-arrow-left-right text-purple-500"></i>
                    @endif
                    <span class="font-medium capitalize">{{ $method->method }}</span>
                </div>
                <div class="text-right">
                    <p class="font-semibold">Rp {{ number_format($method->total_amount, 0, ',', '.') }}</p>
                    <p class="text-gray-500">{{ $method->transaction_count }} transaksi</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

<!-- RECENT TRANSACTIONS COMPACT -->
<div class="bg-white p-4 rounded-xl shadow flex flex-col" style="height: 400px;">
    <h2 class="text-sm font-semibold text-gray-800 mb-3">📋 Transaksi Terbaru</h2>
    
    @if($recentSales->count() > 0)
    <div class="space-y-2 overflow-y-auto flex-1">
        @foreach($recentSales as $sale)
        <div class="flex justify-between items-center p-2 border rounded text-xs hover:bg-gray-50">
            <div class="flex-1">
                <div class="flex justify-between items-start mb-1">
                    <div>
                        <p class="font-medium text-sm">{{ $sale->so_number }}</p>
                        <p class="text-xs text-gray-600">{{ $sale->customer->name ?? 'Guest' }}</p>
                    </div>
                    <div class="text-right">
                        <!-- STATUS PEMBAYARAN -->
                        @if($sale->payment_status === 'lunas')
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium">
                                <i class="bi bi-check-circle mr-1"></i>Lunas
                            </span>
                        @elseif($sale->payment_status === 'dp')
                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-medium">
                                <i class="bi bi-currency-dollar mr-1"></i>DP
                            </span>
                        @else
                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-medium">
                                <i class="bi bi-clock mr-1"></i>Belum Bayar
                            </span>
                        @endif
                    </div>
                </div>
                
                <!-- DETAIL PEMBAYARAN -->
                <div class="grid grid-cols-3 gap-2 text-xs text-gray-600 mt-2">
                    <div>
                        <span class="font-medium">Total:</span>
                        <br>Rp {{ number_format($sale->grand_total, 0, ',', '.') }}
                    </div>
                    <div>
                        <span class="font-medium">Dibayar:</span>
                        <br>Rp {{ number_format($sale->paid_total, 0, ',', '.') }}
                    </div>
                    <div class="{{ $sale->remaining_amount > 0 ? 'text-red-600' : 'text-green-600' }}">
                        <span class="font-medium">Sisa:</span>
                        <br>Rp {{ number_format($sale->remaining_amount, 0, ',', '.') }}
                    </div>
                </div>
                
                <!-- STATUS ORDER -->
                <div class="mt-2">
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium">
                        {{ ucfirst(str_replace('_', ' ', $sale->status)) }}
                    </span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="flex-1 flex items-center justify-center text-gray-500 text-xs">
        <div class="text-center">
        <i class="bi bi-receipt text-3xl text-gray-400 mb-2"></i>
        <p>Tidak ada transaksi</p>
        <p class="text-sm">(Exclude draft orders)</p>
        </div>
    </div>
    @endif
</div>

    <!-- PRODUK TERLARIS COMPACT -->
    <div class="bg-white p-4 rounded-xl shadow flex flex-col" style="height: 400px;">
        <h2 class="text-sm font-semibold text-gray-800 mb-3">🏆 Produk Terlaris</h2>
        
        @if($bestSellingProducts->count() > 0)
        <div class="space-y-2 overflow-y-auto flex-1">
            @foreach($bestSellingProducts as $product)
            <div class="flex justify-between items-center p-2 bg-yellow-50 rounded text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-5 h-5 bg-yellow-500 rounded-full flex items-center justify-center text-white text-xs font-bold">#{{ $loop->iteration }}</span>
                    <div>
                        <p class="font-medium">{{ $product->product_name }}</p>
                        <p class="text-gray-500">{{ $product->product_sku }}</p>
                    </div>
                </div>
                <span class="font-semibold text-yellow-700">{{ number_format($product->total_terjual) }} pcs</span>
            </div>
            @endforeach
        </div>
        @else
        <div class="flex-1 flex items-center justify-center text-gray-500 text-xs">
            <div class="text-center">
                <i class="bi bi-box text-2xl text-gray-400 mb-1"></i>
            <p>Belum ada penjualan produk</p>
            </div>
        </div>
        @endif
    </div>
</div>

            <!-- === MONITORING IKLAN & TARGET COMPACT === -->
            @if(isset($monthlySales) && isset($grossProfit))
            <div class="bg-white p-4 rounded-xl shadow-lg mb-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i class="bi bi-megaphone text-blue-600"></i>
                        Monitoring Iklan & Target
                    </h2>
                    <form method="GET" action="{{ route('finance.dashboard') }}" class="flex items-center gap-2">
                        <input type="hidden" name="start_date" value="{{ $startDate }}">
                        <input type="hidden" name="end_date" value="{{ $endDate }}">
                        <label class="text-xs font-medium text-gray-700">Bulan:</label>
                        <select name="advertisement_month" onchange="this.form.submit()" class="border rounded-lg px-2 py-1 text-xs">
                            @foreach($availableMonths as $month)
                                @php
                                    $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $month);
                                    $isCurrentMonth = $month === now()->format('Y-m');
                                    $isSelected = $month === $selectedMonth;
                                @endphp
                                <option value="{{ $month }}" {{ $isSelected ? 'selected' : '' }}>
                                    {{ $monthDate->translatedFormat('F Y') }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                @php
                    $grossProfitIsPositive = $grossProfit >= 0;
                    $statusColor = $grossProfit >= $targetGrossProfit ? 'text-green-600' : ($grossProfitIsPositive ? 'text-amber-600' : 'text-red-600');
                    $invoiceStatusColor = ($currentMonthInvoiceCount ?? 0) >= ($invoiceTarget ?? 0) ? 'text-green-600' : 'text-amber-600';
                @endphp

                <!-- 2 COLUMNS: TARGET GROSS PROFIT & TARGET INVOICE -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                    <!-- Target Gross Profit -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-3 rounded-lg border-l-4 border-blue-500">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="text-xs font-semibold text-gray-600 uppercase">Target Gross Profit</p>
                                <h3 class="text-lg font-bold text-gray-900">Rp {{ number_format($targetGrossProfit, 0, ',', '.') }}</h3>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Hari {{ $currentDay }}/{{ $daysInMonth }}</p>
                                <p class="text-sm font-semibold {{ $statusColor }}">{{ $grossProfit >= $targetGrossProfit ? '✓ Tercapai' : 'Perlu Akselerasi' }}</p>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs font-medium text-gray-700">
                                <span>Progress</span>
                                <span>{{ number_format($grossProfitProgress, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full {{ $grossProfitProgress >= 100 ? 'bg-green-500' : 'bg-blue-500' }}" style="width: {{ min(100, max(0, $grossProfitProgress)) }}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-600">
                                <span>Realisasi: <span class="font-semibold {{ $statusColor }}">Rp {{ number_format($grossProfit, 0, ',', '.') }}</span></span>
                                @if($grossProfitShortfall > 0)
                                    <span class="text-red-600">Kurang: Rp {{ number_format($grossProfitShortfall, 0, ',', '.') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Target Invoice -->
                    @if(isset($invoiceTarget) && isset($currentMonthInvoiceCount))
                    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 p-3 rounded-lg border-l-4 border-indigo-500">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="text-xs font-semibold text-gray-600 uppercase">Target Invoice</p>
                                <h3 class="text-lg font-bold text-gray-900">{{ number_format($invoiceTarget, 0, ',', '.') }} Nota</h3>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Hari {{ $currentDay }}/{{ $daysInMonth }}</p>
                                <p class="text-sm font-semibold {{ $invoiceStatusColor }}">{{ $currentMonthInvoiceCount >= $invoiceTarget ? '✓ Tercapai' : 'Perlu Akselerasi' }}</p>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs font-medium text-gray-700">
                                <span>Progress</span>
                                <span>{{ number_format($invoiceProgress, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full {{ $invoiceProgress >= 100 ? 'bg-green-500' : 'bg-indigo-500' }}" style="width: {{ min(100, max(0, $invoiceProgress)) }}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-600">
                                <span>Realisasi: <span class="font-semibold {{ $invoiceStatusColor }}">{{ number_format($currentMonthInvoiceCount, 0, ',', '.') }} nota</span></span>
                                @if($remainingInvoiceTarget > 0)
                                    <span class="text-red-600">Kurang: {{ number_format($remainingInvoiceTarget, 0, ',', '.') }} nota</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500">Bulan lalu: {{ number_format($previousMonthInvoiceCount, 0, ',', '.') }} nota</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- QUICK ACTIONS -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('finance.shift.history') }}" 
                   class="bg-white p-4 rounded-xl shadow border hover:border-blue-500 transition-colors text-center group">
                    <i class="bi bi-clock-history text-blue-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <p class="font-semibold">Shift History</p>
                    <p class="text-xs text-gray-600">Lihat riwayat shift</p>
                </a>

                <a href="{{ route('finance.purchases.index') }}" 
                   class="bg-white p-4 rounded-xl shadow border hover:border-green-500 transition-colors text-center group">
                    <i class="bi bi-cart-check text-green-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <p class="font-semibold">Data Pembelian</p>
                    <p class="text-xs text-gray-600">Lihat purchase orders</p>
                </a>

                <a href="{{ route('finance.shift.export') }}" 
                   class="bg-white p-4 rounded-xl shadow border hover:border-purple-500 transition-colors text-center group">
                    <i class="bi bi-file-earmark-excel text-purple-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <p class="font-semibold">Export Laporan</p>
                    <p class="text-xs text-gray-600">Download Excel</p>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    // Date Range Picker untuk Filter Utama (FIX TIMEZONE ISSUE)
    const dateRangePicker = flatpickr("#dateRangePicker", {
        mode: "range",
        dateFormat: "Y-m-d",
        defaultDate: ["{{ $startDate }}", "{{ $endDate }}"],
        time_24hr: true,
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                // Format tanggal tanpa timezone conversion (langsung ambil YYYY-MM-DD)
                const startDate = selectedDates[0].getFullYear() + '-' + 
                                 String(selectedDates[0].getMonth() + 1).padStart(2, '0') + '-' + 
                                 String(selectedDates[0].getDate()).padStart(2, '0');
                const endDate = selectedDates[1].getFullYear() + '-' + 
                               String(selectedDates[1].getMonth() + 1).padStart(2, '0') + '-' + 
                               String(selectedDates[1].getDate()).padStart(2, '0');
                document.getElementById('start_date').value = startDate;
                document.getElementById('end_date').value = endDate;
            }
        }
    });

    @if(isset($advertisementChatCount))
    // Chart.js untuk Grafik Iklan
    @if(isset($advertisementChartData) && isset($advertisementChartDates))
    const ctx = document.getElementById('advertisementChart').getContext('2d');
    const chartData = @json($advertisementChartData);
    const chartDates = @json($advertisementChartDates);
    
    const chatData = chartDates.map(date => chartData[date]?.chat || 0);
    const followupData = chartDates.map(date => chartData[date]?.followup || 0);
    const closingData = chartDates.map(date => chartData[date]?.closing || 0);
    const labels = chartDates.map(date => {
        const d = new Date(date);
        return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
    });

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Chat Masuk',
                    data: chatData,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Follow Up',
                    data: followupData,
                    borderColor: 'rgb(249, 115, 22)',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Closing',
                    data: closingData,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    @endif
    @endif
</script>
</body>
</html>