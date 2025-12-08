<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stock Opname - Inventory - Custom Pare</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body { 
      font-family: 'Raleway', sans-serif; 
    }
    .nav-text { position: relative; display: inline-block; }
    .nav-text::after { content: ''; position: absolute; width: 0; height: 2px; bottom: -2px; left: 0; background-color: #e17f12; transition: width .2s; }
    .hover-link:hover .nav-text::after { width: 100%; }
    .clickable-row {
      cursor: pointer;
      transition: background-color 0.2s ease;
    }
    .clickable-row:hover {
      background-color: #f9fafb;
    }
    .action-buttons {
      pointer-events: auto;
    }
    .action-buttons form {
      display: inline-block;
    }
  </style>
</head>
<body class="bg-gray-100">
  <div class="flex">

    <x-navbar-finance></x-navbar-finance>

    <div class="flex-1 lg:w-5/6">
      <x-navbar-top-finance></x-navbar-top-finance>

      <div class="p-4 lg:p-8">
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

          <nav class="mb-4 flex bg-gray-100 p-3 rounded-lg">
            <ol class="inline-flex items-center space-x-1 md:space-x-3 text-sm">
              <li>
                <a href="{{ route('finance.dashboard') }}" class="text-gray-700 hover:text-[#005281] flex items-center">
                  <i class="bi bi-house-door mr-1"></i> Dashboard
                </a>
              </li>
              <li>
                <span class="text-gray-400 mx-1">/</span>
                <a href="{{ route('finance.inventory.index') }}" class="text-gray-700 hover:text-[#005281]">Inventory</a>
              </li>
              <li>
                <span class="text-gray-400 mx-1">/</span>
                <span class="text-gray-900 font-semibold">Stock Opname</span>
              </li>
            </ol>
          </nav>

          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Stock Opname</h3>
          </div>

          @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
              {{ session('success') }}
            </div>
          @endif
          @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
              {{ session('error') }}
            </div>
          @endif

          <div class="overflow-x-auto">
            <table class="min-w-full border rounded-lg">
              <thead class="bg-gray-100">
                <tr>
                  <th class="px-4 py-2">No Dokumen</th>
                  <th class="px-4 py-2">Tanggal</th>
                  <th class="px-4 py-2">Catatan</th>
                  <th class="px-4 py-2">Status</th>
                  <th class="px-4 py-2">Dibuat oleh</th>
                  <th class="px-4 py-2">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($stockOpnames as $opname)
                  <tr class="border-b clickable-row hover:bg-gray-50" onclick="window.location='{{ route('finance.inventory.stock-opnames.show', $opname->id) }}'">
                    <td class="px-4 py-2">{{ $opname->document_number }}</td>
                    <td class="px-4 py-2">
                      {{ \Carbon\Carbon::parse($opname->date)->format('d M Y') }}
                    </td>
                    <td class="px-4 py-2">{{ $opname->notes ?? '-' }}</td>
                    <td class="px-4 py-2">
                      <span class="px-2 py-1 rounded-full text-xs 
                        {{ $opname->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ ucfirst($opname->status) }}
                      </span>
                    </td>
                    <td class="px-4 py-2">
                      {{ $opname->creator->name ?? $opname->creator->email ?? '-' }}<br>
                      <small class="text-gray-500 text-xs">
                        {{ \Carbon\Carbon::parse($opname->created_at)->format('d M Y H:i') }}
                      </small>
                    </td>
                    <td class="px-4 py-2 action-buttons" onclick="event.stopPropagation()">
                      <div class="flex space-x-2">
                        @if($opname->status === 'draft')
                          <form action="{{ route('finance.inventory.stock-opnames.approve', $opname->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">
                              <i class="bi bi-check-circle"></i>
                            </button>
                          </form>
                        @endif
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Tidak ada data stock opname.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="mt-4">
            {{ $stockOpnames->links() }}
          </div>
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
    document.addEventListener('DOMContentLoaded', function() {
      const rows = document.querySelectorAll('.clickable-row');
      rows.forEach(row => {
        row.addEventListener('mouseenter', function() {
          this.style.backgroundColor = '#f9fafb';
        });
        row.addEventListener('mouseleave', function() {
          this.style.backgroundColor = '';
        });
      });
    });
  </script>
</body>
</html>