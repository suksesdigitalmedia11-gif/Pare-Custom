<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gross Profit Iklan - Kepala Toko</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
</head>
<body class="bg-gray-100">
    <div class="flex">
        <x-navbar-kepala-toko></x-navbar-kepala-toko>
        
        <div class="flex-1 lg:w-5/6">
            <x-navbar-top-kepala-toko></x-navbar-top-kepala-toko>

            <div class="p-4 lg:p-8 space-y-6">
                @if(!$userActiveShift)
                    <div class="bg-orange-50 border-l-4 border-orange-400 p-4 rounded-lg shadow">
                        <div class="flex items-center gap-3">
                            <i class="bi bi-info-circle-fill text-orange-600 text-xl"></i>
                            <div class="flex-1">
                                <p class="font-semibold text-orange-800">Belum Buka Shift Hari Ini</p>
                                <p class="text-sm text-orange-700 mt-1">
                                    Anda belum membuka shift hari ini. Buka shift terlebih dahulu sebelum input data iklan.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg shadow">
                        <div class="flex items-center gap-3">
                            <i class="bi bi-x-circle-fill text-red-600 text-xl"></i>
                            <p class="text-red-800">{{ session('error') }}</p>
        </div>
    </div>
@endif
                
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wider">Dashboard Iklan</p>
                            <h1 class="text-2xl font-bold text-gray-900">Gross Profit Bulan {{ now()->translatedFormat('F Y') }}</h1>
                            <p class="text-gray-600 mt-1">Ringkasan sederhana berdasarkan input closing (penjualan) dan total pembelian (HPP).</p>
            </div>
                        @if(!$userActiveShift)
                            <button disabled
                                    class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg bg-gray-400 text-white font-semibold shadow cursor-not-allowed"
                                    title="Buka shift terlebih dahulu">
                                <i class="bi bi-lock-fill"></i>
                                Input Data Iklan (Belum Buka Shift)
                            </button>
                        @else
                            <a href="{{ route('kepala-toko.advertisement.create') }}"
                               class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg bg-blue-600 text-white font-semibold shadow hover:bg-blue-700 transition">
                                <i class="bi bi-plus-circle-fill"></i>
                                Input Data Iklan
                            </a>
                        @endif
                    </div>
        </div>

                @php
                    $grossProfitIsPositive = $grossProfit >= 0;
                    $statusColor = $grossProfit >= $targetGrossProfit ? 'text-green-600' : ($grossProfitIsPositive ? 'text-amber-600' : 'text-red-600');
                    $statusLabel = $grossProfit >= $targetGrossProfit ? 'Target tercapai' : 'Perlu akselerasi';
                @endphp

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
                        <p class="text-sm text-gray-500 mb-1 flex items-center gap-2">
                            <i class="bi bi-cash-stack text-blue-500"></i>
                            Penjualan (Closing)
                        </p>
                        <div class="text-2xl font-bold text-gray-900">Rp {{ number_format($monthlySales, 0, ',', '.') }}</div>
                        <p class="text-xs text-gray-500 mt-2">Total nominal closing yang sudah diinput bulan ini.</p>
            </div>
                    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
                        <p class="text-sm text-gray-500 mb-1 flex items-center gap-2">
                            <i class="bi bi-truck text-orange-500"></i>
                            HPP (Pembelian)
                        </p>
                        <div class="text-2xl font-bold text-gray-900">Rp {{ number_format($monthlyHpp, 0, ',', '.') }}</div>
                        <p class="text-xs text-gray-500 mt-2">HPP dari item yang dijual bulan ini (cost_price × qty).</p>
        </div>
                    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
                        <p class="text-sm text-gray-500 mb-1 flex items-center gap-2">
                            <i class="bi bi-graph-up text-green-500"></i>
                            Gross Profit (Penjualan - HPP)
                        </p>
                        <div class="text-2xl font-bold {{ $statusColor }}">
                            Rp {{ number_format($grossProfit, 0, ',', '.') }}
        </div>
                        <p class="text-xs text-gray-500 mt-2">Nilai bersih sebelum biaya lain.</p>
        </div>
    </div>

                <!-- === TARGET GROSS PROFIT & INVOICE (2 KOLOM) === -->
                @php
                    $invoiceStatusColor = $currentMonthInvoiceCount >= $invoiceTarget ? 'text-green-600' : 'text-amber-600';
                    $invoiceStatusLabel = $currentMonthInvoiceCount >= $invoiceTarget ? 'Target tercapai' : 'Perlu akselerasi';
                @endphp

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Target Gross Profit -->
                    <div class="bg-white p-5 rounded-xl shadow-lg border-l-4 border-blue-500">
                        <div class="flex items-center justify-between mb-3">
                        <div>
                                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Target Gross Profit</p>
                                <h2 class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($targetGrossProfit, 0, ',', '.') }}</h2>
    </div>
                        <div class="text-right">
                                <p class="text-xs text-gray-500">Hari {{ $currentDay }}/{{ $daysInMonth }}</p>
                                <p class="text-sm font-semibold {{ $statusColor }} mt-1">{{ $statusLabel }}</p>
</div>
    </div>

                    <div class="space-y-2">
                            <div class="flex justify-between text-xs font-medium text-gray-700">
                                <span>Progress</span>
                            <span>{{ number_format($grossProfitProgress, 1) }}%</span>
            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full transition-all duration-500 {{ $grossProfitProgress >= 100 ? 'bg-green-500' : 'bg-blue-500' }}"
                                 style="width: {{ min(100, max(0, $grossProfitProgress)) }}%"></div>
        </div>
                            <div class="flex justify-between text-xs text-gray-600 mt-2">
                                <span>Realisasi: <span class="font-semibold {{ $statusColor }}">Rp {{ number_format($grossProfit, 0, ',', '.') }}</span></span>
                        @if($grossProfitShortfall > 0)
                                    <span class="text-red-600">Kurang: Rp {{ number_format($grossProfitShortfall, 0, ',', '.') }}</span>
    @endif
                            </div>
                        </div>
</div>

                    <!-- Target Invoice -->
                    <div class="bg-white p-5 rounded-xl shadow-lg border-l-4 border-indigo-500">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Target Invoice</p>
                                <h2 class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($invoiceTarget, 0, ',', '.') }} Nota</h2>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Hari {{ $currentDay }}/{{ $daysInMonth }}</p>
                                <p class="text-sm font-semibold {{ $invoiceStatusColor }} mt-1">{{ $invoiceStatusLabel }}</p>
            </div>
        </div>
                        
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs font-medium text-gray-700">
                                <span>Progress</span>
                                <span>{{ number_format($invoiceProgress, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full transition-all duration-500 {{ $invoiceProgress >= 100 ? 'bg-green-500' : 'bg-indigo-500' }}"
                                     style="width: {{ min(100, max(0, $invoiceProgress)) }}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-600 mt-2">
                                <span>Realisasi: <span class="font-semibold {{ $invoiceStatusColor }}">{{ number_format($currentMonthInvoiceCount, 0, ',', '.') }} nota</span></span>
                                @if($remainingInvoiceTarget > 0)
                                    <span class="text-red-600">Kurang: {{ number_format($remainingInvoiceTarget, 0, ',', '.') }} nota</span>
                                @endif
            </div>
                            <p class="text-xs text-gray-500 mt-1">Bulan lalu: {{ number_format($previousMonthInvoiceCount, 0, ',', '.') }} nota</p>
            </div>
        </div>
    </div>

                <!-- Grafik Real-time Chat, Follow Up, dan Closing -->
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="bi bi-graph-up-arrow text-purple-500"></i>
                        Grafik Trend 7 Hari Terakhir
                    </h3>
                    <div class="h-80">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>

                <!-- History Inputan Hari Ini -->
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="bi bi-clock-history text-blue-500"></i>
                        History Inputan Hari Ini ({{ now()->translatedFormat('d F Y') }})
                    </h3>
                    
                    @php
                        $types = [
                            'chat' => ['color' => 'blue', 'icon' => 'chat-left-text', 'label' => 'Chat Masuk'],
                            'followup' => ['color' => 'orange', 'icon' => 'telephone', 'label' => 'Follow Up'], 
                            'closing' => ['color' => 'green', 'icon' => 'currency-dollar', 'label' => 'Closing']
                        ];
                    @endphp

                    @foreach($types as $type => $info)
                        <div class="mb-6 last:mb-0">
                            <h4 class="font-medium text-gray-700 mb-3 flex items-center gap-2">
    <i class="bi bi-{{ $info['icon'] }} text-{{ $info['color'] }}-500"></i>
                                {{ $info['label'] }} 
                                <span class="text-sm font-normal text-gray-500">
                                    ({{ ($todayDetails[$type] ?? collect())->count() }} inputan)
                                </span>
</h4>
                            
                            @if(($todayDetails[$type] ?? collect())->count() > 0)
                                <div class="space-y-2">
                                @foreach(($todayDetails[$type] ?? collect()) as $item)
                                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                            <div class="flex-1">
                                                <span class="font-medium text-gray-800">{{ $item->description }}</span>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="text-xs text-gray-500">
                                                        <i class="bi bi-clock"></i>
                                                    {{ $item->created_at->format('H:i') }}
                                                </span>
                                                    @if($item->user)
                                                        <span class="text-xs text-gray-500">
                                                            <i class="bi bi-person"></i>
                                                            {{ $item->user->name }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if($type === 'closing' && $item->amount > 0)
                                                <span class="font-bold text-green-600 ml-4">
                                                    Rp {{ number_format($item->amount, 0, ',', '.') }}
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-sm py-2 italic">Belum ada data {{ strtolower($info['label']) }} hari ini</p>
                            @endif
                        </div>
                        
                        @if(!$loop->last)
                            <div class="border-b border-gray-200 my-4"></div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script>
        // Grafik Real-time untuk Chat, Follow Up, dan Closing
        const ctx = document.getElementById('performanceChart');
        if (ctx) {
        const chartData = {
            labels: {!! json_encode($dates) !!},
            datasets: [
                {
                        label: 'Chat Masuk',
                    data: {!! json_encode(array_column($formattedChartData, 'chat')) !!},
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                },
                {
                    label: 'Follow Up',
                    data: {!! json_encode(array_column($formattedChartData, 'followup')) !!},
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                        tension: 0.4,
                        fill: true
                },
                {
                    label: 'Closing',
                    data: {!! json_encode(array_column($formattedChartData, 'closing')) !!},
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                }
            ]
        };

        new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                        }
                    }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                }
            }
        });
        }
    </script>
</body>
</html>

