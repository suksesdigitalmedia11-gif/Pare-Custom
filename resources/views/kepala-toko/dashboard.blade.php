<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kepala Toko Dashboard - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
    <style>
      body {
        font-family: 'Raleway', sans-serif;
      }
      .nav-text {
        position: relative;
        display: inline-block;
      }
      .nav-text::after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: -2px;
        left: 0;
        background-color: #e17f12;
        transition: width 0.2s ease-in-out;
      }
      .hover-link:hover .nav-text::after {
        width: 100%;
      }
      .pulse-alert {
        animation: pulse 2s infinite;
      }
      @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
        100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
      }
    </style>
  </head>
  <body class="bg-gray-100">
    <div class="flex">
      <!-- Sidebar -->
      <x-navbar-kepala-toko></x-navbar-kepala-toko>

      <!-- Main Content -->
      <div class="flex-1 lg:w-5/6">
        {{-- Navbar Top --}}
        <x-navbar-top-kepala-toko></x-navbar-top-kepala-toko>

        <!-- Content Wrapper -->
        <div class="p-4 lg:p-8">
          <!-- Welcome Message -->
          <div class="bg-white p-6 rounded-xl shadow-lg mb-6 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-32 h-32 bg-[#005281]/5 rounded-bl-full"></div>
            
            <div class="flex items-center gap-6 relative">
                <div class="flex items-center justify-center w-16 h-16 bg-[#005281]/10 rounded-full">
                    <i class="bi bi-person-badge text-4xl text-[#005281]"></i>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <h2 class="text-2xl font-semibold text-gray-700">
                            Halo {{ ucfirst(Auth::user()->name) }}!
                        </h2>
                        <span class="px-3 py-1 text-sm bg-[#005281]/10 text-[#005281] rounded-full">
                            Kepala Toko
                        </span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <i class="bi bi-clock"></i>
                        <span>{{ now()->format('l, d F Y') }}</span>
                    </div>
                    <p class="text-gray-600 text-sm">
                        Dashboard operasional toko untuk supervisi dan manajemen harian.
                    </p>
                </div>
            </div>
          </div>

          <!-- === DATE RANGE FILTER === -->
          <div class="bg-white p-4 rounded-xl shadow-lg mb-6">
            <form method="GET" action="{{ route('kepala-toko.dashboard') }}" class="flex flex-wrap items-end gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" 
                           class="block w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#005281] focus:border-transparent text-sm">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal Selesai</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" 
                           class="block w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#005281] focus:border-transparent text-sm">
                </div>
                <button type="submit" class="px-6 py-2 bg-[#005281] text-white rounded-lg text-sm font-medium hover:bg-[#005281]/90 transition-all flex items-center gap-2">
                    <i class="bi bi-filter"></i> Filter
                </button>
                <a href="{{ route('kepala-toko.dashboard') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200 transition-all">
                    Reset
                </a>
            </form>
          </div>

          <!-- === SHIFT STATUS & TODAY'S PERFORMANCE === -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            
            <!-- 🔵 SHIFT STATUS CARD -->
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-blue-500">
              <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                  <i class="bi bi-clock-history text-blue-500"></i>
                  Status Shift
                </h3>
                <div class="px-3 py-1 rounded-full text-sm font-medium
                  {{ isset($activeShift) && $activeShift ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                  {{ isset($activeShift) && $activeShift ? 'AKTIF' : 'TUTUP' }}
                </div>
              </div>
              
              @if(isset($activeShift) && $activeShift)
                <div class="space-y-3">
                  <div class="flex justify-between items-center">
                    <span class="text-gray-600">Kas Awal:</span>
                    <span class="font-semibold">Rp {{ number_format($activeShift->initial_cash, 0, ',', '.') }}</span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-gray-600">Kas di Laci:</span>
                    <span class="font-semibold text-green-600">Rp {{ number_format($tunaiDiLaci ?? 0, 0, ',', '.') }}</span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-gray-600">Durasi:</span>
                    <span class="font-semibold">{{ $shiftDuration ?? '0 jam 0 menit' }}</span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-gray-600">Transaksi:</span>
                    <span class="font-semibold">{{ $totalTransactions ?? 0 }}</span>
                  </div>
                </div>
                <div class="mt-4 pt-4 border-t">
                  <a href="{{ route('kepala-toko.shift.dashboard') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                    <i class="bi bi-arrow-right"></i>
                    Kelola Shift
                  </a>
                </div>
              @else
                <p class="text-gray-500 text-sm py-2">Tidak ada shift aktif</p>
                <a href="{{ route('kepala-toko.shift.dashboard') }}" class="inline-block mt-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                  Mulai Shift
                </a>
              @endif
            </div>

            <!-- 📊 TODAY'S PERFORMANCE -->
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-green-500">
              <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="bi bi-graph-up text-green-500"></i>
                Performa Hari Ini
              </h3>
              
              <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-3 bg-green-50 rounded-lg">
                  <div class="text-2xl font-bold text-green-700">{{ $todayStats['transactions'] ?? 0 }}</div>
                  <div class="text-xs text-green-600">Transaksi</div>
                </div>
                <div class="text-center p-3 bg-blue-50 rounded-lg">
                  <div class="text-2xl font-bold text-blue-700">Rp {{ number_format($todayStats['revenue'] ?? 0, 0, ',', '.') }}</div>
                  <div class="text-xs text-blue-600">Pendapatan</div>
                </div>
                <div class="text-center p-3 bg-purple-50 rounded-lg">
                  <div class="text-2xl font-bold text-purple-700">{{ $todayStats['customers'] ?? 0 }}</div>
                  <div class="text-xs text-purple-600">Customer</div>
                </div>
                <div class="text-center p-3 bg-orange-50 rounded-lg">
                  <div class="text-2xl font-bold text-orange-700">Rp {{ number_format($todayStats['avg_transaction'] ?? 0, 0, ',', '.') }}</div>
                  <div class="text-xs text-orange-600">Rata-rata</div>
                </div>
              </div>
            </div>
          </div>

          <!-- === ALERTS SECTION === -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            
            <!-- 🔴 DEADLINE TERLEWAT -->
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-red-500 {{ $overdueCount > 0 ? 'pulse-alert' : '' }}">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                        <i class="bi bi-exclamation-octagon text-red-500"></i>
                        Deadline Terlewat
                    </h3>
                    @if($overdueCount > 0)
                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-sm font-bold">
                            {{ $overdueCount }}
                        </span>
                    @endif
                </div>
                
                @if($overdueCount > 0)
                    <div class="space-y-2">
                        @foreach($overdueOrders as $order)
                            @php
                                $daysLate = \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($order->deadline)->startOfDay());
                            @endphp
                            
                            <a href="{{ route('kepala-toko.sales.show', $order) }}" class="block">
                                <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg border border-red-200 hover:bg-red-100 transition-colors">
                                    <div class="flex-1">
                                        <div class="font-semibold text-sm text-gray-800">{{ $order->so_number }}</div>
                                        <div class="text-xs text-gray-600 mt-1">{{ $order->customer->name ?? 'Customer Umum' }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-bold text-red-600">
                                            {{ $daysLate }} HARI TERLEWAT
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1 capitalize">
                                            {{ \Carbon\Carbon::parse($order->deadline)->format('d/m') }} • {{ $order->status }}
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    <div class="mt-4 pt-4 border-t">
                        <a href="{{ route('kepala-toko.sales.index') }}?status=pending" class="text-red-600 hover:text-red-800 text-sm font-medium flex items-center gap-1">
                            <i class="bi bi-arrow-right"></i>
                            Lihat Semua
                        </a>
                    </div>
                @else
                    <div class="text-center py-6">
                        <i class="bi bi-check-circle text-green-500 text-3xl mb-2"></i>
                        <p class="text-gray-500 text-sm">Tidak ada deadline terlewat 🎉</p>
                    </div>
                @endif
            </div>

            <!-- 🟡 DEADLINE MENDEKAT -->
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-orange-500 {{ $upcomingCount > 0 ? 'pulse-alert' : '' }}">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                        <i class="bi bi-exclamation-triangle text-orange-500"></i>
                        Deadline Mendekat (≤5 hari)
                    </h3>
                    @if($upcomingCount > 0)
                        <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-sm font-bold">
                            {{ $upcomingCount }}
                        </span>
                    @endif
                </div>
                
                @if($upcomingCount > 0)
                    <div class="space-y-2">
                        @foreach($upcomingOrders as $order)
                            @php
                                $daysLeft = \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($order->deadline)->startOfDay(), false);
                                $isToday = $daysLeft == 0;
                                
                                if ($isToday) {
                                    $bgColor = 'bg-red-100';
                                    $textColor = 'text-red-800';
                                    $statusText = 'HARI INI';
                                } elseif ($daysLeft <= 1) {
                                    $bgColor = 'bg-orange-100';
                                    $textColor = 'text-orange-800';
                                    $statusText = '1 HARI LAGI';
                                } else {
                                    $bgColor = 'bg-yellow-100';
                                    $textColor = 'text-yellow-800';
                                    $statusText = $daysLeft . ' HARI LAGI';
                                }
                            @endphp
                            
                            <a href="{{ route('kepala-toko.sales.show', $order) }}" class="block">
                                <div class="flex justify-between items-center p-3 {{ $bgColor }} rounded-lg border hover:opacity-90 transition-opacity">
                                    <div class="flex-1">
                                        <div class="font-semibold text-sm text-gray-800">{{ $order->so_number }}</div>
                                        <div class="text-xs text-gray-600 mt-1">{{ $order->customer->name ?? 'Customer Umum' }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-bold {{ $textColor }}">
                                            {{ $statusText }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1 capitalize">
                                            {{ \Carbon\Carbon::parse($order->deadline)->format('d/m') }} • {{ $order->status }}
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <i class="bi bi-check-circle text-green-500 text-3xl mb-2"></i>
                        <p class="text-gray-500 text-sm">Tidak ada deadline mendekat 🎉</p>
                    </div>
                @endif
            </div>
          </div>

          <!-- === PENDING APPROVALS & LOW STOCK === -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            
            <!-- ⚠️ PENDING APPROVALS -->
            @if($pendingApprovalsCount > 0)
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-yellow-500">
              <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                  <i class="bi bi-check-circle text-yellow-500"></i>
                  Menunggu Persetujuan
                </h3>
                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-bold">
                  {{ $pendingApprovalsCount }}
                </span>
              </div>
              
              <div class="space-y-2">
                @if($pendingStockOpnames->count() > 0)
                  <div class="mb-3">
                    <h4 class="text-xs font-semibold text-gray-600 mb-2">Stock Opname</h4>
                    @foreach($pendingStockOpnames->take(3) as $opname)
                      <a href="{{ route('kepala-toko.inventory.stock-opnames.index') }}" class="block">
                        <div class="flex justify-between items-center p-2 bg-yellow-50 rounded text-xs hover:bg-yellow-100 transition-colors">
                          <div>
                            <div class="font-medium">{{ $opname->document_number }}</div>
                            <div class="text-gray-500">{{ $opname->creator->name ?? '-' }}</div>
                          </div>
                          <span class="text-yellow-700 font-semibold">Pending</span>
                        </div>
                      </a>
                    @endforeach
                  </div>
                @endif

                @if($pendingPurchaseOrders->count() > 0)
                  <div>
                    <h4 class="text-xs font-semibold text-gray-600 mb-2">Purchase Order</h4>
                    @foreach($pendingPurchaseOrders->take(3) as $po)
                      <a href="{{ route('kepala-toko.purchases.show', $po) }}" class="block">
                        <div class="flex justify-between items-center p-2 bg-yellow-50 rounded text-xs hover:bg-yellow-100 transition-colors">
                          <div>
                            <div class="font-medium">{{ $po->po_number }}</div>
                            <div class="text-gray-500">{{ $po->supplier->name ?? '-' }}</div>
                          </div>
                          <span class="text-yellow-700 font-semibold">Pending</span>
                        </div>
                      </a>
                    @endforeach
                  </div>
                @endif
              </div>
              <div class="mt-4 pt-4 border-t">
                <a href="{{ route('kepala-toko.purchases.index') }}?status=pending" class="text-yellow-600 hover:text-yellow-800 text-sm font-medium flex items-center gap-1">
                  <i class="bi bi-arrow-right"></i>
                  Lihat Semua
                </a>
              </div>
            </div>
            @endif

            <!-- 📦 LOW STOCK ALERTS -->
            @if($lowStockCount > 0)
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-red-500">
              <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                  <i class="bi bi-box-seam text-red-500"></i>
                  Stok Rendah
                </h3>
                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-sm font-bold">
                  {{ $lowStockCount }}
                </span>
              </div>
              
              <div class="space-y-2 max-h-64 overflow-y-auto">
                @foreach($lowStockProducts as $product)
                  <a href="{{ route('kepala-toko.product.index') }}?q={{ $product->name }}" class="block">
                    <div class="flex justify-between items-center p-2 bg-red-50 rounded text-xs hover:bg-red-100 transition-colors">
                      <div>
                        <div class="font-medium">{{ $product->name }}</div>
                        <div class="text-gray-500">{{ $product->sku ?? '-' }}</div>
                      </div>
                      <span class="text-red-700 font-semibold">{{ $product->stock_qty }} pcs</span>
                    </div>
                  </a>
                @endforeach
              </div>
              <div class="mt-4 pt-4 border-t">
                <a href="{{ route('kepala-toko.product.index') }}" class="text-red-600 hover:text-red-800 text-sm font-medium flex items-center gap-1">
                  <i class="bi bi-arrow-right"></i>
                  Kelola Produk
                </a>
              </div>
            </div>
            @endif
          </div>

          <!-- === PENDING PAYMENTS === -->
          @if($pendingPaymentsCount > 0)
          <div class="bg-white p-6 rounded-xl shadow-lg mb-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                <i class="bi bi-clock text-yellow-500"></i>
                Belum Lunas
              </h3>
              <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-bold">
                {{ $pendingPaymentsCount }}
              </span>
            </div>
            
            <p class="text-xs text-gray-600 mb-3">Total Piutang: <span class="font-bold text-yellow-700">Rp {{ number_format($totalPiutang ?? 0, 0, ',', '.') }}</span></p>
            
            <div class="space-y-2">
              @foreach($pendingPayments as $order)
                <a href="{{ route('kepala-toko.sales.show', $order) }}" class="block">
                  <div class="flex justify-between items-center p-2 hover:bg-yellow-50 rounded transition-colors">
                    <div>
                      <div class="font-medium text-sm">{{ $order->so_number }}</div>
                      <div class="text-xs text-gray-500">{{ $order->customer->name ?? 'Umum' }}</div>
                    </div>
                    <div class="text-right">
                      <div class="text-sm font-semibold text-yellow-700">
                        Rp {{ number_format($order->remaining_amount, 0, ',', '.') }}
                      </div>
                      <div class="text-xs text-gray-500">{{ $order->payment_status }}</div>
                    </div>
                  </div>
                </a>
              @endforeach
            </div>
            <div class="mt-4 pt-4 border-t">
              <a href="{{ route('kepala-toko.sales.index') }}?payment_status=dp" class="text-yellow-600 hover:text-yellow-800 text-sm font-medium flex items-center gap-1">
                <i class="bi bi-arrow-right"></i>
                Kelola Pembayaran
              </a>
            </div>
          </div>
          @endif

          <!-- === FINANCIAL PERFORMANCE CHART === -->
          <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
              <!-- Summary Cards for Chart -->
              <div class="lg:col-span-1 space-y-4">
                  <div class="bg-white p-4 rounded-xl shadow-lg border-l-4 border-blue-600 transition-transform hover:scale-[1.02]">
                      <div class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-1">Total Omset</div>
                      <div class="text-lg font-bold text-blue-600">Rp {{ number_format($financialChartData['total_omset'], 0, ',', '.') }}</div>
                  </div>
                  <div class="bg-white p-4 rounded-xl shadow-lg border-l-4 border-orange-600 transition-transform hover:scale-[1.02]">
                      <div class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-1">Total HPP</div>
                      <div class="text-lg font-bold text-orange-600">Rp {{ number_format($financialChartData['total_hpp'], 0, ',', '.') }}</div>
                  </div>
                  <div class="bg-white p-4 rounded-xl shadow-lg border-l-4 border-green-600 transition-transform hover:scale-[1.02]">
                      <div class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-1">Total Profit</div>
                      <div class="text-lg font-bold text-green-600">Rp {{ number_format($financialChartData['total_profit'], 0, ',', '.') }}</div>
                  </div>
                  <div class="bg-white p-4 rounded-xl shadow-lg border-l-4 border-purple-600 transition-transform hover:scale-[1.02]">
                      <div class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-1">Total Invoice</div>
                      <div class="text-lg font-bold text-purple-600">{{ $financialChartData['total_invoices'] }} <span class="text-xs text-gray-400 font-normal ml-1">Nota</span></div>
                  </div>
              </div>

              <!-- Main Chart -->
              <div class="lg:col-span-3 bg-white p-6 rounded-xl shadow-lg">
                  <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                      <i class="bi bi-bar-chart-line text-[#005281]"></i>
                      Grafik Performa Finansial
                  </h3>
                  <div class="h-[300px] w-full">
                      <canvas id="financialChart"></canvas>
                  </div>
              </div>
          </div>

          <!-- === BEST SELLING PRODUCTS === -->
          <div class="bg-white p-6 rounded-xl shadow-lg mb-6 border-l-4 border-[#005281]">
              <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                  <i class="bi bi-star-fill text-yellow-400"></i>
                  10 Produk Terlaris
                  <span class="text-xs font-normal text-gray-500">(Kecuali DTF & Spunbound)</span>
              </h3>
              <div class="overflow-x-auto">
                  <table class="w-full text-sm text-left">
                      <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] font-bold tracking-wider">
                          <tr>
                              <th class="px-4 py-3 rounded-l-lg">Nama Produk</th>
                              <th class="px-4 py-3 text-center rounded-r-lg">Qty Terjual</th>
                          </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-100">
                          @forelse($bestSellingProducts as $product)
                          <tr class="hover:bg-gray-50 transition-colors">
                              <td class="px-4 py-3 font-medium text-gray-800">{{ $product->product_name }}</td>
                              <td class="px-4 py-3 text-center">
                                  <span class="px-2 py-1 bg-[#005281]/5 text-[#005281] rounded text-xs font-bold">
                                      {{ number_format($product->total_qty, 0, ',', '.') }}
                                  </span>
                              </td>
                          </tr>
                          @empty
                          <tr>
                              <td colspan="2" class="px-4 py-12 text-center text-gray-500 italic">
                                  <i class="bi bi-inbox text-4xl block mb-2 opacity-20"></i>
                                  Tidak ada data penjualan untuk periode ini.
                              </td>
                          </tr>
                          @endforelse
                      </tbody>
                  </table>
              </div>
          </div>

          <!-- === 5 KATEGORI DETAIL (Full Width Grid) === -->
          <div class="mb-6">
              <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 text-lg">
                  <i class="bi bi-tags text-[#005281]"></i> Analisa Per Kategori
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                  <!-- 1. KAOS POLOS -->
                  <div class="bg-white p-4 rounded-xl shadow-lg border-t-4 border-blue-500 flex flex-col h-full">
                      <h4 class="font-bold text-gray-700 mb-3 text-xs uppercase flex items-center gap-2">
                          <i class="bi bi-circle-fill text-blue-500 text-[8px]"></i> Kaos Polos
                      </h4>
                      <div class="overflow-y-auto flex-1 max-h-64 pr-1 custom-scrollbar">
                          <table class="w-full text-xs text-left">
                              <thead class="bg-gray-50 text-gray-500 mb-2 block">
                                  <tr class="flex justify-between px-2 py-1">
                                    <th>Produk</th>
                                    <th>Qty</th>
                                  </tr>
                              </thead>
                              <tbody class="space-y-1 block">
                                  @forelse($catKaosPolos as $item)
                                  <tr class="flex justify-between items-center p-2 hover:bg-blue-50 bg-white border border-gray-100 rounded transition-colors group">
                                      <td class="flex-1 min-w-0 pr-2">
                                          <div class="font-medium text-gray-800 line-clamp-2 leading-tight" title="{{ $item->product_name }}">{{ $item->product_name }}</div>
                                          <div class="text-[10px] text-gray-400 mt-0.5">{{ $item->product_sku }}</div>
                                      </td>
                                      <td class="text-right whitespace-nowrap">
                                          <span class="font-bold text-blue-600 bg-blue-100 px-2 py-1 rounded text-[10px]">{{ number_format($item->total_terjual) }}</span>
                                      </td>
                                  </tr>
                                  @empty
                                  <tr><td colspan="2" class="text-center py-8 text-gray-300 italic text-xs">Belum ada data</td></tr>
                                  @endforelse
                              </tbody>
                          </table>
                      </div>
                  </div>

                  <!-- 2. KAOS POLO -->
                  <div class="bg-white p-4 rounded-xl shadow-lg border-t-4 border-indigo-500 flex flex-col h-full">
                      <h4 class="font-bold text-gray-700 mb-3 text-xs uppercase flex items-center gap-2">
                          <i class="bi bi-circle-fill text-indigo-500 text-[8px]"></i> Kaos Polo
                      </h4>
                      <div class="overflow-y-auto flex-1 max-h-64 pr-1 custom-scrollbar">
                          <table class="w-full text-xs text-left">
                               <thead class="bg-gray-50 text-gray-500 mb-2 block">
                                  <tr class="flex justify-between px-2 py-1">
                                    <th>Produk</th>
                                    <th>Qty</th>
                                  </tr>
                              </thead>
                              <tbody class="space-y-1 block">
                                  @forelse($catKaosPolo as $item)
                                  <tr class="flex justify-between items-center p-2 hover:bg-indigo-50 bg-white border border-gray-100 rounded transition-colors group">
                                      <td class="flex-1 min-w-0 pr-2">
                                          <div class="font-medium text-gray-800 line-clamp-2 leading-tight" title="{{ $item->product_name }}">{{ $item->product_name }}</div>
                                          <div class="text-[10px] text-gray-400 mt-0.5">{{ $item->product_sku }}</div>
                                      </td>
                                      <td class="text-right whitespace-nowrap">
                                          <span class="font-bold text-indigo-600 bg-indigo-100 px-2 py-1 rounded text-[10px]">{{ number_format($item->total_terjual) }}</span>
                                      </td>
                                  </tr>
                                  @empty
                                  <tr><td colspan="2" class="text-center py-8 text-gray-300 italic text-xs">Belum ada data</td></tr>
                                  @endforelse
                              </tbody>
                          </table>
                      </div>
                  </div>

                  <!-- 3. JAKET -->
                  <div class="bg-white p-4 rounded-xl shadow-lg border-t-4 border-red-500 flex flex-col h-full">
                      <h4 class="font-bold text-gray-700 mb-3 text-xs uppercase flex items-center gap-2">
                          <i class="bi bi-circle-fill text-red-500 text-[8px]"></i> Jaket
                      </h4>
                      <div class="overflow-y-auto flex-1 max-h-64 pr-1 custom-scrollbar">
                          <table class="w-full text-xs text-left">
                               <thead class="bg-gray-50 text-gray-500 mb-2 block">
                                  <tr class="flex justify-between px-2 py-1">
                                    <th>Produk</th>
                                    <th>Qty</th>
                                  </tr>
                              </thead>
                              <tbody class="space-y-1 block">
                                  @forelse($catJaket as $item)
                                  <tr class="flex justify-between items-center p-2 hover:bg-red-50 bg-white border border-gray-100 rounded transition-colors group">
                                      <td class="flex-1 min-w-0 pr-2">
                                          <div class="font-medium text-gray-800 line-clamp-2 leading-tight" title="{{ $item->product_name }}">{{ $item->product_name }}</div>
                                          <div class="text-[10px] text-gray-400 mt-0.5">{{ $item->product_sku }}</div>
                                      </td>
                                      <td class="text-right whitespace-nowrap">
                                          <span class="font-bold text-red-600 bg-red-100 px-2 py-1 rounded text-[10px]">{{ number_format($item->total_terjual) }}</span>
                                      </td>
                                  </tr>
                                  @empty
                                  <tr><td colspan="2" class="text-center py-8 text-gray-300 italic text-xs">Belum ada data</td></tr>
                                  @endforelse
                              </tbody>
                          </table>
                      </div>
                  </div>

                  <!-- 4. JERSEY -->
                  <div class="bg-white p-4 rounded-xl shadow-lg border-t-4 border-green-500 flex flex-col h-full">
                      <h4 class="font-bold text-gray-700 mb-3 text-xs uppercase flex items-center gap-2">
                          <i class="bi bi-circle-fill text-green-500 text-[8px]"></i> Jersey
                      </h4>
                      <div class="overflow-y-auto flex-1 max-h-64 pr-1 custom-scrollbar">
                          <table class="w-full text-xs text-left">
                               <thead class="bg-gray-50 text-gray-500 mb-2 block">
                                  <tr class="flex justify-between px-2 py-1">
                                    <th>Produk</th>
                                    <th>Qty</th>
                                  </tr>
                              </thead>
                              <tbody class="space-y-1 block">
                                  @forelse($catJersey as $item)
                                  <tr class="flex justify-between items-center p-2 hover:bg-green-50 bg-white border border-gray-100 rounded transition-colors group">
                                      <td class="flex-1 min-w-0 pr-2">
                                          <div class="font-medium text-gray-800 line-clamp-2 leading-tight" title="{{ $item->product_name }}">{{ $item->product_name }}</div>
                                          <div class="text-[10px] text-gray-400 mt-0.5">{{ $item->product_sku }}</div>
                                      </td>
                                      <td class="text-right whitespace-nowrap">
                                          <span class="font-bold text-green-600 bg-green-100 px-2 py-1 rounded text-[10px]">{{ number_format($item->total_terjual) }}</span>
                                      </td>
                                  </tr>
                                  @empty
                                  <tr><td colspan="2" class="text-center py-8 text-gray-300 italic text-xs">Belum ada data</td></tr>
                                  @endforelse
                              </tbody>
                          </table>
                      </div>
                  </div>

                  <!-- 5. TOPI -->
                  <div class="bg-white p-4 rounded-xl shadow-lg border-t-4 border-yellow-500 flex flex-col h-full">
                      <h4 class="font-bold text-gray-700 mb-3 text-xs uppercase flex items-center gap-2">
                          <i class="bi bi-circle-fill text-yellow-500 text-[8px]"></i> Topi
                      </h4>
                      <div class="overflow-y-auto flex-1 max-h-64 pr-1 custom-scrollbar">
                          <table class="w-full text-xs text-left">
                               <thead class="bg-gray-50 text-gray-500 mb-2 block">
                                  <tr class="flex justify-between px-2 py-1">
                                    <th>Produk</th>
                                    <th>Qty</th>
                                  </tr>
                              </thead>
                              <tbody class="space-y-1 block">
                                  @forelse($catTopi as $item)
                                  <tr class="flex justify-between items-center p-2 hover:bg-yellow-50 bg-white border border-gray-100 rounded transition-colors group">
                                      <td class="flex-1 min-w-0 pr-2">
                                          <div class="font-medium text-gray-800 line-clamp-2 leading-tight" title="{{ $item->product_name }}">{{ $item->product_name }}</div>
                                          <div class="text-[10px] text-gray-400 mt-0.5">{{ $item->product_sku }}</div>
                                      </td>
                                      <td class="text-right whitespace-nowrap">
                                          <span class="font-bold text-yellow-600 bg-yellow-100 px-2 py-1 rounded text-[10px]">{{ number_format($item->total_terjual) }}</span>
                                      </td>
                                  </tr>
                                  @empty
                                  <tr><td colspan="2" class="text-center py-8 text-gray-300 italic text-xs">Belum ada data</td></tr>
                                  @endforelse
                              </tbody>
                          </table>
                      </div>
                  </div>
              </div>
          </div>

          <!-- === JENIS TRANSAKSI === -->
          <div class="bg-white p-6 rounded-xl shadow-lg mb-6 border-l-4 border-purple-500">
            <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="bi bi-pie-chart text-purple-500"></i>
                Jenis Transaksi 
                <span class="text-sm text-gray-500 font-normal">
                    ({{ \Carbon\Carbon::parse($startDate)->format('d/m') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m') }})
                </span>
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <div class="text-2xl font-bold text-blue-700">{{ $salesTypeStats['total'] ?? 0 }}</div>
                    <div class="text-sm text-blue-600">Total Transaksi</div>
                </div>
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="text-2xl font-bold text-green-700">{{ $salesTypeStats['direct'] ?? 0 }}</div>
                    <div class="text-sm text-green-600">Langsung ({{ $salesTypeStats['direct_percentage'] ?? 0 }}%)</div>
                </div>
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <div class="text-2xl font-bold text-purple-700">{{ $salesTypeStats['po'] ?? 0 }}</div>
                    <div class="text-sm text-purple-600">Pre-Order ({{ $salesTypeStats['po_percentage'] ?? 0 }}%)</div>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="mt-4">
                <div class="flex justify-between text-sm text-gray-600 mb-1">
                    <span>Langsung di Toko</span>
                    <span>Pre-Order</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="flex h-3 rounded-full">
                        <div class="bg-green-500" style="width: {{ $salesTypeStats['direct_percentage'] ?? 0 }}%"></div>
                        <div class="bg-purple-500" style="width: {{ $salesTypeStats['po_percentage'] ?? 0 }}%"></div>
                    </div>
                </div>
            </div>
          </div>

          <!-- === QUICK ACTIONS === -->
          <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
            <h3 class="font-semibold text-gray-700 mb-4">Aksi Cepat</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <a href="{{ route('kepala-toko.sales.create') }}" class="p-4 bg-blue-50 rounded-lg text-center hover:bg-blue-100 transition-colors">
                <i class="bi bi-plus-circle text-blue-600 text-2xl mb-2"></i>
                <div class="font-medium text-blue-800 text-sm">Buat SO</div>
              </a>
              <a href="{{ route('kepala-toko.sales.index') }}" class="p-4 bg-green-50 rounded-lg text-center hover:bg-green-100 transition-colors">
                <i class="bi bi-receipt text-green-600 text-2xl mb-2"></i>
                <div class="font-medium text-green-800 text-sm">Lihat Sales</div>
              </a>
              <a href="{{ route('kepala-toko.shift.dashboard') }}" class="p-4 bg-purple-50 rounded-lg text-center hover:bg-purple-100 transition-colors">
                <i class="bi bi-cash-coin text-purple-600 text-2xl mb-2"></i>
                <div class="font-medium text-purple-800 text-sm">Kelola Shift</div>
              </a>
              <a href="{{ route('kepala-toko.product.index') }}" class="p-4 bg-orange-50 rounded-lg text-center hover:bg-orange-100 transition-colors">
                <i class="bi bi-box-seam text-orange-600 text-2xl mb-2"></i>
                <div class="font-medium text-orange-800 text-sm">Produk</div>
              </a>
            </div>
          </div>

        </div>
      </div>
    </div>

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
    </script>

    <!-- Chart Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
          const ctx = document.getElementById('financialChart').getContext('2d');
          new Chart(ctx, {
              data: {
                  labels: {!! json_encode($financialChartData['labels']) !!},
                  datasets: [
                      {
                          type: 'line',
                          label: 'Omset',
                          data: {!! json_encode($financialChartData['omset']) !!},
                          borderColor: '#2563eb',
                          backgroundColor: 'rgba(37, 99, 235, 0.1)',
                          fill: true,
                          tension: 0.4,
                          yAxisID: 'y'
                      },
                      {
                          type: 'line',
                          label: 'HPP',
                          data: {!! json_encode($financialChartData['hpp']) !!},
                          borderColor: '#ea580c',
                          backgroundColor: 'rgba(234, 88, 12, 0.1)',
                          fill: true,
                          tension: 0.4,
                          yAxisID: 'y'
                      },
                      {
                          type: 'line',
                          label: 'Profit',
                          data: {!! json_encode($financialChartData['profit']) !!},
                          borderColor: '#16a34a',
                          backgroundColor: 'rgba(22, 163, 74, 0.1)',
                          fill: true,
                          tension: 0.4,
                          yAxisID: 'y'
                      },
                      {
                          type: 'bar',
                          label: 'Invoice',
                          data: {!! json_encode($financialChartData['invoices']) !!},
                          backgroundColor: 'rgba(147, 51, 234, 0.2)',
                          borderColor: '#9333ea',
                          borderWidth: 1,
                          yAxisID: 'y1',
                          barThickness: 'flex'
                      }
                  ]
              },
              options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  interaction: {
                      mode: 'index',
                      intersect: false,
                  },
                  plugins: {
                      legend: {
                          position: 'top',
                          labels: {
                              usePointStyle: true,
                              padding: 20,
                              font: {
                                  size: 11
                              }
                          }
                      },
                      tooltip: {
                          backgroundColor: 'rgba(255, 255, 255, 0.9)',
                          titleColor: '#1f2937',
                          bodyColor: '#4b5563',
                          borderColor: '#e5e7eb',
                          borderWidth: 1,
                          padding: 12,
                          displayColors: true,
                          callbacks: {
                              label: function(context) {
                                  let label = context.dataset.label || '';
                                  if (label) {
                                      label += ': ';
                                  }
                                  if (context.dataset.yAxisID === 'y') {
                                      label += new Intl.NumberFormat('id-ID', { 
                                          style: 'currency', 
                                          currency: 'IDR', 
                                          maximumFractionDigits: 0 
                                      }).format(context.parsed.y);
                                  } else {
                                      label += context.parsed.y + ' Nota';
                                  }
                                  return label;
                              }
                          }
                      }
                  },
                  scales: {
                      y: {
                          beginAtZero: true,
                          position: 'left',
                          ticks: {
                              font: { size: 10 },
                              callback: function(value) {
                                  if (value >= 1000000) return 'Rp ' + (value / 1000000) + 'jt';
                                  if (value >= 1000) return 'Rp ' + (value / 1000) + 'rb';
                                  return 'Rp ' + value;
                              }
                          },
                          grid: {
                              color: 'rgba(0, 0, 0, 0.05)'
                          }
                      },
                      y1: {
                          beginAtZero: true,
                          position: 'right',
                          ticks: {
                              font: { size: 10 },
                              stepSize: 1
                          },
                          grid: {
                              drawOnChartArea: false
                          },
                          title: {
                              display: true,
                              text: 'Jumlah Nota',
                              font: { size: 10 }
                          }
                      },
                      x: {
                          ticks: {
                              font: { size: 10 },
                              maxRotation: 45,
                              minRotation: 45
                          },
                          grid: {
                              display: false
                          }
                      }
                  }
              }
          });
      });
    </script>
  </body>
</html>
