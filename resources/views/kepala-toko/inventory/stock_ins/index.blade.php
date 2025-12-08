<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Stok Masuk - Inventory - Custom Pare</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Raleway', sans-serif; }
    .nav-text { position: relative; display: inline-block; }
    .nav-text::after { content: ''; position: absolute; width: 0; height: 2px; bottom: -2px; left: 0; background-color: #e17f12; transition: width .2s; }
    .hover-link:hover .nav-text::after { width: 100%; }
    .clickable-row { cursor: pointer; transition: background-color 0.2s ease; }
    .clickable-row:hover { background-color: #f9fafb; }
    .non-clickable-row { cursor: default; }
  </style>
</head>
<body class="bg-gray-100">
  <div class="flex">

    <x-navbar-kepala-toko></x-navbar-kepala-toko>

    <div class="flex-1 lg:w-5/6">
      <x-navbar-top-kepala-toko></x-navbar-top-kepala-toko>

      <div class="p-4 lg:p-8">
        <div class="bg-white p-6 rounded-xl shadow-lg">
          <div class="flex border-b mb-4">
            <a href="{{ route('kepala-toko.inventory.index') }}" 
               class="px-4 py-2 font-semibold {{ request()->routeIs('kepala-toko.inventory.index') ? 'border-b-2 border-[#005281] text-[#005281]' : 'text-gray-500 hover:text-[#005281] hover:border-b-2 hover:border-gray-300' }}">
              Overview
            </a>
            <a href="{{ route('kepala-toko.inventory.stock-ins.index') }}" 
               class="px-4 py-2 font-semibold {{ request()->routeIs('kepala-toko.inventory.stock-ins.*') ? 'border-b-2 border-[#005281] text-[#005281]' : 'text-gray-500 hover:text-[#005281] hover:border-b-2 hover:border-gray-300' }}">
              Stok Masuk
            </a>
            <a href="{{ route('kepala-toko.inventory.stock-opnames.index') }}" 
               class="px-4 py-2 font-semibold {{ request()->routeIs('kepala-toko.inventory.stock-opnames.*') ? 'border-b-2 border-[#005281] text-[#005281]' : 'text-gray-500 hover:text-[#005281] hover:border-b-2 hover:border-gray-300' }}">
              Stock Opname
            </a>
            <a href="{{ route('kepala-toko.inventory.stock-movements.index') }}" 
               class="px-4 py-2 font-semibold {{ request()->routeIs('kepala-toko.inventory.stock-movements.*') ? 'border-b-2 border-[#005281] text-[#005281]' : 'text-gray-500 hover:text-[#005281] hover:border-b-2 hover:border-gray-300' }}">
              Pergerakan Stok
            </a>
          </div>

          <nav class="mb-4 flex bg-gray-100 p-3 rounded-lg">
            <ol class="inline-flex items-center space-x-1 md:space-x-3 text-sm">
              <li>
                <a href="{{ route('kepala-toko.dashboard') }}" class="text-gray-700 hover:text-[#005281] flex items-center">
                  <i class="bi bi-house-door mr-1"></i> Dashboard
                </a>
              </li>
              <li>
                <span class="text-gray-400 mx-1">/</span>
                <a href="{{ route('kepala-toko.inventory.index') }}" class="text-gray-700 hover:text-[#005281]">Inventory</a>
              </li>
              <li>
                <span class="text-gray-400 mx-1">/</span>
                <span class="text-gray-900 font-semibold">Stok Masuk</span>
              </li>
            </ol>
          </nav>

          <form method="GET" class="flex items-center space-x-2 mb-4">
            <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari No Stok Masuk" class="border rounded p-2 text-gray-900 flex-1" />
            <button type="submit" class="bg-[#005281] text-white px-3 py-2 rounded hover:bg-[#00446a]">Filter</button>
            <a href="{{ route('kepala-toko.inventory.stock-ins.index') }}" class="bg-gray-300 px-3 py-2 rounded text-gray-700 hover:bg-gray-400">Reset</a>
          </form>

          <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
              <thead>
                <tr class="border-b text-gray-600">
                  <th class="px-3 py-2">No. Stok Masuk</th>
                  <th class="px-3 py-2">Tanggal</th>
                  <th class="px-3 py-2">Supplier</th>
                  <th class="px-3 py-2">No. Pembelian</th>
                  <th class="px-3 py-2">Status</th>
                  <th class="px-3 py-2">Diterima Oleh</th>
                  <th class="px-3 py-2">Jumlah Item</th>
                </tr>
              </thead>
              <tbody>
                @forelse($stockIns as $s)
                <tr class="{{ $s->purchaseOrder ? 'clickable-row' : 'non-clickable-row' }} border-b {{ $s->purchaseOrder ? 'hover:bg-gray-50' : '' }}"
                    @if($s->purchaseOrder) onclick="window.location='{{ route('kepala-toko.purchases.show', $s->purchaseOrder->id) }}'" @endif>
                    <td class="px-3 py-2">{{ $s->stock_in_number }}</td>
                    <td class="px-3 py-2">{{ \Carbon\Carbon::parse($s->received_date)->format('d M Y') }}</td>
                    <td class="px-3 py-2">{{ $s->supplier?->name ?? '-' }}</td>
                    <td class="px-3 py-2">{{ $s->purchaseOrder?->po_number ?? '-' }}</td>
                    <td class="px-3 py-2 capitalize">{{ $s->status }}</td>
                    <td class="px-3 py-2">{{ $s->receiver?->name ?? '-' }}</td>
                    <td class="px-3 py-2">{{ $s->items->sum('qty') }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-3 py-4 text-center text-gray-500">Tidak ada data.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="mt-3">{{ $stockIns->withQueryString()->links() }}</div>
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