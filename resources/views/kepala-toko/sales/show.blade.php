<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Detail Sales Order - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Raleway', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
<div class="flex">
    <x-navbar-kepala-toko />
    <div class="flex-1 lg:w-5/6">
        <x-navbar-top-kepala-toko />
        <div class="p-4 lg:p-8">
            <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Detail Sales Order</h1>
                        <p class="text-sm text-gray-500 mt-1">SO Number: {{ $salesOrder->so_number }}</p>
                    </div>
                    <div class="flex space-x-2">
    <a href="{{ route('kepala-toko.sales.index', request()->query()) }}"
       class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded shadow">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>

    @if($salesOrder->status === 'draft' && $activeShift && Auth::user()->hasRole('kepala_toko'))
        <!-- Tombol Edit Draft -->
        <a href="{{ route('kepala-toko.sales.edit', $salesOrder) }}"
           class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded shadow">
            <i class="bi bi-pencil"></i> Edit Draft
        </a>

        <!-- Tombol Proses Draft -->
        <form action="{{ route('kepala-toko.sales.update', $salesOrder) }}" method="POST" style="display:inline;">
            @csrf
            @method('PUT')
            <input type="hidden" name="status" value="pending">
            <input type="hidden" name="add_to_purchase" value="{{ $salesOrder->add_to_purchase ? '1' : '0' }}">
            <input type="hidden" name="order_type" value="{{ $salesOrder->order_type }}">
            <input type="hidden" name="order_date" value="{{ $salesOrder->order_date->format('Y-m-d\TH:i') }}">
            <input type="hidden" name="deadline" value="{{ $salesOrder->deadline?->format('Y-m-d') }}">
            <input type="hidden" name="customer_id" value="{{ $salesOrder->customer_id }}">
            <input type="hidden" name="payment_method" value="{{ $salesOrder->payment_method ?? 'cash' }}">
            <input type="hidden" name="payment_status" value="{{ $salesOrder->payment_status ?? 'dp' }}">
            @foreach($salesOrder->items as $index => $item)
                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                <input type="hidden" name="items[{{ $index }}][product_name]" value="{{ $item->product_name }}">
                <input type="hidden" name="items[{{ $index }}][sku]" value="{{ $item->sku }}">
                <input type="hidden" name="items[{{ $index }}][sale_price]" value="{{ $item->sale_price }}">
                <input type="hidden" name="items[{{ $index }}][qty]" value="{{ $item->qty }}">
                <input type="hidden" name="items[{{ $index }}][discount]" value="{{ $item->discount }}">
            @endforeach
            <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow"
                    onclick="return confirm('Yakin ingin memproses draft ini? Ini akan mengubah status menjadi \"Pending\".')">
                <i class="bi bi-play-circle"></i> Proses Draft
            </button>
        </form>
    @elseif($salesOrder->isEditable() && $activeShift && Auth::user()->hasRole('kepala_toko'))
        <a href="{{ route('kepala-toko.sales.edit', $salesOrder) }}"
           class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded shadow">
            <i class="bi bi-pencil"></i> Edit
        </a>
    @endif

    @if($salesOrder->status === 'pending' && $salesOrder->approved_by === null && $activeShift && Auth::user()->hasRole('kepala_toko'))
        <form action="{{ route('kepala-toko.sales.approve', $salesOrder) }}" method="POST">
            @csrf
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
                <i class="bi bi-check-circle"></i> Approve
            </button>
        </form>
    @endif

    @php
        $userType = strtolower(Auth::user()->usertype ?? Auth::user()->role ?? '');
        $hasPO = $salesOrder->hasRelatedPO();
        $canPendingToRequestKain = in_array($userType, ['owner', 'kepala_toko', 'finance']);
        $canRequestKainToPayment = in_array($userType, ['finance', 'owner', 'kepala_toko']);
        $canPaymentToProsesJahit = in_array($userType, ['admin', 'finance', 'kepala_toko', 'owner']);
        $canProsesJahitToPrinting = in_array($userType, ['admin', 'finance', 'kepala_toko', 'owner']);
        $canPrintingToDiterimaToko = in_array($userType, ['admin', 'finance', 'kepala_toko', 'owner']);
        $canDiterimaTokoToSelesai = in_array($userType, ['admin', 'finance', 'kepala_toko', 'owner']);
        
        $paymentValid = true;
        if (in_array($salesOrder->payment_method, ['transfer', 'split'])) {
            $invalidPayments = $salesOrder->payments()
                ->whereNull('proof_path')
                ->where(function($q) {
                    $q->whereNull('reference_number')
                      ->orWhere('reference_number', '')
                      ->orWhere('reference_number', ' ')
                      ->orWhere('reference_number', 'null')
                      ->orWhere('reference_number', 'NULL');
                })
                ->count();
            $paymentValid = $invalidPayments == 0;
        }
    @endphp

    <!-- 🔘 ACTION BUTTONS: Inline & Consistent Sizing -->
    <!-- pending → request_kain -->
    @if($salesOrder->status === 'pending' && ($hasPO || $salesOrder->add_to_purchase || $salesOrder->order_type === 'jahit_sendiri') && $salesOrder->approved_by !== null && $salesOrder->paid_total > 0 && $paymentValid && $canPendingToRequestKain && $activeShift)
        <form action="{{ route('kepala-toko.sales.move-to-request-kain', $salesOrder) }}" method="POST">
            @csrf
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded shadow flex items-center gap-2">
                <i class="bi bi-play-circle-fill"></i> Request Kain
            </button>
        </form>
    @endif

    <!-- pending → selesai (Tanpa PO) -->
    @if($salesOrder->status === 'pending' && !$hasPO && !$salesOrder->add_to_purchase && $salesOrder->order_type !== 'jahit_sendiri' && $salesOrder->approved_by !== null && $salesOrder->remaining_amount == 0 && in_array($userType, ['admin', 'owner', 'finance', 'kepala_toko']) && $activeShift)
        <form action="{{ route('kepala-toko.sales.complete-without-po', $salesOrder) }}" method="POST">
            @csrf
            <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded shadow flex items-center gap-2">
                <i class="bi bi-check2-all"></i> Selesai (No PO)
            </button>
        </form>
    @endif

    <!-- request_kain → payment -->
    @if($salesOrder->status === 'request_kain' && $canRequestKainToPayment && $activeShift)
        <form action="{{ route('kepala-toko.sales.move-to-payment', $salesOrder) }}" method="POST">
            @csrf
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow flex items-center gap-2">
                <i class="bi bi-credit-card-2-front-fill"></i> Ubah ke Payment
            </button>
        </form>
    @endif

    <!-- payment → proses_jahit -->
    @if($salesOrder->status === 'payment' && $salesOrder->order_type === 'jahit_sendiri' && $canPaymentToProsesJahit && $activeShift)
        <form action="{{ route('kepala-toko.sales.process-jahit', $salesOrder) }}" method="POST">
            @csrf
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow flex items-center gap-2">
                <i class="bi bi-scissors"></i> Proses Jahit
            </button>
        </form>
    @endif

    <!-- proses_jahit → printing -->
    @if($salesOrder->status === 'proses_jahit' && $salesOrder->order_type === 'jahit_sendiri' && $canProsesJahitToPrinting && $activeShift)
        <form action="{{ route('kepala-toko.sales.mark-as-jadi', $salesOrder) }}" method="POST">
            @csrf
            <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white px-4 py-2 rounded shadow flex items-center gap-2">
                <i class="bi bi-printer-fill"></i> Tandai Printing
            </button>
        </form>
    @endif

    <!-- printing → diterima_toko -->
    @if((($salesOrder->order_type === 'jahit_sendiri' && $salesOrder->status === 'printing') || ($salesOrder->order_type === 'beli_jadi' && $salesOrder->status === 'payment')) && $canPrintingToDiterimaToko && $activeShift)
        <form action="{{ route('kepala-toko.sales.mark-as-diterima-toko', $salesOrder) }}" method="POST">
            @csrf
            <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded shadow flex items-center gap-2">
                <i class="bi bi-shop"></i> Diterima Toko
            </button>
        </form>
    @endif

    <!-- diterima_toko → selesai -->
    @if($salesOrder->status === 'diterima_toko' && $salesOrder->remaining_amount == 0 && $canDiterimaTokoToSelesai && $activeShift)
        <form action="{{ route('kepala-toko.sales.complete', $salesOrder) }}" method="POST">
            @csrf
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded shadow flex items-center gap-2">
                <i class="bi bi-check-all"></i> Selesaikan
            </button>
        </form>
    @endif
    
    </div> <!-- End flex space-x-2 -->
    </div> <!-- End flex justify-between -->
    </div> <!-- End Header Card -->
    
    <!-- ℹ️ Alerts Info Production (Moved below header) -->
    @if($salesOrder->status === 'pending' && $salesOrder->approved_by !== null)
        @if(in_array($salesOrder->payment_method, ['transfer', 'split']) && !$paymentValid)
            <div class="mt-6 flex items-start gap-2 text-amber-700 bg-amber-50 border border-amber-200 px-4 py-3 rounded-lg text-sm">
                <i class="bi bi-exclamation-triangle-fill mt-0.5 text-lg"></i>
                <div>
                    <strong class="font-semibold block mb-0.5">Menunggu Bukti Transfer</strong>
                    Lengkapi bukti pembayaran atau nomor referensi agar tombol proses muncul.
                </div>
            </div>
        @endif

        @if($salesOrder->order_type === 'jahit_sendiri' && !$hasPO && !$salesOrder->add_to_purchase)
            <div class="mt-6 flex items-start gap-2 text-rose-700 bg-rose-50 border border-rose-200 px-4 py-3 rounded-lg text-sm">
                <i class="bi bi-info-circle-fill mt-0.5 text-lg"></i>
                <div>
                    <strong class="font-semibold block mb-0.5">Alur Produksi (Tanpa PO)</strong>
                    Pesanan ini tidak terhubung ke PO. Anda menggunakan alur produksi manual.
                </div>
            </div>
        @endif
    @endif

    <!-- ✅ WORKFLOW CENTER: Compact & Clean UI (For Status Only) -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 mt-4">
        <!-- Header & Status -->
        <div class="px-4 py-3 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-lg">
            <h3 class="font-semibold text-gray-700 text-sm flex items-center">
                <i class="bi bi-gear-wide-connected mr-2 text-indigo-500"></i>
                Update Alur Kerja
            </h3>
            <span class="px-2.5 py-0.5 bg-indigo-100 text-indigo-700 text-xs font-bold rounded uppercase tracking-wide">
                {{ str_replace('_', ' ', $salesOrder->status) }}
            </span>
        </div>
    </div>

            <!-- Tambah di bagian atas show view: -->
@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
        {{ session('success') }}
    </div>
@endif

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 mb-6 flex items-center shadow-sm">
        <div class="bg-blue-600 p-2.5 rounded-lg mr-4">
            <i class="bi bi-cash-stack text-white text-xl"></i>
        </div>
        <div>
            <h3 class="font-bold text-blue-900 leading-tight">Manajemen Pembayaran</h3>
            <p class="text-xs text-blue-700 mt-1 font-medium italic">
                Pembayaran baru wajib ditambahkan melalui tombol <span class="uppercase tracking-wider font-bold underline">Tambah Pembayaran</span> di bagian bawah halaman ini.
            </p>
        </div>
    </div>

            @if(!$activeShift)
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
                    Shift belum dimulai. Anda tidak bisa menambah pembayaran atau melakukan aksi lain. Silakan mulai shift terlebih dahulu di <a href="{{ route('kepala-toko.shift.dashboard') }}" class="underline">Dashboard Shift</a>.
                </div>
            @endif

            @if($salesOrder->status === 'pending')
                @php
                    $insufficientStock = false;
                    $stockMessages = [];
                @endphp
                @foreach($salesOrder->items as $item)
                    @if($item->product_id)
                        @php
                            $product = \App\Models\Product::find($item->product_id);
                            if ($product && $product->stock_qty < $item->qty) {
                                $insufficientStock = true;
                                $stockMessages[] = 'Stok ' . $product->name . ' tidak cukup. Tersedia: ' . $product->stock_qty . ', Dibutuhkan: ' . $item->qty;
                            }
                        @endphp
                    @endif
                @endforeach
                @if($insufficientStock)
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
                        <h4 class="font-bold">Peringatan Stok!</h4>
                        <ul class="list-disc list-inside">
                            @foreach($stockMessages as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                        <p class="mt-2">Stok akan tetap diproses meski negatif saat mulai proses.</p>
                    </div>
                @endif
            @endif


            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <h4 class="font-bold">Terjadi kesalahan:</h4>
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h2 class="text-lg font-semibold mb-4 text-gray-800">Informasi Order</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between"><span class="text-gray-600">SO Number:</span><span class="font-mono font-semibold">{{ $salesOrder->so_number }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Tipe Order:</span><span class="capitalize">{{ str_replace('_', ' ', $salesOrder->order_type) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Tanggal Order:</span><span>{{ \Carbon\Carbon::parse($salesOrder->order_date)->format('d/m/Y') }}</span></div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Customer:</span>
                            <span>
                                {{ $salesOrder->customer ? $salesOrder->customer->name : 'Umum' }}
                                @if($salesOrder->customer && $salesOrder->customer->phone)
                                    <br><small class="text-gray-500">({{ $salesOrder->customer->phone }})</small>
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Deadline:</span>
                            <span>{{ $salesOrder->deadline ? \Carbon\Carbon::parse($salesOrder->deadline)->format('d/m/Y') : '-' }}</span>
                        </div>
                        <div class="flex justify-between"><span class="text-gray-600">Dibuat Oleh:</span><span>{{ $salesOrder->creator->name ?? 'System' }}</span></div>
                        @if($salesOrder->approved_by)
                            <div class="flex justify-between"><span class="text-gray-600">Disetujui Oleh:</span><span>{{ $salesOrder->approver->name ?? 'System' }}</span></div>
                        @endif
                        @if($salesOrder->approved_at)
                            <div class="flex justify-between"><span class="text-gray-600">Tanggal Approve:</span><span>{{ \Carbon\Carbon::parse($salesOrder->approved_at)->format('d/m/Y H:i') }}</span></div>
                        @endif
                        @if($salesOrder->completed_at)
                            <div class="flex justify-between"><span class="text-gray-600">Tanggal Selesai:</span><span>{{ \Carbon\Carbon::parse($salesOrder->completed_at)->format('d/m/Y H:i') }}</span></div>
                        @endif
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h2 class="text-lg font-semibold mb-4 text-gray-800">Informasi Pembayaran & Status</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between"><span class="text-gray-600">Status Order:</span><span class="px-2 py-1 rounded-full text-xs font-medium @if($salesOrder->status === 'selesai') bg-green-100 text-green-600 @elseif(in_array($salesOrder->status, ['request_kain', 'payment', 'proses_jahit', 'printing', 'diterima_toko'])) bg-yellow-100 text-yellow-600 @else bg-blue-100 text-blue-600 @endif">{{ ucfirst(str_replace('_', ' ', $salesOrder->status)) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Metode Pembayaran:</span><span class="capitalize">{{ $salesOrder->payment_method }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Status Pembayaran:</span><span class="px-2 py-1 rounded-full text-xs font-medium @if($salesOrder->payment_status === 'lunas') bg-green-100 text-green-600 @else bg-yellow-100 text-yellow-600 @endif">{{ ucfirst($salesOrder->payment_status) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Subtotal:</span><span>Rp {{ number_format($salesOrder->subtotal, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Diskon:</span><span>Rp {{ number_format($salesOrder->discount_total, 0, ',', '.') }}</span></div>
                        <!-- ✅ TAMBAH DISPLAY ONGKIR DI SINI -->
                        <div class="flex justify-between"><span class="text-gray-600">Ongkir:</span><span>Rp {{ number_format($salesOrder->shipping_cost, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Grand Total:</span><span class="text-lg font-bold text-blue-600">Rp {{ number_format($salesOrder->grand_total, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Total Dibayar:</span><span class="text-green-600 font-medium">Rp {{ number_format($salesOrder->paid_total, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Sisa:</span><span class="@if($salesOrder->remaining_amount > 0) text-red-600 @else text-green-600 @endif font-medium">Rp {{ number_format($salesOrder->remaining_amount, 0, ',', '.') }}</span></div>
                    </div>
                </div>
            </div>

            @if($salesOrder->status !== 'selesai' && $activeShift && Auth::user()->hasRole('kepala_toko'))
                <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                    <h2 class="text-lg font-semibold mb-4 text-gray-800">Tambah Pembayaran</h2>
                    <form action="{{ route('kepala-toko.sales.addPayment', $salesOrder) }}" method="POST" enctype="multipart/form-data" id="paymentForm">
                        @csrf
                        <div class="grid md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="payment_method" class="block font-medium mb-1">Metode Pembayaran</label>
                                <select name="payment_method" id="payment_method" required class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">
                                    <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="transfer" {{ old('payment_method') == 'transfer' ? 'selected' : '' }}>Transfer</option>
                                    <option value="split" {{ old('payment_method') == 'split' ? 'selected' : '' }}>Split</option>
                                </select>
                                @error('payment_method')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="payment_amount" class="block font-medium mb-1">Jumlah Pembayaran</label>
                                <input type="number" name="payment_amount" id="payment_amount" min="0" step="0.01"
                                       required class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                                       value="{{ old('payment_amount', $salesOrder->remaining_amount) }}">
                                @error('payment_amount')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div id="split-payment-fields" class="hidden col-span-2">
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="cash_amount" class="block font-medium mb-1">Jumlah Cash</label>
                                        <input type="number" name="cash_amount" id="cash_amount" class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" step="0.01" min="0" value="{{ old('cash_amount') }}">
                                        @error('cash_amount')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="transfer_amount" class="block font-medium mb-1">Jumlah Transfer</label>
                                        <input type="number" name="transfer_amount" id="transfer_amount" class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" step="0.01" min="0" value="{{ old('transfer_amount') }}">
                                        @error('transfer_amount')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label for="paid_at" class="block font-medium mb-1">Tanggal Pembayaran</label>
                                <input type="datetime-local" name="paid_at" id="paid_at"
                                       value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}" required
                                       class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">
                                @error('paid_at')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
    <div>
        <label for="reference_number" class="block font-medium mb-1">No Referensi Transfer (Opsional)</label>
        <input type="text" name="reference_number" id="reference_number" 
               class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" 
               placeholder="Contoh: TRF123456789" value="{{ old('reference_number') }}">
        <p class="text-sm text-gray-600 mt-1">No referensi bank atau keterangan</p>
        @error('reference_number')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
                            <div>
                                <label for="proof_path" class="block font-medium mb-1">Bukti Pembayaran (opsional)</label>
                                <input type="file" name="proof_path" id="proof_path" accept=".jpg,.jpeg,.png,.pdf"
                                       class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">
                                @error('proof_path')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        <div class="mb-4">
                            <label for="note" class="block font-medium mb-1">Catatan (opsional)</label>
                            <textarea name="note" id="note" rows="2"
                                      class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">{{ old('note') }}</textarea>
                            @error('note')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        </div>
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded shadow">
                            <i class="bi bi-check-lg"></i> Simpan Pembayaran
                        </button>
                    </form>
                </div>
            @endif

            <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
    <h2 class="text-lg font-semibold mb-4 text-gray-800">Riwayat Pembayaran</h2>
    <div class="overflow-x-auto">
        <table class="w-full table-auto border-collapse">
            <thead>
                <tr class="bg-gray-50 text-left text-sm font-semibold text-gray-600">
                    <th class="px-4 py-2 border">Tanggal</th>
                    <th class="px-4 py-2 border">Metode</th>
                    <th class="px-4 py-2 border text-right">Jumlah</th>
                    <th class="px-4 py-2 border">Operator</th>
                    <th class="px-4 py-2 border">Keterangan</th>
                    <th class="px-4 py-2 border text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $cumulativePayment = 0;
                    $totalPaid = 0; // Hitung total bayar dari payments
                @endphp
                @forelse($salesOrder->payments as $payment)
                    @php
                        $cumulativePayment += $payment->amount;
                        $totalPaid += $payment->amount;
                    @endphp
<tr class="border-b hover:bg-gray-50 {{ $loop->first ? 'border-l-4 border-l-green-500 bg-green-50' : '' }}">
    <td class="px-4 py-2 border">
        {{ \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y H:i') }}
        @if($loop->first)
            <span class="ml-2 bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded">Terbaru</span>
        @endif
    </td>
    <td class="px-4 py-2 border">
        @if($payment->method === 'cash')
            <span class="inline-flex items-center"><i class="bi bi-cash mr-1 text-green-600"></i> Cash</span>
        @elseif($payment->method === 'transfer')
            <span class="inline-flex items-center"><i class="bi bi-bank mr-1 text-blue-600"></i> Transfer</span>
        @else
            <span class="inline-flex items-center"><i class="bi bi-cash-stack mr-1 text-purple-600"></i> Split</span>
            <br>
            <small class="text-gray-500">
                (Cash: Rp {{ number_format($payment->cash_amount, 0, ',', '.') }},
                Transfer: Rp {{ number_format($payment->transfer_amount, 0, ',', '.') }})
            </small>
        @endif
    </td>
    <td class="px-4 py-2 border text-right font-medium text-green-600">
        Rp {{ number_format($payment->amount, 0, ',', '.') }}
        <br>
        <small class="text-gray-500 text-xs">
            Total: Rp {{ number_format($cumulativePayment, 0, ',', '.') }}
        </small>
        <br>
        <span class="px-2 py-0.5 rounded-full text-xs font-medium 
            @if($payment->category === 'pelunasan') bg-green-100 text-green-700 
            @else bg-yellow-100 text-yellow-700 @endif">
            {{ ucfirst($payment->category) }}
        </span>
    </td>
    <td class="px-4 py-2 border">
        {{ $payment->creator->name ?? 'System' }}
        <br>
        <small class="text-gray-500 text-xs">#{{ $payment->created_by }}</small>
    </td>
    <td class="px-4 py-2 border">
        @if($payment->reference_number)
            No Ref: {{ $payment->reference_number }}<br>
        @endif
        @if($payment->note)
            <small class="text-gray-600">{{ $payment->note }}</small><br>
        @endif
        @if($payment->proof_path)
            <a href="{{ route('kepala-toko.sales.payment-proof', $payment) }}" target="_blank" class="text-blue-500 text-xs hover:underline inline-flex items-center">
                <i class="bi bi-file-earmark-image mr-1"></i> Lihat Bukti
            </a>
        @elseif(in_array($payment->method, ['transfer', 'split']) && $activeShift && Auth::user()->hasRole('kepala_toko'))
            <form action="{{ route('kepala-toko.sales.uploadProof', ['salesOrder' => $salesOrder, 'payment' => $payment]) }}" method="POST" enctype="multipart/form-data" class="upload-proof-form mt-2">
                @csrf
                <input type="file" name="proof_path" accept=".jpg,.jpeg,.png,.pdf" class="border rounded px-2 py-1 text-xs w-full" required>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs mt-1 w-full">
                    <i class="bi bi-upload"></i> Upload Bukti
                </button>
                @error('proof_path')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </form>
        @endif
    </td>
    <td class="px-4 py-2 border text-center">
        <div class="flex justify-center gap-2">
            <button onclick="showPrintOptions({{ $payment->id }})" class="text-green-600 hover:text-green-800" title="Cetak Nota">
                <i class="bi bi-printer"></i>
            </button>
            <a href="{{ route('kepala-toko.sales.printNota', $payment) }}" class="text-blue-600 hover:text-blue-800" title="Download PDF">
                <i class="bi bi-download"></i>
            </a>
        </div>
    </td>
</tr>                @empty
                    <tr>
                        <td colspan="6" class="text-center text-gray-500 px-4 py-4">Belum ada pembayaran</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($salesOrder->payments->isNotEmpty())
        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="font-semibold">Total yang harus dibayar:</div>
                <div class="text-right">Rp {{ number_format($salesOrder->grand_total, 0, ',', '.') }}</div>
                <div class="font-semibold">Total sudah dibayar:</div>
                <div class="text-right text-green-600 font-medium">Rp {{ number_format($totalPaid, 0, ',', '.') }}</div>
                <div class="font-semibold">Sisa pembayaran:</div>
                <div class="text-right @if(($salesOrder->grand_total - $totalPaid) > 0) text-red-600 @else text-green-600 @endif font-medium">
                    Rp {{ number_format($salesOrder->grand_total - $totalPaid, 0, ',', '.') }}
                </div>
                <div class="font-semibold">Status Pembayaran:</div>
                <div class="text-right">
                    <span class="px-2 py-1 rounded-full text-xs font-medium @if($totalPaid >= $salesOrder->grand_total) bg-green-100 text-green-600 @else bg-yellow-100 text-yellow-600 @endif">
                        {{ $totalPaid >= $salesOrder->grand_total ? 'Lunas' : 'DP' }}
                    </span>
                </div>
            </div>
        </div>
    @endif
</div>

            <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                <h2 class="text-lg font-semibold mb-4 text-gray-800">Item Order</h2>
                <div class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-left text-sm font-semibold text-gray-600">
                                <th class="px-4 py-2 border">Produk</th>
                                <th class="px-4 py-2 border">SKU</th>
                                <th class="px-4 py-2 border text-right">Harga</th>
                                <th class="px-4 py-2 border text-center">Qty</th>
                                <th class="px-4 py-2 border text-right">Diskon</th>
                                @php
                                    $hasDesignItems = $salesOrder->items->contains(function($item) {
                                        return $item->requires_design || in_array($item->product_type, ['dtf', 'jersey']);
                                    });
                                @endphp
                                @if($hasDesignItems)
                                    <th class="px-4 py-2 border text-center">Status Desain</th>
                                @endif
                                <th class="px-4 py-2 border text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salesOrder->items as $item)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-2 border">
                                        {{ $item->product_name }}
                                        @if($item->product_id)<br><small class="text-gray-500">ID: {{ $item->product_id }}</small>@endif
                                        @if($item->requires_design || in_array($item->product_type, ['dtf', 'jersey']))
                                            <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-700 mt-1">
                                                <i class="bi bi-brush mr-1"></i> Butuh Desain
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 border">{{ $item->sku ?? '-' }}</td>
                                    <td class="px-4 py-2 border text-right">Rp {{ number_format($item->sale_price, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2 border text-center">{{ $item->qty }}</td>
                                    <td class="px-4 py-2 border text-right">Rp {{ number_format($item->discount, 0, ',', '.') }}</td>
                                    @if($hasDesignItems)
                                        <td class="px-4 py-2 border text-center">
                                            @if($item->requires_design || in_array($item->product_type, ['dtf', 'jersey']))
                                                @php
                                                    $statusColors = [
                                                        'pending' => 'bg-amber-100 text-amber-800',
                                                        'in_progress' => 'bg-blue-100 text-blue-800',
                                                        'waiting_customer' => 'bg-purple-100 text-purple-800',
                                                        'approved' => 'bg-emerald-100 text-emerald-800',
                                                        'rejected' => 'bg-red-100 text-red-800',
                                                    ];
                                                    $statusLabels = \App\Models\SalesOrderItem::designStatusOptions();
                                                @endphp
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$item->design_status] ?? 'bg-gray-100 text-gray-600' }}">
                                                    {{ $statusLabels[$item->design_status] ?? $item->design_status ?? 'Belum Ditetapkan' }}
                                                </span>
                                                @if($item->design_preview_path)
                                                    <br><a href="{{ Storage::url($item->design_preview_path) }}" target="_blank" class="text-xs text-blue-600 hover:underline mt-1 inline-flex items-center">
                                                        <i class="bi bi-eye mr-1"></i> Preview
                                                    </a>
                                                @endif
                                            @else
                                                <span class="text-xs text-gray-400">-</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-4 py-2 border text-right font-semibold">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
    @php
        $colspan = $hasDesignItems ? 7 : 6;
    @endphp
    <tr>
        <td colspan="{{ $colspan }}" class="px-4 py-2 border text-right font-semibold">Subtotal:</td>
        <td class="px-4 py-2 border text-right font-semibold">Rp {{ number_format($salesOrder->subtotal, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td colspan="{{ $colspan }}" class="px-4 py-2 border text-right font-semibold">Total Diskon:</td>
        <td class="px-4 py-2 border text-right font-semibold text-red-600">- Rp {{ number_format($salesOrder->discount_total, 0, ',', '.') }}</td>
    </tr>
    <!-- ✅ TAMBAH ROW ONGKIR DI SINI -->
    <tr>
        <td colspan="{{ $colspan }}" class="px-4 py-2 border text-right font-semibold">Ongkir:</td>
        <td class="px-4 py-2 border text-right font-semibold text-green-600">+ Rp {{ number_format($salesOrder->shipping_cost, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td colspan="{{ $colspan }}" class="px-4 py-2 border text-right font-semibold">Grand Total:</td>
        <td class="px-4 py-2 border text-right font-semibold text-blue-600">Rp {{ number_format($salesOrder->grand_total, 0, ',', '.') }}</td>
    </tr>
</tfoot>
                    </table>
                </div>
            </div>

            @php
                use Illuminate\Support\Facades\Storage;
                use Illuminate\Support\Str;
                $designItems = $salesOrder->items->filter(function($item) {
                    return $item->requires_design || in_array($item->product_type, ['dtf', 'jersey']);
                });
            @endphp
            @if($designItems->isNotEmpty())
                <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <i class="bi bi-brush text-purple-600"></i>
                                Status Desain (DTF & Jersey)
                            </h2>
                            <p class="text-sm text-gray-500 mt-1">Pantau progress desain untuk item DTF dan Jersey yang membutuhkan desain.</p>
                        </div>
                        <a href="{{ route('editor.dashboard', ['search' => $salesOrder->so_number]) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            <i class="bi bi-box-arrow-up-right"></i>
                            Buka di Editor
                        </a>
                    </div>
                    <div class="space-y-4">
                        @foreach($designItems as $item)
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800">{{ $item->product_name }}</h3>
                                        <p class="text-sm text-gray-500 mt-1">Qty: {{ $item->qty }} • SKU: {{ $item->sku ?? '-' }}</p>
                                        @if($item->design_brief)
                                            <p class="text-sm text-gray-600 mt-2">
                                                <strong>Brief:</strong> {{ Str::limit($item->design_brief, 100) }}
                                            </p>
                                        @endif
                                        @if($item->design_notes)
                                            <p class="text-sm text-gray-600 mt-1">
                                                <strong>Catatan Editor:</strong> {{ Str::limit($item->design_notes, 100) }}
                                            </p>
                                        @endif
                                        @if($item->design_feedback)
                                            <p class="text-sm text-amber-700 mt-1 bg-amber-50 p-2 rounded">
                                                <strong>Feedback Customer:</strong> {{ Str::limit($item->design_feedback, 100) }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="ml-4 text-right">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-amber-100 text-amber-800 border-amber-300',
                                                'in_progress' => 'bg-blue-100 text-blue-800 border-blue-300',
                                                'waiting_customer' => 'bg-purple-100 text-purple-800 border-purple-300',
                                                'approved' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                                'rejected' => 'bg-red-100 text-red-800 border-red-300',
                                            ];
                                            $statusLabels = \App\Models\SalesOrderItem::designStatusOptions();
                                        @endphp
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border {{ $statusColors[$item->design_status] ?? 'bg-gray-100 text-gray-600 border-gray-300' }}">
                                            {{ $statusLabels[$item->design_status] ?? $item->design_status ?? 'Belum Ditetapkan' }}
                                        </span>
                                        @if($item->design_confirmed_at)
                                            <p class="text-xs text-gray-500 mt-1">
                                                Disetujui: {{ $item->design_confirmed_at->format('d/m/Y H:i') }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 mt-3 pt-3 border-t">
                                    @if($item->design_reference_path)
                                        <a href="{{ Storage::url($item->design_reference_path) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                            <i class="bi bi-cloud-arrow-down"></i>
                                            Download Brief
                                        </a>
                                    @endif
                                    @if($item->design_preview_path)
                                        <a href="{{ Storage::url($item->design_preview_path) }}" target="_blank" class="text-sm text-emerald-600 hover:text-emerald-800 flex items-center gap-1">
                                            <i class="bi bi-eye"></i>
                                            Lihat Preview
                                        </a>
                                    @endif
                                    @if(!$item->design_reference_path && !$item->design_preview_path)
                                        <span class="text-sm text-gray-400">Belum ada file desain</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-white p-6 rounded-xl shadow-lg mt-6">
                <h2 class="text-lg font-semibold mb-4 text-gray-800">Informasi Sistem</h2>
                    {{-- ✅ TAMBAH SECTION PURCHASE ORDER TERKAIT --}}
    <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <h3 class="font-semibold text-blue-800 mb-3 flex items-center">
            <i class="bi bi-link-45deg mr-2"></i>
            Purchase Order Terkait
        </h3>
        
        <div id="po-related-section">
            {{-- Content akan di-load via AJAX --}}
            <div class="text-center py-4">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                <p class="text-sm text-gray-600 mt-2">Memuat informasi PO...</p>
            </div>
        </div>
    </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div><span class="text-gray-600">Dibuat pada:</span><span>{{ $salesOrder->created_at->format('d/m/Y H:i:s') }}</span></div>
                    <div><span class="text-gray-600">Terakhir diupdate:</span><span>{{ $salesOrder->updated_at->format('d/m/Y H:i:s') }}</span></div>
                    <div><span class="text-gray-600">Dibuat Oleh:</span><span>{{ $salesOrder->creator->name ?? 'System' }}</span></div>
                    @if($salesOrder->approved_by)
                        <div><span class="text-gray-600">Disetujui Oleh:</span><span>{{ $salesOrder->approver->name ?? 'System' }}</span></div>
                    @endif
                    @if($salesOrder->approved_at)
                        <div><span class="text-gray-600">Disetujui pada:</span><span>{{ \Carbon\Carbon::parse($salesOrder->approved_at)->format('d/m/Y H:i') }}</span></div>
                    @endif
                    @if($salesOrder->completed_at)
                        <div><span class="text-gray-600">Diselesaikan pada:</span><span>{{ \Carbon\Carbon::parse($salesOrder->completed_at)->format('d/m/Y H:i') }}</span></div>
                    @endif
                </div>
                <div class="mt-6">
                    <h2 class="text-lg font-semibold mb-4 text-gray-800">Riwayat Aktivitas</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-left text-sm font-semibold text-gray-600">
                                    <th class="px-4 py-2 border">Waktu</th>
                                    <th class="px-4 py-2 border">Aksi</th>
                                    <th class="px-4 py-2 border">Deskripsi</th>
                                    <th class="px-4 py-2 border">Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($salesOrder->logs as $log)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-4 py-2 border">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-2 border">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</td>
                                        <td class="px-4 py-2 border">{{ $log->description }}</td>
                                        <td class="px-4 py-2 border">{{ $log->user->name ?? 'System' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-gray-500 px-4 py-4">Belum ada log aktivitas</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Print Options -->
<div id="printModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 w-80 mx-4">
        <h3 class="text-lg font-semibold mb-4 text-center">Pilih Metode Cetak</h3>
        
        <div class="space-y-3">
            <!-- Option 1: Pure HTML Thermal -->
            <button onclick="printThermalHTML()" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg flex items-center justify-center gap-3 shadow">
                <i class="bi bi-printer text-xl"></i>
                <div class="text-left">
                    <div class="font-semibold">Thermal Printer</div>
                    <div class="text-xs opacity-90">Format thermal 58mm + detail barang</div>
                </div>
            </button>
            
            <!-- Option 2: ESC/POS Text -->
            <button onclick="printESCPOS()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg flex items-center justify-center gap-3 shadow">
                <i class="bi bi-file-text text-xl"></i>
                <div class="text-left">
                    <div class="font-semibold">Text Printer</div>
                    <div class="text-xs opacity-90">Format text + detail barang</div>
                </div>
            </button>
            
            <!-- Option 3: PDF Download -->
            <button onclick="downloadThermalPDF()" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-lg flex items-center justify-center gap-3 shadow">
                <i class="bi bi-file-earmark-pdf text-xl"></i>
                <div class="text-left">
                    <div class="font-semibold">Download PDF</div>
                    <div class="text-xs opacity-90">Simpan sebagai PDF</div>
                </div>
            </button>
        </div>
        
        <div class="mt-4 flex justify-center">
            <button onclick="closePrintModal()" class="text-gray-600 hover:text-gray-800 px-4 py-2">Batal</button>
        </div>
    </div>
</div>

<!-- Loading Indicator -->
<div id="loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 flex items-center gap-3">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span>Loading...</span>
    </div>
</div>

<!-- Thermal Receipt Template (Hidden) -->
<div id="thermalReceipt" style="display: none;">
    <div class="thermal-receipt">
        <pre id="rcpt-text">Menyiapkan nota...</pre>
    </div>
</div>

<style>
/* Thermal Receipt Styles */
.thermal-receipt {
    width: 58mm; /* Sesuaikan dengan lebar kertas */
    max-width: 58mm;
    padding: 1mm;
    background: white;
    margin: 0 auto;
    border: none;
    font-size: 10px;
}

.thermal-receipt pre {
    font-family: 'Courier New', Courier, monospace;
    font-size: 10px;
    line-height: 1.3;
    margin: 0;
    white-space: pre-wrap;
    word-break: break-word;
    letter-spacing: 0;
}

/* Print Styles */
@media print {
    @page {
        margin: 0;
        padding: 0;
        size: 58mm auto; /* Lebar kertas 58mm */
        width: 58mm;
    }
    
    body {
        margin: 0 !important;
        padding: 0 !important;
        width: 58mm !important;
        background: white !important;
    }
    
    body * {
        visibility: hidden;
    }
    
    .thermal-receipt, 
    .thermal-receipt * {
        visibility: visible;
    }
    
    .thermal-receipt {
        position: absolute;
        left: 0;
        top: 0;
        width: 58mm !important;
        max-width: 58mm !important;
        margin: 0 !important;
        padding: 2mm !important;
        background: white !important;
        box-shadow: none !important;
        border: none !important;
    }
    
    /* Hilangkan semua tombol saat print */
    button, .no-print {
        display: none !important;
    }
}
@media screen {
    .thermal-receipt {
        border: 1px solid #ccc;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
}

/* Loading */
.loading {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}

.loading-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

/* Toast Notification */
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    z-index: 10000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.toast.success { background: #10b981; }
.toast.error { background: #ef4444; }
.toast.info { background: #3b82f6; }
</style>

<script>
// Global variables
let currentPaymentId = null;
let currentPaymentData = null;
// PERBAIKAN: Fungsi format thermal receipt
const RECEIPT_CHAR_WIDTH = 32; // 58mm thermal = 32 karakter, bukan 28
const RECEIPT_MAX_WIDTH = 32; // Max karakter per baris

// PERBAIKAN: Hitung total bayar dari semua payments
function calculateTotalPaid(payments) {
    if (!payments || payments.length === 0) return 0;
    return payments.reduce((total, payment) => total + (parseFloat(payment.amount) || 0), 0);
}

// PERBAIKAN: Hitung sisa bayar
function calculateRemaining(grandTotal, totalPaid) {
    return Math.max(0, parseFloat(grandTotal) - totalPaid);
}

// Show print options modal
function showPrintOptions(paymentId) {
    currentPaymentId = paymentId;
    currentPaymentData = getPaymentById(paymentId);
    
    if (!currentPaymentData) {
        alert('Data pembayaran tidak ditemukan!');
        return;
    }
    
    document.getElementById('printModal').classList.remove('hidden');
}

// Close print modal
function closePrintModal() {
    document.getElementById('printModal').classList.add('hidden');
}

// 1. PURE HTML THERMAL PRINTING
function printThermalHTML() {
    if (!currentPaymentData) return;
    
    showLoading('Menyiapkan cetakan thermal...');
    
    const salesOrder = {!! json_encode($salesOrder) !!};
    const payment = currentPaymentData;
    const allPayments = {!! json_encode($salesOrder->payments) !!};
    
    const totalPaid = calculateTotalPaid(allPayments);
    const remaining = calculateRemaining(salesOrder.grand_total, totalPaid);
    
    const textReceipt = buildThermalReceiptText(salesOrder, payment, totalPaid, remaining);
    const receiptPre = document.getElementById('rcpt-text');
    
    if (receiptPre) {
        receiptPre.textContent = textReceipt;
    }
    
    // Gunakan iframe untuk print yang lebih bersih
    const printContent = `
<!DOCTYPE html>
<html>
<head>
    <title>Nota - ${salesOrder.so_number}</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=58mm, initial-scale=1">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 58mm;
            background: white;
            font-family: 'Courier New', monospace;
            font-size: 10px;
            line-height: 1.3;
        }
        
        .receipt {
            width: 58mm;
            padding: 2mm;
            white-space: pre-wrap;
            word-break: break-word;
            letter-spacing: normal;
        }
        
        @media print {
            @page {
                size: 58mm auto;
                margin: 0;
                padding: 0;
            }
            
            body {
                margin: 0;
                padding: 0;
                width: 58mm;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">${textReceipt}</div>
    
    <script>
        // Auto print setelah load
        setTimeout(() => {
            window.print();
            setTimeout(() => {
                window.close();
            }, 500);
        }, 300);
    <\/script>
</body>
</html>`;
    
    // Buat iframe untuk print
    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = 'none';
    iframe.style.left = '-9999px';
    document.body.appendChild(iframe);
    
    iframe.contentDocument.open();
    iframe.contentDocument.write(printContent);
    iframe.contentDocument.close();
    
    hideLoading();
    closePrintModal();
    
    showToast('Mencetak nota thermal...', 'success');
}

// 2. ESC/POS TEXT PRINTING (Alternative)
function printESCPOS() {
    if (!currentPaymentData) return;
    
    showLoading('Membuat format text...');
    
    const salesOrder = {!! json_encode($salesOrder) !!};
    const payment = currentPaymentData;
    const allPayments = {!! json_encode($salesOrder->payments) !!};
    
    const totalPaid = calculateTotalPaid(allPayments);
    const remaining = calculateRemaining(salesOrder.grand_total, totalPaid);
    
    const textReceipt = buildThermalReceiptText(salesOrder, payment, totalPaid, remaining);
    
    // Create text file and download
    const blob = new Blob([textReceipt], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `nota-${salesOrder.so_number}-${payment.id}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    hideLoading();
    closePrintModal();
    showToast('File text berhasil diunduh! Buka dengan aplikasi printer.', 'success');
}

// 3. PDF DOWNLOAD
function downloadThermalPDF() {
    if (!currentPaymentData) return;
    
    // Redirect to PDF route
    window.open('{{ route("admin.sales.printNota", ":paymentId") }}'.replace(':paymentId', currentPaymentId), '_blank');
    closePrintModal();
}

// PERBAIKAN: Utility Functions yang lebih aman
function safeFormatNumber(num) {
    // Handle null, undefined, NaN, dll
    if (num === null || num === undefined || num === '' || isNaN(num)) {
        return '0';
    }
    return parseInt(num).toLocaleString('id-ID');
}

function formatNumber(num) {
    return safeFormatNumber(num); // Fallback ke yang aman
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID') + ' ' + date.toLocaleTimeString('id-ID').substring(0, 5);
}

function buildThermalReceiptText(salesOrder, payment, totalPaid, remaining) {
    const lines = [];
    const customerName = salesOrder.customer ? salesOrder.customer.name : 'Umum';
    const operatorName = payment.creator_name || 'System';
    const orderDate = salesOrder.order_date ? formatDate(salesOrder.order_date) : new Date().toLocaleDateString('id-ID');
    const paymentDate = payment.paid_at ? formatDate(payment.paid_at) : new Date().toLocaleDateString('id-ID');
    const now = new Date();

    // HEADER
    lines.push(centerText('PARE CUSTOM', RECEIPT_MAX_WIDTH));
    lines.push(centerText('NOTA PEMBAYARAN', RECEIPT_MAX_WIDTH));
    lines.push(divider(RECEIPT_MAX_WIDTH));
    
    // INFORMASI ORDER
    lines.push(alignLeftRight('No SO', salesOrder.so_number || '-', RECEIPT_MAX_WIDTH));
    lines.push(alignLeftRight('Customer', customerName, RECEIPT_MAX_WIDTH));
    lines.push(alignLeftRight('Tgl Order', orderDate, RECEIPT_MAX_WIDTH));
    lines.push(alignLeftRight('Kasir', operatorName, RECEIPT_MAX_WIDTH));
    lines.push(divider(RECEIPT_MAX_WIDTH));
    
    // DETAIL BARANG
    lines.push(centerText('DETAIL BARANG', RECEIPT_MAX_WIDTH));
    if (Array.isArray(salesOrder.items) && salesOrder.items.length) {
        salesOrder.items.forEach((item, index) => {
            const itemNumber = `${index + 1}.`;
            const productName = item.product_name || '-';
            
            // Nama produk dengan wrap
            const nameLines = wrapText(productName, RECEIPT_MAX_WIDTH - 8);
            lines.push(`${itemNumber} ${nameLines[0]}`);
            if (nameLines.length > 1) {
                for (let i = 1; i < nameLines.length; i++) {
                    lines.push(`  ${nameLines[i]}`);
                }
            }
            
            // Qty dan harga
            const qtyText = `${item.qty || 0} x ${formatCurrency(item.sale_price)}`;
            const lineTotal = formatCurrency(item.line_total);
            lines.push(alignLeftRight(qtyText, lineTotal, RECEIPT_MAX_WIDTH));
            
            // Diskon jika ada
            if (Number(item.discount) > 0) {
                lines.push(alignLeftRight('Disc', formatCurrency(item.discount), RECEIPT_MAX_WIDTH));
            }
            
            lines.push(''); // Spasi antar item
        });
        // Hapus spasi terakhir jika ada
        if (lines[lines.length - 1] === '') lines.pop();
    } else {
        lines.push('(Tidak ada item)');
    }
    lines.push(divider(RECEIPT_MAX_WIDTH));
    
    // RINGKASAN
    lines.push(centerText('RINGKASAN', RECEIPT_MAX_WIDTH));
    lines.push(alignLeftRight('Subtotal', formatCurrency(salesOrder.subtotal), RECEIPT_MAX_WIDTH));
    lines.push(alignLeftRight('Diskon', formatCurrency(salesOrder.discount_total), RECEIPT_MAX_WIDTH));
    lines.push(alignLeftRight('Grand Total', formatCurrency(salesOrder.grand_total), RECEIPT_MAX_WIDTH));
    lines.push(alignLeftRight('Total Bayar', formatCurrency(totalPaid), RECEIPT_MAX_WIDTH));
    lines.push(alignLeftRight('Sisa', formatCurrency(remaining), RECEIPT_MAX_WIDTH));
    lines.push(divider(RECEIPT_MAX_WIDTH));
    
    // DETAIL PEMBAYARAN
    lines.push(centerText('DETAIL PEMBAYARAN', RECEIPT_MAX_WIDTH));
    lines.push(alignLeftRight('Tgl Bayar', paymentDate, RECEIPT_MAX_WIDTH));
    lines.push(alignLeftRight('Metode', (payment.method || '').toUpperCase(), RECEIPT_MAX_WIDTH));
    lines.push(alignLeftRight('Jumlah', formatCurrency(payment.amount), RECEIPT_MAX_WIDTH));
    
    if (payment.method === 'split') {
        lines.push(alignLeftRight('- Cash', formatCurrency(payment.cash_amount), RECEIPT_MAX_WIDTH));
        lines.push(alignLeftRight('- Transfer', formatCurrency(payment.transfer_amount), RECEIPT_MAX_WIDTH));
    }
    
    if (payment.reference) {
        addKeyValue(lines, 'Referensi', payment.reference);
    }
    
    if (payment.note) {
        addKeyValue(lines, 'Catatan', payment.note);
    }
    
    lines.push(divider(RECEIPT_MAX_WIDTH));
    
    // FOOTER
    lines.push(alignLeftRight('Operator', operatorName, RECEIPT_MAX_WIDTH));
    lines.push(divider(RECEIPT_MAX_WIDTH));
    lines.push(centerText('Terima kasih', RECEIPT_MAX_WIDTH));
    lines.push(centerText(`*** ${now.toLocaleDateString('id-ID')} ${now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })} ***`, RECEIPT_MAX_WIDTH));
    lines.push(divider(RECEIPT_MAX_WIDTH));
        lines.push(divider(RECEIPT_MAX_WIDTH));
            lines.push(divider(RECEIPT_MAX_WIDTH));
            lines.push(divider(RECEIPT_MAX_WIDTH));
    return lines.join('\n');
}

// FUNGSI HELPER YANG DIPERBAIKI

// 1. Center text dengan tepat
function centerText(text, width) {
    const textStr = String(text || '').trim();
    if (textStr.length >= width) return textStr;
    
    const leftPadding = Math.floor((width - textStr.length) / 2);
    const rightPadding = width - textStr.length - leftPadding;
    
    return ' '.repeat(Math.max(0, leftPadding)) + textStr + ' '.repeat(Math.max(0, rightPadding));
}

// 2. Align left-right dengan benar
function alignLeftRight(left, right, width) {
    const leftStr = String(left || '');
    const rightStr = String(right || '');
    
    if (leftStr.length + rightStr.length > width) {
        // Jika terlalu panjang, buat dua baris
        return leftStr + '\n' + ' '.repeat(width - rightStr.length) + rightStr;
    }
    
    const middleSpaces = width - leftStr.length - rightStr.length;
    return leftStr + ' '.repeat(Math.max(0, middleSpaces)) + rightStr;
}

// 3. Divider line
function divider(width, char = '-') {
    return char.repeat(width);
}

// 4. Wrap text untuk nama produk panjang
function wrapText(text, maxWidth) {
    const words = String(text || '').split(' ');
    const lines = [];
    let currentLine = '';
    
    words.forEach(word => {
        if (word.length > maxWidth) {
            // Handle kata yang sangat panjang
            if (currentLine) {
                lines.push(currentLine);
                currentLine = '';
            }
            
            for (let i = 0; i < word.length; i += maxWidth) {
                lines.push(word.substring(i, i + maxWidth));
            }
        } else if ((currentLine + ' ' + word).length > maxWidth) {
            lines.push(currentLine);
            currentLine = word;
        } else {
            currentLine = currentLine ? currentLine + ' ' + word : word;
        }
    });
    
    if (currentLine) {
        lines.push(currentLine);
    }
    
    return lines;
}

// 5. Key-value dengan format yang baik
function addKeyValue(lines, key, value) {
    const keyPart = `${key}: `;
    const valueStr = String(value || '-');
    const availableWidth = RECEIPT_MAX_WIDTH - keyPart.length;
    
    if (availableWidth <= 0) {
        lines.push(keyPart);
        const valueLines = wrapText(valueStr, RECEIPT_MAX_WIDTH);
        valueLines.forEach(line => lines.push(line));
        return;
    }
    
    const valueLines = wrapText(valueStr, availableWidth);
    
    if (valueLines.length === 0) {
        lines.push(keyPart + '-');
        return;
    }
    
    // Baris pertama
    lines.push(keyPart + valueLines[0]);
    
    // Baris berikutnya (jika ada) dengan indent
    for (let i = 1; i < valueLines.length; i++) {
        lines.push(' '.repeat(keyPart.length) + valueLines[i]);
    }
}

// 6. Format currency tetap sama
function formatCurrency(num) {
    if (num === null || num === undefined || num === '' || isNaN(num)) {
        return 'Rp 0';
    }
    
    // Format dengan dua digit desimal
    const formatted = parseFloat(num).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
    
    return 'Rp ' + formatted;
}

// 7. Format date
function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    } catch (e) {
        return '-';
    }
}

function getPaymentById(paymentId) {
    const payments = {!! json_encode($salesOrder->payments->map(function($payment) {
        return [
            'id' => $payment->id,
            'amount' => $payment->amount,
            'method' => $payment->method,
            'cash_amount' => $payment->cash_amount,
            'transfer_amount' => $payment->transfer_amount,
            'reference' => $payment->reference_number,
            'note' => $payment->note,
            'paid_at' => $payment->paid_at,
            'creator_name' => $payment->creator->name ?? 'System'
        ];
    })) !!};
    
    return payments.find(p => p.id === paymentId);
}

function showLoading(message = 'Loading...') {
    const loading = document.getElementById('loading');
    if (loading) {
        loading.querySelector('span').textContent = message;
        loading.classList.remove('hidden');
    }
}

function hideLoading() {
    const loading = document.getElementById('loading');
    if (loading) {
        loading.classList.add('hidden');
    }
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Close modal when clicking outside
document.getElementById('printModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePrintModal();
    }
});

// Existing functions (keep your existing code)
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
}

function toggleDropdown(button) {
    const dropdown = button.nextElementSibling;
    const chevron = button.querySelector('.bi-chevron-down');
    dropdown.classList.toggle('max-h-0');
    dropdown.classList.toggle('max-h-40');
    chevron.classList.toggle('rotate-180');
}

// Your existing DOMContentLoaded code...
document.addEventListener('DOMContentLoaded', function () {
    const paymentMethod = '{{ $salesOrder->payment_method }}';
    const proofInput = document.getElementById('proof_path');
    const form = document.getElementById('paymentForm');

    const splitFields = document.getElementById('split-payment-fields');
    splitFields.classList.toggle('hidden', paymentMethod !== 'split');

    document.getElementById('payment_method').addEventListener('change', function () {
        splitFields.classList.toggle('hidden', this.value !== 'split');
        if (this.value !== 'split') {
            document.getElementById('cash_amount').value = 0;
            document.getElementById('transfer_amount').value = 0;
            document.getElementById('proof_path').value = '';
        }
        updatePaymentAmount();
        updateProofRequired(this.value);
    });

    function updatePaymentAmount() {
        const method = document.getElementById('payment_method').value;
        const cash = parseFloat(document.getElementById('cash_amount')?.value || 0);
        const transfer = parseFloat(document.getElementById('transfer_amount')?.value || 0);
        const total = method === 'split' ? cash + transfer : parseFloat(document.getElementById('payment_amount').value) || 0;
        document.getElementById('payment_amount').value = total.toFixed(2);
    }

        // === PERBAIKAN: Function updateProofRequired yang baru ===
        function updateProofRequired(method) {
            if (!proofInput || !referenceInput) return;
            
            if (method === 'transfer' || method === 'split') {
                // Untuk transfer/split, bukti dan referensi jadi opsional (salah satu wajib)
                proofInput.required = false;
                referenceInput.required = false;
            } else {
                proofInput.required = false;
                referenceInput.required = false;
            }
        }

    updateProofRequired(paymentMethod);

    document.getElementById('cash_amount')?.addEventListener('input', updatePaymentAmount);
    document.getElementById('transfer_amount')?.addEventListener('input', updatePaymentAmount);

    form.addEventListener('submit', function (e) {
        const method = document.getElementById('payment_method').value;
        if ((method === 'transfer' || method === 'split') && !proofInput.files.length) {
            e.preventDefault();
            alert('Harap unggah bukti pembayaran untuk metode ' + method);
        }
    });
});

// Escape key to close modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePrintModal();
    }
});
// ✅ FUNGSI UNTUK MANAGE PURCHASE ORDER TERKAIT - PERBAIKI URL
function loadRelatedPO() {
    const salesOrderId = {{ $salesOrder->id }};
    
    // ✅ PERBAIKI URL - PAKAI ROUTE NAME YANG BENAR (tanpa prefix admin.)
    fetch(`/kepala-toko/sales/${salesOrderId}/related-po`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            const poSection = document.getElementById('po-related-section');
            
            if (data.exists) {
                // Tampilkan info PO terkait
                poSection.innerHTML = `
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h4 class="font-semibold text-green-800 mb-2">Purchase Order Terkait</h4>
                                <div class="space-y-1 text-sm">
                                    <div class="flex">
                                        <span class="text-gray-600 w-24">PO Number:</span>
                                        <span class="font-medium">
                                            <a href="${data.show_url}" target="_blank" class="text-blue-600 hover:underline">
                                                ${data.po_number}
                                            </a>
                                        </span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-600 w-24">Supplier:</span>
                                        <span>${data.supplier_name}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-600 w-24">Status:</span>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            ${data.status}
                                        </span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-600 w-24">Tipe:</span>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            ${data.purchase_type}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <a href="${data.show_url}" target="_blank" 
                                   class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm flex items-center">
                                    <i class="bi bi-eye mr-1"></i> Lihat
                                </a>
                                @if($activeShift && Auth::user()->hasRole('kepala_toko'))
<button onclick="unlinkFromPO()" 
        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm flex items-center">
    <i class="bi bi-trash mr-1"></i> Hapus PO
</button>
                                @endif
                            </div>
                        </div>
                    </div>
                `;
            } else {
                // Tampilkan form untuk link ke PO
                poSection.innerHTML = `
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h4 class="font-semibold text-yellow-800 mb-3">Belum ada Purchase Order Terkait</h4>
                        
                        @if($activeShift && Auth::user()->hasRole('kepala_toko'))
                        <form id="linkPoForm" onsubmit="linkToPO(event)" class="space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                                    <select name="supplier_id" class="w-full border rounded px-3 py-2 text-sm focus:ring focus:ring-blue-300">
                                        <option value="">-- Pilih Supplier --</option>
                                        @foreach(\App\Models\Supplier::where('is_active', true)->orderBy('name')->get() as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Atau Nama Supplier Baru</label>
                                    <input type="text" name="supplier_name" 
                                           class="w-full border rounded px-3 py-2 text-sm focus:ring focus:ring-blue-300" 
                                           placeholder="Ketik nama supplier baru">
                                </div>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-3 bg-white">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Produk ke Purchase Order</label>
                                <div class="flex flex-wrap gap-4 text-sm">
                                    <label class="inline-flex items-center space-x-2">
                                        <input type="radio" name="items_mode" value="all" class="text-blue-600 focus:ring-blue-500" checked>
                                        <span>Semua produk</span>
                                    </label>
                                    <label class="inline-flex items-center space-x-2">
                                        <input type="radio" name="items_mode" value="selected" class="text-blue-600 focus:ring-blue-500">
                                        <span>Pilih produk tertentu</span>
                                    </label>
                                </div>
                                <div id="select-items-container" class="hidden mt-3 border border-dashed border-gray-300 rounded-lg p-3 bg-gray-50">
                                    <p class="text-xs text-gray-500 mb-2">Centang produk yang ingin dimasukkan ke Purchase Order.</p>
                                    <div class="max-h-48 overflow-y-auto space-y-2 pr-1">
                                        @foreach($salesOrder->items as $item)
                                            <label class="flex items-start justify-between bg-white rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                                <div class="flex items-start space-x-3">
                                                    <input type="checkbox" name="include_items[]" value="{{ $item->id }}" class="po-item-checkbox mt-1 text-blue-600 focus:ring-blue-500">
                                                    <div>
                                                        <p class="font-medium text-gray-800">{{ $item->product_name }}</p>
                                                        <p class="text-xs text-gray-500">Qty: {{ $item->qty }} @if($item->sku) • SKU: {{ $item->sku }} @endif</p>
                                                    </div>
                                                </div>
                                                <span class="text-xs text-gray-500">Rp {{ number_format($item->sale_price * $item->qty, 0, ',', '.') }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="text-xs text-gray-600 mt-2" id="selected-items-count"></p>
                                </div>
                            </div>
                            <div class="text-xs text-gray-600">
                                <i class="bi bi-info-circle"></i> 
                                Purchase Order baru akan dibuat secara otomatis berdasarkan items sales order ini.
                            </div>
                            <button type="submit" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm flex items-center">
                                <i class="bi bi-link mr-1"></i> Buat & Link Purchase Order
                            </button>
                        </form>
                        @else
                        <p class="text-sm text-gray-600">Shift belum aktif untuk membuat Purchase Order.</p>
                        @endif
                    </div>
                `;
                setupPoSelectionControls();
            }
        })
        .catch(error => {
            console.error('Error loading related PO:', error);
            document.getElementById('po-related-section').innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <p class="text-red-700">Error memuat informasi PO terkait: ${error.message}</p>
                    <p class="text-sm text-red-600 mt-1">Route: /kepala-toko/sales/{{ $salesOrder->id }}/related-po</p>
                </div>
            `;
        });
}
function setupPoSelectionControls() {
    const form = document.getElementById('linkPoForm');
    if (!form) {
        return;
    }

    const selectionContainer = form.querySelector('#select-items-container');
    const radios = form.querySelectorAll('input[name="items_mode"]');
    const checkboxes = form.querySelectorAll('.po-item-checkbox');
    const selectedCount = form.querySelector('#selected-items-count');

    const toggleSelectionContainer = () => {
        const selectedRadio = form.querySelector('input[name="items_mode"]:checked');
        const showSelection = selectedRadio && selectedRadio.value === 'selected';
        if (selectionContainer) {
            selectionContainer.classList.toggle('hidden', !showSelection);
        }
        if (selectedCount) {
            selectedCount.classList.toggle('hidden', !showSelection);
        }
    };

    const updateSelectedCount = () => {
        if (!selectedCount) {
            return;
        }
        const selectedTotal = Array.from(checkboxes).filter(checkbox => checkbox.checked).length;
        selectedCount.textContent = `${selectedTotal} produk dipilih`;
    };

    radios.forEach(radio => radio.addEventListener('change', toggleSelectionContainer));
    checkboxes.forEach(checkbox => checkbox.addEventListener('change', updateSelectedCount));

    toggleSelectionContainer();
    updateSelectedCount();
}

// ✅ FUNGSI UNLINK DARI PO + DELETE - PERBAIKI HANDLE RESPONSE
function unlinkFromPO() {
    if (!confirm('Yakin ingin menghapus Purchase Order terkait? Tindakan ini tidak dapat dibatalkan dan PO akan dihapus permanen dari sistem.')) {
        return;
    }
    
    const salesOrderId = {{ $salesOrder->id }};
    
    showLoading('Menghapus Purchase Order...');
    
    fetch(`/kepala-toko/sales/${salesOrderId}/unlink-from-po`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        }
    })
    .then(response => {
        // ✅ PERBAIKI: Handle response yang bukan JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
        }
        return response.json();
    })
    .then(data => {
        hideLoading();
        if (data.success) {
            showToast(data.message || 'Berhasil menghapus Purchase Order!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast('Error: ' + (data.error || 'Gagal menghapus PO'), 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error unlinking from PO:', error);
        showToast('Terjadi kesalahan saat menghapus PO: ' + error.message, 'error');
    });
}

// ✅ FUNGSI LINK KE PO - PERBAIKI HANDLE RESPONSE
function linkToPO(event) {
    event.preventDefault();
    
    if (!confirm('Yakin ingin membuat Purchase Order untuk sales order ini?')) {
        return;
    }
    
    const form = event.target;
    const formData = new FormData(form);
    const salesOrderId = {{ $salesOrder->id }};
    const itemsMode = formData.get('items_mode') || 'all';
    const selectedItems = formData.getAll('include_items[]').filter(Boolean);
    
    showLoading('Membuat Purchase Order...');
    if (itemsMode === 'selected' && selectedItems.length === 0) {
        hideLoading();
        showToast('Pilih minimal satu produk untuk dimasukkan ke Purchase Order.', 'error');
        return;
    }
    
    fetch(`/kepala-toko/sales/${salesOrderId}/link-to-po`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            supplier_id: formData.get('supplier_id'),
            supplier_name: formData.get('supplier_name'),
            items_mode: itemsMode,
            selected_items: selectedItems.map(id => Number(id))
        })
    })
    .then(response => {
        // ✅ PERBAIKI: Handle response yang bukan JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
        }
        return response.json();
    })
    .then(data => {
        hideLoading();
        if (data.success) {
            showToast(data.message || 'Berhasil membuat Purchase Order terkait!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast('Error: ' + (data.error || 'Gagal membuat PO'), 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error linking to PO:', error);
        showToast('Terjadi kesalahan saat membuat PO: ' + error.message, 'error');
    });
}
// ✅ LOAD RELATED PO SAAT PAGE LOAD
document.addEventListener('DOMContentLoaded', function() {
    loadRelatedPO();
});
</script>
</body>
</html>