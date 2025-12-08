<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Stock Opname - Custom Pare</title>
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

    <x-navbar-owner></x-navbar-owner>

    <div class="flex-1 lg:w-5/6">
      <x-navbar-top-owner></x-navbar-top-owner>

      <div class="p-4 lg:p-8">
        <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-700">Edit Stock Opname</h2>
            <a href="{{ route('owner.inventory.stock-opnames.show', $stockOpname->id) }}" class="text-blue-600 hover:underline">
              Kembali ke Detail
            </a>
          </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-lg">
          <form action="{{ route('owner.inventory.stock-opnames.update', $stockOpname->id) }}" method="POST" x-data="opnameForm()">
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
              <a href="{{ route('owner.inventory.stock-opnames.show', $stockOpname->id) }}"
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
        </div>
      </div>
    </div>
  </div>

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
</body>
</html>