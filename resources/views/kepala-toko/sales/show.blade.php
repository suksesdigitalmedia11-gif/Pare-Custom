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
                        <a href="{{ route('kepala-toko.sales.index') }}"
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
@if($salesOrder->status === 'pending' && $salesOrder->approved_by === null && Auth::user()->hasRole('kepala_toko'))
    <form action="{{ route('kepala-toko.sales.approve', $salesOrder) }}" method="POST">
        @csrf
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
            <i class="bi bi-check-circle"></i> Approve
        </button>
    </form>
@endif
@php
    // ✅ UPDATE: Bisa proses dengan pembayaran berapapun asal > 0 (seperti Admin)
    $canStartProcess = $salesOrder->status === 'pending' 
        && $salesOrder->approved_by !== null 
        && $salesOrder->paid_total > 0; // ✅ UBAH: > 0 saja, tidak perlu 50%
    
        if ($canStartProcess && in_array($salesOrder->payment_method, ['transfer', 'split'])) {
    // ✅ FIX: Cek payment yang benar-benar TIDAK ADA bukti DAN TIDAK ADA referensi yang valid
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
    
    $canStartProcess = $invalidPayments == 0;
}
@endphp

@php
    $userType = strtolower(Auth::user()->usertype ?? Auth::user()->role ?? '');
    $hasPO = $salesOrder->hasRelatedPO();
    $canPendingToRequestKain = in_array($userType, ['owner', 'kepala_toko', 'finance']);
    $canRequestKainToPayment = $userType === 'finance';
    $canPaymentToProsesJahit = in_array($userType, ['admin', 'finance', 'kepala_toko']);
    $canProsesJahitToPrinting = in_array($userType, ['admin', 'finance', 'kepala_toko']);
    $canPrintingToDiterimaToko = in_array($userType, ['admin', 'finance', 'kepala_toko']);
    $canDiterimaTokoToSelesai = in_array($userType, ['admin', 'finance', 'kepala_toko']);
    
    // Validasi pembayaran untuk pending → request_kain
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

<!-- ✅ WORKFLOW BARU: Tombol sesuai role dan status -->

<!-- pending → request_kain (untuk SO dengan PO) - Owner, Kepala Toko, Finance -->
@if($salesOrder->status === 'pending' && $hasPO && $salesOrder->approved_by !== null && $salesOrder->paid_total > 0 && $paymentValid && $canPendingToRequestKain)
    <form action="{{ route('kepala-toko.sales.move-to-request-kain', $salesOrder) }}" method="POST">
        @csrf
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
            <i class="bi bi-play-circle"></i> Mulai Proses (Request Kain)
        </button>
    </form>
@endif

<!-- pending → selesai (untuk SO tanpa PO) - Setelah approved dan pembayaran lunas -->
@if($salesOrder->status === 'pending' && !$hasPO && $salesOrder->approved_by !== null && $salesOrder->remaining_amount == 0 && in_array($userType, ['admin', 'owner', 'finance', 'kepala_toko']))
    <form action="{{ route('kepala-toko.sales.complete-without-po', $salesOrder) }}" method="POST">
        @csrf
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
            <i class="bi bi-check2-all"></i> Selesaikan (Tanpa PO)
        </button>
    </form>
@endif

<!-- request_kain → payment - Hanya Finance -->
@if($salesOrder->status === 'request_kain' && $hasPO && $canRequestKainToPayment)
    <form action="{{ route('kepala-toko.sales.move-to-payment', $salesOrder) }}" method="POST">
        @csrf
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
            <i class="bi bi-credit-card"></i> Ubah ke Payment
        </button>
    </form>
@endif

<!-- payment → proses_jahit (untuk jahit_sendiri) - Admin, Finance, Kepala Toko -->
@if($salesOrder->status === 'payment' && $salesOrder->order_type === 'jahit_sendiri' && $hasPO && $canPaymentToProsesJahit)
    <form action="{{ route('kepala-toko.sales.process-jahit', $salesOrder) }}" method="POST">
        @csrf
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
            <i class="bi bi-scissors"></i> Proses Jahit
        </button>
    </form>
@endif

<!-- proses_jahit → printing - Admin, Finance, Kepala Toko -->
@if($salesOrder->status === 'proses_jahit' && $salesOrder->order_type === 'jahit_sendiri' && $hasPO && $canProsesJahitToPrinting)
    <form action="{{ route('kepala-toko.sales.mark-as-jadi', $salesOrder) }}" method="POST">
        @csrf
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
            <i class="bi bi-check-circle"></i> Tandai Printing
        </button>
    </form>
@endif

<!-- printing → diterima_toko (jahit_sendiri) atau payment → diterima_toko (beli_jadi) - Admin, Finance, Kepala Toko -->
@if((($salesOrder->order_type === 'jahit_sendiri' && $salesOrder->status === 'printing') || ($salesOrder->order_type === 'beli_jadi' && $salesOrder->status === 'payment')) && $hasPO && $canPrintingToDiterimaToko)
    <form action="{{ route('kepala-toko.sales.mark-as-diterima-toko', $salesOrder) }}" method="POST">
        @csrf
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
            <i class="bi bi-shop"></i> Diterima Toko
        </button>
    </form>
@endif

<!-- diterima_toko → selesai - Admin, Finance, Kepala Toko -->
@if($salesOrder->status === 'diterima_toko' && $salesOrder->remaining_amount == 0 && $canDiterimaTokoToSelesai)
    <form action="{{ route('kepala-toko.sales.complete', $salesOrder) }}" method="POST">
        @csrf
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
            <i class="bi bi-check2-all"></i> Selesaikan
        </button>
    </form>
@endif
                    </div>
                </div>
            </div>

            <!-- Info Pembayaran Alert Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <i class="bi bi-credit-card text-blue-600 text-xl mr-3"></i>
                    <div>
                        <h3 class="font-semibold text-blue-800">Info Pembayaran</h3>
                        <p class="text-sm text-blue-700 mt-1">
                            <strong>Pembayaran hanya bisa ditambah melalui section "Tambah Pembayaran" di bawah.</strong><br>
                            Edit sales order hanya untuk mengubah data order, tidak untuk pembayaran.
                        </p>
                        <div class="mt-2 text-sm">
        <strong>Total Dibayar:</strong> Rp {{ number_format($salesOrder->paid_total, 0, ',', '.') }} |
        <strong>Sisa:</strong> Rp {{ number_format($salesOrder->remaining_amount, 0, ',', '.') }}
    </div>
                    </div>
                </div>
            </div>

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

<!-- ✅ NEW: Success Message -->
@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
        {{ session('success') }}
    </div>
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
                        <div class="flex justify-between"><span class="text-gray-600">Tanggal Deadline:</span><span>{{ \Carbon\Carbon::parse($salesOrder->deadline)->format('d/m/Y') }}</span></div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Customer:</span>
                            <span>
                                {{ $salesOrder->customer ? $salesOrder->customer->name : 'Umum' }}
                                @if($salesOrder->customer && $salesOrder->customer->phone)
                                    <br><small class="text-gray-500">({{ $salesOrder->customer->phone }})</small>
                                @endif
                            </span>
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
                        <!-- ✅ NEW: Shipping Cost Display -->
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
           placeholder="Sisa: Rp {{ number_format($salesOrder->remaining_amount, 0, ',', '.') }}"
           value="{{ old('payment_amount') }}">
    <p class="text-sm text-gray-600 mt-1">Sisa yang harus dibayar: <span class="font-semibold">Rp {{ number_format($salesOrder->remaining_amount, 0, ',', '.') }}</span></p>
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
</div>               <div>
                                <label for="proof_path" class="block font-medium mb-1">Bukti Pembayaran (opsional)</label>
                                <input type="file" name="proof_path" id="proof_path" accept=".jpg,.jpeg,.png,.pdf"
                                       class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">
                                @error('proof_path')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="note" class="block font-medium mb-1">Catatan (opsional)</label>
                            <textarea name="note" id="note" rows="2"
                                      class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">{{ old('note') }}</textarea>
                            @error('note')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
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
                @endphp
                @forelse($salesOrder->payments as $payment)
                    @php
                        $cumulativePayment += $payment->amount;
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
<!-- Tampilkan Link Bukti jika sudah upload -->
@if($payment->proof_path)
    <a href="{{ route('kepala-toko.sales.payment-proof', $payment) }}" target="_blank" class="text-blue-500 text-xs hover:underline inline-flex items-center">
        <i class="bi bi-file-earmark-image mr-1"></i> Lihat Bukti
    </a>
@elseif(in_array($payment->method, ['transfer', 'split']))
    {{-- Form Upload Bukti jika belum ada bukti --}} 
    <!-- PERBAIKAN: TETAP tampilkan form upload, meskipun reference_number sudah ada -->
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
            <button onclick="printPaymentNota({{ $payment->id }})" class="text-green-600 hover:underline" title="Print Langsung">
                <i class="bi bi-printer"></i>
            </button>
            <a href="{{ route('kepala-toko.sales.printNota', $payment) }}" class="text-blue-600 hover:underline" title="Download PDF">
                <i class="bi bi-download"></i>
            </a>
        </div>
    </td>
</tr>
                @empty
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
                <div class="text-right text-green-600 font-medium">Rp {{ number_format($salesOrder->paid_total, 0, ',', '.') }}</div>
                <div class="font-semibold">Sisa pembayaran:</div>
                <div class="text-right @if($salesOrder->remaining_amount > 0) text-red-600 @else text-green-600 @endif font-medium">
                    Rp {{ number_format($salesOrder->remaining_amount, 0, ',', '.') }}
                </div>
                <div class="font-semibold">Status Pembayaran:</div>
                <div class="text-right">
                    <span class="px-2 py-1 rounded-full text-xs font-medium @if($salesOrder->payment_status === 'lunas') bg-green-100 text-green-600 @else bg-yellow-100 text-yellow-600 @endif">
                        {{ ucfirst($salesOrder->payment_status) }}
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
    <tr>
        <td colspan="6" class="px-4 py-2 border text-right font-semibold">Subtotal:</td>
        <td class="px-4 py-2 border text-right font-semibold">Rp {{ number_format($salesOrder->subtotal, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td colspan="6" class="px-4 py-2 border text-right font-semibold">Total Diskon:</td>
        <td class="px-4 py-2 border text-right font-semibold text-red-600">- Rp {{ number_format($salesOrder->discount_total, 0, ',', '.') }}</td>
    </tr>
    <!-- ✅ NEW: Shipping Cost Row -->
    <tr>
        <td colspan="6" class="px-4 py-2 border text-right font-semibold">Ongkir:</td>
        <td class="px-4 py-2 border text-right font-semibold text-green-600">+ Rp {{ number_format($salesOrder->shipping_cost, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td colspan="6" class="px-4 py-2 border text-right font-semibold">Grand Total:</td>
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
                <!-- ✅ NEW: Purchase Order Link Info -->
@if($salesOrder->logs->contains('action', 'linked_to_purchase'))
    <div class="mt-6 p-4 bg-blue-50 rounded-lg">
        <h3 class="font-semibold text-blue-800">Purchase Order Terkait</h3>
        @php
            $linkedLog = $salesOrder->logs->firstWhere('action', 'linked_to_purchase');
            $poNumber = $linkedLog ? explode(': ', $linkedLog->description)[1] ?? null : null;
            $purchaseOrder = $poNumber ? \App\Models\PurchaseOrder::where('po_number', $poNumber)->first() : null;
        @endphp
        @if($purchaseOrder)
            <p class="text-sm">
                PO: <a href="{{ route('kepala-toko.purchases.show', $purchaseOrder) }}" class="text-blue-600 underline">{{ $purchaseOrder->po_number }}</a><br>
                Supplier: {{ $purchaseOrder->supplier->name ?? '-' }}<br>
                Status: <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ $purchaseOrder->status }}</span>
            </p>
        @else
            <p class="text-sm text-gray-600">PO: {{ $poNumber ?? '-' }}</p>
        @endif
    </div>
@endif
            </div>
        </div>
    </div>
</div>

<script>
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

    document.addEventListener('DOMContentLoaded', function () {
        const paymentMethod = '{{ $salesOrder->payment_method }}';
        const proofInput = document.getElementById('proof_path');
        const referenceInput = document.getElementById('reference_number');
        const form = document.getElementById('paymentForm');

        const splitFields = document.getElementById('split-payment-fields');
        splitFields.classList.toggle('hidden', paymentMethod !== 'split');

        document.getElementById('payment_method').addEventListener('change', function () {
            splitFields.classList.toggle('hidden', this.value !== 'split');
            if (this.value !== 'split') {
                document.getElementById('cash_amount').value = 0;
                document.getElementById('transfer_amount').value = 0;
                document.getElementById('proof_path').value = '';
                document.getElementById('reference_number').value = '';
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

        // === PERBAIKAN: Submit validation yang baru ===
        // form.addEventListener('submit', function (e) {
        //     const method = document.getElementById('payment_method').value;
            
        //     if (method === 'transfer' || method === 'split') {
        //         const proof = document.getElementById('proof_path');
        //         const reference = document.getElementById('reference_number');
                
        //         const hasProof = proof && proof.files && proof.files[0];
        //         const hasReference = reference && reference.value.trim() !== '';
                
        //         // Validasi baru: wajib bukti ATAU no referensi
        //         if (!hasProof && !hasReference) {
        //             e.preventDefault();
        //             alert('Untuk metode transfer/split, wajib upload bukti transfer atau isi no referensi.');
        //             return;
        //         }
        //     }
        // });
    });
    document.addEventListener('submit', function(e) {
    if (e.target.classList.contains('upload-proof-form')) {
        const fileInput = e.target.querySelector('input[type="file"]');
        if (!fileInput.files.length) {
            e.preventDefault();
            alert('Harap pilih file bukti terlebih dahulu.');
            return;
        }
    }
});
function printPaymentNota(paymentId) {
    const printBtn = event.target;
    const originalHTML = printBtn.innerHTML;
    printBtn.innerHTML = '<i class="bi bi-hourglass"></i>';
    printBtn.disabled = true;

    const payment = getPaymentById(paymentId);
    if (!payment) {
        alert('Data pembayaran tidak ditemukan!');
        resetButton(printBtn, originalHTML);
        return;
    }

    // === Ambil data dari Blade (dijamin aman karena di-encode via JSON) ===
    const soNumber = '{{ addslashes($salesOrder->so_number) }}';
    const customerName = '{{ addslashes($salesOrder->customer ? $salesOrder->customer->name : 'Umum') }}';
    const kasirName = payment.creator_name || 'System';
    const orderDate = '{{ \Carbon\Carbon::parse($salesOrder->order_date)->format('d/m/Y') }}';
    const grandTotal = {{ $salesOrder->grand_total }};
    const paidTotal = {{ $salesOrder->paid_total }};
    const remaining = {{ $salesOrder->remaining_amount }};
    const paymentStatus = '{{ $salesOrder->payment_status }}';
    const items = {!! json_encode($salesOrder->items->map(function($item) {
        return [
            'name' => substr($item->product_name, 0, 22),
            'qty' => $item->qty,
            'price' => $item->sale_price,
            'subtotal' => $item->line_total
        ];
    })) !!};

    // === Bangun teks nota thermal (58mm, monospace) ===
    let text = "PARE CUSTOM\n";
    text += "NOTA PEMBAYARAN\n";
    text += "--------------------------------\n";
    text += `SO Number   : ${soNumber}\n`;
    text += `Tgl Order   : ${orderDate}\n`;
    text += `Customer    : ${customerName}\n`;
    text += `Kasir       : ${kasirName}\n`;
    text += "--------------------------------\n";

    // Item list (max 22 char nama)
    items.forEach(item => {
        const name = item.name.padEnd(16, ' ').substring(0, 16);
        const qty = String(item.qty).padStart(2, ' ');
        const price = formatNumber(item.price).padStart(10, ' ');
        text += `${name}${qty}x${price}\n`;
    });

    text += "--------------------------------\n";
    text += `TOTAL       : ${formatNumber(grandTotal).padStart(16, ' ')}\n`;
    text += `BAYAR       : ${formatNumber(paidTotal).padStart(16, ' ')}\n`;
    text += `SISA        : ${formatNumber(remaining).padStart(16, ' ')}\n`;
    text += `STATUS      : ${paymentStatus.toUpperCase().padEnd(16, ' ')}\n`;
    text += "--------------------------------\n";
    text += "PEMBAYARAN\n";
    text += "--------------------------------\n";
    text += `Tgl Bayar   : ${formatDate(payment.paid_at)}\n`;
    text += `Metode      : ${payment.method.toUpperCase()}\n`;
    text += `Jumlah      : ${formatNumber(payment.amount).padStart(16, ' ')}\n`;

    if (payment.method === 'split') {
        text += `- Cash     : ${formatNumber(payment.cash_amount).padStart(16, ' ')}\n`;
        text += `- Transfer : ${formatNumber(payment.transfer_amount).padStart(16, ' ')}\n`;
    }

    if (payment.reference_number) {
        text += `Ref         : ${payment.reference_number}\n`;
    }
    if (payment.note) {
        text += `Catatan     : ${payment.note}\n`;
    }

    text += "--------------------------------\n";
    text += "Terima kasih atas pembayarannya!\n";
    text += `*** ${new Date().toLocaleDateString('id-ID')} ${new Date().toLocaleTimeString('id-ID')} ***\n`;
    text += "\x1B\x69"; // ESC/POS cut command

    // Deteksi device
    const isMobile = /Android|iPhone|iPad/i.test(navigator.userAgent);

    if (isMobile) {
        // Kirim ke RawBT
        const encoded = encodeURIComponent(text);
        window.location.href = `rawbt://print?text=${encoded}`;
        setTimeout(() => resetButton(printBtn, originalHTML), 2000);
    } else {
        // Print via browser (PC)
        const printWin = window.open('', '_blank', 'width=230,height=600');
        if (!printWin) {
            alert('Popup diblokir! Izinkan popup untuk cetak.');
            resetButton(printBtn, originalHTML);
            return;
        }
        const html = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Nota ${soNumber}</title>
                <meta charset="UTF-8">
                <style>
                    body { font-family: 'Courier New', monospace; font-size: 12px; width: 58mm; margin: 0; padding: 5px; line-height: 1.3; }
                    pre { margin: 0; white-space: pre; }
                </style>
            </head>
            <body><pre>${text.replace(/\x1B\x69/g, '')}</pre></body>
            </html>
        `;
        printWin.document.write(html);
        printWin.document.close();
        printWin.print();
        setTimeout(() => {
            printWin.close();
            resetButton(printBtn, originalHTML);
        }, 3000);
    }
}

function resetButton(btn, html) {
    btn.innerHTML = html;
    btn.disabled = false;
}

function formatNumber(num) {
    return parseInt(num).toLocaleString('id-ID');
}

function formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID') + ' ' + d.toLocaleTimeString('id-ID', { hour12: false });
}

function getPaymentById(id) {
    const payments = {!! json_encode($salesOrder->payments->map(function($p) {
        return [
            'id' => $p->id,
            'amount' => $p->amount,
            'method' => $p->method,
            'cash_amount' => $p->cash_amount,
            'transfer_amount' => $p->transfer_amount,
            'reference_number' => $p->reference_number,
            'note' => $p->note,
            'paid_at' => $p->paid_at,
            'creator_name' => $p->creator->name ?? 'System'
        ];
    })) !!};
    return payments.find(p => p.id === id);
}
</script>
</body>
</html>