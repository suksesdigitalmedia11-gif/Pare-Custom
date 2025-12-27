<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pergerakan Stok - Kepala Toko - Custom Pare</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Raleway', sans-serif; }
    .nav-text { position: relative; display: inline-block; }
    .nav-text::after { content: ''; position: absolute; width: 0; height: 2px; bottom: -2px; left: 0; background-color: #e17f12; transition: width .2s; }
    .hover-link:hover .nav-text::after { width: 100%; }
  </style>
</head>
<body class="bg-gray-100">
  <div class="flex">
    <x-navbar-kepala-toko></x-navbar-kepala-toko>
    <div class="flex-1 lg:w-5/6">
      <x-navbar-top-kepala-toko></x-navbar-top-kepala-toko>
      <div class="p-4 lg:p-8">
        <div class="bg-white p-6 rounded-xl shadow-lg">
          {{-- Tabs --}}
          <div class="flex border-b mb-4 overflow-x-auto">
            <a href="{{ route('kepala-toko.inventory.index') }}" 
               class="px-4 py-2 font-semibold whitespace-nowrap {{ request()->routeIs('kepala-toko.inventory.index') ? 'border-b-2 border-[#005281] text-[#005281]' : 'text-gray-500 hover:text-[#005281] hover:border-b-2 hover:border-gray-300' }}">Overview</a>
            <a href="{{ route('kepala-toko.inventory.stock-ins.index') }}" 
               class="px-4 py-2 font-semibold whitespace-nowrap {{ request()->routeIs('kepala-toko.inventory.stock-ins.*') ? 'border-b-2 border-[#005281] text-[#005281]' : 'text-gray-500 hover:text-[#005281] hover:border-b-2 hover:border-gray-300' }}">Stok Masuk</a>
            <a href="{{ route('kepala-toko.inventory.stock-opnames.index') }}" 
               class="px-4 py-2 font-semibold whitespace-nowrap {{ request()->routeIs('kepala-toko.inventory.stock-opnames.*') ? 'border-b-2 border-[#005281] text-[#005281]' : 'text-gray-500 hover:text-[#005281] hover:border-b-2 hover:border-gray-300' }}">Stock Opname</a>
            <a href="{{ route('kepala-toko.inventory.stock-movements.index') }}" 
               class="px-4 py-2 font-semibold whitespace-nowrap {{ request()->routeIs('kepala-toko.inventory.stock-movements.*') ? 'border-b-2 border-[#005281] text-[#005281]' : 'text-gray-500 hover:text-[#005281] hover:border-b-2 hover:border-gray-300' }}">Pergerakan Stok</a>
          </div>

          {{-- Header & Filters --}}
          <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div>
                  <h3 class="text-xl font-bold text-gray-800">Riwayat Pergerakan Stok</h3>
                  <p class="text-sm text-gray-500">Mencatat setiap pergerakan stok secara kronologis.</p>
              </div>
          </div>

          {{-- Filter Form --}}
          <form method="GET" class="bg-gray-50 p-4 rounded-xl border border-gray-200 mb-6">
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                  {{-- Search --}}
                  <div class="md:col-span-2">
                      <label class="block text-sm font-medium text-gray-700 mb-1">Cari Barang / Referensi</label>
                      <div class="relative">
                          <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                          <input type="text" name="search" value="{{ $search ?? '' }}" 
                              class="w-full pl-10 pr-4 py-2 rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" 
                              placeholder="Nama Produk, SKU, No. Ref...">
                      </div>
                  </div>
                  {{-- Date From --}}
                  <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                      <input type="date" name="from" value="{{ $from ?? date('Y-m-d') }}" 
                          class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                  </div>
                  {{-- Date To --}}
                  <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                      <div class="flex gap-2">
                          <input type="date" name="to" value="{{ $to ?? date('Y-m-d') }}" 
                              class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors" title="Terapkan Filter">
                              <i class="bi bi-funnel-fill"></i>
                          </button>
                          <a href="{{ route('kepala-toko.inventory.stock-movements.index') }}" class="bg-gray-200 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-300 transition-colors" title="Reset">
                              <i class="bi bi-arrow-clockwise"></i>
                          </a>
                      </div>
                  </div>
              </div>
          </form>

          {{-- Main Table --}}
          <div class="overflow-x-auto rounded-lg border border-gray-100">
              <table class="min-w-full text-sm text-left">
                  <thead class="bg-gray-100 text-gray-600 font-semibold uppercase tracking-wider">
                      <tr>
                          <th class="px-4 py-3">Waktu</th>
                          <th class="px-4 py-3">Produk</th>
                          <th class="px-4 py-3">Tipe & Referensi</th>
                          <th class="px-4 py-3 text-center">Stok Awal</th>
                          <th class="px-4 py-3 text-center">Masuk</th>
                          <th class="px-4 py-3 text-center">Keluar</th>
                          <th class="px-4 py-3 text-center">Stok Akhir</th>
                          <th class="px-4 py-3">User & Ket</th>
                      </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                  @forelse($stockMovements as $move)
                      <tr class="hover:bg-blue-50/50 transition-colors group">
                          <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                              <div class="font-medium text-gray-900">{{ $move->moved_at->format('d M Y') }}</div>
                              <div class="text-xs">{{ $move->moved_at->format('H:i') }}</div>
                          </td>
                          <td class="px-4 py-3">
                              <div class="font-bold text-gray-800">{{ $move->product->name ?? 'Produk Dihapus' }}</div>
                              <div class="text-xs font-mono text-gray-500 group-hover:text-blue-600 transition-colors">{{ $move->product->sku ?? '-' }}</div>
                          </td>
                          <td class="px-4 py-3">
                              @php
                                  $badgeColor = match($move->type) {
                                      'INCOMING' => 'bg-green-100 text-green-700',
                                      'POS_SALE' => 'bg-blue-50 text-blue-700',
                                      'OPNAME' => 'bg-purple-100 text-purple-700',
                                      'POS_CANCEL', 'SALE_RETURN' => 'bg-yellow-100 text-yellow-700',
                                      'PURCHASE_RETURN' => 'bg-orange-100 text-orange-700',
                                      'adjustment' => 'bg-gray-100 text-gray-800',
                                      default => 'bg-gray-100 text-gray-600'
                                  };
                              @endphp
                              <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide {{ $badgeColor }}">{{ str_replace('_', ' ', $move->type) }}</span>
                              @if($move->ref_code)
                                  <div class="text-xs mt-1 font-mono text-gray-500 select-all">{{ $move->ref_code }}</div>
                              @endif
                          </td>
                          <td class="px-4 py-3 text-center text-gray-400">{{ $move->initial_qty }}</td>
                          <td class="px-4 py-3 text-center">
                              @if($move->qty_in > 0)
                                  <span class="text-green-600 font-bold bg-green-50 px-2 py-0.5 rounded">+{{ $move->qty_in }}</span>
                              @else <span class="text-gray-300">-</span> @endif
                          </td>
                          <td class="px-4 py-3 text-center">
                              @if($move->qty_out > 0)
                                  <span class="text-red-600 font-bold bg-red-50 px-2 py-0.5 rounded">-{{ $move->qty_out }}</span>
                              @else <span class="text-gray-300">-</span> @endif
                          </td>
                          <td class="px-4 py-3 text-center font-bold text-gray-800 bg-gray-50/50 rounded">{{ $move->final_qty }}</td>
                          <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate">
                              <div class="font-medium text-gray-700">{{ $move->user->name ?? 'System' }}</div>
                              @if($move->notes) <div class="italic truncate" title="{{ $move->notes }}">{{ $move->notes }}</div> @endif
                          </td>
                      </tr>
                  @empty
                      <tr>
                          <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                              <div class="flex flex-col items-center justify-center">
                                  <i class="bi bi-search text-3xl mb-2 text-gray-300"></i>
                                  <p>Tidak ada riwayat mutasi yang cocok dengan filter.</p>
                              </div>
                          </td>
                      </tr>
                  @endforelse
                  </tbody>
              </table>
          </div>
          <div class="mt-6">
              {{ $stockMovements->appends(request()->query())->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>