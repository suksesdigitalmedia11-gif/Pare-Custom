<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Penyesuaian Stok - Custom Pare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body {
            font-family: 'Raleway', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex">
        <x-navbar-editor></x-navbar-editor>
        <div class="flex-1">
            <x-navbar-top-editor></x-navbar-top-editor>
            <div class="p-4 lg:p-8">
                <!-- Content Start -->
                <div class="px-6 py-8" x-data="stockAdjustmentForm()" x-init="initForm()">

                    <!-- Header -->
                    <div class="mb-8">
                        <h1 class="text-2xl font-bold text-gray-900">Buat Penyesuaian Stok Baru</h1>
                        <p class="text-sm text-gray-500 mt-1">Kelola stok masuk/keluar manual (Rusak, Expired, Bonus,
                            dll)</p>
                    </div>

                    <!-- Alert Box -->
                    <div
                        class="mb-6 p-4 rounded-xl border border-blue-200 bg-blue-50 text-blue-800 flex items-start gap-3">
                        <i class="bi bi-info-circle-fill mt-0.5"></i>
                        <div>
                            <h4 class="font-bold text-sm">Informasi Penting</h4>
                            <ul class="list-disc list-inside text-sm mt-1 space-y-1">
                                <li>Gunakan <strong>STOK MASUK</strong> untuk barang bonus, retur customer tanpa nota,
                                    atau koreksi positif.</li>
                                <li>Gunakan <strong>STOK KELUAR</strong> untuk barang rusak, expired, hilang, pemakaian
                                    sendiri, atau koreksi negatif.</li>
                                <li>Stok akan langsung terupdate setelah disimpan.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Error / Success Alerts -->
                    @if(session('success'))
                        <div
                            class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 flex items-center gap-3">
                            <i class="bi bi-check-circle-fill text-xl"></i>
                            <p class="font-medium">{{ session('success') }}</p>
                        </div>
                    @endif

                    @if(session('error'))
                        <div
                            class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 flex items-center gap-3">
                            <i class="bi bi-exclamation-triangle-fill text-xl"></i>
                            <p class="font-medium">{{ session('error') }}</p>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700">
                            <div class="flex items-center gap-3 mb-2">
                                <i class="bi bi-exclamation-circle-fill text-xl"></i>
                                <h4 class="font-bold">Terdapat Kesalahan</h4>
                            </div>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-9">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('editor.inventory.stock-adjustments.store') }}" method="POST"
                        @submit.prevent="submitForm">
                        @csrf

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                            <!-- Left Panel: General Info -->
                            <div class="lg:col-span-1 space-y-6">
                                <!-- Type Selection -->
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                                    <h3 class="font-bold text-gray-900 mb-4">Jenis Penyesuaian</h3>

                                    <div class="grid grid-cols-2 gap-4">
                                        <label class="cursor-pointer relative">
                                            <input type="radio" name="type" value="in" x-model="type"
                                                class="peer sr-only">
                                            <div
                                                class="p-4 rounded-xl border-2 border-gray-200 peer-checked:border-green-500 peer-checked:bg-green-50 transition-all text-center h-full flex flex-col items-center justify-center hover:bg-gray-50">
                                                <i
                                                    class="bi bi-box-arrow-in-down text-2xl mb-2 text-gray-400 peer-checked:text-green-600"></i>
                                                <span
                                                    class="block font-bold text-gray-600 peer-checked:text-green-700">STOK
                                                    MASUK</span>
                                                <span class="text-xs text-gray-400">(Penambahan)</span>
                                            </div>
                                        </label>

                                        <label class="cursor-pointer relative">
                                            <input type="radio" name="type" value="out" x-model="type"
                                                class="peer sr-only">
                                            <div
                                                class="p-4 rounded-xl border-2 border-gray-200 peer-checked:border-red-500 peer-checked:bg-red-50 transition-all text-center h-full flex flex-col items-center justify-center hover:bg-gray-50">
                                                <i
                                                    class="bi bi-box-arrow-up text-2xl mb-2 text-gray-400 peer-checked:text-red-600"></i>
                                                <span
                                                    class="block font-bold text-gray-600 peer-checked:text-red-700">STOK
                                                    KELUAR</span>
                                                <span class="text-xs text-gray-400">(Pengurangan)</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Reference Info -->
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                                        <input type="date" name="date" x-model="date"
                                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500"
                                            required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Alasan</label>
                                        <select name="reason" x-model="reason"
                                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500"
                                            required>
                                            <option value="">Pilih Alasan...</option>
                                            <!-- Dynamic Options based on Type -->
                                            <template x-if="type === 'in'">
                                                <optgroup label="Alasan Masuk">
                                                    <option value="Bonus Supplier">Bonus Supplier</option>
                                                    <option value="Retur Customer (No Nota)">Retur Customer (Jarang)
                                                    </option>
                                                    <option value="Koreksi Opname (+)">Koreksi Opname (+)</option>
                                                    <option value="Temuan Barang">Temuan Barang</option>
                                                    <option value="Lainnya (Masuk)">Lainnya</option>
                                                </optgroup>
                                            </template>
                                            <template x-if="type === 'out'">
                                                <optgroup label="Alasan Keluar">
                                                    <option value="Rusak / Cacat">Barang Rusak / Cacat</option>
                                                    <option value="Kedaluwarsa (Expired)">Kedaluwarsa (Expired)</option>
                                                    <option value="Hilang / Selisih (-)">Barang Hilang / Selisih (-)
                                                    </option>
                                                    <option value="Pemakaian Sendiri">Pemakaian Internal / Sample
                                                    </option>
                                                    <option value="Giveaway / Promosi">Giveaway / Promosi</option>
                                                    <option value="Lainnya (Keluar)">Lainnya</option>
                                                </optgroup>
                                            </template>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan
                                            (Opsional)</label>
                                        <textarea name="notes" rows="3"
                                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Contoh: Barang ditemukan saat bersih-bersih gudang..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Panel: Items -->
                            <div class="lg:col-span-2">
                                <div
                                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 min-h-[500px] flex flex-col">
                                    <div class="items-center justify-between mb-6">
                                        <h3 class="font-bold text-gray-900">Daftar Barang</h3>
                                        <p class="text-sm" :class="type === 'in' ? 'text-green-600' : 'text-red-600'">
                                            Mode: <span
                                                x-text="type === 'in' ? 'PENAMBAHAN STOK' : 'PENGURANGAN STOK'"></span>
                                        </p>
                                    </div>

                                    <!-- Search Box -->
                                    <div class="relative mb-6">
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="bi bi-search text-gray-400"></i>
                                            </div>
                                            <input type="text" x-model="searchQuery"
                                                @input.debounce.300ms="searchProducts()"
                                                @keydown.escape="showSearchResults = false"
                                                placeholder="Cari Sesuatu (Ketik nama / SKU)..."
                                                class="pl-10 w-full rounded-xl border-gray-300 focus:ring-blue-500 focus:border-blue-500 transition-shadow">
                                            <!-- Loading Spinner -->
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                                x-show="isLoading">
                                                <svg class="animate-spin h-5 w-5 text-blue-500"
                                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                                        stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                    </path>
                                                </svg>
                                            </div>
                                        </div>

                                        <!-- Search Results Dropdown -->
                                        <div x-show="showSearchResults && searchResults.length > 0"
                                            @click.away="showSearchResults = false"
                                            class="absolute z-50 w-full mt-2 bg-white rounded-xl shadow-xl border border-gray-100 max-h-60 overflow-y-auto"
                                            style="display: none;">
                                            <template x-for="product in searchResults" :key="product.id">
                                                <button type="button" @click="addItem(product)"
                                                    class="w-full px-4 py-3 text-left hover:bg-gray-50 border-b border-gray-50 last:border-0 flex items-center justify-between group">
                                                    <div>
                                                        <p class="font-medium text-gray-800" x-text="product.name"></p>
                                                        <div
                                                            class="flex items-center gap-3 text-xs text-gray-500 mt-0.5">
                                                            <span class="bg-gray-100 px-2 py-0.5 rounded text-gray-600"
                                                                x-text="product.sku"></span>
                                                            <span
                                                                x-text="product.category?.name || 'Uncategorized'"></span>
                                                        </div>
                                                    </div>
                                                    <div class="text-right">
                                                        <p class="text-xs text-gray-500">Stok Saat Ini</p>
                                                        <p class="font-bold font-mono text-blue-600"
                                                            x-text="product.stock_qty"></p>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>

                                        <div x-show="showSearchResults && searchResults.length === 0 && searchQuery.length >= 2 && !isLoading"
                                            class="absolute z-50 w-full mt-2 bg-white rounded-xl shadow-xl border border-gray-100 p-4 text-center text-gray-500">
                                            Tidak ada produk ditemukan.
                                        </div>
                                    </div>

                                    <!-- Items Table -->
                                    <div class="flex-1 overflow-x-auto">
                                        <table class="w-full text-left border-collapse">
                                            <thead>
                                                <tr class="text-xs text-gray-500 border-b border-gray-100">
                                                    <th class="py-3 font-medium w-1/3">Produk</th>
                                                    <th class="py-3 font-medium text-center w-24">Stok Awal</th>
                                                    <th class="py-3 font-medium text-center w-32">Qty <span
                                                            x-text="type==='in' ? 'Masuk' : 'Keluar'"></span></th>
                                                    <th class="py-3 font-medium text-center w-24">Stok Akhir</th>
                                                    <th class="py-3 font-medium w-10"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="text-sm">
                                                <template x-if="items.length === 0">
                                                    <tr>
                                                        <td colspan="5" class="py-10 text-center text-gray-400">
                                                            <i class="bi bi-basket text-3xl mb-2 block opacity-50"></i>
                                                            Belum ada barang dipilih
                                                        </td>
                                                    </tr>
                                                </template>
                                                <template x-for="(item, index) in items" :key="item.product_id">
                                                    <tr
                                                        class="border-b border-gray-50 last:border-0 hover:bg-gray-50/50">
                                                        <td class="py-3 pr-4">
                                                            <p class="font-medium text-gray-800"
                                                                x-text="item.product_name"></p>
                                                            <p class="text-xs text-gray-500 font-mono"
                                                                x-text="item.sku"></p>
                                                            <input type="hidden" :name="`items[${index}][product_id]`"
                                                                :value="item.product_id">
                                                            <input type="hidden"
                                                                :name="`items[${index}][current_stock]`"
                                                                :value="item.current_stock">
                                                        </td>
                                                        <td class="py-3 text-center">
                                                            <span
                                                                class="inline-block px-2 py-1 bg-gray-100 rounded text-gray-600 font-mono text-xs"
                                                                x-text="item.current_stock"></span>
                                                        </td>
                                                        <td class="py-3 px-2">
                                                            <input type="number" :name="`items[${index}][qty]`"
                                                                x-model.number="item.qty"
                                                                class="w-full text-center rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 font-bold"
                                                                :class="type === 'in' ? 'text-green-600' : 'text-red-600'"
                                                                min="1" :max="type === 'out' ? item.current_stock : ''"
                                                                required>
                                                            <div x-show="type === 'out' && item.qty > item.current_stock"
                                                                class="text-xs text-red-500 mt-1 text-center">
                                                                Melebihi stok!
                                                            </div>
                                                        </td>
                                                        <td class="py-3 text-center">
                                                            <span class="font-mono font-bold" :class="{
                                                                    'text-green-600': calculateFinalStock(item) > item.current_stock,
                                                                    'text-red-600': calculateFinalStock(item) < item.current_stock
                                                                }" x-text="calculateFinalStock(item)">
                                                            </span>
                                                        </td>
                                                        <td class="py-3 text-right">
                                                            <button type="button" @click="removeItem(index)"
                                                                class="text-gray-400 hover:text-red-500 transition-colors p-1 rounded hover:bg-red-50">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Footer Actions -->
                                    <div class="mt-8 pt-6 border-t border-gray-100 flex items-center justify-between">
                                        <button type="button" @click="resetForm"
                                            class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                                            Reset Form
                                        </button>
                                        <button type="submit"
                                            class="px-6 py-2.5 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all flex items-center gap-2"
                                            :disabled="items.length === 0 || isSubmitting"
                                            :class="{'opacity-50 cursor-not-allowed': items.length === 0 || isSubmitting}">
                                            <span x-show="!isSubmitting">Simpan Penyesuaian</span>
                                            <span x-show="isSubmitting">Menyimpan...</span>
                                            <i class="bi bi-arrow-right" x-show="!isSubmitting"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- Content End -->
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        function stockAdjustmentForm() {
            return {
                type: 'in', // 'in' or 'out'
                date: new Date().toISOString().split('T')[0],
                reason: '',
                searchQuery: '',
                searchResults: [],
                showSearchResults: false,
                isLoading: false,
                isSubmitting: false,
                items: [], // { product_id, product_name, sku, current_stock, qty }

                checkPersist() {
                    const saved = localStorage.getItem('stock_adjustment_draft_editor');
                    if (saved) {
                        try {
                            const parsed = JSON.parse(saved);
                            this.type = parsed.type || 'in';
                            this.date = parsed.date || new Date().toISOString().split('T')[0];
                            this.reason = parsed.reason || '';
                            this.items = parsed.items || [];
                        } catch (e) { console.error("Error loading draft", e); }
                    }
                },
                saveDraft() {
                    const draft = { type: this.type, date: this.date, reason: this.reason, items: this.items };
                    localStorage.setItem('stock_adjustment_draft_editor', JSON.stringify(draft));
                },
                initForm() {
                    this.checkPersist();
                    this.$watch('items', () => this.saveDraft());
                    this.$watch('type', () => this.saveDraft());
                    this.$watch('reason', () => this.saveDraft());
                    this.$watch('date', () => this.saveDraft());
                },
                resetForm() {
                    if (confirm('Yakin ingin mereset formulir? Data draft akan dihapus.')) {
                        this.type = 'in'; this.date = new Date().toISOString().split('T')[0]; this.reason = ''; this.items = []; this.searchQuery = '';
                        localStorage.removeItem('stock_adjustment_draft_editor');
                    }
                },
                async searchProducts() {
                    if (this.searchQuery.length < 2) { this.searchResults = []; this.showSearchResults = false; return; }
                    this.isLoading = true;
                    try {
                        const response = await axios.get('{{ route("editor.catalog.products.search") }}', { params: { q: this.searchQuery } });
                        this.searchResults = response.data; this.showSearchResults = true;
                    } catch (error) { console.error('Search error:', error); } finally { this.isLoading = false; }
                },
                addItem(product) {
                    const existing = this.items.find(i => i.product_id === product.id);
                    if (existing) { alert('Produk sudah ada di daftar.'); this.searchQuery = ''; this.showSearchResults = false; return; }
                    this.items.push({ product_id: product.id, product_name: product.name, sku: product.sku, current_stock: parseInt(product.stock_qty), qty: 1 });
                    this.searchQuery = ''; this.showSearchResults = false;
                },
                removeItem(index) { this.items.splice(index, 1); },
                calculateFinalStock(item) {
                    const current = parseInt(item.current_stock); const qty = parseInt(item.qty) || 0;
                    return this.type === 'in' ? current + qty : current - qty;
                },
                submitForm(e) {
                    if (this.type === 'out') {
                        const invalidItems = this.items.filter(i => i.qty > i.current_stock);
                        if (invalidItems.length > 0) { alert('Beberapa item melebihi stok yang tersedia! Mohon periksa kembali.'); return; }
                    }
                    this.isSubmitting = true; localStorage.removeItem('stock_adjustment_draft_editor'); e.target.submit();
                }
            }
        }
    </script>
</body>

</html>