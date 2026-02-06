<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Owner Dashboard - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
      body { font-family: 'Raleway', sans-serif; }
      .pulse-alert { animation: pulse 2s infinite; }
      @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
        100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
      }
    </style>
  </head>
  <body class="bg-gray-100">
    <div class="flex">
      <x-navbar-owner></x-navbar-owner>
      <div class="flex-1 lg:w-5/6">
        <x-navbar-top-owner></x-navbar-top-owner>
        <div class="p-4 lg:p-6 space-y-4">
          
          <!-- [SECTION 1] HEADER & FILTERS -->
          <div class="bg-white p-4 rounded-xl shadow-lg">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
              <div>
                <h2 class="text-xl font-bold text-gray-800">Dashboard Owner</h2>
                <p class="text-sm text-gray-600">{{ now()->format('l, d F Y') }}</p>
              </div>
              <form method="GET" id="filterForm" class="flex flex-col sm:flex-row gap-2">
                <div class="flex gap-2">
                  <input type="text" id="dateRangePicker" name="date_range" 
                         value="{{ $startDate }} to {{ $endDate }}" 
                         placeholder="Pilih rentang tanggal" 
                         class="border rounded-lg px-3 py-2 text-sm w-full sm:w-auto" readonly>
                  <input type="hidden" name="start_date" id="start_date" value="{{ $startDate }}">
                  <input type="hidden" name="end_date" id="end_date" value="{{ $endDate }}">
                  <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Filter</button>
                </div>
                <div class="flex gap-2">
                  <a href="?start_date={{ now()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Hari Ini</a>
                  <a href="?start_date={{ now()->startOfMonth()->format('Y-m-d') }}&end_date={{ now()->endOfMonth()->format('Y-m-d') }}" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Bulan Ini</a>
                </div>
              </form>
            </div>
          </div>

          <!-- [SECTION 2] ALERTS & TODAY'S PERFORMANCE (Priority 1: Danger Zone & Pulse) -->
          <!-- [SECTION 2] ALERTS (Priority 1: Danger Zone) -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- DEADLINE ALERTS: TERLEWAT -->
            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-red-500 {{ ($overdueCount ?? 0) > 0 ? 'pulse-alert' : '' }}">
                <div class="flex items-center justify-between mb-2">
                  <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                    <i class="bi bi-exclamation-octagon text-red-500"></i>
                    Deadline Terlewat
                  </h3>
                  <div class="flex items-center gap-2">
                    @if(($overdueCount ?? 0) > 0)
                      <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-bold">{{ $overdueCount }}</span>
                    @endif
                    <a href="{{ route('owner.sales.index', ['filter' => 'overdue']) }}" class="text-[10px] text-red-600 hover:text-red-700 font-semibold flex items-center gap-1">
                      Lihat Semua <i class="bi bi-arrow-right"></i>
                    </a>
                  </div>
                </div>

                @if(($overdueCount ?? 0) > 0)
                <div class="space-y-1.5 max-h-48 overflow-y-auto">
                  @foreach($overdueOrders as $order)
                    @php $daysLate = \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($order->deadline)->startOfDay()); @endphp
                    <a href="{{ route('owner.sales.show', $order->id) }}" class="block group">
                        <div class="flex justify-between items-center p-2 bg-red-50 rounded text-xs group-hover:bg-red-100 transition-colors border border-transparent group-hover:border-red-200">
                          <div>
                            <div class="font-semibold text-gray-800 group-hover:text-blue-700">
                              {{ $order->so_number }}
                            </div>
                            <p class="text-gray-600">{{ $order->customer->name ?? 'Umum' }}</p>
                          </div>
                          <span class="font-bold text-red-600">{{ $daysLate }} HARI</span>
                        </div>
                    </a>
                  @endforeach
                </div>
                @else
                <div class="text-center py-4">
                    <i class="bi bi-check-circle text-green-500 text-2xl mb-1"></i>
                    <p class="text-gray-500 text-xs">Tidak ada deadline terlewat 🎉</p>
                </div>
                @endif
            </div>

            <!-- DEADLINE ALERTS: MENDEKAT -->
            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-orange-500">
                <div class="flex items-center justify-between mb-2">
                  <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                    <i class="bi bi-exclamation-triangle text-orange-500"></i>
                    Deadline Mendekat (≤5 hari)
                  </h3>
                  <div class="flex items-center gap-2">
                    @if(($upcomingCount ?? 0) > 0)
                      <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs font-bold">{{ $upcomingCount }}</span>
                    @endif
                    <a href="{{ route('owner.sales.index', ['filter' => 'upcoming']) }}" class="text-[10px] text-orange-600 hover:text-orange-700 font-semibold flex items-center gap-1">
                      Lihat Semua <i class="bi bi-arrow-right"></i>
                    </a>
                  </div>
                </div>

                @if(($upcomingCount ?? 0) > 0)
                <div class="space-y-1.5 max-h-48 overflow-y-auto">
                  @foreach($upcomingOrders as $order)
                    @php
                      $daysLeft = \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($order->deadline)->startOfDay(), false);
                      $isToday = $daysLeft == 0;
                      $statusText = $isToday ? 'HARI INI' : ($daysLeft <= 1 ? '1 HARI' : $daysLeft . ' HARI');
                      $bgColor = $isToday ? 'bg-red-50' : ($daysLeft <= 1 ? 'bg-orange-50' : 'bg-yellow-50');
                      $hoverColor = $isToday ? 'group-hover:bg-red-100' : ($daysLeft <= 1 ? 'group-hover:bg-orange-100' : 'group-hover:bg-yellow-100');
                    @endphp
                    <a href="{{ route('owner.sales.show', $order->id) }}" class="block group">
                        <div class="flex justify-between items-center p-2 {{ $bgColor }} {{ $hoverColor }} rounded text-xs transition-colors border border-transparent group-hover:border-gray-200">
                          <div>
                            <div class="font-semibold text-gray-800 group-hover:text-blue-700">
                              {{ $order->so_number }}
                            </div>
                            <p class="text-gray-600">{{ $order->customer->name ?? 'Umum' }}</p>
                          </div>
                          <span class="font-bold text-orange-600">{{ $statusText }}</span>
                        </div>
                    </a>
                  @endforeach
                </div>
                @else
                <div class="text-center py-4">
                    <i class="bi bi-check-circle text-green-500 text-2xl mb-1"></i>
                    <p class="text-gray-500 text-xs">Tidak ada deadline mendekat 🎉</p>
                </div>
                @endif
            </div>
          </div>

          <!-- [SECTION 3] TODAY'S PERFORMANCE & PIUTANG -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
              <!-- TODAY'S PERFORMANCE -->
              <div class="bg-white p-4 rounded-xl shadow border-l-4 border-green-500 h-full">
                <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                  <i class="bi bi-graph-up text-green-500"></i>
                  Performa Hari Ini
                </h3>
                <div class="grid grid-cols-2 gap-2">
                  <a href="{{ route('owner.sales.index') }}" class="group text-center p-2 bg-green-50 rounded hover:bg-green-100 transition-colors cursor-pointer block">
                    <div class="text-lg font-bold text-green-700 group-hover:scale-110 transition-transform">{{ $todayStats['transactions'] ?? 0 }}</div>
                    <div class="text-xs text-green-600">Transaksi</div>
                  </a>
                  <div class="text-center p-2 bg-blue-50 rounded">
                    <div class="text-lg font-bold text-blue-700">Rp {{ number_format($todayStats['revenue'] ?? 0, 0, ',', '.') }}</div>
                    <div class="text-xs text-blue-600">Pendapatan</div>
                  </div>
                  <div class="text-center p-2 bg-purple-50 rounded">
                    <div class="text-lg font-bold text-purple-700">{{ $todayStats['customers'] ?? 0 }}</div>
                    <div class="text-xs text-purple-600">Customer</div>
                  </div>
                  <div class="text-center p-2 bg-orange-50 rounded">
                    <div class="text-lg font-bold text-orange-700">Rp {{ number_format($todayStats['avg_transaction'] ?? 0, 0, ',', '.') }}</div>
                    <div class="text-xs text-orange-600">Rata-rata</div>
                  </div>
                </div>
              </div>

              <!-- PIUTANG -->
              @if(($pendingPaymentsCount ?? 0) > 0)
              <div class="bg-white p-4 rounded-xl shadow border-l-4 border-yellow-500 h-full">
                <div class="flex items-center justify-between mb-2">
                  <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                    <i class="bi bi-clock text-yellow-500"></i>
                    Belum Lunas
                  </h3>
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-bold">{{ $pendingPaymentsCount }}</span>
                    <a href="{{ route('owner.sales.index', ['payment_status' => 'dp']) }}" class="text-[10px] text-yellow-700 hover:text-yellow-800 font-semibold flex items-center gap-1">
                      Lihat Semua <i class="bi bi-arrow-right"></i>
                    </a>
                  </div>
                </div>
                <div class="flex justify-between items-center mb-2">
                     <p class="text-xs text-gray-600">Total Piutang:</p>
                     <span class="font-bold text-lg text-yellow-700">Rp {{ number_format($totalPiutang ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="space-y-1.5 overflow-y-auto" style="max-height: 140px;">
                  @foreach($pendingPayments as $order)
                    <a href="{{ route('owner.sales.show', $order->id) }}" class="block group">
                        <div class="flex justify-between items-center p-2 bg-yellow-50 rounded text-xs group-hover:bg-yellow-100 transition-colors border border-transparent group-hover:border-yellow-200">
                          <div>
                             <div class="font-semibold text-gray-800 group-hover:text-blue-700">{{ $order->customer->name ?? 'Guest' }}</div>
                             <div class="text-gray-500">{{ $order->so_number }}</div>
                          </div>
                          <div class="text-right">
                             <div class="text-red-600 font-bold">Rp {{ number_format($order->remaining_amount, 0, ',', '.') }}</div>
                             <div class="text-gray-400 text-[10px]">{{ $order->order_date->format('d M') }}</div>
                          </div>
                        </div>
                    </a>
                  @endforeach
                </div>
              </div>
              @else
              <!-- Empty State for Piutang -->
              <div class="bg-white p-4 rounded-xl shadow border-l-4 border-gray-200 h-full flex flex-col justify-center items-center text-gray-400">
                  <i class="bi bi-check-circle text-4xl mb-2 text-green-500 opacity-50"></i>
                  <p class="text-sm font-medium">Tidak ada piutang jatuh tempo</p>
                  <p class="text-xs">Semua pembayaran lancar</p>
              </div>
              @endif
          </div>

          <!-- [SECTION 3] FINANCIAL OVERVIEW (Priority 2: Financial Health) -->
          <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-green-500">
              <div class="flex justify-between items-start">
                  <p class="text-xs text-gray-500 mb-1">OMSET</p>
                  @if(isset($growthOmset))
                    <span class="text-[10px] font-bold {{ $growthOmset >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        <i class="bi bi-graph-{{ $growthOmset >= 0 ? 'up' : 'down' }}"></i>
                        {{ number_format(abs($growthOmset), 1) }}%
                    </span>
                  @endif
              </div>
              <p class="text-xl font-bold text-green-600">Rp {{ number_format($omset ?? 0, 0, ',', '.') }}</p>
              <p class="text-xs text-gray-400 mt-1">Penjualan + Pemasukan</p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-red-500">
              <p class="text-xs text-gray-500 mb-1">HPP</p>
              <p class="text-xl font-bold text-red-600">Rp {{ number_format($hpp ?? 0, 0, ',', '.') }}</p>
              <p class="text-xs text-gray-400 mt-1">Cost of Goods Sold</p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-teal-500">
              <p class="text-xs text-gray-500 mb-1">GROSS PROFIT</p>
              <p class="text-xl font-bold text-teal-700">Rp {{ number_format($grossProfit ?? 0, 0, ',', '.') }}</p>
              <p class="text-xs text-gray-400 mt-1">Pendapatan penjualan - HPP</p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-orange-500">
              <p class="text-xs text-gray-500 mb-1">OPERASIONAL</p>
              <p class="text-xl font-bold text-orange-600">Rp {{ number_format($operasional ?? 0, 0, ',', '.') }}</p>
              <p class="text-xs text-gray-400 mt-1">Operating Expenses</p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-blue-500">
              <p class="text-xs text-gray-500 mb-1">PROFIT</p>
              <p class="text-xl font-bold {{ ($profit ?? 0) >= 0 ? 'text-blue-600' : 'text-red-600' }}">Rp {{ number_format($profit ?? 0, 0, ',', '.') }}</p>
              <p class="text-xs {{ ($profit ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1 font-medium">{{ ($profit ?? 0) >= 0 ? 'Laba' : 'Rugi' }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-purple-500">
              <p class="text-xs text-gray-500 mb-1">CASH TRANSFER</p>
              <p class="text-xl font-bold text-purple-600">Rp {{ number_format($totalCashTransfer ?? 0, 0, ',', '.') }}</p>
              <p class="text-xs text-gray-400 mt-1">Setor/Tukar Tunai</p>
            </div>
          </div>

          <!-- [SECTION 3.5] FINANCIAL TREND CHART -->
          <div class="bg-white p-4 rounded-xl shadow-lg">
            <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
              <i class="bi bi-graph-up-arrow text-emerald-600"></i>
              Tren Performa Keuangan
            </h2>
            <div class="bg-white p-2 rounded-lg border">
              <canvas id="financialChart" height="80"></canvas>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                    <span class="text-xs text-gray-600">Omset</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                    <span class="text-xs text-gray-600">HPP</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                    <span class="text-xs text-gray-600">Operasional</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-indigo-500"></div>
                    <span class="text-xs text-gray-600">Net Profit</span>
                </div>
            </div>
          </div>

          <!-- [SECTION 4] PAYMENT BREAKDOWN (Part of Financial Health) -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-blue-50 p-4 rounded-xl border-l-4 border-blue-500">
              <p class="text-xs text-blue-600 font-medium mb-1">Total Orders</p>
              <p class="text-2xl font-bold text-blue-800">{{ $salesBreakdown->total_orders ?? 0 }}</p>
            </div>
            <div class="bg-green-50 p-4 rounded-xl border-l-4 border-green-500">
              <p class="text-xs text-green-600 font-medium mb-1">Lunas</p>
              <p class="text-2xl font-bold text-green-800">{{ $salesBreakdown->lunas_count ?? 0 }}</p>
              <p class="text-xs text-green-700">Rp {{ number_format($salesBreakdown->lunas_amount ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-yellow-50 p-4 rounded-xl border-l-4 border-yellow-500">
              <p class="text-xs text-yellow-600 font-medium mb-1">DP</p>
              <p class="text-2xl font-bold text-yellow-800">{{ $salesBreakdown->dp_count ?? 0 }}</p>
              <p class="text-xs text-yellow-700">Rp {{ number_format($salesBreakdown->dp_amount ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-red-50 p-4 rounded-xl border-l-4 border-red-500">
              <p class="text-xs text-red-600 font-medium mb-1">Belum Bayar</p>
              <p class="text-2xl font-bold text-red-800">{{ $salesBreakdown->belum_bayar_count ?? 0 }}</p>
              <p class="text-xs text-red-700">Rp {{ number_format($salesBreakdown->belum_bayar_amount ?? 0, 0, ',', '.') }}</p>
            </div>
          </div>

          <!-- [SECTION 5] MONITORING IKLAN & TARGET (Priority 3: Strategic Tracking) -->
          @if(isset($monthlySales) && isset($grossProfit))
          <div class="bg-white p-4 rounded-xl shadow-lg">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
              <div>
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                  <i class="bi bi-megaphone text-blue-600"></i>
                  Monitoring Iklan & Target
                </h2>
                <p class="text-xs text-gray-600">Bulan: 
                  <form method="GET" class="inline">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    <select name="month" onchange="this.form.submit()" class="border rounded px-2 py-1 text-xs">
                      @foreach($availableMonths as $month)
                        @php $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $month); @endphp
                        <option value="{{ $month }}" {{ $month === $selectedMonth ? 'selected' : '' }}>
                          {{ $monthDate->translatedFormat('F Y') }}
                        </option>
                      @endforeach
                    </select>
                  </form>
                </p>
              </div>
            </div>

            @php
              $grossProfitIsPositive = $grossProfit >= 0;
              $statusColor = $grossProfit >= $targetGrossProfit ? 'text-green-600' : ($grossProfitIsPositive ? 'text-amber-600' : 'text-red-600');
              $invoiceStatusColor = ($currentMonthInvoiceCount ?? 0) >= ($invoiceTarget ?? 0) ? 'text-green-600' : 'text-amber-600';
            @endphp

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
              <!-- Target Gross Profit -->
              <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-4 rounded-lg border-l-4 border-blue-500">
                <div class="flex items-center justify-between mb-2">
                  <div>
                    <p class="text-xs font-semibold text-gray-600 uppercase">Target Gross Profit</p>
                    <h3 class="text-xl font-bold text-gray-900">Rp {{ number_format($targetGrossProfit, 0, ',', '.') }}</h3>
                  </div>
                  <div class="text-right">
                    <p class="text-xs text-gray-500">Hari {{ $currentDay }}/{{ $daysInMonth }}</p>
                    <p class="text-sm font-semibold {{ $statusColor }}">{{ $grossProfit >= $targetGrossProfit ? '✓ Tercapai' : 'Perlu Akselerasi' }}</p>
                  </div>
                </div>
                <div class="space-y-1.5">
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
              <div class="bg-gradient-to-br from-indigo-50 to-purple-50 p-4 rounded-lg border-l-4 border-indigo-500">
                <div class="flex items-center justify-between mb-2">
                  <div>
                    <p class="text-xs font-semibold text-gray-600 uppercase">Target Invoice</p>
                    <h3 class="text-xl font-bold text-gray-900">{{ number_format($invoiceTarget, 0, ',', '.') }} Nota</h3>
                  </div>
                  <div class="text-right">
                    <p class="text-xs text-gray-500">Hari {{ $currentDay }}/{{ $daysInMonth }}</p>
                    <p class="text-sm font-semibold {{ $invoiceStatusColor }}">{{ $currentMonthInvoiceCount >= $invoiceTarget ? '✓ Tercapai' : 'Perlu Akselerasi' }}</p>
                  </div>
                </div>
                <div class="space-y-1.5">
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

          <!-- [SECTION 6] ADVERTISEMENT PERFORMANCE CHART -->
          @if(isset($advertisementChatCount))
          <div class="bg-white p-4 rounded-xl shadow-lg">
            <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
              <i class="bi bi-megaphone text-blue-600"></i>
              Data Iklan
            </h2>
            
            @if(isset($advertisementHasActualData) && !$advertisementHasActualData)
                @if(!empty($advertisementHasValidatedEmpty))
                  <div class="bg-gray-50 border-l-4 border-gray-400 p-3 mb-4 rounded-lg">
                    <div class="flex items-center gap-2">
                      <i class="bi bi-info-circle-fill text-gray-600"></i>
                      <p class="text-sm text-gray-700">
                        <strong>Tidak ada data iklan untuk periode ini.</strong> Admin telah memvalidasi bahwa tidak ada aktivitas iklan.
                      </p>
                    </div>
                  </div>
                @elseif(empty($advertisementHasAnyData))
                  <div class="bg-gray-50 border-l-4 border-gray-400 p-3 mb-4 rounded-lg">
                    <div class="flex items-center gap-2">
                      <i class="bi bi-info-circle-fill text-gray-600"></i>
                      <p class="text-sm text-gray-700">
                        <strong>Belum ada data iklan untuk periode ini.</strong> Silakan input atau validasi aktivitas.
                      </p>
                    </div>
                  </div>
                @else
                  <div class="bg-gray-50 border-l-4 border-gray-400 p-3 mb-4 rounded-lg">
                    <div class="flex items-center gap-2">
                      <i class="bi bi-info-circle-fill text-gray-600"></i>
                      <p class="text-sm text-gray-700">
                        <strong>Tidak ada data iklan untuk periode ini.</strong> Data ada, namun belum ada aktivitas (menunggu input).
                      </p>
                    </div>
                  </div>
                @endif
            @endif
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
              <div class="bg-blue-50 p-4 rounded-xl border-l-4 border-blue-500">
                <div class="flex items-center justify-between mb-2">
                  <div>
                    <p class="text-xs text-blue-600 font-medium mb-1">Chat Masuk</p>
                    <p class="text-2xl font-bold text-blue-800">{{ $advertisementChatCount ?? 0 }}</p>
                  </div>
                  <i class="bi bi-chat-left-text text-blue-500 text-2xl"></i>
                </div>
                <p class="text-xs text-blue-600">Total chat masuk periode ini</p>
              </div>
              
              <div class="bg-orange-50 p-4 rounded-xl border-l-4 border-orange-500">
                <div class="flex items-center justify-between mb-2">
                  <div>
                    <p class="text-xs text-orange-600 font-medium mb-1">Follow Up</p>
                    <p class="text-2xl font-bold text-orange-800">{{ $advertisementFollowupCount ?? 0 }}</p>
                  </div>
                  <i class="bi bi-telephone text-orange-500 text-2xl"></i>
                </div>
                <p class="text-xs text-orange-600">Total follow up periode ini</p>
              </div>
              
              <div class="bg-green-50 p-4 rounded-xl border-l-4 border-green-500">
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
            
            <div class="bg-white p-4 rounded-lg border">
              <h3 class="text-sm font-semibold text-gray-800 mb-3">Grafik Data Iklan</h3>
              <canvas id="advertisementChart" height="80"></canvas>
            </div>
          </div>
          @endif

          <!-- [SECTION 7] THREE COLUMN ANALYSIS (Payment Methods, Trans Type, Best Selling) -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Payment Methods -->
            <div class="bg-white p-4 rounded-xl shadow">
              <h3 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <i class="bi bi-credit-card text-blue-500"></i>
                Metode Pembayaran
              </h3>
              <div class="space-y-2">
                @if(isset($salesByPaymentMethod) && is_iterable($salesByPaymentMethod))
                  @foreach($salesByPaymentMethod as $method)
                    @if(isset($method) && is_object($method))
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded text-xs">
                      <div class="flex items-center gap-2">
                        @php $methodType = $method->method ?? ''; @endphp
                        @if($methodType === 'cash')
                          <i class="bi bi-cash-coin text-green-500"></i>
                        @elseif($methodType === 'transfer')
                          <i class="bi bi-bank text-blue-500"></i>
                        @else
                          <i class="bi bi-arrow-left-right text-purple-500"></i>
                        @endif
                        <span class="font-medium capitalize">{{ $methodType }}</span>
                      </div>
                      <div class="text-right">
                        <p class="font-semibold">Rp {{ number_format($method->total_amount ?? 0, 0, ',', '.') }}</p>
                        <p class="text-gray-500">{{ $method->transaction_count ?? 0 }} transaksi</p>
                      </div>
                    </div>
                    @endif
                  @endforeach
                @else
                  <p class="text-xs text-gray-400 text-center py-2">Belum ada data</p>
                @endif
              </div>
            </div>

            <!-- VIP Customers -->
            <div class="bg-white p-4 rounded-xl shadow border-t-4 border-blue-600">
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                  <i class="bi bi-star-fill text-blue-600"></i>
                  Customer VIP (Top Spender)
                </h3>
                <a href="{{ route('owner.contacts.index') }}" class="text-[10px] text-blue-600 hover:text-blue-700 font-semibold flex items-center gap-1">
                  Lihat Semua <i class="bi bi-arrow-right"></i>
                </a>
              </div>
              <div class="space-y-2">
                @if(isset($topCustomers) && $topCustomers->count() > 0)
                  @foreach($topCustomers as $customer)
                    <div class="p-2 bg-blue-50 rounded-lg flex justify-between items-center">
                      <div class="min-w-0">
                        <p class="text-xs font-bold text-blue-900 truncate">{{ $customer->customer_name }}</p>
                        <p class="text-[10px] text-blue-600">{{ $customer->total_orders }} Transaksi</p>
                      </div>
                      <div class="text-right">
                        <p class="text-xs font-bold text-blue-800">Rp {{ number_format($customer->total_spent, 0, ',', '.') }}</p>
                      </div>
                    </div>
                  @endforeach
                @else
                  <p class="text-xs text-gray-400 text-center py-4">Belum ada data periode ini</p>
                @endif
              </div>
            </div>

            <!-- Cash Flow Projection -->
            <div class="bg-white p-4 rounded-xl shadow border-t-4 border-emerald-600">
              <h3 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <i class="bi bi-calendar-check text-emerald-600"></i>
                Prediksi Uang Masuk (30 Hari)
              </h3>
              <div class="space-y-2">
                @if(isset($cashFlowProjection) && count($cashFlowProjection) > 0)
                  @foreach($cashFlowProjection as $projection)
                    <div class="p-2 bg-emerald-50 rounded-lg flex justify-between items-center">
                      <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        <p class="text-xs text-emerald-900">{{ $projection['date'] }}</p>
                      </div>
                      <p class="text-xs font-bold text-emerald-800">Rp {{ number_format($projection['total'], 0, ',', '.') }}</p>
                    </div>
                  @endforeach
                  <p class="text-[10px] text-gray-400 italic mt-2">* Berdasarkan tanggal deadline pelunasan DP</p>
                @else
                  <div class="text-center py-4">
                    <i class="bi bi-info-circle text-gray-300 text-xl"></i>
                    <p class="text-xs text-gray-400 mt-1">Tidak ada piutang jatuh tempo</p>
                  </div>
                @endif
              </div>
            </div>

            <!-- Produk Terlaris (Move here for better 3-col balance) -->
            <div class="bg-white p-4 rounded-xl shadow">
              <h3 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <i class="bi bi-trophy text-yellow-500"></i>
                Produk Terlaris
              </h3>
              <div class="space-y-2 max-h-48 overflow-y-auto">
                @if(isset($bestSellingProducts) && $bestSellingProducts->count() > 0)
                  @foreach($bestSellingProducts as $product)
                    @if(isset($product) && is_object($product))
                    <div class="flex justify-between items-center p-2 bg-yellow-50 rounded text-xs">
                      <div class="flex items-center gap-2">
                        <span class="w-5 h-5 bg-yellow-500 rounded-full flex items-center justify-center text-white text-xs font-bold">#{{ $loop->iteration }}</span>
                        <div>
                          <a href="{{ route('owner.product.show', $product->product_id) }}" class="font-medium text-gray-800 hover:text-blue-600 hover:underline decoration-blue-500 underline-offset-2">
                            {{ $product->product_name ?? '-' }}
                          </a>
                          <p class="text-gray-500 text-xs">{{ $product->product_sku ?? '-' }}</p>
                        </div>
                      </div>
                      <span class="font-semibold text-yellow-700">{{ number_format($product->total_terjual ?? 0) }} pcs</span>
                    </div>

                    @endif
                  @endforeach
                @else
                  <p class="text-xs text-gray-400 text-center py-2">Belum ada data</p>
                @endif
              </div>
            </div>

            <!-- Produk Terlaris per Kategori (Grid 5 Sections) -->
            <!-- We break out of the 3-col grid logic above or create a new row spanning full width if needed? 
                 Actually, the previous elements were in a 3-col grid. 
                 To display 5 items comfortably, let's put them in a dedicated grid container spanning full width effectively if we were closing the previous container. 
                 However, looking at the code, we are inside a container. 
                 Let's assume we are replacing one "cell" of the grid. 
                 If we put 5 cards in one cell of a 3-col grid, it will be very tall.
                 The user wants a nice layout.
                 I will close the current </div> (if it's the 3-col grid) and start a new full-width section.
                 Wait, checking line 590: `</div>` closed something? No, it closed "HPP & Target Gross Profit".
                 The 3-col grid starts at `[SECTION 2]`.
                 If I close the grid div `</div>` here, I can start a new grid.
                 Let's try to fit 5 cards. A grid of 5 is awkward (2,2,1 or 3,2).
                 Let's do a uniform design.
            -->
          </div> 
          <!-- End of Upper Grid -->

          <!-- [SECTION 3] 5 CATEGORY BREAKDOWN -->
          <div class="mb-6">
              <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                  <i class="bi bi-tags text-blue-600"></i> Analisa Per Kategori
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                  <!-- 1. KAOS POLOS -->
                  <div class="bg-white p-3 rounded-xl shadow border-t-4 border-blue-500 flex flex-col h-full">
                      <h4 class="font-semibold text-gray-700 mb-3 text-xs uppercase flex items-center gap-2">
                          <i class="bi bi-circle-fill text-blue-500 text-[8px]"></i> Kaos Polos
                      </h4>
                      <div class="overflow-y-auto flex-1 max-h-60 pr-1 custom-scrollbar">
                          <table class="w-full text-xs text-left">
                              <tbody class="divide-y divide-gray-100">
                                  @forelse($catKaosPolos as $item)
                                  <tr class="hover:bg-blue-50 transition-colors">
                                      <td class="py-2 pr-2">
                                          <div class="font-medium text-gray-800 line-clamp-2" title="{{ $item->product_name }}">{{ $item->product_name }}</div>
                                          <div class="text-[10px] text-gray-400">{{ $item->product_sku }}</div>
                                      </td>
                                      <td class="py-2 text-right whitespace-nowrap">
                                          <span class="font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">{{ number_format($item->total_terjual) }}</span>
                                      </td>
                                  </tr>
                                  @empty
                                  <tr><td colspan="2" class="text-center py-8 text-gray-300 italic">Belum ada data</td></tr>
                                  @endforelse
                              </tbody>
                          </table>
                      </div>
                  </div>

                  <!-- 2. KAOS POLO -->
                  <div class="bg-white p-3 rounded-xl shadow border-t-4 border-indigo-500 flex flex-col h-full">
                      <h4 class="font-semibold text-gray-700 mb-3 text-xs uppercase flex items-center gap-2">
                          <i class="bi bi-circle-fill text-indigo-500 text-[8px]"></i> Kaos Polo
                      </h4>
                      <div class="overflow-y-auto flex-1 max-h-60 pr-1 custom-scrollbar">
                          <table class="w-full text-xs text-left">
                              <tbody class="divide-y divide-gray-100">
                                  @forelse($catKaosPolo as $item)
                                  <tr class="hover:bg-indigo-50 transition-colors">
                                      <td class="py-2 pr-2">
                                          <div class="font-medium text-gray-800 line-clamp-2" title="{{ $item->product_name }}">{{ $item->product_name }}</div>
                                          <div class="text-[10px] text-gray-400">{{ $item->product_sku }}</div>
                                      </td>
                                      <td class="py-2 text-right whitespace-nowrap">
                                          <span class="font-bold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">{{ number_format($item->total_terjual) }}</span>
                                      </td>
                                  </tr>
                                  @empty
                                  <tr><td colspan="2" class="text-center py-8 text-gray-300 italic">Belum ada data</td></tr>
                                  @endforelse
                              </tbody>
                          </table>
                      </div>
                  </div>

                  <!-- 3. JAKET -->
                  <div class="bg-white p-3 rounded-xl shadow border-t-4 border-red-500 flex flex-col h-full">
                      <h4 class="font-semibold text-gray-700 mb-3 text-xs uppercase flex items-center gap-2">
                          <i class="bi bi-circle-fill text-red-500 text-[8px]"></i> Jaket
                      </h4>
                      <div class="overflow-y-auto flex-1 max-h-60 pr-1 custom-scrollbar">
                          <table class="w-full text-xs text-left">
                              <tbody class="divide-y divide-gray-100">
                                  @forelse($catJaket as $item)
                                  <tr class="hover:bg-red-50 transition-colors">
                                      <td class="py-2 pr-2">
                                          <div class="font-medium text-gray-800 line-clamp-2" title="{{ $item->product_name }}">{{ $item->product_name }}</div>
                                          <div class="text-[10px] text-gray-400">{{ $item->product_sku }}</div>
                                      </td>
                                      <td class="py-2 text-right whitespace-nowrap">
                                          <span class="font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded">{{ number_format($item->total_terjual) }}</span>
                                      </td>
                                  </tr>
                                  @empty
                                  <tr><td colspan="2" class="text-center py-8 text-gray-300 italic">Belum ada data</td></tr>
                                  @endforelse
                              </tbody>
                          </table>
                      </div>
                  </div>

                  <!-- 4. JERSEY -->
                  <div class="bg-white p-3 rounded-xl shadow border-t-4 border-green-500 flex flex-col h-full">
                      <h4 class="font-semibold text-gray-700 mb-3 text-xs uppercase flex items-center gap-2">
                          <i class="bi bi-circle-fill text-green-500 text-[8px]"></i> Jersey
                      </h4>
                      <div class="overflow-y-auto flex-1 max-h-60 pr-1 custom-scrollbar">
                          <table class="w-full text-xs text-left">
                              <tbody class="divide-y divide-gray-100">
                                  @forelse($catJersey as $item)
                                  <tr class="hover:bg-green-50 transition-colors">
                                      <td class="py-2 pr-2">
                                          <div class="font-medium text-gray-800 line-clamp-2" title="{{ $item->product_name }}">{{ $item->product_name }}</div>
                                          <div class="text-[10px] text-gray-400">{{ $item->product_sku }}</div>
                                      </td>
                                      <td class="py-2 text-right whitespace-nowrap">
                                          <span class="font-bold text-green-600 bg-green-50 px-1.5 py-0.5 rounded">{{ number_format($item->total_terjual) }}</span>
                                      </td>
                                  </tr>
                                  @empty
                                  <tr><td colspan="2" class="text-center py-8 text-gray-300 italic">Belum ada data</td></tr>
                                  @endforelse
                              </tbody>
                          </table>
                      </div>
                  </div>

                  <!-- 5. TOPI -->
                  <div class="bg-white p-3 rounded-xl shadow border-t-4 border-yellow-500 flex flex-col h-full">
                      <h4 class="font-semibold text-gray-700 mb-3 text-xs uppercase flex items-center gap-2">
                          <i class="bi bi-circle-fill text-yellow-500 text-[8px]"></i> Topi
                      </h4>
                      <div class="overflow-y-auto flex-1 max-h-60 pr-1 custom-scrollbar">
                          <table class="w-full text-xs text-left">
                              <tbody class="divide-y divide-gray-100">
                                  @forelse($catTopi as $item)
                                  <tr class="hover:bg-yellow-50 transition-colors">
                                      <td class="py-2 pr-2">
                                          <div class="font-medium text-gray-800 line-clamp-2" title="{{ $item->product_name }}">{{ $item->product_name }}</div>
                                          <div class="text-[10px] text-gray-400">{{ $item->product_sku }}</div>
                                      </td>
                                      <td class="py-2 text-right whitespace-nowrap">
                                          <span class="font-bold text-yellow-600 bg-yellow-50 px-1.5 py-0.5 rounded">{{ number_format($item->total_terjual) }}</span>
                                      </td>
                                  </tr>
                                  @empty
                                  <tr><td colspan="2" class="text-center py-8 text-gray-300 italic">Belum ada data</td></tr>
                                  @endforelse
                              </tbody>
                          </table>
                      </div>
                  </div>
              </div>
          </div> 
          
          <!-- Re-open div just in case the layout below relies on previous closure, although I closed the grid above -->
          <div>
          </div>

          <!-- [SECTION 8] RINCIAN OPERASIONAL -->
          @if(isset($operasionalDetails) && $operasionalDetails->total() > 0)
          <div class="bg-white p-4 rounded-xl shadow-lg">
            <h2 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
              <i class="bi bi-list-ul text-orange-600"></i>
              Rincian Operasional
              <span class="text-xs font-normal text-gray-500">({{ $operasionalDetails->total() }} item)</span>
            </h2>
            
            <div id="expenses-container">
               @include('owner.partials.expenses_table', [
                  'operasionalDetails' => $operasionalDetails,
                  'operasional' => $operasional,
                  'showTotal' => true
               ])
            </div>
          </div>
          @else
          <div class="bg-white p-4 rounded-xl shadow-lg">
            <h2 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
              <i class="bi bi-list-ul text-orange-600"></i>
              Rincian Operasional
            </h2>
            <p class="text-sm text-gray-500 text-center py-4">Tidak ada data operasional untuk periode ini.</p>
          </div>
          @endif

          <!-- [SECTION 9] TRANSAKSI TERBARU -->
          <div class="bg-white p-4 rounded-xl shadow">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                <i class="bi bi-clock-history text-gray-500"></i>
                Transaksi Terbaru
              </h3>
              <a href="{{ route('owner.sales.index') }}" class="text-[10px] text-blue-600 hover:text-blue-700 font-semibold flex items-center gap-1">
                Lihat Semua Transaksi <i class="bi bi-arrow-right"></i>
              </a>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full text-xs">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-3 py-2 text-left text-gray-600 font-medium">SO Number</th>
                    <th class="px-3 py-2 text-left text-gray-600 font-medium">Customer</th>
                    <th class="px-3 py-2 text-right text-gray-600 font-medium">Total</th>
                    <th class="px-3 py-2 text-right text-gray-600 font-medium">Dibayar</th>
                    <th class="px-3 py-2 text-right text-gray-600 font-medium">Sisa</th>
                    <th class="px-3 py-2 text-center text-gray-600 font-medium">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  @if(isset($recentSales) && $recentSales->count() > 0)
                    @foreach($recentSales as $sale)
                      @if(isset($sale) && is_object($sale))
                      <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2">
                          <a href="{{ route('owner.sales.show', $sale->id) }}" class="font-medium text-blue-600 hover:underline decoration-blue-500 underline-offset-2">
                            {{ $sale->so_number ?? '-' }}
                          </a>
                        </td>
                        <td class="px-3 py-2 text-gray-600">{{ ($sale->customer->name ?? null) ?? 'Guest' }}</td>
                        <td class="px-3 py-2 text-right">Rp {{ number_format($sale->grand_total ?? 0, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">Rp {{ number_format($sale->paid_total ?? 0, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right {{ ($sale->remaining_amount ?? 0) > 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                          Rp {{ number_format($sale->remaining_amount ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-3 py-2 text-center">
                          @php $paymentStatus = $sale->payment_status ?? ''; @endphp
                          @if($paymentStatus === 'lunas')
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Lunas</span>
                          @elseif($paymentStatus === 'dp')
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">DP</span>
                          @else
                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">Belum</span>
                          @endif
                        </td>
                      </tr>
                      @endif
                    @endforeach
                  @else
                    <tr>
                      <td colspan="6" class="px-3 py-4 text-center text-gray-400">Tidak ada transaksi</td>
                    </tr>
                  @endif
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
      function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('-translate-x-full');
      }
      function toggleDropdown(button) {
        const dropdownMenu = button.nextElementSibling;
        const chevronIcon = button.querySelector('.bi-chevron-down');
        dropdownMenu.classList.toggle('max-h-0');
        dropdownMenu.classList.toggle('max-h-40');
        chevronIcon.classList.toggle('rotate-180');
      }

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
      const ctxAdv = document.getElementById('advertisementChart').getContext('2d');
      const advChartData = @json($advertisementChartData);
      const advChartDates = @json($advertisementChartDates);
      
      const advChatData = advChartDates.map(date => advChartData[date]?.chat || 0);
      const advFollowupData = advChartDates.map(date => advChartData[date]?.followup || 0);
      const advClosingData = advChartDates.map(date => advChartData[date]?.closing || 0);
      const advLabels = advChartDates.map(date => {
        const d = new Date(date);
        return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
      });

      new Chart(ctxAdv, {
        type: 'line',
        data: {
          labels: advLabels,
          datasets: [
            {
              label: 'Chat Masuk',
              data: advChatData,
              borderColor: 'rgb(59, 130, 246)',
              backgroundColor: 'rgba(59, 130, 246, 0.1)',
              tension: 0.4
            },
            {
              label: 'Follow Up',
              data: advFollowupData,
              borderColor: 'rgb(249, 115, 22)',
              backgroundColor: 'rgba(249, 115, 22, 0.1)',
              tension: 0.4
            },
            {
              label: 'Closing',
              data: advClosingData,
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
            legend: { position: 'top' }
          },
          scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
          }
        }
      });
      @endif

      // --- Financial Trend Chart ---
      @if(isset($financialChartLabels))
      const ctxFin = document.getElementById('financialChart').getContext('2d');
      new Chart(ctxFin, {
        type: 'line',
        data: {
          labels: @json($financialChartLabels),
          datasets: [
            {
              label: 'Omset',
              data: @json($financialChartOmset),
              borderColor: '#10b981',
              backgroundColor: 'rgba(16, 185, 129, 0.1)',
              fill: true,
              tension: 0.3
            },
            {
              label: 'HPP',
              data: @json($financialChartHpp),
              borderColor: '#f43f5e',
              backgroundColor: 'rgba(244, 63, 94, 0.1)',
              tension: 0.3
            },
            {
              label: 'Operasional',
              data: @json($financialChartExpense),
              borderColor: '#f59e0b',
              backgroundColor: 'rgba(245, 158, 11, 0.1)',
              tension: 0.3
            },
            {
              label: 'Net Profit',
              data: @json($financialChartProfit),
              borderColor: '#6366f1',
              backgroundColor: 'rgba(99, 102, 241, 0.1)',
              borderDash: [5, 5],
              tension: 0.3
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        if (context.parsed.y !== null) {
                            label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed.y);
                        }
                        return label;
                    }
                }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: function(value) {
                    if (value >= 1000000) return 'Rp ' + (value/1000000).toFixed(1) + ' jt';
                    if (value >= 1000) return 'Rp ' + (value/1000).toFixed(0) + ' rb';
                    return 'Rp ' + value;
                }
              }
            }
          }
        }
      });
      @endif
      @endif
    </script>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Expenses Pagination AJAX
            const expensesContainer = document.getElementById('expenses-container');
            if (expensesContainer) {
                expensesContainer.addEventListener('click', function(e) {
                    const link = e.target.closest('a');
                    if (link && link.href && !link.href.includes('#')) {
                        e.preventDefault();
                        fetchExpenses(link.href);
                    }
                });
            }

            function fetchExpenses(url) {
                const expensesContainer = document.getElementById('expenses-container');
                expensesContainer.style.opacity = '0.5';
                
                try {
                    const urlObj = new URL(url);
                    const params = urlObj.search;
                    
                    // Gunakan route helper agar URL selalu benar dan absolut
                    const baseUrl = "{{ route('owner.dashboard.expenses-pagination') }}";
                    const ajaxUrl = baseUrl + params;

                    fetch(ajaxUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.text();
                    })
                    .then(html => {
                        expensesContainer.innerHTML = html;
                        expensesContainer.style.opacity = '1';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        expensesContainer.style.opacity = '1';
                    });
                } catch (e) {
                    console.error('Invalid URL:', e);
                    expensesContainer.style.opacity = '1';
                }
            }
        });
    </script>
</body>
</html>
