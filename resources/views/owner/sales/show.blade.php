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
    <x-navbar-owner />
    <div class="flex-1 lg:w-5/6">
        <x-navbar-top-owner />
        <div class="p-4 lg:p-8">


            <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Detail Sales Order</h1>
                        <p class="text-sm text-gray-500 mt-1">SO Number: {{ $salesOrder->so_number }}</p>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ route('owner.sales.index') }}"
                           class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded shadow">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                            <a href="{{ route('owner.sales.edit', $salesOrder) }}"
                               class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded shadow">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        @if($salesOrder->status === 'pending' && $salesOrder->approved_by === null)
                            <form action="{{ route('owner.sales.approve', $salesOrder) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
                                    <i class="bi bi-check-circle"></i> Approve
                                </button>
                            </form>
                        @endif
@php
    // Validasi payment: untuk transfer/split, boleh ada proof_path ATAU reference_number
    $canStartProcess = $salesOrder->status === 'pending' 
        && $salesOrder->approved_by !== null 
        && $salesOrder->paid_total >= $salesOrder->grand_total * 0.5;
    
    if ($canStartProcess && in_array($salesOrder->payment_method, ['transfer', 'split'])) {
        // Cek apakah semua payment punya bukti ATAU no referensi
        $invalidPayments = $salesOrder->payments()
            ->where(function($q) {
                $q->whereNull('proof_path')->whereNull('reference_number');
            })
            ->count();
        
        $canStartProcess = $invalidPayments == 0;
    }
@endphp

@if($canStartProcess)
    <form action="{{ route('owner.sales.startProcess', $salesOrder) }}" method="POST">
        @csrf
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
            <i class="bi bi-play-circle"></i> Mulai Proses
        </button>
    </form>
@endif
                        @if($salesOrder->order_type === 'jahit_sendiri' && $salesOrder->status === 'request_kain')
                            <form action="{{ route('owner.sales.processJahit', $salesOrder) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                                    <i class="bi bi-scissors"></i> Proses Jahit
                                </button>
                            </form>
                        @endif
                        @if($salesOrder->order_type === 'jahit_sendiri' && $salesOrder->status === 'proses_jahit')
                            <form action="{{ route('owner.sales.markAsJadi', $salesOrder) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                                    <i class="bi bi-check-circle"></i> Tandai Printing
                                </button>
                            </form>
                        @endif
                        @if(($salesOrder->order_type === 'jahit_sendiri' && $salesOrder->status === 'printing') || ($salesOrder->order_type === 'beli_jadi' && $salesOrder->status === 'payment'))
                            <form action="{{ route('owner.sales.markAsDiterimaToko', $salesOrder) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                                    <i class="bi bi-shop"></i> Diterima Toko
                                </button>
                            </form>
                        @endif
                        @if($salesOrder->status === 'diterima_toko' && $salesOrder->remaining_amount == 0)
                            <form action="{{ route('owner.sales.complete', $salesOrder) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                                    <i class="bi bi-check2-all"></i> Selesaikan
                                </button>
                            </form>
                        @endif
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

            @if(in_array($salesOrder->status, ['pending', 'request_kain', 'payment', 'proses_jahit', 'printing', 'diterima_toko']))
                <div class="bg-gray-100 p-4 rounded-xl mb-6">
                    <h4 class="font-semibold text-gray-700">Debug Status Proses</h4>
                    <p class="text-sm text-gray-600">
                        Approved: {{ $salesOrder->approved_by ? 'Ya (User ID: ' . $salesOrder->approved_by . ')' : 'Belum' }}<br>
                        Total Dibayar: Rp {{ number_format($salesOrder->paid_total, 0, ',', '.') }}<br>
                        Minimal 50% Grand Total: Rp {{ number_format($salesOrder->grand_total * 0.5, 0, ',', '.') }}<br>
                        @if(in_array($salesOrder->payment_method, ['transfer', 'split']))
    @php
        $paymentsWithoutProof = $salesOrder->payments()
            ->where(function($q) {
                $q->whereNull('proof_path')->whereNull('reference_number');
            })
            ->count();
    @endphp
    Bukti Pembayaran: {{ $paymentsWithoutProof == 0 ? 'Semua pembayaran valid (bukti/referensi)' : 'Ada pembayaran tanpa bukti DAN tanpa no referensi' }}<br>
@endif
@php
    $paymentsValid = true;
    if (in_array($salesOrder->payment_method, ['transfer', 'split'])) {
        $paymentsValid = $salesOrder->payments()
            ->where(function($q) {
                $q->whereNull('proof_path')->whereNull('reference_number');
            })
            ->count() == 0;
    }
@endphp

@if($salesOrder->status === 'pending' && $salesOrder->approved_by && $salesOrder->paid_total >= $salesOrder->grand_total * 0.5 && ($salesOrder->payment_method === 'cash' || $paymentsValid))
    <span class="text-green-600">Tombol Mulai Proses harusnya muncul.</span>
@elseif($salesOrder->status === 'pending')
    <span class="text-red-600">Tombol Mulai Proses tidak muncul karena: 
        {{ !$salesOrder->approved_by ? 'Belum di-approve. ' : '' }}
        {{ $salesOrder->paid_total < $salesOrder->grand_total * 0.5 ? 'Pembayaran kurang dari 50%. ' : '' }}
        @if(in_array($salesOrder->payment_method, ['transfer', 'split']) && !$paymentsValid)
            Ada pembayaran tanpa bukti DAN tanpa no referensi.
        @endif
    </span>
                        @elseif($salesOrder->order_type === 'jahit_sendiri' && $salesOrder->status === 'request_kain')
                            <span class="text-green-600">Tombol Proses Jahit harusnya muncul.</span>
                        @elseif($salesOrder->order_type === 'jahit_sendiri' && $salesOrder->status === 'proses_jahit')
                            <span class="text-green-600">Tombol Tandai Printing harusnya muncul.</span>
                        @elseif(($salesOrder->order_type === 'jahit_sendiri' && $salesOrder->status === 'printing') || ($salesOrder->order_type === 'beli_jadi' && $salesOrder->status === 'payment'))
                            <span class="text-green-600">Tombol Diterima Toko harusnya muncul.</span>
                        @elseif($salesOrder->status === 'diterima_toko' && $salesOrder->remaining_amount == 0)
                            <span class="text-green-600">Tombol Selesaikan harusnya muncul.</span>
                        @elseif($salesOrder->status === 'diterima_toko')
                            <span class="text-red-600">Tombol Selesaikan tidak muncul karena pembayaran belum lunas.</span>
                        @endif
                    </p>
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

            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
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
                        <div class="flex justify-between"><span class="text-gray-600">Customer:</span><span>{{ $salesOrder->customer ? $salesOrder->customer->name : 'Umum' }}</span></div>
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
                        <div class="flex justify-between"><span class="text-gray-600">Grand Total:</span><span class="text-lg font-bold text-blue-600">Rp {{ number_format($salesOrder->grand_total, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Total Dibayar:</span><span class="text-green-600 font-medium">Rp {{ number_format($salesOrder->paid_total, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Sisa:</span><span class="@if($salesOrder->remaining_amount > 0) text-red-600 @else text-green-600 @endif font-medium">Rp {{ number_format($salesOrder->remaining_amount, 0, ',', '.') }}</span></div>
                    </div>
                </div>
            </div>

            @if($salesOrder->status !== 'selesai')
                <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                    <h2 class="text-lg font-semibold mb-4 text-gray-800">Tambah Pembayaran</h2>
                    <form action="{{ route('owner.sales.addPayment', $salesOrder) }}" method="POST" enctype="multipart/form-data" id="paymentForm">
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
    </div>
                            <div>
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
    <a href="{{ route('owner.sales.payment-proof', $payment) }}" target="_blank" class="text-blue-500 text-xs hover:underline inline-flex items-center">
        <i class="bi bi-file-earmark-image mr-1"></i> Lihat Bukti
    </a>
@elseif(in_array($payment->method, ['transfer', 'split']))
    {{-- Form Upload Bukti jika belum ada bukti --}} 
    <!-- PERBAIKAN: TETAP tampilkan form upload, meskipun reference_number sudah ada -->
    <form action="{{ route('owner.sales.uploadProof', ['salesOrder' => $salesOrder, 'payment' => $payment]) }}" method="POST" enctype="multipart/form-data" class="upload-proof-form mt-2">
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
        <a href="{{ route('owner.sales.printNota', $payment) }}" class="text-blue-600 hover:underline" title="Download PDF">
            <i class="bi bi-download"></i>
        </a>
        <!-- TAMBAH TOMBOL INI -->
        <button onclick="openEditPaymentMethodModal({{ $payment->id }}, '{{ $payment->method }}', {{ $payment->cash_amount }}, {{ $payment->transfer_amount }}, '{{ $payment->reference_number }}')" 
                class="text-yellow-600 hover:underline" title="Ubah Metode">
            <i class="bi bi-pencil"></i>
        </button>
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
                                <th class="px-4 py-2 border text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salesOrder->items as $item)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-2 border">{{ $item->product_name }}
                                        @if($item->product_id)<br><small class="text-gray-500">ID: {{ $item->product_id }}</small>@endif</td>
                                    <td class="px-4 py-2 border">{{ $item->sku ?? '-' }}</td>
                                    <td class="px-4 py-2 border text-right">Rp {{ number_format($item->sale_price, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2 border text-center">{{ $item->qty }}</td>
                                    <td class="px-4 py-2 border text-right">Rp {{ number_format($item->discount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2 border text-right font-semibold">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="5" class="px-4 py-2 border text-right font-semibold">Subtotal:</td>
                                <td class="px-4 py-2 border text-right font-semibold">Rp {{ number_format($salesOrder->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="5" class="px-4 py-2 border text-right font-semibold">Total Diskon:</td>
                                <td class="px-4 py-2 border text-right font-semibold text-red-600">- Rp {{ number_format($salesOrder->discount_total, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="5" class="px-4 py-2 border text-right font-semibold">Grand Total:</td>
                                <td class="px-4 py-2 border text-right font-semibold text-blue-600">Rp {{ number_format($salesOrder->grand_total, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

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
            </div>
        </div>
    </div>
</div>

<!-- Modal Ubah Metode Pembayaran -->
<div id="editPaymentMethodModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 w-96 mx-4">
        <h3 class="text-lg font-semibold mb-4">Ubah Metode Pembayaran</h3>
        
        <form id="editPaymentMethodForm" method="POST">
            @csrf
            @method('PUT')
            
            <div class="space-y-4">
                <div>
                    <label class="block font-medium mb-1">Metode Pembayaran</label>
                    <select name="method" id="edit_payment_method" required 
                            class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">
                        <option value="cash">Cash</option>
                        <option value="transfer">Transfer</option>
                        <option value="split">Split</option>
                    </select>
                </div>

                <div id="edit_split_fields" class="hidden space-y-2">
                    <div>
                        <label class="block font-medium mb-1">Jumlah Cash</label>
                        <input type="number" name="cash_amount" id="edit_cash_amount" 
                               class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" 
                               step="0.01" min="0" value="0">
                    </div>
                    <div>
                        <label class="block font-medium mb-1">Jumlah Transfer</label>
                        <input type="number" name="transfer_amount" id="edit_transfer_amount" 
                               class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" 
                               step="0.01" min="0" value="0">
                    </div>
                </div>

                <div>
                    <label class="block font-medium mb-1">No Referensi Transfer (Opsional)</label>
                    <input type="text" name="reference_number" id="edit_reference_number" 
                           class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" 
                           placeholder="Contoh: TRF123456789">
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeEditPaymentMethodModal()" 
                        class="px-4 py-2 text-gray-600 hover:text-gray-800">
                    Batal
                </button>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    Simpan Perubahan
                </button>
            </div>
        </form>
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
// Variabel global
let currentEditingPaymentId = null;

// Buka modal edit metode pembayaran
function openEditPaymentMethodModal(paymentId, currentMethod, cashAmount, transferAmount, referenceNumber) {
    currentEditingPaymentId = paymentId;
    
    // Set form values
    document.getElementById('edit_payment_method').value = currentMethod;
    document.getElementById('edit_cash_amount').value = cashAmount || 0;
    document.getElementById('edit_transfer_amount').value = transferAmount || 0;
    document.getElementById('edit_reference_number').value = referenceNumber || '';
    
    // Toggle split fields
    toggleEditSplitFields(currentMethod);
    
    // Set form action
    const form = document.getElementById('editPaymentMethodForm');
    form.action = `/owner/sales/{{ $salesOrder->id }}/payments/${paymentId}/update-method`;
    
    // Show modal
    document.getElementById('editPaymentMethodModal').classList.remove('hidden');
}

// Tutup modal
function closeEditPaymentMethodModal() {
    document.getElementById('editPaymentMethodModal').classList.add('hidden');
    currentEditingPaymentId = null;
}

// Toggle split fields di modal edit
function toggleEditSplitFields(method) {
    const splitFields = document.getElementById('edit_split_fields');
    if (method === 'split') {
        splitFields.classList.remove('hidden');
    } else {
        splitFields.classList.add('hidden');
    }
}

// Event listener untuk select method di modal edit
document.getElementById('edit_payment_method').addEventListener('change', function() {
    toggleEditSplitFields(this.value);
    
    // Auto-set amounts based on method
    const payment = getPaymentById(currentEditingPaymentId);
    if (payment) {
        if (this.value === 'cash') {
            document.getElementById('edit_cash_amount').value = payment.amount;
            document.getElementById('edit_transfer_amount').value = 0;
        } else if (this.value === 'transfer') {
            document.getElementById('edit_cash_amount').value = 0;
            document.getElementById('edit_transfer_amount').value = payment.amount;
        }
    }
});

// Validasi form sebelum submit
document.getElementById('editPaymentMethodForm').addEventListener('submit', function(e) {
    const method = document.getElementById('edit_payment_method').value;
    const cashAmount = parseFloat(document.getElementById('edit_cash_amount').value) || 0;
    const transferAmount = parseFloat(document.getElementById('edit_transfer_amount').value) || 0;
    const payment = getPaymentById(currentEditingPaymentId);
    
    if (method === 'split') {
        if (cashAmount + transferAmount !== payment.amount) {
            e.preventDefault();
            alert('Jumlah cash + transfer harus sama dengan total pembayaran: Rp ' + formatNumber(payment.amount));
            return;
        }
    }
});

// Close modal when clicking outside
document.getElementById('editPaymentMethodModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditPaymentMethodModal();
    }
});

// Escape key to close modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('editPaymentMethodModal').classList.contains('hidden')) {
        closeEditPaymentMethodModal();
    }
});
</script>
</body>
</html>