<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventory - Custom Pare</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    body{ font-family: 'Raleway', sans-serif; }
    .nav-text{ position: relative; display: inline-block; }
    .nav-text::after{ content:''; position:absolute; width:0; height:2px; bottom:-2px; left:0; background-color:#e17f12; transition:width .2s; }
    .hover-link:hover .nav-text::after{ width:100%; }
    
    /* Active link effect */
    .active .nav-text::after {
      width: 100%;
    }
  </style>
</head>
<body class="bg-gray-100">
  <div class="flex">

    <x-navbar-finance></x-navbar-finance>

    <div class="flex-1 lg:w-5/6">
      <x-navbar-top-finance></x-navbar-top-finance>

      <div class="p-4">
        <div class="bg-white p-6 rounded-xl shadow-lg">
          <div class="flex border-b mb-4">
            <a href="{{ route('finance.inventory.index') }}" 
               class="px-4 py-2 font-semibold {{ request()->routeIs('finance.inventory.index') ? 'border-b-2 border-[#005281] text-[#005281]' : 'text-gray-500 hover:text-[#005281] hover:border-b-2 hover:border-gray-300' }}">
              Overview
            </a>
            <a href="{{ route('finance.inventory.stock-ins.index') }}" 
               class="px-4 py-2 font-semibold {{ request()->routeIs('finance.inventory.stock-ins.*') ? 'border-b-2 border-[#005281] text-[#005281]' : 'text-gray-500 hover:text-[#005281] hover:border-b-2 hover:border-gray-300' }}">
              Stok Masuk
            </a>
            <a href="{{ route('finance.inventory.stock-opnames.index') }}" 
               class="px-4 py-2 font-semibold {{ request()->routeIs('finance.inventory.stock-opnames.*') ? 'border-b-2 border-[#005281] text-[#005281]' : 'text-gray-500 hover:text-[#005281] hover:border-b-2 hover:border-gray-300' }}">
              Stock Opname
            </a>
            <a href="{{ route('finance.inventory.stock-movements.index') }}" 
               class="px-4 py-2 font-semibold {{ request()->routeIs('finance.inventory.stock-movements.*') ? 'border-b-2 border-[#005281] text-[#005281]' : 'text-gray-500 hover:text-[#005281] hover:border-b-2 hover:border-gray-300' }}">
              Pergerakan Stok
            </a>
          </div>

          {{-- Overview Content Baru --}}
          @if(request()->routeIs('finance.inventory.index'))
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Card Total Produk --}}
        <div class="bg-blue-50 p-4 rounded-lg shadow">
            <div class="flex items-center">
                <i class="bi bi-box-seam text-2xl text-blue-600 mr-3"></i>
                <div>
                    <h4 class="text-sm text-gray-600">Total Produk</h4>
                    <p class="text-2xl font-semibold text-blue-800">{{ $totalProducts }}</p>
                    <a href="{{ route('finance.product.index') }}" class="text-xs text-blue-600 hover:underline">Lihat Produk</a>
                </div>
            </div>
        </div>

        {{-- Card Total Stok Masuk --}}
        <div class="bg-green-50 p-4 rounded-lg shadow">
            <div class="flex items-center">
                <i class="bi bi-arrow-down-circle text-2xl text-green-600 mr-3"></i>
                <div>
                    <h4 class="text-sm text-gray-600">Stok Masuk</h4>
                    <p class="text-2xl font-semibold text-green-800">{{ $totalStockIns }} ({{ $totalStockInItems }} item)</p>
                    <a href="{{ route('finance.inventory.stock-ins.index') }}" class="text-xs text-green-600 hover:underline">Lihat Detail</a>
                </div>
            </div>
        </div>

        {{-- Card Opname Pending --}}
        <div class="bg-yellow-50 p-4 rounded-lg shadow">
            <div class="flex items-center">
                <i class="bi bi-clipboard-check text-2xl text-yellow-600 mr-3"></i>
                <div>
                    <h4 class="text-sm text-gray-600">Opname Pending</h4>
                    <p class="text-2xl font-semibold text-yellow-800">{{ $pendingOpnames }}</p>
                    <a href="{{ route('finance.inventory.stock-opnames.index') }}" class="text-xs text-yellow-600 hover:underline">Lihat Opname</a>
                </div>
            </div>
        </div>

        {{-- Card Stok Rendah --}}
        <div class="bg-red-50 p-4 rounded-lg shadow">
            <div class="flex items-center">
                <i class="bi bi-exclamation-triangle text-2xl text-red-600 mr-3"></i>
                <div>
                    <h4 class="text-sm text-gray-600">Stok Rendah</h4>
                    <p class="text-2xl font-semibold text-red-800">{{ $lowStockProducts }}</p>
                    <a href="{{ route('finance.product.index') }}" class="text-xs text-red-600 hover:underline">Lihat Produk</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Recent Movements --}}
    <div class="mt-6">
        <h3 class="text-lg font-semibold mb-3">Pergerakan Stok Terbaru</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2">Tanggal</th>
                        <th class="px-4 py-2">Produk</th>
                        <th class="px-4 py-2">Tipe</th>
                        <th class="px-4 py-2">Masuk</th>
                        <th class="px-4 py-2">Keluar</th>
                        <th class="px-4 py-2">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentMovements as $move)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">{{ \Carbon\Carbon::parse($move->moved_at)->format('d M Y H:i') }}</td>
                        <td class="px-4 py-2">{{ $move->product->name ?? '-' }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded-full text-xs 
                                @if($move->type == 'INCOMING') bg-green-100 text-green-800
                                @elseif($move->type == 'POS_SALE') bg-red-100 text-red-800
                                @elseif($move->type == 'POS_CANCEL') bg-yellow-100 text-yellow-800
                                @elseif($move->type == 'SALE_RETURN') bg-blue-100 text-blue-800
                                @elseif($move->type == 'OPNAME') bg-purple-100 text-purple-800
                                @elseif($move->type == 'PURCHASE_RETURN') bg-orange-100 text-orange-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ $move->type }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-green-600">{{ $move->qty_in > 0 ? '+' . $move->qty_in : '-' }}</td>
                        <td class="px-4 py-2 text-red-600">{{ $move->qty_out > 0 ? '-' . $move->qty_out : '-' }}</td>
                        <td class="px-4 py-2">{{ $move->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-4 text-center text-gray-500">Tidak ada pergerakan stok terbaru.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <a href="{{ route('finance.inventory.stock-movements.index') }}" class="text-blue-600 hover:underline text-sm">Lihat Semua Pergerakan</a>
        </div>
    </div>
@endif
        </div>
      </div>
    </div>
  </div>

  <script>
    function toggleSidebar(){
      const el = document.getElementById('sidebar');
      if(!el) return; el.classList.toggle('-translate-x-full');
    }
    function toggleDropdown(btn){
      const menu = btn.nextElementSibling;
      if(!menu) return;
      if(menu.style.maxHeight && menu.style.maxHeight !== '0px'){
        menu.style.maxHeight = '0px';
        btn.querySelector('i.bi-chevron-down')?.classList.remove('rotate-180');
      } else {
        menu.style.maxHeight = menu.scrollHeight + 'px';
        btn.querySelector('i.bi-chevron-down')?.classList.add('rotate-180');
      }
    }
  </script>
</body>
</html>