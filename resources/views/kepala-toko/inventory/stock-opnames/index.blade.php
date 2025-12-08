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
                <span class="text-gray-900 font-semibold">Stock Opname</span>
              </li>
            </ol>
          </nav>

          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Stock Opname</h3>
            <div class="flex space-x-2">
              <a href="{{ route('kepala-toko.inventory.stock-opnames.template') }}"
                 class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                <i class="bi bi-download mr-2"></i> Download Template
              </a>
              <button onclick="document.getElementById('importModal').classList.toggle('hidden')"
                      class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="bi bi-upload mr-2"></i> Import XLSX
              </button>
              <a href="{{ route('kepala-toko.inventory.stock-opnames.create') }}"
                 class="bg-[#005281] text-white px-4 py-2 rounded-lg hover:bg-[#00446a]">
                <i class="bi bi-plus-circle"></i> Tambah Opname
              </a>
            </div>
          </div>

          <!-- Modal Import -->
          <div id="importModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
              <h3 class="text-lg font-semibold mb-4">Import Stock Opname</h3>
              <form action="{{ route('kepala-toko.inventory.stock-opnames.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                  <label class="block text-gray-700 mb-2">Pilih File XLSX</label>
                  <input type="file" name="file" accept=".xlsx" class="w-full border rounded p-2" required>
                </div>
                <div class="flex justify-end space-x-2">
                  <button type="button" onclick="document.getElementById('importModal').classList.toggle('hidden')"
                          class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Batal
                  </button>
                  <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Import
                  </button>
                </div>
              </form>
            </div>
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
                  <tr class="border-b clickable-row hover:bg-gray-50" onclick="window.location='{{ route('kepala-toko.inventory.stock-opnames.show', $opname->id) }}'">
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
                          <a href="{{ route('kepala-toko.inventory.stock-opnames.edit', $opname->id) }}" 
                             class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">
                            <i class="bi bi-pencil"></i>
                          </a>
                          <form action="{{ route('kepala-toko.inventory.stock-opnames.approve', $opname->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">
                              <i class="bi bi-check-circle"></i>
                            </button>
                          </form>
                          <form action="{{ route('kepala-toko.inventory.stock-opnames.destroy', $opname->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600"
                              onclick="return confirm('Apakah Anda yakin ingin menghapus stock opname ini?')">
                              <i class="bi bi-trash"></i>
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