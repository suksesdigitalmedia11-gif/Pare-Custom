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
    <x-navbar-finance />
    <div class="flex-1 lg:w-5/6">
        <x-navbar-top-finance />
        <div class="p-4 lg:p-8">
            <!-- Debug Info -->
            <!-- <div class="bg-gray-100 p-4 rounded-xl mb-6">
                <h4 class="font-semibold text-gray-700">Debug Info</h4>
                <p class="text-sm text-gray-600">
                    Active Shift: {{ $activeShift ? 'Yes (ID: ' . $activeShift->id . ')' : 'No' }}<br>
                    User Role: {{ Auth::user()->hasRole('finance') ? 'Finance' : 'Other' }}<br>
                    SO Status: {{ $salesOrder->status }}<br>
                    Approved By: {{ $salesOrder->approved_by ? $salesOrder->approver->name : 'Not Approved' }}<br>
                    Paid Total: Rp {{ number_format($salesOrder->paid_total, 0, ',', '.') }}<br>
                    Grand Total: Rp {{ number_format($salesOrder->grand_total, 0, ',', '.') }}<br>
                    Editable: {{ $salesOrder->isEditable() ? 'Yes' : 'No' }}
                </p>
            </div> -->

            <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Detail Sales Order</h1>
                        <p class="text-sm text-gray-500 mt-1">SO Number: {{ $salesOrder->so_number }}</p>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ route('finance.sales.index') }}"
                           class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded shadow">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        @if($salesOrder->isEditable() && $activeShift && Auth::user()->hasRole('finance'))
                            <a href="{{ route('finance.sales.edit', $salesOrder) }}"
                               class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded shadow">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        @endif
                        @if($salesOrder->status === 'pending' && $salesOrder->approved_by === null && $activeShift && Auth::user()->hasRole('finance'))
                            <form action="{{ route('finance.sales.approve', $salesOrder) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
                                    <i class="bi bi-check-circle"></i> Approve
                                </button>
                            </form>
                        @endif
                        @if($salesOrder->status === 'pending' && $salesOrder->approved_by !== null && $salesOrder->paid_total >= $salesOrder->grand_total * 0.5 && $activeShift && Auth::user()->hasRole('finance'))
                            <form action="{{ route('finance.sales.startProcess', $salesOrder) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                                    <i class="bi bi-play-circle"></i> Mulai Proses
                                </button>
                            </form>
                        @endif
                        @if($salesOrder->order_type === 'jahit_sendiri' && $salesOrder->status === 'request_kain' && $activeShift && Auth::user()->hasRole('finance'))
                            <form action="{{ route('finance.sales.processJahit', $salesOrder) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                                    <i class="bi bi-scissors"></i> Proses Jahit
                                </button>
                            </form>
                        @endif
                        @if($salesOrder->order_type === 'jahit_sendiri' && $salesOrder->status === 'proses_jahit' && $activeShift && Auth::user()->hasRole('finance'))
                            <form action="{{ route('finance.sales.markAsJadi', $salesOrder) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                                    <i class="bi bi-check-circle"></i> Tandai Printing
                                </button>
                            </form>
                        @endif
                        @if(($salesOrder->order_type === 'jahit_sendiri' && $salesOrder->status === 'printing') || ($salesOrder->order_type === 'beli_jadi' && $salesOrder->status === 'payment') && $activeShift && Auth::user()->hasRole('finance'))
                            <form action="{{ route('finance.sales.markAsDiterimaToko', $salesOrder) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                                    <i class="bi bi-shop"></i> Diterima Toko
                                </button>
                            </form>
                        @endif
                        @if($salesOrder->status === 'diterima_toko' && $salesOrder->remaining_amount == 0 && $activeShift && Auth::user()->hasRole('finance'))
                            <form action="{{ route('finance.sales.complete', $salesOrder) }}" method="POST">
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
                            Bukti Pembayaran: {{ $salesOrder->payments()->whereNull('proof_path')->count() == 0 ? 'Semua pembayaran memiliki bukti' : 'Ada pembayaran tanpa bukti' }}<br>
                        @endif
                        @if($salesOrder->status === 'pending' && $salesOrder->approved_by && $salesOrder->paid_total >= $salesOrder->grand_total * 0.5 && ($salesOrder->payment_method === 'cash' || $salesOrder->payments()->whereNull('proof_path')->count() == 0))
                            <span class="text-green-600">Tombol Mulai Proses harusnya muncul.</span>
                        @elseif($salesOrder->status === 'pending')
                            <span class="text-red-600">Tombol Mulai Proses tidak muncul karena: 
                                {{ !$salesOrder->approved_by ? 'Belum di-approve. ' : '' }}
                                {{ $salesOrder->paid_total < $salesOrder->grand_total * 0.5 ? 'Pembayaran kurang dari 50%. ' : '' }}
                                @if(in_array($salesOrder->payment_method, ['transfer', 'split']) && $salesOrder->payments()->whereNull('proof_path')->count() > 0)
                                    Ada pembayaran tanpa bukti.
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
                        <div class="flex justify-between"><span class="text-gray-600">Customer:</span><span>{{ $salesOrder->customer->name ?? 'Guest' }}</span></div>
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

            @if($salesOrder->status !== 'selesai' && $activeShift && Auth::user()->hasRole('finance'))
                <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                    <h2 class="text-lg font-semibold mb-4 text-gray-800">Tambah Pembayaran</h2>
                    <form action="{{ route('finance.sales.addPayment', $salesOrder) }}" method="POST" enctype="multipart/form-data" id="paymentForm">
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
                                <label for="reference" class="block font-medium mb-1">No. Referensi (opsional)</label>
                                <input type="text" name="reference" id="reference" value="{{ old('reference') }}"
                                       class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300">
                                @error('reference')
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
        @if($payment->proof_path)
            <a href="{{ route('owner.sales.payment-proof', $payment) }}" target="_blank" class="text-blue-500 text-xs hover:underline inline-flex items-center">
                <i class="bi bi-file-earmark-image mr-1"></i> Lihat Bukti
            </a>
        @elseif(in_array($payment->method, ['transfer', 'split']) && $activeShift && Auth::user()->hasRole('owner'))
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
    <!-- ✅ TAMBAH ROW ONGKIR DI SINI -->
    <tr>
        <td colspan="5" class="px-4 py-2 border text-right font-semibold">Ongkir:</td>
        <td class="px-4 py-2 border text-right font-semibold text-green-600">+ Rp {{ number_format($salesOrder->shipping_cost, 0, ',', '.') }}</td>
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

        function updateProofRequired(method) {
            proofInput.required = (method === 'transfer' || method === 'split');
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
    function printPaymentNota(paymentId) {
    // Tampilkan loading
    const printBtn = event.target;
    const originalHTML = printBtn.innerHTML;
    printBtn.innerHTML = '<i class="bi bi-hourglass"></i>';
    printBtn.disabled = true;

    // Cari data payment berdasarkan ID
    const payment = getPaymentById(paymentId);
    if (!payment) {
        alert('Data pembayaran tidak ditemukan!');
        printBtn.innerHTML = originalHTML;
        printBtn.disabled = false;
        return;
    }

    // Format plain text yang sudah terbukti work
    const textContent = `PARECUSTOM
NOTA PEMBAYARAN
${''.padEnd(32, '-')}
SO Number  : {{ $salesOrder->so_number }}
Customer   : {{ $salesOrder->customer->name ?? 'Guest' }}
Tanggal    : ${new Date().toLocaleDateString('id-ID')} ${new Date().toLocaleTimeString('id-ID')}
${''.padEnd(32, '-')}
Grand Total: Rp ${formatNumber({{ $salesOrder->grand_total }})}
Total Bayar: Rp ${formatNumber({{ $salesOrder->paid_total }})}
Sisa       : Rp ${formatNumber({{ $salesOrder->remaining_amount }})}
${''.padEnd(32, '-')}
DETAIL PEMBAYARAN
${''.padEnd(32, '-')}
Tanggal Bayar: ${formatDate(payment.paid_at)}
Metode      : ${payment.method.toUpperCase()}
Jumlah      : Rp ${formatNumber(payment.amount)}
${payment.method === 'split' ? `- Cash     : Rp ${formatNumber(payment.cash_amount)}
- Transfer : Rp ${formatNumber(payment.transfer_amount)}` : ''}
${payment.reference ? `Referensi  : ${payment.reference}` : ''}
${payment.note ? `Catatan    : ${payment.note}` : ''}
${''.padEnd(32, '-')}
Operator   : ${payment.creator_name || 'System'}
${''.padEnd(32, '-')}
Terima kasih atas pembayarannya
*** ${new Date().toLocaleDateString('id-ID')} ${new Date().toLocaleTimeString('id-ID')} ***`;

    // Buka window baru untuk print
    const printWindow = window.open('', '_blank', 'width=230,height=500');
    
    if (!printWindow) {
        alert('Popup diblokir! Izinkan popup untuk cetak.');
        printBtn.innerHTML = originalHTML;
        printBtn.disabled = false;
        return;
    }

    const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Payment Nota</title>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: 'Courier New', monospace;
                    font-size: 11px;
                    width: 58mm;
                    margin: 0;
                    padding: 5px;
                    line-height: 1.2;
                }
                pre {
                    margin: 0;
                    white-space: pre;
                    font-family: 'Courier New', monospace;
                }
                @media print {
                    body { margin: 0; padding: 5px; }
                }
            </style>
        </head>
        <body>
            <pre>${textContent}</pre>
            <script>
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                        setTimeout(function() {
                            window.close();
                        }, 100);
                    }, 100);
                };
            <\/script>
        </body>
        </html>
    `;

    printWindow.document.write(html);
    printWindow.document.close();

    // Reset tombol setelah 3 detik
    setTimeout(function() {
        printBtn.innerHTML = originalHTML;
        printBtn.disabled = false;
    }, 3000);
}

// Helper functions
function formatNumber(num) {
    return parseInt(num).toLocaleString('id-ID');
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID') + ' ' + date.toLocaleTimeString('id-ID');
}

// Function untuk mendapatkan data payment dari JavaScript
function getPaymentById(paymentId) {
    const payments = {!! json_encode($salesOrder->payments->map(function($payment) {
        return [
            'id' => $payment->id,
            'amount' => $payment->amount,
            'method' => $payment->method,
            'cash_amount' => $payment->cash_amount,
            'transfer_amount' => $payment->transfer_amount,
            'reference' => $payment->reference,
            'note' => $payment->note,
            'paid_at' => $payment->paid_at,
            'creator_name' => $payment->creator->name ?? 'System'
        ];
    })) !!};
    
    return payments.find(p => p.id === paymentId);
}
</script>
</body>
</html>