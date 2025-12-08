<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pergerakan Stok - Inventory - Custom Pare</title>
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
          {{-- Tabs (sama seperti Overview) --}}
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

          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Stock Movements</h3>
            <form method="GET" class="flex space-x-2">
              <input type="date" name="from" value="{{ $from ?? '' }}" class="border rounded px-2 py-1 flex-1">
              <input type="date" name="to" value="{{ $to ?? '' }}" class="border rounded px-2 py-1 flex-1">
              <button type="submit" class="bg-[#005281] text-white px-4 py-1 rounded hover:bg-[#00446a]">
                <i class="bi bi-funnel"></i> Filter
              </button>
              <a href="{{ route('kepala-toko.inventory.stock-movements.index') }}" 
                 class="bg-gray-300 text-gray-700 px-4 py-1 rounded hover:bg-gray-400 flex items-center">
                <i class="bi bi-arrow-clockwise"></i> Reset
              </a>
            </form>
          </div>

          @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
              {{ session('success') }}
            </div>
          @endif

          <div class="overflow-x-auto">
            <table class="min-w-full border rounded-lg text-sm">
              <thead class="bg-gray-100">
                <tr>
                  <th class="px-4 py-2">Tanggal</th>
                  <th class="px-4 py-2">Produk</th>
                  <th class="px-4 py-2">Stok Awal</th>
                  <th class="px-4 py-2 text-green-600">Masuk</th>
                  <th class="px-4 py-2 text-red-600">Keluar</th>
                  <th class="px-4 py-2">Stok Akhir</th>
                  <th class="px-4 py-2">Transaksi</th>
                  <th class="px-4 py-2">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($stockMovements as $move)
                <tr class="border-b hover:bg-gray-50">
                  <td class="px-4 py-2">{{ \Carbon\Carbon::parse($move->movement_date)->format('d M Y') }}</td>
                  <td class="px-4 py-2">{{ $move->product->name ?? '-' }}</td>
                  <td class="px-4 py-2">{{ $move->initial_qty ?? 0 }}</td>
                  <td class="px-4 py-2 text-green-600 font-semibold">+{{ $move->total_in ?? 0 }}</td>
                  <td class="px-4 py-2 text-red-600 font-semibold">-{{ $move->total_out ?? 0 }}</td>
                  <td class="px-4 py-2 font-semibold">{{ $move->final_qty ?? 0 }}</td>
                  <td class="px-4 py-2 text-center">{{ $move->transaction_count ?? 0 }}</td>
                  <td class="px-4 py-2">
                    <button onclick="showMovementDetails('{{ $move->product_id }}', '{{ $move->movement_date }}')"
                            class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">
                      <i class="bi bi-eye"></i> Detail
                    </button>
                  </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Tidak ada data pergerakan stok.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="mt-4">
            {{ $stockMovements->appends(request()->query())->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="movementModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <div class="flex justify-between items-center pb-3 border-b">
          <h3 class="text-xl font-semibold" id="modalTitle">Detail Pergerakan Stok</h3>
          <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <div class="mt-4">
          <div id="modalContent" class="max-h-96 overflow-y-auto">
            <!-- Content will be loaded via AJAX -->
          </div>
        </div>
        
        <div class="mt-4 flex justify-end">
          <button onclick="closeModal()" 
                  class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
            Tutup
          </button>
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
    function showMovementDetails(productId, date) {
        document.getElementById('modalContent').innerHTML = '<div class="text-center py-8">Loading...</div>';
        document.getElementById('movementModal').classList.remove('hidden');
        fetch(`/kepala-toko/inventory/stock-movements/${productId}/${date}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                let html = `
                    <h4 class="font-semibold mb-4">${data.product} - ${new Date(data.date).toLocaleDateString('id-ID')}</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left">Waktu</th>
                                    <th class="px-4 py-2 text-left">Tipe</th>
                                    <th class="px-4 py-2 text-left">Ref</th>
                                    <th class="px-4 py-2 text-left">Awal</th>
                                    <th class="px-4 py-2 text-left">Masuk</th>
                                    <th class="px-4 py-2 text-left">Keluar</th>
                                    <th class="px-4 py-2 text-left">Akhir</th>
                                    <th class="px-4 py-2 text-left">User</th>
                                    <th class="px-4 py-2 text-left">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                data.movements.forEach(movement => {
                    let typeBadge = movement.type === 'INCOMING' ? 'bg-green-100 text-green-800' :
                                  movement.type === 'POS_SALE' ? 'bg-red-100 text-red-800' :
                                  movement.type === 'POS_CANCEL' ? 'bg-yellow-100 text-yellow-800' :
                                  movement.type === 'SALE_RETURN' ? 'bg-blue-100 text-blue-800' :
                                  movement.type === 'OPNAME' ? 'bg-purple-100 text-purple-800' :
                                  movement.type === 'PURCHASE_RETURN' ? 'bg-orange-100 text-orange-800' :
                                  'bg-gray-100 text-gray-800';
                    html += `
                        <tr class="border-b">
                            <td class="px-4 py-2">${new Date(movement.moved_at).toLocaleTimeString('id-ID')}</td>
                            <td class="px-4 py-2"><span class="px-2 py-1 rounded text-xs ${typeBadge}">${movement.type}</span></td>
                            <td class="px-4 py-2">${movement.ref_code || '-'}</td>
                            <td class="px-4 py-2">${movement.initial_qty}</td>
                            <td class="px-4 py-2 text-green-600">${movement.qty_in > 0 ? '+' + movement.qty_in : '-'}</td>
                            <td class="px-4 py-2 text-red-600">${movement.qty_out > 0 ? '-' + movement.qty_out : '-'}</td>
                            <td class="px-4 py-2 font-semibold">${movement.final_qty}</td>
                            <td class="px-4 py-2">${movement.user?.username || '-'}</td>
                            <td class="px-4 py-2">${movement.notes || '-'}</td>
                        </tr>
                    `;
                });
                html += `</tbody></table></div>`;
                document.getElementById('modalContent').innerHTML = html;
                document.getElementById('modalTitle').textContent = `Detail Pergerakan: ${data.product}`;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('modalContent').innerHTML = '<div class="text-center py-8 text-red-600">Error loading data</div>';
            });
    }
    function closeModal() {
        document.getElementById('movementModal').classList.add('hidden');
    }
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('movementModal').addEventListener('click', function(e) {
            if (e.target.id === 'movementModal') closeModal();
        });
        const today = new Date().toISOString().split('T')[0];
        const fromInput = document.querySelector('input[name="from"]');
        const toInput = document.querySelector('input[name="to"]');
        if (fromInput && !fromInput.value) fromInput.value = today;
        if (toInput && !toInput.value) toInput.value = today;
    });
  </script>
</body>
</html>