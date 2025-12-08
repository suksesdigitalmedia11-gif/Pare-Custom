<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Stock Opname - Custom Pare</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    body { font-family: 'Raleway', sans-serif; }
    .nav-text { position: relative; display: inline-block; }
    .nav-text::after { content: ''; position: absolute; width: 0; height: 2px; bottom: -2px; left: 0; background-color: #e17f12; transition: width .2s; }
    .hover-link:hover .nav-text::after { width: 100%; }
  </style>
</head>
<body class="bg-gray-100">
  <div class="flex">

    <x-navbar-finance></x-navbar-finance>

    <div class="flex-1 lg:w-5/6">
      <x-navbar-top-finance></x-navbar-top-finance>

      <div class="p-4 lg:p-8">
        <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-700">Detail Stock Opname</h2>
            <div class="flex space-x-2">
              @if($stockOpname->status === 'approved')
                <a href="{{ route('finance.inventory.stock-opnames.pdf', $stockOpname->id) }}"
                   class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                  <i class="bi bi-file-earmark-pdf mr-2"></i> Export PDF
                </a>
              @endif
              <a href="{{ route('finance.inventory.stock-opnames.index') }}"
                           class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded shadow">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
            </div>
          </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-lg">
          @if($stockOpname->status === 'draft')
            <!-- Form Edit untuk Draft -->
            <form action="{{ route('finance.inventory.stock-opnames.update', $stockOpname->id) }}" method="POST" x-data="opnameForm()">
              @csrf
              @method('PUT')

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-gray-700 mb-2">No. Dokumen</label>
                  <input type="text" name="document_number" class="w-full border rounded p-2 bg-gray-100"
                    value="{{ $stockOpname->document_number }}" readonly>
                </div>
                <div>
                  <label class="block text-gray-700 mb-2">Tanggal</label>
                  <input type="text" class="w-full border rounded p-2 bg-gray-100" value="{{ date('Y-m-d') }}" disabled>
                  <input type="hidden" name="date" value="{{ date('Y-m-d') }}">
                </div>
              </div>

              <h3 class="text-lg font-semibold mb-4">Daftar Produk</h3>

              <div id="product-items">
                <template x-for="(item, index) in items" :key="index">
                  <div class="border rounded-lg p-4 mb-4 bg-gray-50">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div>
                        <label class="block text-gray-700 mb-2">Produk</label>
                        <select :name="`items[${index}][product_id]`" class="w-full border rounded p-2 product-select"
                          x-model="item.product_id" @change="getQtySystem(index)" required>
                          <option value="">-- Pilih Produk --</option>
                          @foreach($products as $product)
                            <option value="{{ $product->id }}" data-stock="{{ $product->stock_qty }}"
                              :selected="item.product_id == {{ $product->id }}">
                              {{ $product->name }} (Stok: {{ $product->stock_qty }})
                            </option>
                          @endforeach
                        </select>
                        <input type="hidden" :name="`items[${index}][product_name]`" :value="getProductName(item.product_id)">
                        <input type="hidden" :name="`items[${index}][sku]`" :value="getProductSku(item.product_id)">
                      </div>
                      <div>
                        <label class="block text-gray-700 mb-2">Qty Sistem</label>
                        <input type="number" :name="`items[${index}][system_qty]`"
                          class="w-full border rounded p-2 bg-gray-100 qty-system" x-model="item.system_qty" readonly>
                      </div>
                      <div>
                        <label class="block text-gray-700 mb-2">Qty Aktual</label>
                        <input type="number" :name="`items[${index}][actual_qty]`"
                          class="w-full border rounded p-2" x-model="item.actual_qty" required>
                      </div>
                    </div>
                    <div class="text-right mt-2" x-show="items.length > 1">
                      <button type="button" class="text-red-500 hover:underline" @click="removeItem(index)">
                        <i class="bi bi-trash"></i> Hapus
                      </button>
                    </div>
                  </div>
                </template>
              </div>

              <button type="button" @click="addItem"
                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 mb-4">
                <i class="bi bi-plus-circle"></i> Tambah Produk
              </button>

              <div class="mb-4">
                <label class="block text-gray-700 mb-2">Catatan</label>
                <textarea name="notes" class="w-full border rounded p-2" rows="3">{{ $stockOpname->notes ?? '' }}</textarea>
              </div>

              <div class="flex justify-end space-x-4 pt-6 border-t">
                <a href="{{ route('finance.inventory.stock-opnames.index') }}"
                  class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                  <i class="bi bi-arrow-left mr-2"></i>
                  Kembali
                </a>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                  <i class="bi bi-check-lg mr-2"></i>
                  Simpan
                </button>
              </div>
            </form>

            <script>
              function opnameForm() {
                return {
                  products: @json($products),
                  items: @json($stockOpname->items->map(function($item) {
                    return [
                      'product_id' => $item->product_id,
                      'system_qty' => $item->system_qty,
                      'actual_qty' => $item->actual_qty
                    ];
                  })),
                  addItem() {
                    this.items.push({ 
                      product_id: '', 
                      system_qty: 0,
                      actual_qty: 0 
                    });
                  },
                  removeItem(index) {
                    this.items.splice(index, 1);
                  },
                  getProductName(productId) {
                    if (!productId) return '';
                    const product = this.products.find(p => p.id == productId);
                    return product ? product.name : '';
                  },
                  getProductSku(productId) {
                    if (!productId) return '';
                    const product = this.products.find(p => p.id == productId);
                    return product ? (product.sku || '') : '';
                  },
                  async getQtySystem(index) {
                    const productId = this.items[index].product_id;
                    if (!productId) return;
                    
                    const selectedOption = document.querySelector(`select[name="items[${index}][product_id]"] option:checked`);
                    if (selectedOption) {
                      const qtySystem = selectedOption.getAttribute('data-stock') || 0;
                      this.items[index].system_qty = qtySystem;
                    }
                  }
                }
              }
            </script>
          @else
            <!-- Tabel untuk Approved -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
              <div>
                <h3 class="font-medium text-gray-700">Informasi Dokumen</h3>
                <div class="mt-2 space-y-2">
                  <p><span class="font-medium">No. Dokumen:</span> {{ $stockOpname->document_number }}</p>
                  <p><span class="font-medium">Tanggal:</span> 
                    {{ \Carbon\Carbon::parse($stockOpname->date)->format('d M Y') }}</p>
                  <p><span class="font-medium">Status:</span> 
                    <span class="px-2 py-1 rounded-full text-xs 
                      {{ $stockOpname->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                      {{ ucfirst($stockOpname->status) }}
                    </span>
                  </p>
                </div>
              </div>
              <div>
                <h3 class="font-medium text-gray-700">Informasi User</h3>
                <div class="mt-2 space-y-2">
                  <p>
                    <span class="font-medium">Dibuat oleh:</span> 
                    {{ $stockOpname->creator->name ?? $stockOpname->creator->email ?? '-' }}<br>
                    <small class="text-gray-500 text-sm">
                      {{ \Carbon\Carbon::parse($stockOpname->created_at)->format('d M Y H:i') }}
                    </small>
                  </p>
                  @if($stockOpname->status === 'approved')
                    <p>
                      <span class="font-medium">Disetujui oleh:</span> 
                      {{ $stockOpname->approver->name ?? $stockOpname->approver->email ?? '-' }}<br>
                      <small class="text-gray-500 text-sm">
                        {{ \Carbon\Carbon::parse($stockOpname->approved_at)->format('d M Y H:i') }}
                      </small>
                    </p>
                  @endif
                </div>
              </div>
            </div>

            <h3 class="font-medium text-gray-700 mb-4">Daftar Produk</h3>
            <table class="min-w-full border rounded-lg">
              <thead class="bg-gray-100">
                <tr>
                  <th class="px-4 py-2">Produk</th>
                  <th class="px-4 py-2">Qty Sistem</th>
                  <th class="px-4 py-2">Qty Aktual</th>
                  <th class="px-4 py-2">Selisih</th>
                </tr>
              </thead>
              <tbody>
                @foreach($stockOpname->items as $item)
                <tr class="border-b">
                  <td class="px-4 py-2">{{ $item->product->name }}</td>
                  <td class="px-4 py-2 text-center">{{ $item->system_qty }}</td>
                  <td class="px-4 py-2 text-center">{{ $item->actual_qty }}</td>
                  <td class="px-4 py-2 text-center {{ $item->difference < 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ $item->difference }}
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>

            @if($stockOpname->notes)
            <div class="mt-6">
              <h3 class="font-medium text-gray-700">Catatan</h3>
              <p class="mt-2 p-3 bg-gray-50 rounded">{{ $stockOpname->notes }}</p>
            </div>
            @endif
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