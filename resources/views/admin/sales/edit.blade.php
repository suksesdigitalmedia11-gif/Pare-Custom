<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Sales Order - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Raleway', sans-serif;
        }
        
        .form-section {
            transition: all 0.3s ease;
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
        }
        
        .form-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .autocomplete-dropdown {
            max-height: 200px;
            overflow-y: auto;
            z-index: 50;
        }
        
        .item-row {
            transition: all 0.2s ease;
        }
        
        .item-row:hover {
            background-color: #f8fafc;
        }
        
        .summary-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
        }
        
        .step-indicator {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }
        
        .step-active {
            background: #3b82f6;
            color: white;
        }
        
        .step-inactive {
            background: #e5e7eb;
            color: #6b7280;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-weight: 500;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <x-navbar-admin />
        
        <!-- Main Content -->
        <div class="flex-1">
            <x-navbar-top-admin />
            
            <div class="p-4 lg:p-6">
                <!-- Header -->
                <div class="mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="mb-4 sm:mb-0">
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Edit Sales Order</h1>
                            <div class="flex items-center space-x-4 mt-2">
                                <span class="text-gray-600">SO Number: <strong>{{ $salesOrder->so_number }}</strong></span>
                                <span class="status-badge 
                                    @if($salesOrder->status === 'selesai') bg-green-100 text-green-800
                                    @elseif($salesOrder->status === 'payment') bg-yellow-100 text-yellow-800
                                    @elseif($salesOrder->status === 'pending') bg-blue-100 text-blue-800
                                    @elseif($salesOrder->status === 'draft') bg-gray-100 text-gray-800
                                    @else bg-orange-100 text-orange-800 @endif">
                                    {{ ucfirst(str_replace('_', ' ', $salesOrder->status)) }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Progress Steps -->
                        <div class="flex items-center space-x-4 text-sm">
                            <div class="flex items-center space-x-2">
                                <div class="step-indicator step-active">1</div>
                                <span class="text-blue-600 font-medium">Informasi Order</span>
                            </div>
                            <div class="w-8 h-0.5 bg-gray-300"></div>
                        </div>
                    </div>
                </div>

                <!-- Error Messages -->
                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                        <div class="flex items-center">
                            <i class="bi bi-exclamation-triangle-fill text-red-500 mr-3"></i>
                            <div>
                                <h3 class="text-red-800 font-medium">Terjadi kesalahan</h3>
                                <ul class="text-red-700 text-sm mt-1 list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                        <div class="flex items-center">
                            <i class="bi bi-exclamation-circle-fill text-red-500 mr-3"></i>
                            <span class="text-red-700">{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                <!-- Payment Info Banner -->
                @if($salesOrder->payments->count() > 0)
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                    <div class="flex items-center">
                        <i class="bi bi-info-circle-fill text-blue-500 mr-3 text-lg"></i>
                        <div class="flex-1">
                            <h3 class="text-blue-800 font-medium">Informasi Pembayaran</h3>
                            <p class="text-blue-700 text-sm mt-1">
                                Sales order ini sudah memiliki {{ $salesOrder->payments->count() }} pembayaran.
                                Untuk menambah/mengubah pembayaran, gunakan tombol <strong>"Tambah Pembayaran"</strong> di halaman detail.
                            </p>
                            <div class="mt-2 text-sm text-blue-800">
                                <span class="font-medium">Total Dibayar: Rp {{ number_format($salesOrder->paid_total, 0, ',', '.') }}</span> |
                                <span class="font-medium">Sisa: Rp {{ number_format($salesOrder->remaining_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <form action="{{ route('admin.sales.update', $salesOrder) }}" method="POST" id="soForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Hidden status -->
                    <input type="hidden" name="status" value="{{ $salesOrder->status }}">

                    <!-- Step 1: Order Information -->
                    <div class="form-section">
                        <div class="form-card p-6 mb-6">
                            <div class="flex items-center mb-6">
                                <div class="w-1 h-8 bg-blue-600 rounded-full mr-4"></div>
                                <h2 class="text-xl font-semibold text-gray-900">Informasi Order</h2>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Order Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-3">Tipe Order</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="relative flex cursor-pointer">
                                            <input type="radio" name="order_type" value="jahit_sendiri" 
                                                {{ $salesOrder->order_type == 'jahit_sendiri' ? 'checked' : '' }}
                                                class="peer sr-only">
                                            <div class="flex items-center justify-center w-full p-4 border-2 border-gray-200 rounded-xl text-gray-600 hover:border-gray-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 transition-all">
                                                <i class="bi bi-scissors mr-2"></i>
                                                <span>Jahit Sendiri</span>
                                            </div>
                                        </label>
                                        <label class="relative flex cursor-pointer">
                                            <input type="radio" name="order_type" value="beli_jadi" 
                                                {{ $salesOrder->order_type == 'beli_jadi' ? 'checked' : '' }}
                                                class="peer sr-only">
                                            <div class="flex items-center justify-center w-full p-4 border-2 border-gray-200 rounded-xl text-gray-600 hover:border-gray-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 transition-all">
                                                <i class="bi bi-bag-check mr-2"></i>
                                                <span>Beli Jadi</span>
                                            </div>
                                        </label>
                                    </div>
                                    @error('order_type')
                                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Order Date -->
                                <div>
                                    <label for="order_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Order</label>
                                    <input type="datetime-local" name="order_date" id="order_date"
                                        value="{{ old('order_date', $salesOrder->order_date->format('Y-m-d\TH:i')) }}" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                    @error('order_date')
                                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Deadline -->
                                <div>
                                    <label for="deadline" class="block text-sm font-medium text-gray-700 mb-2">
                                        Deadline Selesai
                                        <span class="text-gray-500 font-normal">(Opsional)</span>
                                    </label>
                                    <input type="date" name="deadline" id="deadline" 
                                        value="{{ old('deadline', $salesOrder->deadline ? $salesOrder->deadline->format('Y-m-d') : '') }}" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                    @error('deadline')
                                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Customer Search -->
                                <div class="relative">
                                    <label for="customer_search" class="block text-sm font-medium text-gray-700 mb-2">
                                        Customer
                                        <span class="text-gray-500 font-normal">(Opsional)</span>
                                    </label>
                                    
                                    <input type="text" 
                                        id="customer_search"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        placeholder="Cari customer..."
                                        autocomplete="off"
                                        value="{{ old('customer_name', $salesOrder->customer ? $salesOrder->customer->name : '') }}">

                                    <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id', $salesOrder->customer_id) }}">
                                    <input type="hidden" name="customer_name" id="customer_name" value="{{ old('customer_name', $salesOrder->customer ? $salesOrder->customer->name : '') }}">
                                    <input type="hidden" name="customer_phone" id="customer_phone" value="{{ old('customer_phone', $salesOrder->customer ? $salesOrder->customer->phone : '') }}">

                                    <div id="customer_autocomplete_results" 
                                        class="autocomplete-dropdown hidden absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg">
                                    </div>

                                    <!-- Selected Customer -->
                                    <div id="selected_customer" class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-xl {{ $salesOrder->customer || old('customer_id') ? '' : 'hidden' }}">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span id="customer_display_name" class="font-medium text-blue-900">
                                                    {{ old('customer_name', $salesOrder->customer ? $salesOrder->customer->name : '') }}
                                                </span>
                                                <span id="customer_display_phone" class="text-sm text-blue-700 ml-2">
                                                    {{ ($salesOrder->customer && $salesOrder->customer->phone) || old('customer_phone') ? '(' . old('customer_phone', $salesOrder->customer ? $salesOrder->customer->phone : '') . ')' : '' }}
                                                </span>
                                            </div>
                                            <button type="button" id="clear_customer" class="text-red-500 hover:text-red-700 transition-colors">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- New Customer Fields -->
                                    <div id="new_customer_fields" class="mt-3 p-4 bg-gray-50 border border-gray-200 rounded-xl hidden">
                                        <p class="text-sm font-medium text-gray-700 mb-3">Buat Customer Baru</p>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm text-gray-600 mb-2">Nama Customer</label>
                                                <input type="text" id="new_customer_name" 
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                    placeholder="Nama customer">
                                            </div>
                                            <div>
                                                <label class="block text-sm text-gray-600 mb-2">Nomor Telepon</label>
                                                <input type="text" id="new_customer_phone" 
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                    placeholder="08123456789">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Method -->
                                <div>
                                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">Metode Pembayaran</label>
                                    <select name="payment_method" id="payment_method" {{ $salesOrder->status === 'draft' ? '' : 'required' }}
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                        <option value="cash" {{ old('payment_method', $salesOrder->payment_method) == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="transfer" {{ old('payment_method', $salesOrder->payment_method) == 'transfer' ? 'selected' : '' }}>Transfer</option>
                                        <option value="split" {{ old('payment_method', $salesOrder->payment_method) == 'split' ? 'selected' : '' }}>Split Payment</option>
                                    </select>
                                    @error('payment_method')
                                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Payment Status -->
                                <div>
                                    <label for="payment_status" class="block text-sm font-medium text-gray-700 mb-2">Status Pembayaran</label>
                                    <select name="payment_status" id="payment_status" {{ $salesOrder->status === 'draft' ? '' : 'required' }}
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                        <option value="dp" {{ old('payment_status', $salesOrder->payment_status) == 'dp' ? 'selected' : '' }}>DP</option>
                                        <option value="lunas" {{ old('payment_status', $salesOrder->payment_status) == 'lunas' ? 'selected' : '' }}>Lunas</option>
                                    </select>
                                    @error('payment_status')
                                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

<!-- Pre-order Section -->
<div class="mt-6 p-4 bg-orange-50 border border-orange-200 rounded-xl">
    <label class="flex items-center cursor-pointer">
        <input type="hidden" name="add_to_purchase" value="0">
        <input type="checkbox" name="add_to_purchase" id="add_to_purchase" value="1"
            {{ $salesOrder->add_to_purchase ? 'checked' : '' }}
            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
        <span class="ml-3 text-sm font-medium text-orange-800">
            <i class="bi bi-info-circle mr-1"></i>
            Masukkan ke Pembelian (Pre-order)
        </span>
    </label>

    <div id="supplier-section" class="mt-4 {{ $salesOrder->add_to_purchase ? '' : 'hidden' }}">
        <label for="supplier_search" class="block text-sm font-medium text-orange-700 mb-2">
            Supplier untuk Pembelian
        </label>
        <input type="text" id="supplier_search"
            class="w-full px-4 py-2 border border-orange-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
            placeholder="Ketik nama supplier..."
            autocomplete="off"
            value="{{ old('supplier_name', $selectedSupplier ? $selectedSupplier->name : ($salesOrder->add_to_purchase ? 'Pre-order Customer' : '')) }}">

        <input type="hidden" name="supplier_id" id="supplier_id" value="{{ $selectedSupplier ? $selectedSupplier->id : '' }}">
        <input type="hidden" name="supplier_name" id="supplier_name" value="{{ old('supplier_name', $selectedSupplier ? $selectedSupplier->name : ($salesOrder->add_to_purchase ? 'Pre-order Customer' : '')) }}">

        <div id="supplier_dropdown"
            class="autocomplete-dropdown hidden absolute z-40 w-full mt-1 bg-white border border-orange-200 rounded-lg shadow-lg">
            @foreach($suppliers as $supplier)
                <div class="p-3 hover:bg-orange-50 cursor-pointer supplier-option border-b border-orange-100"
                    data-id="{{ $supplier->id }}" data-name="{{ $supplier->name }}">
                    <div class="font-medium text-gray-900">{{ $supplier->name }}</div>
                    @if($supplier->contact_name || $supplier->phone)
                    <div class="text-sm text-gray-600 mt-1">
                        {{ $supplier->contact_name ? $supplier->contact_name : '' }}
                        {{ $supplier->phone ? ' | ' . $supplier->phone : '' }}
                    </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- ✅ TAMPILKAN SUPPLIER YANG SUDAH DIPILIH -->
        <div id="selected_supplier" class="mt-2 p-3 bg-orange-100 border border-orange-300 rounded-lg {{ $selectedSupplier || $salesOrder->add_to_purchase ? '' : 'hidden' }}">
            <div class="flex items-center justify-between">
                <span id="supplier_display_name" class="font-medium text-orange-900">
                    {{ $selectedSupplier ? $selectedSupplier->name : 'Pre-order Customer' }}
                </span>
                <button type="button" id="clear_supplier" class="text-orange-600 hover:text-orange-800 transition-colors">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            @if($selectedSupplier)
            <div class="text-xs text-orange-700 mt-1">
                Supplier terkait dengan Purchase Order
            </div>
            @endif
        </div>
    </div>
</div>
                        </div>
                    </div>

                    <!-- Step 2: Items & Summary -->
                    <div class="form-section">
                        <!-- Items Section -->
                        <div class="form-card p-6 mb-6">
                            <div class="flex items-center mb-6">
                                <div class="w-1 h-8 bg-blue-600 rounded-full mr-4"></div>
                                <h2 class="text-xl font-semibold text-gray-900">Item Order</h2>
                            </div>

                            <div id="items-container" class="space-y-4">
                                @foreach($salesOrder->items as $index => $item)
                                <div class="item-row grid grid-cols-1 lg:grid-cols-12 gap-4 p-4 border border-gray-200 rounded-xl">
                                    <div class="lg:col-span-6">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Produk</label>
                                        <div class="relative">
                                            <input type="text"
                                                class="product-search w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                                placeholder="Ketik nama produk..." 
                                                autocomplete="off"
                                                value="{{ $item->product_name }}">
                                            <input type="hidden" name="items[{{ $index }}][product_id]" class="product-id" value="{{ $item->product_id }}">
                                            <input type="hidden" name="items[{{ $index }}][product_name]" class="product-name" value="{{ $item->product_name }}">
                                            <input type="hidden" name="items[{{ $index }}][sku]" class="sku" value="{{ $item->sku }}">
                                            <div class="product-results autocomplete-dropdown hidden absolute z-40 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg"></div>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-3">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Harga</label>
                                        <input type="number" name="items[{{ $index }}][sale_price]"
                                            class="sale-price w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                            step="0.01" required value="{{ $item->sale_price }}"
                                            placeholder="0">
                                        @error('items.'.$index.'.sale_price')
                                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="lg:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Qty</label>
                                        <input type="number" name="items[{{ $index }}][qty]"
                                            class="qty w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                            min="1" required value="{{ $item->qty }}"
                                            placeholder="1">
                                        @error('items.'.$index.'.qty')
                                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="lg:col-span-1 flex items-end">
                                        <button type="button" class="remove-item w-full py-3 text-red-500 hover:text-red-700 transition-colors {{ $loop->first ? 'disabled:opacity-50 disabled:cursor-not-allowed' : '' }}"
                                                {{ $loop->first ? 'disabled' : '' }}>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="mt-4">
                                <button type="button" id="add-item"
                                    class="flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl transition-colors">
                                    <i class="bi bi-plus-circle"></i>
                                    <span>Tambah Item</span>
                                </button>
                            </div>
                        </div>

                        <!-- Summary Section -->
                        <div class="summary-card p-6 rounded-xl mb-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Ringkasan Order</h2>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                <div class="text-center">
                                    <label class="block text-sm font-medium text-gray-600 mb-2">Subtotal</label>
                                    <div id="display-subtotal" class="text-2xl font-bold text-gray-900">Rp {{ number_format($salesOrder->subtotal, 0, ',', '.') }}</div>
                                </div>
                                <div>
                                    <label for="discount_total" class="block text-sm font-medium text-gray-600 mb-2">Diskon Total</label>
                                    <input type="number" name="discount_total" id="discount_total" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        min="0" step="0.01" value="{{ old('discount_total', $salesOrder->discount_total) }}"
                                        placeholder="0">
                                    @error('discount_total')
                                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="shipping_cost" class="block text-sm font-medium text-gray-600 mb-2">Ongkir</label>
                                    <input type="number" name="shipping_cost" id="shipping_cost" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        min="0" step="0.01" value="{{ old('shipping_cost', $salesOrder->shipping_cost) }}"
                                        placeholder="0">
                                    @error('shipping_cost')
                                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="text-center">
                                    <label class="block text-sm font-medium text-gray-600 mb-2">Grand Total</label>
                                    <div id="display-grand-total" class="text-2xl font-bold text-blue-600">Rp {{ number_format($salesOrder->grand_total, 0, ',', '.') }}</div>
                                    <input type="hidden" name="grand_total" id="grand_total" value="{{ $salesOrder->grand_total }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button type="submit" 
                            class="flex-1 flex items-center justify-center space-x-2 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white px-8 py-4 rounded-xl shadow-lg transition-all font-semibold">
                            <i class="bi bi-check-circle"></i>
                            <span>Simpan Perubahan</span>
                        </button>
                        <a href="{{ route('admin.sales.show', $salesOrder) }}" 
                            class="flex-1 flex items-center justify-center space-x-2 bg-gray-600 hover:bg-gray-700 text-white px-8 py-4 rounded-xl shadow-lg transition-all font-semibold">
                            <i class="bi bi-arrow-left"></i>
                            <span>Kembali ke Detail</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // index awal untuk item baru
        let itemIndex = {{ count($salesOrder->items) }};
        let grandTotal = {{ $salesOrder->grand_total }};

        const soForm = document.getElementById('soForm');
        const itemsContainer = document.getElementById('items-container');
        const paymentMethod = document.getElementById('payment_method');
        const paymentStatus = document.getElementById('payment_status');

        // Pastikan ada hidden grand_total untuk dikirim ke server
        if (soForm && !document.getElementById('grand_total')) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'grand_total';
            hidden.id = 'grand_total';
            hidden.value = grandTotal.toFixed(2);
            soForm.appendChild(hidden);
        }

        // === FUNGSI UTAMA: HITUNG GRAND TOTAL ===
        function updateGrandTotal() {
            const rows = document.querySelectorAll('.item-row');
            let subtotal = 0;

            // Hitung subtotal dari semua items
            rows.forEach(row => {
                const price = parseFloat(row.querySelector('.sale-price').value) || 0;
                const qty = parseInt(row.querySelector('.qty').value) || 0;
                subtotal += price * qty;
            });

            const discountTotal = parseFloat(document.getElementById('discount_total').value) || 0;
            const shippingCost = parseFloat(document.getElementById('shipping_cost').value) || 0;
            
            grandTotal = Math.max(0, subtotal - discountTotal + shippingCost);

            // Update display
            document.getElementById('display-subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
            document.getElementById('display-grand-total').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
            document.getElementById('grand_total').value = grandTotal.toFixed(2);
        }

        // Event listener untuk discount total dan shipping cost
        document.getElementById('shipping_cost').addEventListener('input', updateGrandTotal);
        document.getElementById('discount_total').addEventListener('input', updateGrandTotal);

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
                fetch(`/admin/products/search?q=${encodeURIComponent(searchTerm)}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network error');
                        return response.json();
                    })
                    .then(products => {
                        productResults.innerHTML = '';

                        if (products.length === 0) {
                            const noResult = document.createElement('div');
                            noResult.className = 'p-4 text-gray-500 text-center';
                            noResult.textContent = 'Produk tidak ditemukan';
                            productResults.appendChild(noResult);
                            productResults.classList.remove('hidden');
                            return;
                        }

                        products.forEach(product => {
                            const div = document.createElement('div');
                            div.className = 'p-3 hover:bg-blue-50 cursor-pointer product-option border-b border-gray-100';
                            div.innerHTML = `
                                <div class="font-medium text-gray-900">${product.name}</div>
                                <div class="text-sm text-gray-600 mt-1">
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

                                // Update grand total
                                updateGrandTotal();
                            });

                            productResults.appendChild(div);
                        });

                        productResults.classList.remove('hidden');
                    })
                    .catch(error => {
                        console.error('Error searching products:', error);
                        productResults.innerHTML = '<div class="p-4 text-red-500 text-center">Error loading products</div>';
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

            // Update total ketika harga/qty berubah
            const salePriceInput = row.querySelector('.sale-price');
            const qtyInput = row.querySelector('.qty');

            if (salePriceInput) {
                salePriceInput.addEventListener('input', updateGrandTotal);
            }
            if (qtyInput) {
                qtyInput.addEventListener('input', updateGrandTotal);
            }
        }

        // --- Event listener untuk perubahan harga, qty ---
        itemsContainer.addEventListener('input', function (e) {
            const target = e.target;
            if (target.classList.contains('qty') || target.classList.contains('sale-price')) {
                updateGrandTotal();
            }
        });

        // --- Add item dengan template SEARCH INPUT ---
        document.getElementById('add-item').addEventListener('click', function () {
            const newRow = document.createElement('div');
            newRow.className = 'item-row grid grid-cols-1 lg:grid-cols-12 gap-4 p-4 border border-gray-200 rounded-xl';
            newRow.innerHTML = `
                <div class="lg:col-span-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Produk</label>
                    <div class="relative">
                        <input type="text" 
                               class="product-search w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                               placeholder="Ketik nama produk..."
                               autocomplete="off">
                        <input type="hidden" name="items[${itemIndex}][product_id]" class="product-id">
                        <input type="hidden" name="items[${itemIndex}][product_name]" class="product-name">
                        <input type="hidden" name="items[${itemIndex}][sku]" class="sku">
                        <div class="product-results autocomplete-dropdown hidden absolute z-40 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg"></div>
                    </div>
                </div>
                <div class="lg:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Harga</label>
                    <input type="number" name="items[${itemIndex}][sale_price]" 
                           class="sale-price w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           step="0.01" required
                           placeholder="0">
                </div>
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Qty</label>
                    <input type="number" name="items[${itemIndex}][qty]" 
                           class="qty w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           min="1" value="1" required
                           placeholder="1">
                </div>
                <div class="lg:col-span-1 flex items-end">
                    <button type="button" class="remove-item w-full py-3 text-red-500 hover:text-red-700 transition-colors">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            itemsContainer.appendChild(newRow);
            itemIndex++;

            // Initialize product search untuk row baru
            setTimeout(() => {
                initializeProductSearch(newRow);
            }, 100);

            // Update remove button states
            updateRemoveButtons();
        });

        // --- Remove item ---
        function updateRemoveButtons() {
            const removeButtons = document.querySelectorAll('.remove-item');
            const rows = document.querySelectorAll('.item-row');
            
            removeButtons.forEach((btn, index) => {
                if (rows.length > 1) {
                    btn.disabled = false;
                    btn.classList.remove('disabled:opacity-50', 'disabled:cursor-not-allowed');
                } else {
                    btn.disabled = true;
                    btn.classList.add('disabled:opacity-50', 'disabled:cursor-not-allowed');
                }
            });
        }

        itemsContainer.addEventListener('click', function (e) {
            const btn = e.target.closest('.remove-item');
            if (btn && !btn.disabled) {
                const rows = document.querySelectorAll('.item-row');
                if (rows.length > 1) {
                    btn.closest('.item-row').remove();
                    updateGrandTotal();
                    updateRemoveButtons();
                    reindexItems();
                }
            }
        });

        // === FUNGSI REINDEX ITEMS SETELAH HAPUS ===
        function reindexItems() {
            const rows = document.querySelectorAll('.item-row');
            rows.forEach((row, index) => {
                // Update semua input names
                const inputs = row.querySelectorAll('input');
                inputs.forEach(input => {
                    const name = input.getAttribute('name');
                    if (name && name.includes('items[')) {
                        const newName = name.replace(/items\[\d+\]/, `items[${index}]`);
                        input.setAttribute('name', newName);
                    }
                });
            });
            itemIndex = rows.length;
        }

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

        let customerSearchTimeout = null;

        // Real-time search dengan debounce
        if (customerSearch) {
            customerSearch.addEventListener('input', function () {
                const searchTerm = this.value.trim();
                
                // Clear previous timeout
                if (customerSearchTimeout) {
                    clearTimeout(customerSearchTimeout);
                }
                
                // Hide results if search term is too short
                if (searchTerm.length < 2) {
                    if (customerResults) customerResults.classList.add('hidden');
                    checkNewCustomerFields(searchTerm);
                    return;
                }
                
                // Debounce search - wait 300ms after user stops typing
                customerSearchTimeout = setTimeout(() => {
                    searchCustomers(searchTerm);
                }, 300);
            });
        }

        // Function untuk search customers via AJAX
        function searchCustomers(searchTerm) {
            fetch(`/admin/customers/search?q=${encodeURIComponent(searchTerm)}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(customers => {
                    if (!customerResults) return;
                    
                    customerResults.innerHTML = '';
                    
                    if (customers.length === 0) {
                        // No existing customers found
                        const noResult = document.createElement('div');
                        noResult.className = 'p-4 text-gray-500 text-center';
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
                        div.className = 'p-3 hover:bg-blue-50 cursor-pointer customer-option border-b border-gray-100';
                        div.innerHTML = `
                            <div class="font-medium text-gray-900">${customer.name}</div>
                            <div class="text-sm text-gray-600 mt-1">${customer.phone ? `Telp: ${customer.phone}` : 'No telepon'}</div>
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
                    if (newCustomerFields) newCustomerFields.classList.add('hidden');
                })
                .catch(error => {
                    console.error('Error searching customers:', error);
                    if (customerResults) {
                        customerResults.innerHTML = '<div class="p-4 text-red-500 text-center">Error loading customers</div>';
                        customerResults.classList.remove('hidden');
                    }
                });
        }

        // Function untuk select customer
        function selectCustomer(id, name, phone) {
            if (customerIdInput) customerIdInput.value = id;
            if (customerNameInput) customerNameInput.value = name;
            if (customerPhoneInput) customerPhoneInput.value = phone;
            
            // Update display
            if (document.getElementById('customer_display_name')) {
                document.getElementById('customer_display_name').textContent = name;
            }
            if (document.getElementById('customer_display_phone')) {
                document.getElementById('customer_display_phone').textContent = phone ? `(${phone})` : '';
            }
            if (selectedCustomerDiv) selectedCustomerDiv.classList.remove('hidden');
            
            // Clear search and hide results
            if (customerSearch) customerSearch.value = '';
            if (customerResults) customerResults.classList.add('hidden');
            if (newCustomerFields) newCustomerFields.classList.add('hidden');
        }

        // Function untuk check if we should show new customer fields
        function checkNewCustomerFields(searchTerm) {
            if (searchTerm.length >= 3 && customerIdInput && !customerIdInput.value) {
                if (newCustomerName) newCustomerName.value = searchTerm;
                if (customerNameInput) customerNameInput.value = searchTerm;
                if (newCustomerFields) newCustomerFields.classList.remove('hidden');
            } else {
                if (newCustomerFields) newCustomerFields.classList.add('hidden');
            }
        }

        // Clear customer selection
        if (document.getElementById('clear_customer')) {
            document.getElementById('clear_customer').addEventListener('click', function () {
                if (customerIdInput) customerIdInput.value = '';
                if (customerNameInput) customerNameInput.value = '';
                if (customerPhoneInput) customerPhoneInput.value = '';
                if (selectedCustomerDiv) selectedCustomerDiv.classList.add('hidden');
                if (newCustomerFields) newCustomerFields.classList.add('hidden');
                if (customerSearch) customerSearch.value = '';
                if (newCustomerName) newCustomerName.value = '';
                if (newCustomerPhone) newCustomerPhone.value = '';
                if (customerResults) customerResults.classList.add('hidden');
            });
        }

        // Sync new customer fields
        if (newCustomerName) {
            newCustomerName.addEventListener('input', function () {
                if (customerNameInput) customerNameInput.value = this.value;
            });
        }

        if (newCustomerPhone) {
            newCustomerPhone.addEventListener('input', function () {
                if (customerPhoneInput) customerPhoneInput.value = this.value;
            });
        }

        // Hide dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.relative')) {
                if (customerResults) customerResults.classList.add('hidden');
            }
        });

        // Show dropdown when focusing on search
        if (customerSearch) {
            customerSearch.addEventListener('focus', function () {
                if (this.value.trim().length >= 3) {
                    this.dispatchEvent(new Event('input'));
                }
            });
        }

        // === SUPPLIER SEARCH LOGIC ===
if (document.getElementById('add_to_purchase')) {
    document.getElementById('add_to_purchase').addEventListener('change', function() {
        const supplierSection = document.getElementById('supplier-section');
        if (supplierSection) {
            supplierSection.classList.toggle('hidden', !this.checked);
            
            if (this.checked) {
                // ✅ JANGAN OTOMATIS SET "Pre-order Customer" JIKA SUDAH ADA SUPPLIER
                const supplierIdInput = document.getElementById('supplier_id');
                const supplierNameInput = document.getElementById('supplier_name');
                const selectedSupplierDiv = document.getElementById('selected_supplier');
                
                // Cek apakah sudah ada supplier yang dipilih
                if (supplierIdInput && supplierIdInput.value === '' && supplierNameInput && supplierNameInput.value === '') {
                    // Hanya set default jika benar-benar kosong
                    supplierNameInput.value = 'Pre-order Customer';
                    if (document.getElementById('supplier_display_name')) {
                        document.getElementById('supplier_display_name').textContent = 'Pre-order Customer';
                    }
                    if (selectedSupplierDiv) selectedSupplierDiv.classList.remove('hidden');
                } else if (supplierIdInput && supplierIdInput.value !== '') {
                    // Jika sudah ada supplier_id, pastikan selected supplier ditampilkan
                    if (selectedSupplierDiv) selectedSupplierDiv.classList.remove('hidden');
                }
            }
        }
    });
}

        const supplierSearch = document.getElementById('supplier_search');
        const supplierDropdown = document.getElementById('supplier_dropdown');
        const supplierIdInput = document.getElementById('supplier_id');
        const supplierNameInput = document.getElementById('supplier_name');
        const selectedSupplierDiv = document.getElementById('selected_supplier');

        let supplierSearchTimeout = null;

        // Real-time search dengan debounce
        if (supplierSearch) {
            supplierSearch.addEventListener('input', function() {
                const searchTerm = this.value.trim();
                
                // Clear previous timeout
                if (supplierSearchTimeout) {
                    clearTimeout(supplierSearchTimeout);
                }
                
                // Hide results if search term is too short
                if (searchTerm.length < 2) {
                    if (supplierDropdown) supplierDropdown.classList.add('hidden');
                    return;
                }
                
                // Debounce search - wait 300ms after user stops typing
                supplierSearchTimeout = setTimeout(() => {
                    searchSuppliers(searchTerm);
                }, 300);
            });
        }

        // Function untuk search suppliers via AJAX
        function searchSuppliers(searchTerm) {
            fetch(`/admin/suppliers/search?q=${encodeURIComponent(searchTerm)}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(suppliers => {
                    if (!supplierDropdown) return;
                    
                    supplierDropdown.innerHTML = '';
                    
                    if (suppliers.length === 0) {
                        const noResult = document.createElement('div');
                        noResult.className = 'p-4 text-gray-500 text-center';
                        noResult.textContent = 'Supplier tidak ditemukan';
                        supplierDropdown.appendChild(noResult);
                        supplierDropdown.classList.remove('hidden');
                        return;
                    }
                    
                    // Show matching suppliers
                    suppliers.forEach(supplier => {
                        const div = document.createElement('div');
                        div.className = 'p-3 hover:bg-orange-50 cursor-pointer supplier-option border-b border-orange-100';
                        div.innerHTML = `
                            <div class="font-medium text-gray-900">${supplier.name}</div>
                            <div class="text-sm text-gray-600 mt-1">
                                ${supplier.contact_name ? `Contact: ${supplier.contact_name}` : ''}
                                ${supplier.phone ? ` | Telp: ${supplier.phone}` : ''}
                            </div>
                        `;
                        div.setAttribute('data-id', supplier.id);
                        div.setAttribute('data-name', supplier.name);
                        
                        div.addEventListener('click', function() {
                            selectSupplier(supplier.id, supplier.name);
                        });
                        
                        supplierDropdown.appendChild(div);
                    });
                    
                    supplierDropdown.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error searching suppliers:', error);
                    if (supplierDropdown) {
                        supplierDropdown.innerHTML = '<div class="p-4 text-red-500 text-center">Error loading suppliers</div>';
                        supplierDropdown.classList.remove('hidden');
                    }
                });
        }

        // Function untuk select supplier
        function selectSupplier(id, name) {
            if (supplierIdInput) supplierIdInput.value = id;
            if (supplierNameInput) supplierNameInput.value = name;
            
            // Update display
            if (document.getElementById('supplier_display_name')) {
                document.getElementById('supplier_display_name').textContent = name;
            }
            if (selectedSupplierDiv) selectedSupplierDiv.classList.remove('hidden');
            
            // Clear search and hide results
            if (supplierSearch) supplierSearch.value = '';
            if (supplierDropdown) supplierDropdown.classList.add('hidden');
        }

        // Clear supplier selection
        if (document.getElementById('clear_supplier')) {
            document.getElementById('clear_supplier').addEventListener('click', function() {
                if (supplierIdInput) supplierIdInput.value = '';
                if (supplierNameInput) supplierNameInput.value = 'Pre-order Customer';
                if (selectedSupplierDiv) selectedSupplierDiv.classList.add('hidden');
                if (supplierSearch) supplierSearch.value = '';
                if (supplierDropdown) supplierDropdown.classList.add('hidden');
            });
        }

        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#supplier-section')) {
                if (supplierDropdown) supplierDropdown.classList.add('hidden');
            }
        });

        // Show dropdown when focusing on search
        if (supplierSearch) {
            supplierSearch.addEventListener('focus', function() {
                if (this.value.trim().length >= 2) {
                    this.dispatchEvent(new Event('input'));
                }
            });
        }

        // Handle manual input untuk create supplier baru
        if (supplierSearch) {
            supplierSearch.addEventListener('input', function() {
                const searchTerm = this.value.trim();
                if (searchTerm.length > 0 && supplierNameInput) {
                    // Jika user mengetik manual, set supplier_name tapi biarkan supplier_id kosong
                    supplierNameInput.value = searchTerm;
                    if (supplierIdInput) supplierIdInput.value = '';
                }
            });
        }
        // Clear supplier selection - MODIFIKASI
if (document.getElementById('clear_supplier')) {
    document.getElementById('clear_supplier').addEventListener('click', function() {
        const supplierIdInput = document.getElementById('supplier_id');
        const supplierNameInput = document.getElementById('supplier_name');
        const selectedSupplierDiv = document.getElementById('selected_supplier');
        
        if (supplierIdInput) supplierIdInput.value = '';
        
        // ✅ JANGAN OTOMATIS SET "Pre-order Customer" - biarkan kosong
        if (supplierNameInput) supplierNameInput.value = '';
        
        if (selectedSupplierDiv) selectedSupplierDiv.classList.add('hidden');
        if (supplierSearch) supplierSearch.value = '';
        if (supplierDropdown) supplierDropdown.classList.add('hidden');
    });
}

        // === SUBMIT VALIDATION ===
        if (soForm) {
            soForm.addEventListener('submit', function(e) {
                let hasErrors = false;
                const errorMessages = [];

                // Validasi harga produk > 0 untuk semua baris
                if (grandTotal < 0) {
                     hasErrors = true;
                     errorMessages.push('Total penjualan (Grand Total) tidak boleh kurang dari 0.');
                }
                
                const prices = document.querySelectorAll('.sale-price');
                prices.forEach((p, index) => {
                    const val = parseFloat(p.value);
                    if (val < 0) { // Hanya error jika MINUS, 0 BOLEH
                        hasErrors = true;
                        errorMessages.push(`Harga produk pada item ${index + 1} tidak boleh minus`);
                    }
                });

                // Validasi product name
                const productNames = document.querySelectorAll('.product-search');
                productNames.forEach((input, index) => {
                    if (!input.value.trim()) {
                        hasErrors = true;
                        errorMessages.push(`Nama produk pada item ${index + 1} tidak boleh kosong`);
                    }
                });

                // Validasi quantity
                const quantities = document.querySelectorAll('.qty');
                quantities.forEach((q, index) => {
                    if (!q.value || parseInt(q.value) <= 0) {
                        hasErrors = true;
                        errorMessages.push(`Quantity produk pada item ${index + 1} tidak boleh kosong atau nol`);
                    }
                });

                // Validasi minimal satu item
                const itemRows = document.querySelectorAll('.item-row');
                if (itemRows.length === 0) {
                    hasErrors = true;
                    errorMessages.push('Minimal satu item harus ditambahkan');
                }

                // Validasi grand total
                const grandTotal = parseFloat(document.getElementById('grand_total').value) || 0;
                if (grandTotal < 0) {
                    hasErrors = true;
                    errorMessages.push('Grand total tidak boleh kurang dari 0');
                }

                if (hasErrors) {
                    e.preventDefault();
                    const errorHtml = errorMessages.map(msg => `<li>${msg}</li>`).join('');
                    alert(`Terjadi kesalahan:\n${errorMessages.join('\n')}`);
                } else {
                    // Optional: Show loading state
                    const submitBtn = soForm.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="bi bi-hourglass-split mr-2"></i>Menyimpan...';
                        
                        // Reset setelah 10 detik (fallback)
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Simpan Perubahan';
                        }, 10000);
                    }
                }
            });
        }

        // === INISIALISASI AWAL ===
        
        // Initialize product search untuk semua row yang sudah ada
        document.querySelectorAll('.item-row').forEach(row => {
            initializeProductSearch(row);
        });

        // Initialize remove buttons state
        updateRemoveButtons();

        // Trigger initial calculation untuk existing items
        setTimeout(() => {
            updateGrandTotal();
        }, 500);

        // Add loading states untuk form submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('soForm');
            if (form) {
                form.addEventListener('submit', function() {
                    const submitButtons = this.querySelectorAll('button[type="submit"]');
                    submitButtons.forEach(btn => {
                        const originalText = btn.innerHTML;
                        btn.innerHTML = '<i class="bi bi-hourglass-split mr-2"></i>Memproses...';
                        btn.disabled = true;
                        
                        // Reset setelah 10 detik (fallback)
                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        }, 10000);
                    });
                });
            }
        });
    });
</script>
</body>
</html>