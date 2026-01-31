<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Buat Sales Order - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Raleway', sans-serif;
        }

        .nav-text {
            position: relative;
            display: inline-block;
        }

        .nav-text::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: #e17f12;
            transition: width 0.2s ease-in-out;
        }

        .hover-link:hover .nav-text::after {
            width: 100%;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            text-align: center;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex">
        <x-navbar-owner />
        <div class="flex-1 lg:w-5/6">
            <x-navbar-top-owner />
            <div class="p-4 lg:p-8">
                <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                    <h1 class="text-2xl font-semibold text-gray-800 mb-4">Buat Sales Order</h1>
                    @if ($errors->any())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            {{ session('error') }}
                        </div>
                    @endif
                    <form action="{{ route('owner.sales.store') }}" method="POST" id="soForm"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="grid md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="order_type" class="block font-medium mb-1">Tipe Order</label>
                                <div class="flex items-center space-x-4">
                                    <label><input type="radio" name="order_type" value="jahit_sendiri" checked
                                            class="mr-2">Jahit Sendiri</label>
                                    <label><input type="radio" name="order_type" value="beli_jadi" class="mr-2">Langsung
                                        Beli Jadi</label>
                                </div>
                                @error('order_type')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="order_date" class="block font-medium mb-1">Tanggal Order</label>
                                <input type="datetime-local" name="order_date" id="order_date"
                                    value="{{ old('order_date', now()->format('Y-m-d\TH:i')) }}" required
                                    class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">
                                @error('order_date')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="deadline" class="block font-medium mb-1">Deadline/Target Selesai
                                    (Opsional)</label>
                                <input type="date" name="deadline" id="deadline" value="{{ old('deadline') }}"
                                    class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">
                                @error('deadline')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="mt-4">
    <label class="flex items-center">
        <input type="hidden" name="add_to_purchase" value="0">
        <input type="checkbox" name="add_to_purchase" id="add_to_purchase" value="1" class="mr-2">
        <span>Masukkan ke Pembelian (Pre-order)</span>
    </label>
</div>

<!-- Supplier Input (muncul hanya saat checkbox diceklis) -->
<div id="supplier-section" class="hidden mt-4">
    <label for="supplier_search" class="block font-medium mb-1">Supplier untuk Pembelian</label>
    <input type="text" 
           id="supplier_search" 
           class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" 
           placeholder="Ketik nama supplier atau biarkan kosong untuk 'Pre-order Customer'..."
           autocomplete="off">
    
    <!-- Hidden fields untuk data supplier -->
    <input type="hidden" name="supplier_id" id="supplier_id" value="">
    <input type="hidden" name="supplier_name" id="supplier_name" value="">

    <!-- Dropdown untuk existing suppliers -->
    <div id="supplier_dropdown" class="hidden absolute z-10 w-full mt-1 bg-white border rounded shadow-lg max-h-60 overflow-y-auto">
        @foreach($suppliers as $supplier)
            <div class="p-2 hover:bg-gray-100 cursor-pointer supplier-option"
                 data-id="{{ $supplier->id }}"
                 data-name="{{ $supplier->name }}">
                {{ $supplier->name }}
            </div>
        @endforeach
    </div>

    <!-- Info supplier yang dipilih -->
    <div id="selected_supplier" class="mt-2 p-2 bg-blue-50 rounded hidden">
        <span id="supplier_display_name" class="font-medium"></span>
        <button type="button" id="clear_supplier" class="text-red-600 ml-2">✕</button>
    </div>
</div>
                            <div class="relative">
                                <label for="customer_search" class="block font-medium mb-1">Customer (Opsional)</label>
                                <input type="text" id="customer_search"
                                    class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                                    placeholder="Ketik nama customer atau biarkan kosong..." autocomplete="off">

                                <!-- Hidden fields untuk data customer -->
                                <input type="hidden" name="customer_id" id="customer_id"
                                    value="{{ old('customer_id') }}">
                                <input type="hidden" name="customer_name" id="customer_name"
                                    value="{{ old('customer_name') }}">
                                <input type="hidden" name="customer_phone" id="customer_phone"
                                    value="{{ old('customer_phone') }}">

                                <!-- Dropdown untuk existing customers -->
                                <div id="customer_dropdown"
                                    class="hidden absolute z-10 w-full mt-1 bg-white border rounded shadow-lg max-h-60 overflow-y-auto">
                                    <!-- Existing customers akan dimuat di sini -->
                                    @foreach($customers as $customer)
                                        <div class="p-2 hover:bg-gray-100 cursor-pointer customer-option"
                                            data-id="{{ $customer->id }}" data-name="{{ $customer->name }}"
                                            data-phone="{{ $customer->phone ?? '' }}">
                                            {{ $customer->name }} @if($customer->phone)({{ $customer->phone }})@endif
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Info customer yang dipilih -->
                                <div id="selected_customer" class="mt-2 p-2 bg-blue-50 rounded hidden">
                                    <span id="customer_display_name" class="font-medium"></span>
                                    <span id="customer_display_phone" class="text-sm text-gray-600 ml-2"></span>
                                    <button type="button" id="clear_customer" class="text-red-600 ml-2">✕</button>
                                </div>

                                <!-- Fields untuk customer baru - TAMBAH INPUT PHONE DISINI -->
                                <div id="new_customer_fields" class="hidden mt-2">
                                    <div class="grid md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block font-medium mb-1">Nama Customer Baru *</label>
                                            <input type="text" id="new_customer_name"
                                                class="border rounded px-3 py-2 w-full"
                                                placeholder="Nama customer baru">
                                        </div>
                                        <div>
                                            <label class="block font-medium mb-1">Nomor Telepon</label>
                                            <input type="text" id="new_customer_phone"
                                                class="border rounded px-3 py-2 w-full"
                                                placeholder="Contoh: 08123456789">
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">* Customer baru akan otomatis dibuat</p>
                                </div>
                            </div>
                            <div>
                                <label for="payment_method" class="block font-medium mb-1">Metode Pembayaran</label>
                                <select name="payment_method" id="payment_method" required
                                    class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">
                                    <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash
                                    </option>
                                    <option value="transfer" {{ old('payment_method') == 'transfer' ? 'selected' : '' }}>
                                        Transfer</option>
                                    <option value="split" {{ old('payment_method') == 'split' ? 'selected' : '' }}>Split
                                    </option>
                                </select>
                                @error('payment_method')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="payment_status" class="block font-medium mb-1">Status Pembayaran</label>
                                <select name="payment_status" id="payment_status"
                                    class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">
                                    <option value="dp" {{ old('payment_status') == 'dp' ? 'selected' : '' }}>DP</option>
                                    <option value="lunas" {{ old('payment_status') == 'lunas' ? 'selected' : '' }}>Lunas
                                    </option>
                                </select>
                                @error('payment_status')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-6">
                            <h2 class="text-lg font-semibold mb-4 text-gray-800">Pembayaran (Opsional)</h2>
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label for="payment_amount" class="block font-medium mb-1">Jumlah Pembayaran
                                        (Total)</label>
                                    <input type="number" name="payment_amount" id="payment_amount"
                                        class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                                        step="0.01" min="0" value="{{ old('payment_amount') }}">
                                    <p id="dp-info" class="text-sm text-gray-600 mt-1"></p>
                                    @error('payment_amount')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div id="split-payment-fields" class="hidden md:col-span-2">
                                    <div class="grid md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="cash_amount" class="block font-medium mb-1">Jumlah Cash</label>
                                            <input type="number" name="cash_amount" id="cash_amount"
                                                class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                                                step="0.01" min="0" value="{{ old('cash_amount') }}">
                                            @error('cash_amount')
                                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="transfer_amount" class="block font-medium mb-1">Jumlah
                                                Transfer</label>
                                            <input type="number" name="transfer_amount" id="transfer_amount"
                                                class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                                                step="0.01" min="0" value="{{ old('transfer_amount') }}">
                                            @error('transfer_amount')
                                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div id="proof-field" class="mt-4 hidden md:col-span-2">
                                    <div class="grid md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="proof_path" class="block font-medium mb-1">Bukti Transfer (jpg,
                                                png, pdf, opsional)</label>
                                            <input type="file" name="proof_path" id="proof_path"
                                                accept=".jpg,.jpeg,.png,.pdf"
                                                class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">
                                            <p class="text-sm text-gray-600 mt-1">Upload bukti transfer (opsional)</p>
                                            @error('proof_path')
                                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="reference_number" class="block font-medium mb-1">No Referensi
                                                Transfer (Opsional)</label>
                                            <input type="text" name="reference_number" id="reference_number"
                                                class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                                                placeholder="Contoh: TRF123456789"
                                                value="{{ old('reference_number') }}">
                                            <p class="text-sm text-gray-600 mt-1">No referensi bank atau keterangan</p>
                                            @error('reference_number')
                                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-2">⚠️ Untuk transfer/split, wajib mengisi salah
                                        satu: Bukti Transfer atau No Referensi</p>
                                </div>
                                <div>
                                    <label for="paid_at" class="block font-medium mb-1">Tanggal Pembayaran</label>
                                    <input type="datetime-local" name="paid_at" id="paid_at"
                                        value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}"
                                        class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">
                                    @error('paid_at')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h2 class="text-lg font-semibold mb-4 text-gray-800">Item Order</h2>
                            <div id="items-container" class="space-y-4">
                                <div class="item-row grid md:grid-cols-5 gap-4">
                                    <div class="relative">
                                        <label class="block font-medium mb-1">Produk</label>
                                        <input type="text"
                                            class="product-search border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                                            placeholder="Ketik nama produk, SKU, atau barcode..." autocomplete="off">
                                        <input type="hidden" name="items[0][product_id]" class="product-id">
                                        <input type="hidden" name="items[0][product_name]" class="product-name">

                                        <!-- Dropdown untuk hasil search -->
                                        <div
                                            class="product-results hidden absolute z-20 w-full mt-1 bg-white border rounded shadow-lg max-h-60 overflow-y-auto">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block font-medium mb-1">SKU</label>
                                        <input type="text" name="items[0][sku]"
                                            class="sku border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                                            readonly>
                                        @error('items.0.sku')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block font-medium mb-1">Harga</label>
                                        <input type="number" name="items[0][sale_price]"
                                            class="sale-price border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                                            step="0.01" required>
                                        @error('items.0.sale_price')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block font-medium mb-1">Qty</label>
                                        <input type="number" name="items[0][qty]"
                                            class="qty border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                                            min="1" required>
                                        @error('items.0.qty')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block font-medium mb-1">Diskon</label>
                                        <input type="number" name="items[0][discount]"
                                            class="discount border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                                            min="0" step="0.01" value="0">
                                        @error('items.0.discount')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="add-item"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow mt-4">
                                <i class="bi bi-plus-circle"></i> Tambah Item
                            </button>
                        </div>

                        <button type="submit"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded shadow">
                            <i class="bi bi-save"></i> Simpan Sales Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // index awal untuk item baru (mulai dari 1 jika tidak ada old items)
            let itemIndex = {{ count(old('items', [])) > 0 ? count(old('items', [])) : 1 }};
            let grandTotal = 0;

            const soForm = document.getElementById('soForm');
            const itemsContainer = document.getElementById('items-container');
            const paymentMethod = document.getElementById('payment_method');
            const paymentAmount = document.getElementById('payment_amount');
            const cashAmount = document.getElementById('cash_amount');
            const transferAmount = document.getElementById('transfer_amount');
            const proofField = document.getElementById('proof-field');
            const splitFields = document.getElementById('split-payment-fields');
            const dpInfo = document.getElementById('dp-info');
            const paymentStatus = document.getElementById('payment_status');

            // Pastikan ada hidden grand_total untuk dikirim ke server
            if (soForm && !document.getElementById('grand_total')) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'grand_total';
                hidden.id = 'grand_total';
                hidden.value = '0';
                soForm.appendChild(hidden);
            }

            // --- Fungsi utama: hitung grand total ---
            function updateGrandTotal() {
                const rows = document.querySelectorAll('.item-row');
                let subtotal = 0;
                let discountTotal = 0;

                rows.forEach(row => {
                    const price = parseFloat(row.querySelector('.sale-price').value) || 0;
                    const qty = parseInt(row.querySelector('.qty').value) || 0;
                    const discount = parseFloat(row.querySelector('.discount').value) || 0;

                    const itemSubtotal = price * qty;
                    const itemDiscount = discount * qty;
                    subtotal += itemSubtotal;
                    discountTotal += itemDiscount;

                    // jika ada elemen subtotal atau display per row, kita bisa set (opsional)
                    const displaySubtotal = row.querySelector('.display-subtotal');
                    if (displaySubtotal) {
                        displaySubtotal.textContent = (itemSubtotal - itemDiscount).toLocaleString('id-ID');
                    }
                });

                grandTotal = subtotal - discountTotal;

                // update hidden field
                const grandInput = document.getElementById('grand_total');
                if (grandInput) grandInput.value = grandTotal.toFixed(2);

                // jika metode split, sinkron jumlah
                updatePaymentAmount();
                updatePaymentStatus();
            }

            // === PRODUCT SEARCH AUTCOMPLETE FUNCTION ===
            function initializeProductSearch(row) {
                const productSearch = row.querySelector('.product-search');
                const productResults = row.querySelector('.product-results');
                const productIdInput = row.querySelector('.product-id');
                const productNameInput = row.querySelector('.product-name');
                const skuInput = row.querySelector('.sku');
                const priceInput = row.querySelector('.sale-price');

                if (!productSearch) return;

                // Search products ketika user mengetik
                productSearch.addEventListener('input', function () {
                    const searchTerm = this.value.trim();

                    if (searchTerm.length < 2) {
                        productResults.classList.add('hidden');
                        return;
                    }

                    // AJAX search ke server
                    fetch(`/owner/products/search?q=${encodeURIComponent(searchTerm)}`)
                        .then(response => {
                            if (!response.ok) throw new Error('Network error');
                            return response.json();
                        })
                        .then(products => {
                            productResults.innerHTML = '';

                            if (products.length === 0) {
                                const noResult = document.createElement('div');
                                noResult.className = 'p-2 text-gray-500';
                                noResult.textContent = 'Produk tidak ditemukan';
                                productResults.appendChild(noResult);
                                productResults.classList.remove('hidden');
                                return;
                            }

                            products.forEach(product => {
                                const div = document.createElement('div');
                                div.className = 'p-2 hover:bg-gray-100 cursor-pointer product-option border-b';
                                div.innerHTML = `
                            <div class="font-medium">${product.name}</div>
                            <div class="text-sm text-gray-600">
                                SKU: ${product.sku} | Stok: ${product.stock_qty} | Harga: Rp ${parseFloat(product.price).toLocaleString('id-ID')}
                            </div>
                        `;
                                div.setAttribute('data-id', product.id);
                                div.setAttribute('data-name', product.name);
                                div.setAttribute('data-sku', product.sku);
                                div.setAttribute('data-price', product.price);
                                div.setAttribute('data-stock', product.stock_qty);

                                div.addEventListener('click', function () {
                                    // Set values ke form
                                    productIdInput.value = product.id;
                                    productNameInput.value = product.name;
                                    productSearch.value = product.name;
                                    if (skuInput) skuInput.value = product.sku;
                                    if (priceInput) priceInput.value = parseFloat(product.price).toFixed(2);

                                    // Sembunyikan dropdown
                                    productResults.classList.add('hidden');

                                    // Trigger change event untuk update total
                                    if (priceInput) {
                                        priceInput.dispatchEvent(new Event('input'));
                                    }
                                });

                                productResults.appendChild(div);
                            });

                            productResults.classList.remove('hidden');
                        })
                        .catch(error => {
                            console.error('Error searching products:', error);
                            productResults.innerHTML = '<div class="p-2 text-red-500">Error loading products</div>';
                            productResults.classList.remove('hidden');
                        });
                });

                // Sembunyikan dropdown ketika klik outside
                document.addEventListener('click', function (e) {
                    if (!e.target.closest('.relative')) {
                        productResults.classList.add('hidden');
                    }
                });

                // Focus handling
                productSearch.addEventListener('focus', function () {
                    if (this.value.trim().length >= 2) {
                        this.dispatchEvent(new Event('input'));
                    }
                });
            }

            // --- Event listener untuk perubahan harga, qty, discount ---
            itemsContainer.addEventListener('input', function (e) {
                const target = e.target;
                // perubahan qty / harga / discount juga update total
                if (target.classList.contains('qty') || target.classList.contains('sale-price') || target.classList.contains('discount')) {
                    updateGrandTotal();
                }
            });

            // --- Add item dengan template SEARCH INPUT ---
            document.getElementById('add-item').addEventListener('click', function () {
                const newRow = document.createElement('div');
                newRow.className = 'item-row grid md:grid-cols-5 gap-4 mt-4';
                newRow.innerHTML = `
            <div class="relative">
                <label class="block font-medium mb-1">Produk</label>
                <input type="text" 
                       class="product-search border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" 
                       placeholder="Ketik nama produk, SKU, atau barcode..."
                       autocomplete="off">
                <input type="hidden" name="items[${itemIndex}][product_id]" class="product-id">
                <input type="hidden" name="items[${itemIndex}][product_name]" class="product-name">
                
                <!-- Dropdown untuk hasil search -->
                <div class="product-results hidden absolute z-20 w-full mt-1 bg-white border rounded shadow-lg max-h-60 overflow-y-auto"></div>
            </div>
            <div>
                <label class="block font-medium mb-1">SKU</label>
                <input type="text" name="items[${itemIndex}][sku]" class="sku border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" readonly>
            </div>
            <div>
                <label class="block font-medium mb-1">Harga</label>
                <input type="number" name="items[${itemIndex}][sale_price]" class="sale-price border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" step="0.01" required>
            </div>
            <div>
                <label class="block font-medium mb-1">Qty</label>
                <input type="number" name="items[${itemIndex}][qty]" class="qty border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" min="1" value="1" required>
            </div>
            <div>
                <label class="block font-medium mb-1">Diskon</label>
                <input type="number" name="items[${itemIndex}][discount]" class="discount border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" min="0" step="0.01" value="0">
                <button type="button" class="remove-item text-red-600 hover:text-red-800 mt-2"><i class="bi bi-trash"></i></button>
            </div>
        `;
                itemsContainer.appendChild(newRow);
                itemIndex++;

                // Initialize product search untuk row baru
                setTimeout(() => {
                    initializeProductSearch(newRow);
                }, 100);
            });

            // --- Remove item (handle ikon di dalam button juga) ---
            itemsContainer.addEventListener('click', function (e) {
                const btn = e.target.closest('.remove-item');
                if (btn) {
                    const rows = document.querySelectorAll('.item-row');
                    if (rows.length > 1) {
                        btn.closest('.item-row').remove();
                        updateGrandTotal();
                    } else {
                        alert('Minimal satu item harus ada.');
                    }
                }
            });

            // --- Payment / split handling ---
            function updatePaymentAmount() {
                const method = paymentMethod.value;
                const cash = parseFloat(cashAmount?.value) || 0;
                const transfer = parseFloat(transferAmount?.value) || 0;

                if (method === 'split') {
                    // set payment_amount sebagai jumlah cash+transfer
                    paymentAmount.value = (cash + transfer).toFixed(2);
                    if (splitFields) splitFields.classList.remove('hidden');
                } else {
                    // untuk cash/transfer default isi grandTotal (kasir biasanya otomatis)
                    if (method === 'cash' || method === 'transfer') {
                        paymentAmount.value = grandTotal.toFixed(2);
                    }
                    if (splitFields) splitFields.classList.add('hidden');
                }

                updateProofRequired(method);
                updatePaymentStatus();
            }

            function updateProofRequired(method) {
                const proofInput = document.getElementById('proof_path');
                const referenceInput = document.getElementById('reference_number');

                if (!proofInput || !referenceInput) return;

                if (method === 'transfer' || method === 'split') {
                    // Untuk transfer/split, bukti dan referensi jadi opsional (salah satu wajib)
                    proofInput.required = false;
                    referenceInput.required = false;
                    if (proofField) proofField.classList.remove('hidden');
                } else {
                    proofInput.required = false;
                    referenceInput.required = false;
                    if (proofField) proofField.classList.add('hidden');
                }
            }

            function updatePaymentStatus() {
                const amount = parseFloat(paymentAmount.value) || 0;
                if (amount >= grandTotal) {
                    paymentStatus.value = 'lunas';
                } else {
                    paymentStatus.value = 'dp';
                }
            }

            // Event binding payment fields
            if (paymentMethod) paymentMethod.addEventListener('change', updatePaymentAmount);
            if (cashAmount) cashAmount.addEventListener('input', updatePaymentAmount);
            if (transferAmount) transferAmount.addEventListener('input', updatePaymentAmount);
            if (paymentAmount) paymentAmount.addEventListener('input', updatePaymentStatus);
            if (paymentStatus) paymentStatus.addEventListener('change', updatePaymentStatus);

            // --- Submit validation ---
            if (soForm) {
                soForm.addEventListener('submit', function (e) {
                    // validasi harga produk > 0 untuk semua baris
                    const prices = document.querySelectorAll('.sale-price');
                    for (let p of prices) {
                        if (!p.value || parseFloat(p.value) < 0) {
                            e.preventDefault();
                            alert('Harga produk tidak boleh kosong atau negatif.');
                            return;
                        }
                    }

                    // validasi payment split/proof/dp checks
                    const method = paymentMethod.value;
                    const amount = parseFloat(paymentAmount.value) || 0;

                    if (method === 'split' || method === 'transfer') {
                        const proof = document.getElementById('proof_path');
                        const reference = document.getElementById('reference_number');

                        const hasProof = proof && proof.files && proof.files[0];
                        const hasReference = reference && reference.value.trim() !== '';

                        if (!hasProof && !hasReference) {
                            e.preventDefault();
                            alert('Untuk metode transfer/split, wajib upload bukti transfer atau isi no referensi.');
                            return;
                        }
                    }

                    if (amount > 0) {
    if (paymentStatus.value === 'dp' && amount <= 0) {
        e.preventDefault();
        alert(`Jumlah pembayaran DP harus lebih dari Rp 0`);
        return;
    }
    if (amount > grandTotal) {
        e.preventDefault();
        alert(`Jumlah pembayaran melebihi grand total: Rp ${grandTotal.toLocaleString('id-ID')}`);
        return;
    }
}

                    // kalau lolos semua, biarkan submit
                });
            }

            // Initialize product search untuk semua row yang sudah ada
            document.querySelectorAll('.item-row').forEach(row => {
                initializeProductSearch(row);
            });

            // Jalankan inisialisasi awal
            updateGrandTotal();
            // pastikan payment method render sesuai awal
            if (paymentMethod) paymentMethod.dispatchEvent(new Event('change'));
        });

        // === SIMPLE CUSTOMER SEARCH FIX ===
        const customerSearch = document.getElementById('customer_search');
        const customerDropdown = document.getElementById('customer_dropdown');
        const customerIdInput = document.getElementById('customer_id');
        const customerNameInput = document.getElementById('customer_name');
        const customerPhoneInput = document.getElementById('customer_phone');
        const selectedCustomerDiv = document.getElementById('selected_customer');
        const newCustomerFields = document.getElementById('new_customer_fields');
        const newCustomerName = document.getElementById('new_customer_name');
        const newCustomerPhone = document.getElementById('new_customer_phone');

        // Show dropdown when clicking search
        customerSearch.addEventListener('click', function () {
            customerDropdown.classList.remove('hidden');
        });

        // Hide dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.relative')) {
                customerDropdown.classList.add('hidden');
            }
        });

        // Select existing customer
        customerDropdown.addEventListener('click', function (e) {
            const customerOption = e.target.closest('.customer-option');
            if (customerOption) {
                const id = customerOption.getAttribute('data-id');
                const name = customerOption.getAttribute('data-name');
                const phone = customerOption.getAttribute('data-phone');

                customerIdInput.value = id;
                customerNameInput.value = name;
                customerPhoneInput.value = phone || '';

                // Show selected customer info
                document.getElementById('customer_display_name').textContent = name;
                document.getElementById('customer_display_phone').textContent = phone ? `(${phone})` : '';
                selectedCustomerDiv.classList.remove('hidden');

                // Hide dropdown and clear search
                customerDropdown.classList.add('hidden');
                customerSearch.value = '';
            }
        });

        // Clear customer selection
        document.getElementById('clear_customer').addEventListener('click', function () {
            customerIdInput.value = '';
            customerNameInput.value = '';
            customerPhoneInput.value = '';
            selectedCustomerDiv.classList.add('hidden');
            newCustomerFields.classList.add('hidden');
            customerSearch.value = '';
            newCustomerName.value = '';
            newCustomerPhone.value = '';
        });

        // Create new customer - show fields when typing
        customerSearch.addEventListener('input', function () {
            const searchTerm = this.value.trim();

            if (searchTerm.length > 0) {
                // Check if customer exists
                const existingCustomer = Array.from(customerDropdown.querySelectorAll('.customer-option'))
                    .find(opt => opt.getAttribute('data-name').toLowerCase() === searchTerm.toLowerCase());

                if (!existingCustomer) {
                    // Show new customer fields
                    newCustomerName.value = searchTerm;
                    customerNameInput.value = searchTerm;
                    newCustomerFields.classList.remove('hidden');
                } else {
                    newCustomerFields.classList.add('hidden');
                }
            } else {
                newCustomerFields.classList.add('hidden');
            }
        });

        // Sync new customer fields
        newCustomerName.addEventListener('input', function () {
            customerNameInput.value = this.value;
        });

        newCustomerPhone.addEventListener('input', function () {
            customerPhoneInput.value = this.value;
        });
        // === SUPPLIER SEARCH LOGIC ===
const supplierSearch = document.getElementById('supplier_search');
const supplierDropdown = document.getElementById('supplier_dropdown');
const supplierIdInput = document.getElementById('supplier_id');
const supplierNameInput = document.getElementById('supplier_name');
const selectedSupplierDiv = document.getElementById('selected_supplier');

// Tampilkan/hide supplier section saat checkbox berubah
document.getElementById('add_to_purchase').addEventListener('change', function() {
    const supplierSection = document.getElementById('supplier-section');
    supplierSection.classList.toggle('hidden', !this.checked);
});

// Select existing supplier
supplierDropdown.addEventListener('click', function(e) {
    const supplierOption = e.target.closest('.supplier-option');
    if (supplierOption) {
        const id = supplierOption.getAttribute('data-id');
        const name = supplierOption.getAttribute('data-name');
        
        supplierIdInput.value = id;
        supplierNameInput.value = name;
        
        document.getElementById('supplier_display_name').textContent = name;
        selectedSupplierDiv.classList.remove('hidden');
        
        supplierDropdown.classList.add('hidden');
        supplierSearch.value = '';
    }
});

// Clear supplier selection
document.getElementById('clear_supplier').addEventListener('click', function() {
    supplierIdInput.value = '';
    supplierNameInput.value = '';
    selectedSupplierDiv.classList.add('hidden');
    supplierSearch.value = '';
});

// Show dropdown on focus
supplierSearch.addEventListener('focus', function() {
    if (this.value.trim().length === 0) {
        supplierDropdown.classList.remove('hidden');
    }
});

// Hide dropdown on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('#supplier-section')) {
        supplierDropdown.classList.add('hidden');
    }
});

// Handle new supplier input
supplierSearch.addEventListener('input', function() {
    const searchTerm = this.value.trim();
    if (searchTerm.length > 0) {
        // Cek apakah supplier sudah ada
        const existing = Array.from(supplierDropdown.querySelectorAll('.supplier-option'))
            .find(opt => opt.getAttribute('data-name').toLowerCase() === searchTerm.toLowerCase());
        
        if (!existing) {
            supplierNameInput.value = searchTerm;
            supplierIdInput.value = '';
        }
    } else {
        supplierNameInput.value = '';
        supplierIdInput.value = '';
    }
});
    </script>
</body>

</html>