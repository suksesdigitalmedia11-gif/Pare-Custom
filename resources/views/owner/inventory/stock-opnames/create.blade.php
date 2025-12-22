<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Stock Opname - Custom Pare</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
  <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    body {
      font-family: 'Raleway', sans-serif;
    }

    /* Loader sederhana */
    .loader {
      border: 2px solid #f3f3f3;
      border-top: 2px solid #3498db;
      border-radius: 50%;
      width: 16px;
      height: 16px;
      animation: spin 1s linear infinite;
      display: inline-block;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }
  </style>
</head>

<body class="bg-gray-100">
  <div class="flex">
    <x-navbar-owner></x-navbar-owner>
    <div class="flex-1">
      <x-navbar-top-owner></x-navbar-top-owner>
      <div class="p-4 lg:p-8">

        <!-- Header -->
        <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-semibold text-gray-700">Tambah Stock Opname</h2>
              <p class="text-sm text-gray-500 mt-1">Input stok real (fisik) untuk disesuaikan dengan sistem.</p>
            </div>
          </div>

          @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mt-4" role="alert">
              <p class="font-bold">Error</p>
              <p>{{ session('error') }}</p>
            </div>
          @endif

          @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mt-4">
              <ul class="list-disc leading-5 pl-5 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif
        </div>

        <!-- Form Area -->
        <div class="bg-white p-6 rounded-xl shadow-lg" x-data="opnameForm()">
          <form action="{{ route('owner.inventory.stock-opnames.store') }}" method="POST" @submit="submitForm">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <!-- No Dokumen -->
              <div>
                <label class="block text-gray-700 font-medium mb-2">No. Dokumen</label>
                <div class="w-full border rounded-lg p-2.5 bg-gray-100 text-gray-500 italic">
                  <i class="bi bi-shield-lock mr-2"></i>Akan digenerate otomatis saat disimpan
                </div>
                <p class="text-xs text-gray-400 mt-1">Mencegah duplikasi nomor jika input bersamaan.</p>
              </div>

              <!-- Tanggal -->
              <div>
                <label class="block text-gray-700 font-medium mb-2">Tanggal Opname</label>
                <input type="date" name="date"
                  class="w-full border rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  x-model="formData.date">
                <p class="text-xs text-gray-500 mt-1">Default hari ini, bisa diganti jika input data susulan.</p>
              </div>
            </div>

            <!-- Product List Header -->
            <div class="flex justify-between items-center border-b pb-2 mb-4">
              <h3 class="text-lg font-semibold text-gray-800">Daftar Produk</h3>
              <button type="button" @click="addItem()"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center shadow-md">
                <i class="bi bi-plus-lg mr-2"></i> Tambah Baris
              </button>
            </div>

            <!-- Product Rows -->
            <div class="space-y-4 mb-6">
              <template x-for="(item, index) in items" :key="index">
                <div class="border rounded-xl p-4 bg-gray-50 hover:bg-gray-100 transition relative">
                  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">

                    <!-- Kolom Pencarian Produk (SEARCH) -->
                    <div class="lg:col-span-6 relative" x-data="{ open: false, loading: false }">
                      <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Cari Produk (Nama /
                        SKU)</label>

                      <!-- Input Search -->
                      <div class="relative">
                        <input type="text"
                          class="w-full border rounded-lg p-2 pl-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Ketik nama produk..." x-model="item.product_name"
                          @input.debounce.300ms="searchProduct($event.target.value, index, $data)" @focus="open = true"
                          @click.outside="open = false" autocomplete="off">

                        <!-- Icon Loading / Search -->
                        <div class="absolute right-3 top-2.5 text-gray-400">
                          <span x-show="loading" class="loader"></span>
                          <i x-show="!loading" class="bi bi-search"></i>
                        </div>
                      </div>

                      <!-- Hidden Inputs for Form Submission -->
                      <input type="hidden" :name="`items[${index}][product_id]`" x-model="item.product_id">
                      <input type="hidden" :name="`items[${index}][product_name]`" x-model="item.product_name">
                      <!-- Back up name -->
                      <input type="hidden" :name="`items[${index}][sku]`" x-model="item.sku">

                      <!-- Dropdown Hasil Search -->
                      <div x-show="open && item.searchResults && item.searchResults.length > 0"
                        class="absolute z-10 w-full bg-white border rounded-lg shadow-xl mt-1 max-h-60 overflow-y-auto"
                        x-transition>
                        <ul>
                          <template x-for="result in item.searchResults" :key="result.id">
                            <li @click="selectProduct(index, result); open = false"
                              class="p-3 hover:bg-blue-50 cursor-pointer border-b last:border-b-0">
                              <div class="font-medium text-gray-800" x-text="result.name"></div>
                              <div class="text-xs text-gray-500 flex justify-between mt-1">
                                <span x-text="`SKU: ${result.sku || '-'}`"></span>
                                <span x-text="`Sistem: ${result.stock_qty}`" class="font-bold text-blue-600"></span>
                              </div>
                            </li>
                          </template>
                        </ul>
                      </div>

                      <!-- State Message -->
                      <div
                        x-show="open && !loading && item.product_name.length >= 2 && (!item.searchResults || item.searchResults.length === 0)"
                        class="absolute z-10 w-full bg-white border rounded-lg shadow p-3 mt-1 text-sm text-gray-500 text-center">
                        Tidak ditemukan.
                      </div>
                    </div>

                    <!-- Kolom Stok Sistem -->
                    <div class="lg:col-span-2">
                      <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Di Sistem</label>
                      <input type="text" :name="`items[${index}][system_qty]`"
                        class="w-full bg-gray-200 border border-gray-300 rounded-lg p-2 text-center font-mono text-gray-600 cursor-not-allowed"
                        x-model="item.system_qty" readonly>
                    </div>

                    <!-- Kolom Stok Fisik (INPUT UTAMA) -->
                    <div class="lg:col-span-3">
                      <label class="block text-xs font-semibold text-gray-800 uppercase mb-1">Stok Fisik (Real)</label>
                      <input type="number" :name="`items[${index}][actual_qty]`"
                        class="w-full border-2 border-blue-200 rounded-lg p-2 text-center font-bold text-lg focus:border-blue-500 focus:ring-0"
                        x-model="item.actual_qty" required min="0">

                      <!-- Selisih Indicator -->
                      <div class="text-xs text-center mt-1 font-medium bg-gray-100 rounded px-2 py-1" :class="{
                                                    'text-green-600': (item.actual_qty - item.system_qty) == 0,
                                                    'text-red-500': (item.actual_qty - item.system_qty) != 0
                                                }">
                        Selisih: <span x-text="item.actual_qty - item.system_qty"></span>
                      </div>
                    </div>

                    <!-- Tombol Hapus -->
                    <div class="lg:col-span-1 flex items-end justify-center h-full pb-2">
                      <button type="button" @click="removeItem(index)"
                        class="text-red-400 hover:text-red-600 transition p-2 rounded-full hover:bg-red-50"
                        title="Hapus Baris" x-show="items.length > 1">
                        <i class="bi bi-trash text-xl"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </template>
            </div>

            <!-- Actions -->
            <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-2">Catatan (Optional)</label>
              <textarea name="notes"
                class="w-full border rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-500" rows="2"
                placeholder="Contoh: Stok opname rutin bulan Desember"></textarea>
            </div>

            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                <button type="button" @click="resetDraft()" 
                    class="px-6 py-3 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 font-medium transition mr-auto">
                    <i class="bi bi-trash mr-2"></i> Reset Form
                </button>

              <a href="{{ route('owner.inventory.stock-opnames.index') }}"
                class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition">
                <i class="bi bi-x-lg mr-2"></i> Batal
              </a>
              <button type="submit"
                class="px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 font-medium shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5"
                :disabled="isSubmitting">
                <span x-show="!isSubmitting"><i class="bi bi-save mr-2"></i> Simpan Data</span>
                <span x-show="isSubmitting"><i class="bi bi-hourglass-split mr-2"></i> Menyimpan...</span>
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
        isSubmitting: false,
        formData: {
          date: @json(old('date', date('Y-m-d')))
        },
        items: @json(old('items')) || [],

        init() {
          // Cek apakah ada data validasi server (error form)
          const hasOldData = @json(!!old('items'));

          if (!hasOldData) {
            // Jika tidak ada data server, cek Draft di LocalStorage
            const savedItems = localStorage.getItem('opname_draft_items');
            const savedDate = localStorage.getItem('opname_draft_date');

            if (savedItems) {
              try {
                const parsedRequest = JSON.parse(savedItems);
                if (Array.isArray(parsedRequest) && parsedRequest.length > 0) {
                  this.items = parsedRequest;
                }
              } catch (e) {
                console.error('Error parsing draft items', e);
              }
            }

            if (savedDate) {
              this.formData.date = savedDate;
            }
          }

          // Pastikan minimal ada 1 baris
          if (this.items.length === 0) {
            this.addItem();
          }
          // Bersihkan searchResults dari old data agar tidak membebani memori
          this.items.forEach(item => {
            item.searchResults = [];
          });

          // Setup Auto-Save Watchers
          this.$watch('items', (val) => {
            localStorage.setItem('opname_draft_items', JSON.stringify(val));
          });
          this.$watch('formData.date', (val) => {
            localStorage.setItem('opname_draft_date', val);
          });
        },

        resetDraft() {
          if (confirm('Yakin ingin mereset formulir? Draft tersimpan akan dihapus.')) {
            localStorage.removeItem('opname_draft_items');
            localStorage.removeItem('opname_draft_date');
            window.location.reload();
          }
        },

        addItem() {
          this.items.push({
            product_id: '',
            product_name: '',
            sku: '',
            system_qty: 0,
            actual_qty: 0,
            searchResults: []
          });
        },

        removeItem(index) {
          if (this.items.length > 1) {
            this.items.splice(index, 1);
          }
        },

        async searchProduct(query, index, scope) {
          if (query.length < 2) {
            this.items[index].searchResults = [];
            return;
          }

          scope.loading = true;
          try {
            const response = await fetch(`{{ route('owner.inventory.stock-opnames.search-products') }}?q=${query}`);
            const data = await response.json();
            this.items[index].searchResults = data;
          } catch (error) {
            console.error('Error searching products:', error);
          } finally {
            scope.loading = false;
          }
        },

        selectProduct(index, product) {
          this.items[index].product_id = product.id;
          this.items[index].product_name = product.name;
          this.items[index].sku = product.sku;
          this.items[index].system_qty = product.stock_qty;
          this.items[index].actual_qty = product.stock_qty; // Default actual same as system for faster input
          this.items[index].searchResults = []; // Clear results
        },

        submitForm(e) {
          this.isSubmitting = true;
          // Validasi client-side sederhana
          const invalid = this.items.some(item => !item.product_id);
          if (invalid) {
            alert('Mohon pilih produk yang valid untuk semua baris.');
            e.preventDefault();
            this.isSubmitting = false;
          }
        }
      }
    }
  </script>
</body>

</html>