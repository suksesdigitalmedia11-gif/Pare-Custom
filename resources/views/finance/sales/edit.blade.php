<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Sales Order - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.10/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Raleway', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
<div class="flex">
    <x-navbar-finance />
    <div class="flex-1 lg:w-5/6">
        <x-navbar-top-finance />
        <div class="p-4 lg:p-8">
            <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                <h1 class="text-2xl font-semibold text-gray-800 mb-4">Edit Sales Order</h1>
                @if (session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                        {{ session('error') }}
                    </div>
                @endif
                <form action="{{ route('finance.sales.update', $salesOrder) }}" method="POST" id="soForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Hidden status (jika edit draft, tetap draft) -->
                    <input type="hidden" name="status" value="{{ $salesOrder->status }}">

                    <div class="grid md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="order_type" class="block font-medium mb-1">Tipe Order</label>
                            <div class="flex items-center space-x-4">
                                <label><input type="radio" name="order_type" value="jahit_sendiri" {{ $salesOrder->order_type == 'jahit_sendiri' ? 'checked' : '' }} class="mr-2">Jahit Sendiri</label>
                                <label><input type="radio" name="order_type" value="beli_jadi" {{ $salesOrder->order_type == 'beli_jadi' ? 'checked' : '' }} class="mr-2">Langsung Beli Jadi</label>
                            </div>
                            @error('order_type')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="order_date" class="block font-medium mb-1">Tanggal Order</label>
                            <input type="datetime-local" name="order_date" id="order_date" 
                                   value="{{ old('order_date', $salesOrder->order_date->format('Y-m-d\TH:i')) }}"
                                   required class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">
                            @error('order_date')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="deadline" class="block font-medium mb-1">Deadline/Target Selesai (Opsional)</label>
                            <input type="date" name="deadline" id="deadline" 
                                   value="{{ old('deadline', $salesOrder->deadline ? $salesOrder->deadline->format('Y-m-d') : '') }}" 
                                   class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">
                            @error('deadline')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="relative">
    <label for="customer_search" class="block font-medium mb-1">Customer (Opsional)</label>
    
    <!-- Input untuk search customer dengan autocomplete -->
    <input type="text" 
           id="customer_search"
           class="customer-autocomplete border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
           placeholder="Ketik minimal 3 huruf nama customer..."
           autocomplete="off"
           value="{{ old('customer_name', $salesOrder->customer ? $salesOrder->customer->name : '') }}">

    <!-- Hidden fields untuk data customer -->
    <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id', $salesOrder->customer_id) }}">
    <input type="hidden" name="customer_name" id="customer_name" value="{{ old('customer_name', $salesOrder->customer ? $salesOrder->customer->name : '') }}">
    <input type="hidden" name="customer_phone" id="customer_phone" value="{{ old('customer_phone', $salesOrder->customer ? $salesOrder->customer->phone : '') }}">

    <!-- Dropdown untuk hasil autocomplete -->
    <div id="customer_autocomplete_results" 
         class="hidden absolute z-20 w-full mt-1 bg-white border rounded shadow-lg max-h-60 overflow-y-auto">
    </div>

    <!-- Info customer yang dipilih -->
    <div id="selected_customer" class="mt-2 p-2 bg-blue-50 rounded {{ $salesOrder->customer || old('customer_id') ? '' : 'hidden' }}">
        <span id="customer_display_name" class="font-medium">
            {{ old('customer_name', $salesOrder->customer ? $salesOrder->customer->name : '') }}
        </span>
        <span id="customer_display_phone" class="text-sm text-gray-600 ml-2">
            {{ ($salesOrder->customer && $salesOrder->customer->phone) || old('customer_phone') ? '(' . old('customer_phone', $salesOrder->customer ? $salesOrder->customer->phone : '') . ')' : '' }}
        </span>
        <button type="button" id="clear_customer" class="text-red-600 ml-2">✕</button>
    </div>

    <!-- Fields untuk customer baru -->
    <div id="new_customer_fields" class="hidden mt-2">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block font-medium mb-1">Nama Customer Baru *</label>
                <input type="text" id="new_customer_name" class="border rounded px-3 py-2 w-full" placeholder="Nama customer baru">
            </div>
            <div>
                <label class="block font-medium mb-1">Nomor Telepon</label>
                <input type="text" id="new_customer_phone" class="border rounded px-3 py-2 w-full" placeholder="Contoh: 08123456789">
            </div>
        </div>
        <p class="text-sm text-gray-600 mt-1">* Customer baru akan otomatis dibuat</p>
    </div>
</div>
                        <div>
                            <label for="payment_method" class="block font-medium mb-1">Metode Pembayaran</label>
                            <select name="payment_method" id="payment_method" {{ $salesOrder->status === 'draft' ? '' : 'required' }} class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">
                                <option value="cash" {{ old('payment_method', $salesOrder->payment_method) == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="transfer" {{ old('payment_method', $salesOrder->payment_method) == 'transfer' ? 'selected' : '' }}>Transfer</option>
                                <option value="split" {{ old('payment_method', $salesOrder->payment_method) == 'split' ? 'selected' : '' }}>Split</option>
                            </select>
                            @error('payment_method')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="payment_status" class="block font-medium mb-1">Status Pembayaran</label>
                            <select name="payment_status" id="payment_status" {{ $salesOrder->status === 'draft' ? '' : 'required' }} class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">
                                <option value="dp" {{ old('payment_status', $salesOrder->payment_status) == 'dp' ? 'selected' : '' }}>DP</option>
                                <option value="lunas" {{ old('payment_status', $salesOrder->payment_status) == 'lunas' ? 'selected' : '' }}>Lunas</option>
                            </select>
                            @error('payment_status')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Checkbox + Supplier -->
                    <div class="mt-4">
                        <label class="flex items-center">
                            <input type="hidden" name="add_to_purchase" value="0">
                            <input type="checkbox" name="add_to_purchase" id="add_to_purchase" value="1"
                            class="mr-2" {{ $salesOrder->add_to_purchase ? 'checked' : '' }}>
                            <span>Masukkan ke Pembelian (Pre-order)</span>
                        </label>
                    </div>
                    <div id="supplier-section" class="hidden mt-4">
                        <label for="supplier_search" class="block font-medium mb-1">Supplier untuk Pembelian *</label>
                        <input type="text" id="supplier_search"
                               class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                               placeholder="Ketik nama supplier..."
                               autocomplete="off"
                               value="{{ old('supplier_name', $salesOrder->add_to_purchase ? 'Pre-order Customer' : '') }}">
                        <input type="hidden" name="supplier_id" id="supplier_id" value="">
                        <input type="hidden" name="supplier_name" id="supplier_name" value="{{ old('supplier_name', $salesOrder->add_to_purchase ? 'Pre-order Customer' : '') }}">
                        <div id="selected_supplier" class="mt-2 p-2 bg-blue-50 rounded hidden">
                            <span id="supplier_display_name" class="font-medium">Pre-order Customer</span>
                            <button type="button" id="clear_supplier" class="text-red-600 ml-2">✕</button>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h2 class="text-lg font-semibold mb-4 text-gray-800">Item Order</h2>
                        <div id="items-container" class="space-y-4">
    @foreach($salesOrder->items as $index => $item)
    <div class="item-row grid md:grid-cols-5 gap-4">
        <div class="relative md:col-span-2">
            <label class="block font-medium mb-1">Produk</label>
            <input type="text"
                class="product-search border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                placeholder="Ketik nama produk..." 
                autocomplete="off"
                value="{{ $item->product_name }}">
            <input type="hidden" name="items[{{ $index }}][product_id]" class="product-id" value="{{ $item->product_id }}">
            <input type="hidden" name="items[{{ $index }}][product_name]" class="product-name" value="{{ $item->product_name }}">
            <input type="hidden" name="items[{{ $index }}][sku]" class="sku" value="{{ $item->sku }}">
            <div class="product-results hidden absolute z-20 w-full mt-1 bg-white border rounded shadow-lg max-h-60 overflow-y-auto"></div>
        </div>
        <div>
            <label class="block font-medium mb-1">Harga</label>
            <input type="number" name="items[{{ $index }}][sale_price]"
                class="sale-price border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                step="0.01" required value="{{ $item->sale_price }}">
            @error('items.'.$index.'.sale_price')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block font-medium mb-1">Qty</label>
            <input type="number" name="items[{{ $index }}][qty]"
                class="qty border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                min="1" required value="{{ $item->qty }}">
            @error('items.'.$index.'.qty')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>
    @endforeach
</div>
                        <button type="button" id="add-item" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow mt-4">
                            <i class="bi bi-plus-circle"></i> Tambah Item
                        </button>
                    </div>

                    @if($salesOrder->payments->count() > 0)
<div class="bg-blue-50 p-4 rounded-lg mb-6">
    <h2 class="text-lg font-semibold mb-2 text-blue-800">Info Pembayaran</h2>
    <p class="text-sm text-blue-700">
        <i class="bi bi-info-circle"></i> 
        Sales order ini sudah memiliki {{ $salesOrder->payments->count() }} pembayaran.
        Untuk menambah/mengubah pembayaran, gunakan tombol <strong>"Tambah Pembayaran"</strong> di halaman detail.
    </p>
    <div class="mt-2 text-sm">
        <strong>Total Dibayar:</strong> Rp {{ number_format($salesOrder->paid_total, 0, ',', '.') }} |
        <strong>Sisa:</strong> Rp {{ number_format($salesOrder->remaining_amount, 0, ',', '.') }}
    </div>
</div>
@endif

                    <!-- TAMBAH BAGIAN INI: Summary dengan Discount Total -->
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
    <h2 class="text-lg font-semibold mb-4 text-gray-800">Ringkasan Order</h2>
    <div class="grid md:grid-cols-4 gap-4">
        <div>
            <label class="block font-medium mb-1">Subtotal</label>
            <div id="display-subtotal" class="text-lg font-semibold text-gray-800">Rp {{ number_format($salesOrder->subtotal, 0, ',', '.') }}</div>
        </div>
        <div>
            <label for="discount_total" class="block font-medium mb-1">Diskon Total</label>
            <input type="number" name="discount_total" id="discount_total" 
                   class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                   min="0" step="0.01" value="{{ old('discount_total', $salesOrder->discount_total) }}"
                   placeholder="0">
            @error('discount_total')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
        <!-- ✅ TAMBAH INPUT ONGKIR DI SINI -->
        <div>
            <label for="shipping_cost" class="block font-medium mb-1">Ongkir</label>
            <input type="number" name="shipping_cost" id="shipping_cost" 
                   class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                   min="0" step="0.01" value="{{ old('shipping_cost', $salesOrder->shipping_cost) }}"
                   placeholder="0">
            @error('shipping_cost')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block font-medium mb-1">Grand Total</label>
            <div id="display-grand-total" class="text-lg font-bold text-blue-600">Rp {{ number_format($salesOrder->grand_total, 0, ',', '.') }}</div>
            <input type="hidden" name="grand_total" id="grand_total" value="{{ $salesOrder->grand_total }}">
        </div>
    </div>
</div>

                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded shadow">
                        <i class="bi bi-save"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// === JAVASCRIPT LENGKAP: CUSTOMER, SUPPLIER, PRODUCT SEARCH, DLL ===
document.addEventListener('DOMContentLoaded', function () {
    let itemIndex = {{ count($salesOrder->items) }};
    const itemsContainer = document.getElementById('items-container');
    const soForm = document.getElementById('soForm');

    // Hidden grand total
    if (!document.getElementById('grand_total')) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'grand_total';
        hidden.id = 'grand_total';
        hidden.value = '0';
        soForm.appendChild(hidden);
    }

    let grandTotal = 0;

    // === PRODUCT SEARCH ===
    function initializeProductSearchForEdit(row, index) {
        const productSearch = row.querySelector('.product-search');
        const productResults = row.querySelector('.product-results');
        const productIdInput = row.querySelector('.product-id');
        const productNameInput = row.querySelector('.product-name');
        const skuInput = row.querySelector('.sku');
        const priceInput = row.querySelector('.sale-price');

        if (!productSearch) return;

        productSearch.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            if (searchTerm.length < 2) {
                productResults.classList.add('hidden');
                return;
            }
            fetch(`/finance/products/search?q=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(products => {
                    productResults.innerHTML = '';
                    if (products.length === 0) {
                        productResults.innerHTML = '<div class="p-2 text-gray-500">Produk tidak ditemukan</div>';
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
                        div.addEventListener('click', function() {
                            productIdInput.value = product.id;
                            productNameInput.value = product.name;
                            productSearch.value = product.name;
                            if (skuInput) skuInput.value = product.sku;
                            if (priceInput) priceInput.value = parseFloat(product.price).toFixed(2);
                            productResults.classList.add('hidden');
                            updateGrandTotal();
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

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.relative')) {
                productResults.classList.add('hidden');
            }
        });

        productSearch.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) {
                this.dispatchEvent(new Event('input'));
            }
        });
    }

// Inisialisasi existing items dengan data yang benar
document.querySelectorAll('.item-row').forEach((row, index) => {
    initializeProductSearchForEdit(row, index);
    
    // Trigger update grand total untuk existing items
    const priceInput = row.querySelector('.sale-price');
    const qtyInput = row.querySelector('.qty');
    const discountInput = row.querySelector('.discount');
    
    if (priceInput && qtyInput && discountInput) {
        // Simulasikan input event untuk kalkulasi awal
        setTimeout(() => {
            updateGrandTotal();
        }, 100);
    }
});

    // === FUNGSI UMUM ===
// UPDATE fungsi updateGrandTotal() dengan ongkir
function updateGrandTotal() {
    const rows = document.querySelectorAll('.item-row');
    let subtotal = 0;

    rows.forEach(row => {
        const price = parseFloat(row.querySelector('.sale-price').value) || 0;
        const qty = parseInt(row.querySelector('.qty').value) || 0;
        subtotal += price * qty;
    });

    const discountTotal = parseFloat(document.getElementById('discount_total').value) || 0;
    const shippingCost = parseFloat(document.getElementById('shipping_cost').value) || 0; // ✅ TAMBAH
    
    grandTotal = Math.max(0, subtotal - discountTotal + shippingCost); // ✅ UPDATE

    document.getElementById('display-subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
    document.getElementById('display-grand-total').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
    document.getElementById('grand_total').value = grandTotal.toFixed(2);

    updatePaymentAmount();
    updatePaymentStatus();
}

// ✅ TAMBAH event listener untuk shipping cost
document.getElementById('shipping_cost').addEventListener('input', updateGrandTotal);

// Event listener untuk discount total
document.getElementById('discount_total').addEventListener('input', updateGrandTotal);

    // === TAMBAH ITEM ===
    document.getElementById('add-item').addEventListener('click', function () {
        const newRow = document.createElement('div');
        newRow.className = 'item-row grid md:grid-cols-5 gap-4 mt-4';
        newRow.innerHTML = `
    <div class="relative md:col-span-2">
        <label class="block font-medium mb-1">Produk</label>
        <input type="text" 
               class="product-search border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" 
               placeholder="Ketik nama produk..."
               autocomplete="off">
        <input type="hidden" name="items[${itemIndex}][product_id]" class="product-id">
        <input type="hidden" name="items[${itemIndex}][product_name]" class="product-name">
        <input type="hidden" name="items[${itemIndex}][sku]" class="sku"> <!-- SKU jadi hidden -->
        <div class="product-results hidden absolute z-20 w-full mt-1 bg-white border rounded shadow-lg max-h-60 overflow-y-auto"></div>
    </div>
    <div><label class="block font-medium mb-1">Harga</label>
        <input type="number" name="items[${itemIndex}][sale_price]" class="sale-price border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" step="0.01" required></div>
    <div><label class="block font-medium mb-1">Qty</label>
        <input type="number" name="items[${itemIndex}][qty]" class="qty border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" min="1" value="1" required></div>
    <div><label class="block font-medium mb-1">Diskon</label>
        <input type="number" name="items[${itemIndex}][discount]" class="discount border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" min="0" step="0.01" value="0">
        <button type="button" class="remove-item text-red-600 hover:text-red-800 mt-2"><i class="bi bi-trash"></i></button>
    </div>
`;
        itemsContainer.appendChild(newRow);
        setTimeout(() => {
            initializeProductSearchForEdit(newRow, itemIndex);
        }, 100);
        itemIndex++;
        updateGrandTotal();
    });

    // Hapus item
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

    // Event input harga/qty/diskon
    itemsContainer.addEventListener('input', function (e) {
        const target = e.target;
        if (target.classList.contains('qty') || target.classList.contains('sale-price') || target.classList.contains('discount')) {
            updateGrandTotal();
        }
    });

// === AUTOCOMPLETE CUSTOMER SEARCH ===
const customerSearch = document.getElementById('customer_search');
const customerResults = document.getElementById('customer_autocomplete_results');
const customerIdInput = document.getElementById('customer_id');
const customerNameInput = document.getElementById('customer_name');
const customerPhoneInput = document.getElementById('customer_phone');
const selectedCustomerDiv = document.getElementById('selected_customer');
const newCustomerFields = document.getElementById('new_customer_fields');
const newCustomerName = document.getElementById('new_customer_name');
const newCustomerPhone = document.getElementById('new_customer_phone');

let searchTimeout = null;

// Real-time search dengan debounce
customerSearch.addEventListener('input', function () {
    const searchTerm = this.value.trim();
    
    // Clear previous timeout
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    // Hide results if search term is too short
    if (searchTerm.length < 3) {
        customerResults.classList.add('hidden');
        checkNewCustomerFields(searchTerm);
        return;
    }
    
    // Debounce search - wait 300ms after user stops typing
    searchTimeout = setTimeout(() => {
        searchCustomers(searchTerm);
    }, 300);
});

// Function untuk search customers via AJAX
function searchCustomers(searchTerm) {
    fetch(`/finance/customers/search?q=${encodeURIComponent(searchTerm)}`)
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(customers => {
            customerResults.innerHTML = '';
            
            if (customers.length === 0) {
                // No existing customers found
                const noResult = document.createElement('div');
                noResult.className = 'p-2 text-gray-500';
                noResult.textContent = 'Customer tidak ditemukan. Akan dibuat customer baru.';
                customerResults.appendChild(noResult);
                customerResults.classList.remove('hidden');
                
                // Show new customer fields
                checkNewCustomerFields(searchTerm);
                return;
            }
            
            // Show matching customers
            customers.forEach(customer => {
                const div = document.createElement('div');
                div.className = 'p-2 hover:bg-gray-100 cursor-pointer customer-option border-b';
                div.innerHTML = `
                    <div class="font-medium">${customer.name}</div>
                    <div class="text-sm text-gray-600">${customer.phone ? `(${customer.phone})` : 'No phone'}</div>
                `;
                div.setAttribute('data-id', customer.id);
                div.setAttribute('data-name', customer.name);
                div.setAttribute('data-phone', customer.phone || '');
                
                div.addEventListener('click', function () {
                    selectCustomer(customer.id, customer.name, customer.phone || '');
                });
                
                customerResults.appendChild(div);
            });
            
            customerResults.classList.remove('hidden');
            newCustomerFields.classList.add('hidden');
        })
        .catch(error => {
            console.error('Error searching customers:', error);
            customerResults.innerHTML = '<div class="p-2 text-red-500">Error loading customers</div>';
            customerResults.classList.remove('hidden');
        });
}

// Function untuk select customer
function selectCustomer(id, name, phone) {
    customerIdInput.value = id;
    customerNameInput.value = name;
    customerPhoneInput.value = phone;
    
    // Update display
    document.getElementById('customer_display_name').textContent = name;
    document.getElementById('customer_display_phone').textContent = phone ? `(${phone})` : '';
    selectedCustomerDiv.classList.remove('hidden');
    
    // Clear search and hide results
    customerSearch.value = '';
    customerResults.classList.add('hidden');
    newCustomerFields.classList.add('hidden');
}

// Function untuk check if we should show new customer fields
function checkNewCustomerFields(searchTerm) {
    if (searchTerm.length >= 3 && !customerIdInput.value) {
        newCustomerName.value = searchTerm;
        customerNameInput.value = searchTerm;
        newCustomerFields.classList.remove('hidden');
    } else {
        newCustomerFields.classList.add('hidden');
    }
}

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
    customerResults.classList.add('hidden');
});

// Sync new customer fields
newCustomerName.addEventListener('input', function () {
    customerNameInput.value = this.value;
});

newCustomerPhone.addEventListener('input', function () {
    customerPhoneInput.value = this.value;
});

// Hide dropdown when clicking outside
document.addEventListener('click', function (e) {
    if (!e.target.closest('.relative')) {
        customerResults.classList.add('hidden');
    }
});

// Show dropdown when focusing on search
customerSearch.addEventListener('focus', function () {
    if (this.value.trim().length >= 3) {
        this.dispatchEvent(new Event('input'));
    }
});

    // === SUPPLIER SEARCH ===
    const addToPurchase = document.getElementById('add_to_purchase');
    const supplierSection = document.getElementById('supplier-section');
    const supplierSearch = document.getElementById('supplier_search');
    const supplierIdInput = document.getElementById('supplier_id');
    const supplierNameInput = document.getElementById('supplier_name');
    const selectedSupplierDiv = document.getElementById('selected_supplier');

    addToPurchase.addEventListener('change', function() {
        supplierSection.classList.toggle('hidden', !this.checked);
        if (this.checked && !supplierIdInput.value) {
            supplierNameInput.value = 'Pre-order Customer';
            document.getElementById('supplier_display_name').textContent = 'Pre-order Customer';
            selectedSupplierDiv.classList.remove('hidden');
        }
    });

    supplierSearch.addEventListener('input', function() {
        const term = this.value.trim();
        if (term) {
            supplierNameInput.value = term;
            supplierIdInput.value = '';
        }
    });

    document.getElementById('clear_supplier').addEventListener('click', function() {
        supplierIdInput.value = '';
        supplierNameInput.value = 'Pre-order Customer';
        document.getElementById('supplier_display_name').textContent = 'Pre-order Customer';
        supplierSearch.value = '';
        selectedSupplierDiv.classList.add('hidden');
    });

    // === SUBMIT VALIDATION ===
    soForm.addEventListener('submit', function(e) {
        const prices = document.querySelectorAll('.sale-price');
        for (let p of prices) {
            if (!p.value || parseFloat(p.value) < 0) {
                e.preventDefault();
                alert('Harga produk tidak boleh kosong atau negatif.');
                return;
            }
        }

        const method = paymentMethod.value;
        const amount = parseFloat(paymentAmount.value) || 0;

        if (paymentStatus.value === 'dp' && amount <= 0) {
    e.preventDefault();
    alert('Jumlah pembayaran DP harus lebih dari Rp 0');
    return;
}


        if (amount > grandTotal) {
            e.preventDefault();
            alert(`Jumlah pembayaran melebihi total: Rp ${grandTotal.toLocaleString('id-ID')}`);
            return;
        }
    });

    function updatePaymentAmount() {
        // Finance edit page doesn't have payment amount input
        // but this function is called by updateGrandTotal
    }

    function updatePaymentStatus() {
        // Finance edit page doesn't have payment status auto-update logic like Create
        // but this function is called by updateGrandTotal
    }

    // === INISIALISASI AWAL ===
    updateGrandTotal();
    if (paymentMethod) paymentMethod.dispatchEvent(new Event('change'));
// Initialize supplier section based on add_to_purchase value
if ({{ $salesOrder->add_to_purchase ? 'true' : 'false' }}) {
    supplierSection.classList.remove('hidden');
    document.getElementById('supplier_display_name').textContent = 'Pre-order Customer';
    selectedSupplierDiv.classList.remove('hidden');
}
});
</script>
</body>
</html>