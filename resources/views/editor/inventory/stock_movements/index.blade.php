<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pergerakan Stok - Editor - Custom Pare</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    body { font-family: 'Raleway', sans-serif; }
    .mono { font-family: 'JetBrains Mono', monospace; }
    
    .table-container {
        max-height: calc(100vh - 320px);
        overflow-y: auto;
    }
    thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f8fafc;
    }

    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    .stat-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    [x-cloak] { display: none !important; }
  </style>
</head>
<body class="bg-[#f8fafc]" x-data="movementApp()">
  <div class="flex">
    <x-navbar-editor></x-navbar-editor>
    <div class="flex-1 lg:w-5/6">
      <x-navbar-top-editor></x-navbar-top-editor>
      <div class="p-4 lg:p-8">
        
        {{-- Header & Tabs --}}
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 bg-emerald-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                    <i class="bi bi-arrow-left-right text-xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Manajemen Inventori</h2>
            </div>
            
            <div class="flex border-b border-gray-200 mt-6 overflow-x-auto bg-white rounded-t-xl px-4 pt-2">
                <a href="{{ route('editor.inventory.index') }}" 
                   class="px-6 py-3 font-semibold whitespace-nowrap transition-all {{ request()->routeIs('editor.inventory.index') ? 'border-b-2 border-emerald-600 text-emerald-600' : 'text-gray-500 hover:text-emerald-600' }}">Overview</a>
                <a href="{{ route('editor.inventory.stock-ins.index') }}" 
                   class="px-6 py-3 font-semibold whitespace-nowrap transition-all {{ request()->routeIs('editor.inventory.stock-ins.*') ? 'border-b-2 border-emerald-600 text-emerald-600' : 'text-gray-500 hover:text-emerald-600' }}">Stok Masuk</a>
                <a href="{{ route('editor.inventory.stock-opnames.index') }}" 
                   class="px-6 py-3 font-semibold whitespace-nowrap transition-all {{ request()->routeIs('editor.inventory.stock-opnames.*') ? 'border-b-2 border-emerald-600 text-emerald-600' : 'text-gray-500 hover:text-emerald-600' }}">Stock Opname</a>
                <a href="{{ route('editor.inventory.stock-movements.index') }}" 
                   class="px-6 py-3 font-semibold whitespace-nowrap transition-all {{ request()->routeIs('editor.inventory.stock-movements.*') ? 'border-b-2 border-emerald-600 text-emerald-600' : 'text-gray-500 hover:text-emerald-600' }}">Pergerakan Stok</a>
            </div>
        </div>

        {{-- Stats Summary --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 stat-card">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-500">Total Masuk</span>
                    <i class="bi bi-arrow-down-left-circle text-green-500 text-xl"></i>
                </div>
                <div class="text-2xl font-bold text-gray-900 mono">{{ number_format($summary['total_in'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 stat-card">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-500">Total Keluar</span>
                    <i class="bi bi-arrow-up-right-circle text-red-500 text-xl"></i>
                </div>
                <div class="text-2xl font-bold text-gray-900 mono">{{ number_format($summary['total_out'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 stat-card">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-500">Total Produk Bergerak</span>
                    <i class="bi bi-box-seam text-emerald-500 text-xl"></i>
                </div>
                <div class="text-2xl font-bold text-gray-900 mono">{{ $stockMovements->total() }}</div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 stat-card">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-500">Produk Teraktif</span>
                    <i class="bi bi-star-fill text-yellow-500 text-xl"></i>
                </div>
                <div class="text-lg font-bold text-gray-900 truncate" title="{{ $summary['most_active_product']->product->name ?? '-' }}">
                    {{ $summary['most_active_product']->product->name ?? '-' }}
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
          {{-- Filters --}}
          <div class="p-6 border-b border-gray-100 bg-gray-50/50">
            <form method="GET" class="flex flex-col lg:flex-row gap-4">
                <div class="flex-1 relative">
                    <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="{{ $search }}" 
                        class="w-full pl-11 pr-4 py-3 rounded-xl border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all shadow-sm" 
                        placeholder="Cari Produk atau SKU...">
                </div>
                <div class="flex flex-wrap lg:flex-nowrap gap-3">
                    <div class="relative group">
                        <label class="absolute -top-2 left-3 px-1 bg-white text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Mulai</label>
                        <input type="date" name="from" value="{{ $from }}" 
                            class="rounded-xl border-gray-200 py-3 text-sm focus:ring-emerald-500 focus:border-emerald-500 shadow-sm transition-all">
                    </div>
                    <div class="relative group">
                        <label class="absolute -top-2 left-3 px-1 bg-white text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Sampai</label>
                        <input type="date" name="to" value="{{ $to }}" 
                            class="rounded-xl border-gray-200 py-3 text-sm focus:ring-emerald-500 focus:border-emerald-500 shadow-sm transition-all">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-emerald-600 text-white px-6 py-3 rounded-xl hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-100 font-bold flex items-center gap-2">
                            <i class="bi bi-funnel"></i> <span>Terapkan</span>
                        </button>
                        <a href="{{ route('editor.inventory.stock-movements.index') }}" 
                           class="bg-white border border-gray-200 text-gray-600 px-4 py-3 rounded-xl hover:bg-gray-50 transition-all flex items-center shadow-sm" 
                           title="Reset Filter">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </div>
            </form>
          </div>

          {{-- Main Table (Grouped View) --}}
          <div class="table-container">
              <table class="min-w-full text-sm text-left border-separate border-spacing-0">
                  <thead class="text-gray-500 font-bold text-[11px] uppercase tracking-[0.1em]">
                      <tr>
                          <th class="px-6 py-4 border-b border-gray-100">Informasi Produk</th>
                          <th class="px-6 py-4 border-b border-gray-100 text-center">Stok Awal</th>
                          <th class="px-6 py-4 border-b border-gray-100 text-center">Total Masuk</th>
                          <th class="px-6 py-4 border-b border-gray-100 text-center">Total Keluar</th>
                          <th class="px-6 py-4 border-b border-gray-100 text-center">Stok Akhir</th>
                          <th class="px-6 py-4 border-b border-gray-100 text-center">Mutasi</th>
                          <th class="px-6 py-4 border-b border-gray-100 text-right">Aksi</th>
                      </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-50">
                  @forelse($stockMovements as $item)
                      <tr class="hover:bg-emerald-50/30 transition-all group cursor-pointer" @click="openModal({{ $item->product_id }})">
                          <td class="px-6 py-4">
                              <div class="font-bold text-gray-900 leading-tight">{{ $item->product->name ?? 'Produk Dihapus' }}</div>
                              <div class="text-[11px] font-medium text-emerald-600 mono mt-1 select-all group-hover:underline">
                                  {{ $item->product->sku ?? '-' }}
                              </div>
                          </td>
                          <td class="px-6 py-4 text-center text-gray-400 mono font-medium">
                              {{ number_format($item->opening_balance, 0, ',', '.') }}
                          </td>
                          <td class="px-6 py-4 text-center">
                              @if($item->total_in > 0)
                                  <span class="text-green-600 font-bold mono bg-green-100/50 px-2 py-1 rounded">+{{ number_format($item->total_in, 0, ',', '.') }}</span>
                              @else <span class="text-gray-300">-</span> @endif
                          </td>
                          <td class="px-6 py-4 text-center">
                              @if($item->total_out > 0)
                                  <span class="text-red-600 font-bold mono bg-red-100/50 px-2 py-1 rounded">-{{ number_format($item->total_out, 0, ',', '.') }}</span>
                              @else <span class="text-gray-300">-</span> @endif
                          </td>
                          <td class="px-6 py-4 text-center">
                                <div class="inline-block px-3 py-1 bg-gray-900 text-white rounded-lg font-bold mono shadow-sm">
                                    {{ number_format($item->closing_balance, 0, ',', '.') }}
                                </div>
                          </td>
                          <td class="px-6 py-4 text-center">
                              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                  <i class="bi bi-activity"></i> {{ $item->total_records }} Mutasi
                              </span>
                          </td>
                          <td class="px-6 py-4 text-right">
                              <button class="w-8 h-8 rounded-full bg-white border border-gray-200 text-gray-400 flex items-center justify-center hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition-all shadow-sm">
                                  <i class="bi bi-chevron-right"></i>
                              </button>
                          </td>
                      </tr>
                  @empty
                      <tr>
                          <td colspan="7" class="px-6 py-20 text-center">
                              <div class="flex flex-col items-center justify-center">
                                  <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                      <i class="bi bi-search text-gray-300 text-3xl"></i>
                                  </div>
                                  <h4 class="text-lg font-bold text-gray-800">Tidak ada data mutasi</h4>
                                  <p class="text-gray-500 text-sm">Coba ubah filter tanggal atau pencarian Anda.</p>
                              </div>
                          </td>
                      </tr>
                  @endforelse
                  </tbody>
              </table>
          </div>
          
          {{-- Pagination --}}
          <div class="p-6 bg-gray-50 border-t border-gray-100">
              {{ $stockMovements->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Modal Popup --}}
  <div x-show="isModalOpen" 
       x-cloak
       class="fixed inset-0 z-[100] overflow-y-auto" 
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">
    
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75 backdrop-blur-sm" @click="isModalOpen = false"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-3xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            
            {{-- Modal Header --}}
            <div class="px-6 py-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-emerald-100 rounded-2xl flex items-center justify-center text-emerald-600 shadow-inner">
                        <i class="bi bi-layers-half text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 leading-tight" x-text="details.product.name"></h3>
                        <p class="text-xs text-gray-500 mt-0.5 mono">
                            SKU: <span class="font-bold text-emerald-600" x-text="details.product.sku"></span> | 
                            Periode: <span x-text="formatDate(details.range.from)"></span> - <span x-text="formatDate(details.range.to)"></span>
                        </p>
                    </div>
                </div>
                <button @click="isModalOpen = false" class="w-10 h-10 rounded-xl hover:bg-red-50 hover:text-red-500 text-gray-400 transition-all flex items-center justify-center">
                    <i class="bi bi-x-lg text-xl"></i>
                </button>
            </div>

            {{-- Modal Content (Ledger Style) --}}
            <div class="max-h-[60vh] overflow-y-auto px-6 py-4">
                <div x-show="isLoading" class="flex flex-col items-center justify-center py-20">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
                    <p class="mt-4 text-gray-500 font-medium">Memuat data mutasi...</p>
                </div>

                <div x-show="!isLoading">
                    <table class="min-w-full text-sm">
                        <thead class="text-gray-400 font-bold text-[10px] uppercase tracking-widest border-b border-gray-100">
                            <tr>
                                <th class="pb-3 text-left">Waktu</th>
                                <th class="pb-3 text-left">Tipe & Referensi</th>
                                <th class="pb-3 text-center">Stok Awal</th>
                                <th class="pb-3 text-center">Mutasi</th>
                                <th class="pb-3 text-center">Stok Akhir</th>
                                <th class="pb-3 text-right">User & Ket</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <template x-for="move in details.movements" :key="move.id">
                                <tr class="hover:bg-gray-50/80 transition-all">
                                    <td class="py-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-800 mono" x-text="formatDateTime(move.moved_at).date"></div>
                                        <div class="text-[10px] text-gray-400 mono" x-text="formatDateTime(move.moved_at).time"></div>
                                    </td>
                                    <td class="py-4">
                                        <div class="flex flex-col gap-1">
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase w-fit"
                                                  :class="getTypeStyle(move.type).class">
                                                <i class="bi" :class="getTypeStyle(move.type).icon"></i>
                                                <span x-text="getTypeStyle(move.type).label"></span>
                                            </span>
                                            <template x-if="move.ref_code">
                                                <template x-if="move.detail_url">
                                                    <a :href="move.detail_url" 
                                                       class="text-[10px] font-mono text-emerald-600 bg-emerald-50 border border-emerald-100 px-1.5 py-0.5 rounded w-fit hover:bg-emerald-600 hover:text-white transition-all flex items-center gap-1 group/link">
                                                        <span x-text="move.ref_code"></span>
                                                        <i class="bi bi-box-arrow-up-right text-[8px]"></i>
                                                    </a>
                                                </template>
                                                <template x-if="!move.detail_url">
                                                    <span class="text-[10px] font-mono text-gray-500 bg-white border border-gray-100 px-1.5 py-0.5 rounded w-fit" 
                                                          x-text="move.ref_code"></span>
                                                </template>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center text-gray-400 mono" x-text="move.initial_qty"></td>
                                    <td class="py-4 text-center">
                                        <span x-show="move.qty_in > 0" class="text-green-600 font-bold mono bg-green-50 px-2 py-0.5 rounded">
                                            +<span x-text="move.qty_in"></span>
                                        </span>
                                        <span x-show="move.qty_out > 0" class="text-red-600 font-bold mono bg-red-50 px-2 py-0.5 rounded">
                                            -<span x-text="move.qty_out"></span>
                                        </span>
                                    </td>
                                    <td class="py-4 text-center">
                                        <div class="inline-block px-2.5 py-0.5 bg-gray-900 text-white rounded-md font-bold mono text-xs shadow-sm" x-text="move.final_qty"></div>
                                    </td>
                                    <td class="py-4 text-right">
                                        <div class="font-bold text-gray-700 text-xs" x-text="move.user?.name || 'System'"></div>
                                        <div class="italic text-[10px] text-gray-400 truncate max-w-[150px] ml-auto" :title="move.notes" x-text="move.notes"></div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex justify-end">
                <button @click="isModalOpen = false" class="px-6 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl font-bold hover:bg-gray-50 transition-all shadow-sm">
                    Tutup
                </button>
            </div>
        </div>
    </div>
  </div>

  <script>
    function movementApp() {
        return {
            isModalOpen: false,
            isLoading: false,
            details: {
                product: {},
                range: {},
                movements: []
            },
            
            async openModal(productId) {
                this.isModalOpen = true;
                this.isLoading = true;
                
                const from = document.querySelector('input[name="from"]').value;
                const to = document.querySelector('input[name="to"]').value;
                
                try {
                    const response = await fetch(`{{ route('editor.inventory.stock-movements.index') }}/${productId}/details?from=${from}&to=${to}`);
                    this.details = await response.json();
                } catch (error) {
                    console.error('Error fetching details:', error);
                    alert('Gagal memuat data mutasi.');
                } finally {
                    this.isLoading = false;
                }
            },

            formatDate(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            },

            formatDateTime(dateTimeStr) {
                if (!dateTimeStr) return { date: '', time: '' };
                const d = new Date(dateTimeStr);
                return {
                    date: d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }),
                    time: d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
                };
            },

            getTypeStyle(type) {
                const styles = {
                    'INCOMING': { color: 'green', label: 'Stok Masuk', icon: 'bi-box-arrow-in-down', class: 'bg-green-100 text-green-700' },
                    'IN_PURCHASE': { color: 'green', label: 'Stok Masuk', icon: 'bi-box-arrow-in-down', class: 'bg-green-100 text-green-700' },
                    'OUTGOING': { color: 'blue', label: 'Penjualan', icon: 'bi-cart-check', class: 'bg-blue-100 text-blue-700' },
                    'OUT': { color: 'blue', label: 'Penjualan', icon: 'bi-cart-check', class: 'bg-blue-100 text-blue-700' },
                    'POS_SALE': { color: 'blue', label: 'Penjualan', icon: 'bi-cart-check', class: 'bg-blue-100 text-blue-700' },
                    'OPNAME': { color: 'purple', label: 'Opname', icon: 'bi-clipboard-check', class: 'bg-purple-100 text-purple-700' },
                    'POS_CANCEL': { color: 'yellow', label: 'Batal Jual', icon: 'bi-x-circle', class: 'bg-yellow-100 text-yellow-700' },
                    'purchase_cancel': { color: 'yellow', label: 'Batal Beli', icon: 'bi-x-circle', class: 'bg-yellow-100 text-yellow-700' },
                    'adjustment': { color: 'indigo', label: 'Penyesuaian', icon: 'bi-sliders', class: 'bg-indigo-100 text-indigo-700' },
                };
                return styles[type] || { color: 'gray', label: type, icon: 'bi-gear', class: 'bg-gray-100 text-gray-700' };
            }
        }
    }
  </script>
</body>
</html>