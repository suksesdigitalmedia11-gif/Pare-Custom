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
        body {
            font-family: 'Raleway', sans-serif;
        }

        /* Thermal Receipt Styles */
        .thermal-receipt {
            width: 58mm;
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
                size: 58mm auto;
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

            button,
            .no-print {
                display: none !important;
            }
        }

        @media screen {
            .thermal-receipt {
                border: 1px solid #ccc;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                margin-bottom: 20px;
            }
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
                                    <button type="submit"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
                                        <i class="bi bi-check-circle"></i> Approve
                                    </button>
                                </form>
                            @endif
                            @php
                                $userType = strtolower(Auth::user()->usertype ?? Auth::user()->role ?? '');
                                $hasPO = $salesOrder->hasRelatedPO();
                                $canPendingToRequestKain = in_array($userType, ['owner', 'kepala_toko', 'finance', 'admin']);
                                $canRequestKainToPayment = $userType === 'finance';
                                $canPaymentToProsesJahit = in_array($userType, ['admin', 'finance', 'kepala_toko']);
                                $canProsesJahitToPrinting = in_array($userType, ['admin', 'finance', 'kepala_toko']);
                                $canPrintingToDiterimaToko = in_array($userType, ['admin', 'finance', 'kepala_toko']);
                                $canDiterimaTokoToSelesai = in_array($userType, ['admin', 'finance', 'kepala_toko']);

                                // Validasi pembayaran untuk pending → request_kain
                                $paymentValid = true;
                                $invalidPayments = $salesOrder->payments()
                                    ->whereIn('method', ['transfer', 'split'])
                                    ->whereNull('proof_path')
                                    ->where(function ($q) {
                                        $q->whereNull('reference_number')
                                            ->orWhere('reference_number', '')
                                            ->orWhere('reference_number', ' ')
                                            ->orWhere('reference_number', 'null')
                                            ->orWhere('reference_number', 'NULL');
                                    })
                                    ->count();
                                $paymentValid = $invalidPayments == 0;
                            @endphp

                            <!-- ✅ INFORMASI JIKA TOMBOL TIDAK MUNCUL -->
                            @if($salesOrder->status === 'pending' && $salesOrder->approved_by !== null)
                                {{-- 1. Cek Payment Valid --}}
                                @if(in_array($salesOrder->payment_method, ['transfer', 'split']) && !$paymentValid)
                                    <div
                                        class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded mb-4 flex items-start gap-3">
                                        <i class="bi bi-exclamation-triangle-fill text-yellow-600 mt-1"></i>
                                        <div>
                                            <h4 class="font-bold">Bukti Pembayaran Belum Lengkap</h4>
                                            <p class="text-sm">Anda memilih pembayaran Transfer/Split, namun belum ada bukti
                                                transfer atau nomor referensi yang valid pada riwayat pembayaran. Harap lengkapi
                                                salah satu (Upload Bukti atau Isi No Referensi) agar tombol proses muncul.</p>
                                        </div>
                                    </div>
                                @endif

                                {{-- 2. Cek PO Existence untuk Jahit Sendiri --}}
                                @if($salesOrder->order_type === 'jahit_sendiri' && !$hasPO && !$salesOrder->add_to_purchase)
                                    <div
                                        class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded mb-4 flex items-start gap-3">
                                        <i class="bi bi-file-earmark-x-fill text-red-600 mt-1"></i>
                                        <div>
                                            <h4 class="font-bold">Purchase Order (PO) Tidak Ditemukan</h4>
                                            <p class="text-sm">Order ini bertipe 'Jahit Sendiri' namun tidak memiliki PO Kain
                                                terkait. Tombol proses produksi tidak akan muncul. Anda hanya dapat
                                                menyelesaikan order ini secara langsung jika pembayaran lunas (Workflow Tanpa
                                                PO).</p>
                                        </div>
                                    </div>
                                @elseif($salesOrder->order_type === 'jahit_sendiri' && !$hasPO && $salesOrder->add_to_purchase)
                                    <div
                                        class="bg-blue-100 border border-blue-400 text-blue-800 px-4 py-3 rounded mb-4 flex items-start gap-3">
                                        <i class="bi bi-info-circle-fill text-blue-600 mt-1"></i>
                                        <div>
                                            <h4 class="font-bold">Info: Penyiapan PO Kain</h4>
                                            <p class="text-sm">Order ini memerlukan PO Kain namun belum terbuat secara otomatis. Menekan tombol "Mulai Proses" di bawah akan mencoba membuat PO secara otomatis sebelum memindahkan status.</p>
                                        </div>
                                    </div>
                                @endif
                            @endif

                            <!-- ✅ WORKFLOW BARU: Tombol sesuai role dan status -->

                            <!-- pending → request_kain (untuk SO dengan PO) - Owner, Kepala Toko, Finance -->
                            @if($salesOrder->status === 'pending' && ($hasPO || $salesOrder->add_to_purchase) && $salesOrder->approved_by !== null && $salesOrder->paid_total > 0 && $paymentValid && $canPendingToRequestKain)
                                <form action="{{ route('owner.sales.move-to-request-kain', $salesOrder) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                                        <i class="bi bi-play-circle"></i> Mulai Proses (Request Kain)
                                    </button>
                                </form>
                            @endif

                            <!-- pending → selesai (untuk SO tanpa PO) - Setelah approved dan pembayaran lunas -->
                            @if($salesOrder->status === 'pending' && !$hasPO && !$salesOrder->add_to_purchase && $salesOrder->approved_by !== null && $salesOrder->remaining_amount == 0 && in_array($userType, ['admin', 'owner', 'finance', 'kepala_toko']))
                                <form action="{{ route('owner.sales.complete-without-po', $salesOrder) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                                        <i class="bi bi-check2-all"></i> Selesaikan (Tanpa PO)
                                    </button>
                                </form>
                            @endif

                            <!-- request_kain → payment - Hanya Finance -->
                            @if($salesOrder->status === 'request_kain' && $hasPO && $canRequestKainToPayment)
                                <form action="{{ route('owner.sales.move-to-payment', $salesOrder) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
                                        <i class="bi bi-credit-card"></i> Ubah ke Payment
                                    </button>
                                </form>
                            @endif

                            <!-- payment → proses_jahit (untuk jahit_sendiri) - Admin, Finance, Kepala Toko -->
                            @if($salesOrder->status === 'payment' && $salesOrder->order_type === 'jahit_sendiri' && $hasPO && $canPaymentToProsesJahit)
                                <form action="{{ route('owner.sales.process-jahit', $salesOrder) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                                        <i class="bi bi-scissors"></i> Proses Jahit
                                    </button>
                                </form>
                            @endif

                            <!-- proses_jahit → printing - Admin, Finance, Kepala Toko -->
                            @if($salesOrder->status === 'proses_jahit' && $salesOrder->order_type === 'jahit_sendiri' && $hasPO && $canProsesJahitToPrinting)
                                <form action="{{ route('owner.sales.mark-as-jadi', $salesOrder) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                                        <i class="bi bi-check-circle"></i> Tandai Printing
                                    </button>
                                </form>
                            @endif

                            <!-- printing → diterima_toko (jahit_sendiri) atau payment → diterima_toko (beli_jadi) - Admin, Finance, Kepala Toko -->
                            @if((($salesOrder->order_type === 'jahit_sendiri' && $salesOrder->status === 'printing') || ($salesOrder->order_type === 'beli_jadi' && $salesOrder->status === 'payment')) && $hasPO && $canPrintingToDiterimaToko)
                                <form action="{{ route('owner.sales.mark-as-diterima-toko', $salesOrder) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                                        <i class="bi bi-shop"></i> Diterima Toko
                                    </button>
                                </form>
                            @endif

                            <!-- diterima_toko → selesai - Admin, Finance, Kepala Toko -->
                            @if($salesOrder->status === 'diterima_toko' && $salesOrder->remaining_amount == 0 && $canDiterimaTokoToSelesai)
                                <form action="{{ route('owner.sales.complete', $salesOrder) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
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

                <!-- Info Pembayaran Alert Box -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <i class="bi bi-credit-card text-blue-600 text-xl mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-blue-800">Info Pembayaran</h3>
                            <p class="text-sm text-blue-700 mt-1">
                                <strong>Pembayaran hanya bisa ditambah melalui section "Tambah Pembayaran" di
                                    bawah.</strong><br>
                                Edit sales order hanya untuk mengubah data order, tidak untuk pembayaran.
                            </p>
                            <div class="mt-2 text-sm">
                                <strong>Total Dibayar:</strong> Rp
                                {{ number_format($salesOrder->paid_total, 0, ',', '.') }} |
                                <strong>Sisa:</strong> Rp
                                {{ number_format($salesOrder->remaining_amount, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <h2 class="text-lg font-semibold mb-4 text-gray-800">Informasi Order</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between"><span class="text-gray-600">SO Number:</span><span
                                    class="font-mono font-semibold">{{ $salesOrder->so_number }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Tipe Order:</span><span
                                    class="capitalize">{{ str_replace('_', ' ', $salesOrder->order_type) }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Tanggal
                                    Order:</span><span>{{ \Carbon\Carbon::parse($salesOrder->order_date)->format('d/m/Y') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Deadline:</span>
                                <span>{{ $salesOrder->deadline ? \Carbon\Carbon::parse($salesOrder->deadline)->format('d/m/Y') : '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Customer:</span>
                                <span>
                                    {{ $salesOrder->customer ? $salesOrder->customer->name : 'Umum' }}
                                    @if($salesOrder->customer && $salesOrder->customer->phone)
                                        <br><small class="text-gray-500">({{ $salesOrder->customer->phone }})</small>
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between"><span class="text-gray-600">Dibuat
                                    Oleh:</span><span>{{ $salesOrder->creator->name ?? 'System' }}</span></div>
                            @if($salesOrder->approved_by)
                                <div class="flex justify-between"><span class="text-gray-600">Disetujui
                                        Oleh:</span><span>{{ $salesOrder->approver->name ?? 'System' }}</span></div>
                            @endif
                            @if($salesOrder->approved_at)
                                <div class="flex justify-between"><span class="text-gray-600">Tanggal
                                        Approve:</span><span>{{ \Carbon\Carbon::parse($salesOrder->approved_at)->format('d/m/Y H:i') }}</span>
                                </div>
                            @endif
                            @if($salesOrder->completed_at)
                                <div class="flex justify-between"><span class="text-gray-600">Tanggal
                                        Selesai:</span><span>{{ \Carbon\Carbon::parse($salesOrder->completed_at)->format('d/m/Y H:i') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <h2 class="text-lg font-semibold mb-4 text-gray-800">Informasi Pembayaran & Status</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between"><span class="text-gray-600">Status Order:</span><span
                                    class="px-2 py-1 rounded-full text-xs font-medium @if($salesOrder->status === 'selesai') bg-green-100 text-green-600 @elseif(in_array($salesOrder->status, ['request_kain', 'payment', 'proses_jahit', 'printing', 'diterima_toko'])) bg-yellow-100 text-yellow-600 @else bg-blue-100 text-blue-600 @endif">{{ ucfirst(str_replace('_', ' ', $salesOrder->status)) }}</span>
                            </div>
                            <div class="flex justify-between"><span class="text-gray-600">Metode Pembayaran:</span><span
                                    class="capitalize">{{ $salesOrder->payment_method }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Status Pembayaran:</span><span
                                    class="px-2 py-1 rounded-full text-xs font-medium @if($salesOrder->payment_status === 'lunas') bg-green-100 text-green-600 @else bg-yellow-100 text-yellow-600 @endif">{{ ucfirst($salesOrder->payment_status) }}</span>
                            </div>
                            <div class="flex justify-between"><span class="text-gray-600">Subtotal:</span><span>Rp
                                    {{ number_format($salesOrder->subtotal, 0, ',', '.') }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Diskon:</span><span>Rp
                                    {{ number_format($salesOrder->discount_total, 0, ',', '.') }}</span></div>
                            <!-- ✅ TAMBAH DISPLAY ONGKIR DI SINI -->
                            <div class="flex justify-between"><span class="text-gray-600">Ongkir:</span><span>Rp
                                    {{ number_format($salesOrder->shipping_cost, 0, ',', '.') }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Grand Total:</span><span
                                    class="text-lg font-bold text-blue-600">Rp
                                    {{ number_format($salesOrder->grand_total, 0, ',', '.') }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Total Dibayar:</span><span
                                    class="text-green-600 font-medium">Rp
                                    {{ number_format($salesOrder->paid_total, 0, ',', '.') }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Sisa:</span><span
                                    class="@if($salesOrder->remaining_amount > 0) text-red-600 @else text-green-600 @endif font-medium">Rp
                                    {{ number_format($salesOrder->remaining_amount, 0, ',', '.') }}</span></div>
                        </div>
                    </div>
                </div>

                @if($salesOrder->status !== 'selesai')
                    <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                        <h2 class="text-lg font-semibold mb-4 text-gray-800">Tambah Pembayaran</h2>
                        <form action="{{ route('owner.sales.addPayment', $salesOrder) }}" method="POST"
                            enctype="multipart/form-data" id="paymentForm">
                            @csrf
                            <div class="grid md:grid-cols-2 gap-4 mb-4">
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
                                    <label for="payment_amount" class="block font-medium mb-1">Jumlah Pembayaran</label>
                                    <input type="number" name="payment_amount" id="payment_amount" min="0" step="0.01"
                                        required class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                                        placeholder="Sisa: Rp {{ number_format($salesOrder->remaining_amount, 0, ',', '.') }}"
                                        value="{{ old('payment_amount') }}">
                                    <p class="text-sm text-gray-600 mt-1">Sisa yang harus dibayar: <span
                                            class="font-semibold">Rp
                                            {{ number_format($salesOrder->remaining_amount, 0, ',', '.') }}</span></p>
                                    @error('payment_amount')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div id="split-payment-fields" class="hidden col-span-2">
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
                                    <label for="reference_number" class="block font-medium mb-1">No Referensi Transfer
                                        (Opsional)</label>
                                    <input type="text" name="reference_number" id="reference_number"
                                        class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300"
                                        placeholder="Contoh: TRF123456789" value="{{ old('reference_number') }}">
                                    <p class="text-sm text-gray-600 mt-1">No referensi bank atau keterangan</p>
                                    @error('reference_number')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="proof_path" class="block font-medium mb-1">Bukti Pembayaran
                                        (opsional)</label>
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
                            <button type="submit"
                                class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded shadow">
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
                                    <tr
                                        class="border-b hover:bg-gray-50 {{ $loop->first ? 'border-l-4 border-l-green-500 bg-green-50' : '' }}">
                                        <td class="px-4 py-2 border">
                                            {{ \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y H:i') }}
                                            @if($loop->first)
                                                <span
                                                    class="ml-2 bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded">Terbaru</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 border">
                                            @if($payment->method === 'cash')
                                                <span class="inline-flex items-center"><i
                                                        class="bi bi-cash mr-1 text-green-600"></i> Cash</span>
                                            @elseif($payment->method === 'transfer')
                                                <span class="inline-flex items-center"><i
                                                        class="bi bi-bank mr-1 text-blue-600"></i> Transfer</span>
                                            @else
                                                <span class="inline-flex items-center"><i
                                                        class="bi bi-cash-stack mr-1 text-purple-600"></i> Split</span>
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
                                                <a href="{{ route('owner.sales.payment-proof', $payment) }}" target="_blank"
                                                    class="text-blue-500 text-xs hover:underline inline-flex items-center">
                                                    <i class="bi bi-file-earmark-image mr-1"></i> Lihat Bukti
                                                </a>
                                            @elseif(in_array($payment->method, ['transfer', 'split']))
                                                {{-- Form Upload Bukti jika belum ada bukti --}}
                                                <!-- PERBAIKAN: TETAP tampilkan form upload, meskipun reference_number sudah ada -->
                                                <form
                                                    action="{{ route('owner.sales.uploadProof', ['salesOrder' => $salesOrder, 'payment' => $payment]) }}"
                                                    method="POST" enctype="multipart/form-data" class="upload-proof-form mt-2">
                                                    @csrf
                                                    <input type="file" name="proof_path" accept=".jpg,.jpeg,.png,.pdf"
                                                        class="border rounded px-2 py-1 text-xs w-full" required>
                                                    <button type="submit"
                                                        class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs mt-1 w-full">
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
                                                <button onclick="showPrintOptions({{ $payment->id }})"
                                                    class="text-green-600 hover:text-green-800" title="Cetak Nota">
                                                    <i class="bi bi-printer"></i>
                                                </button>
                                                <a href="{{ route('owner.sales.printNota', $payment) }}"
                                                    class="text-blue-600 hover:text-blue-800" title="Download PDF">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                <!-- TAMBAH TOMBOL INI -->
                                                <button
                                                    onclick="openEditPaymentMethodModal({{ $payment->id }}, '{{ $payment->method }}', {{ $payment->cash_amount }}, {{ $payment->transfer_amount }}, '{{ $payment->reference_number }}')"
                                                    class="text-yellow-600 hover:text-yellow-800" title="Ubah Metode">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                {{-- TOMBOL HAPUS (HARD DELETE) --}}
                                                @if(Auth::user()->role === 'owner' || Auth::user()->usertype === 'owner')
                                                    <form
                                                        action="{{ route('owner.sales.payments.destroy', ['salesOrder' => $salesOrder->id, 'payment' => $payment->id]) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus pembayaran ini?\n\nPERINGATAN: Data pembayaran akan dihapus secara PERMANEN beserta bukti transfernya.\nSemua relasi data akan ikut terhapus clean.\n\nLanjutkan?');"
                                                        class="inline-block">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-800"
                                                            title="Hapus Permanen">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-gray-500 px-4 py-4">Belum ada pembayaran
                                        </td>
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
                                <div class="text-right text-green-600 font-medium">Rp
                                    {{ number_format($salesOrder->paid_total, 0, ',', '.') }}
                                </div>
                                <div class="font-semibold">Sisa pembayaran:</div>
                                <div
                                    class="text-right @if($salesOrder->remaining_amount > 0) text-red-600 @else text-green-600 @endif font-medium">
                                    Rp {{ number_format($salesOrder->remaining_amount, 0, ',', '.') }}
                                </div>
                                <div class="font-semibold">Status Pembayaran:</div>
                                <div class="text-right">
                                    <span
                                        class="px-2 py-1 rounded-full text-xs font-medium @if($salesOrder->payment_status === 'lunas') bg-green-100 text-green-600 @else bg-yellow-100 text-yellow-600 @endif">
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
                                        $hasDesignItems = $salesOrder->items->contains(function ($item) {
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
                                            @if($item->product_id)<br><small class="text-gray-500">ID:
                                            {{ $item->product_id }}</small>@endif
                                            @if($item->requires_design || in_array($item->product_type, ['dtf', 'jersey']))
                                                <br><span
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-700 mt-1">
                                                    <i class="bi bi-brush mr-1"></i> Butuh Desain
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 border">{{ $item->sku ?? '-' }}</td>
                                        <td class="px-4 py-2 border text-right">Rp
                                            {{ number_format($item->sale_price, 0, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-2 border text-center">{{ $item->qty }}</td>
                                        <td class="px-4 py-2 border text-right">Rp
                                            {{ number_format($item->discount, 0, ',', '.') }}
                                        </td>
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
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$item->design_status] ?? 'bg-gray-100 text-gray-600' }}">
                                                        {{ $statusLabels[$item->design_status] ?? $item->design_status ?? 'Belum Ditetapkan' }}
                                                    </span>
                                                    @if($item->design_preview_path)
                                                        <br><a href="{{ Storage::url($item->design_preview_path) }}" target="_blank"
                                                            class="text-xs text-blue-600 hover:underline mt-1 inline-flex items-center">
                                                            <i class="bi bi-eye mr-1"></i> Preview
                                                        </a>
                                                    @endif
                                                @else
                                                    <span class="text-xs text-gray-400">-</span>
                                                @endif
                                            </td>
                                        @endif
                                        <td class="px-4 py-2 border text-right font-semibold">Rp
                                            {{ number_format($item->line_total, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                @php
                                    $colspan = $hasDesignItems ? 7 : 6;
                                @endphp
                                <tr>
                                    <td colspan="{{ $colspan }}" class="px-4 py-2 border text-right font-semibold">
                                        Subtotal:</td>
                                    <td class="px-4 py-2 border text-right font-semibold">Rp
                                        {{ number_format($salesOrder->subtotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="{{ $colspan }}" class="px-4 py-2 border text-right font-semibold">Total
                                        Diskon:</td>
                                    <td class="px-4 py-2 border text-right font-semibold text-red-600">- Rp
                                        {{ number_format($salesOrder->discount_total, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <!-- ✅ TAMBAH ROW ONGKIR DI SINI -->
                                <tr>
                                    <td colspan="{{ $colspan }}" class="px-4 py-2 border text-right font-semibold">
                                        Ongkir:</td>
                                    <td class="px-4 py-2 border text-right font-semibold text-green-600">+ Rp
                                        {{ number_format($salesOrder->shipping_cost, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="{{ $colspan }}" class="px-4 py-2 border text-right font-semibold">Grand
                                        Total:</td>
                                    <td class="px-4 py-2 border text-right font-semibold text-blue-600">Rp
                                        {{ number_format($salesOrder->grand_total, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                @php
                    use Illuminate\Support\Facades\Storage;
                    use Illuminate\Support\Str;
                    $designItems = $salesOrder->items->filter(function ($item) {
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
                                <p class="text-sm text-gray-500 mt-1">Pantau progress desain untuk item DTF dan Jersey yang
                                    membutuhkan desain.</p>
                            </div>
                            <a href="{{ route('editor.dashboard', ['search' => $salesOrder->so_number]) }}" target="_blank"
                                class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
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
                                            <p class="text-sm text-gray-500 mt-1">Qty: {{ $item->qty }} • SKU:
                                                {{ $item->sku ?? '-' }}
                                            </p>
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
                                                    <strong>Feedback Customer:</strong>
                                                    {{ Str::limit($item->design_feedback, 100) }}
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
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border {{ $statusColors[$item->design_status] ?? 'bg-gray-100 text-gray-600 border-gray-300' }}">
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
                                            <a href="{{ Storage::url($item->design_reference_path) }}" target="_blank"
                                                class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                                <i class="bi bi-cloud-arrow-down"></i>
                                                Download Brief
                                            </a>
                                        @endif
                                        @if($item->design_preview_path)
                                            <a href="{{ Storage::url($item->design_preview_path) }}" target="_blank"
                                                class="text-sm text-emerald-600 hover:text-emerald-800 flex items-center gap-1">
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
                        <div><span class="text-gray-600">Dibuat
                                pada:</span><span>{{ $salesOrder->created_at->format('d/m/Y H:i:s') }}</span></div>
                        <div><span class="text-gray-600">Terakhir
                                diupdate:</span><span>{{ $salesOrder->updated_at->format('d/m/Y H:i:s') }}</span></div>
                        <div><span class="text-gray-600">Dibuat
                                Oleh:</span><span>{{ $salesOrder->creator->name ?? 'System' }}</span></div>
                        @if($salesOrder->approved_by)
                            <div><span class="text-gray-600">Disetujui
                                    Oleh:</span><span>{{ $salesOrder->approver->name ?? 'System' }}</span></div>
                        @endif
                        @if($salesOrder->approved_at)
                            <div><span class="text-gray-600">Disetujui
                                    pada:</span><span>{{ \Carbon\Carbon::parse($salesOrder->approved_at)->format('d/m/Y H:i') }}</span>
                            </div>
                        @endif
                        @if($salesOrder->completed_at)
                            <div><span class="text-gray-600">Diselesaikan
                                    pada:</span><span>{{ \Carbon\Carbon::parse($salesOrder->completed_at)->format('d/m/Y H:i') }}</span>
                            </div>
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
                                            <td class="px-4 py-2 border">
                                                {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-4 py-2 border">{{ ucfirst(str_replace('_', ' ', $log->action)) }}
                                            </td>
                                            <td class="px-4 py-2 border">{{ $log->description }}</td>
                                            <td class="px-4 py-2 border">{{ $log->user->name ?? 'System' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-gray-500 px-4 py-4">Belum ada log
                                                aktivitas</td>
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
                <button onclick="printThermalHTML()"
                    class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg flex items-center justify-center gap-3 shadow">
                    <i class="bi bi-printer text-xl"></i>
                    <div class="text-left">
                        <div class="font-semibold">Thermal Printer</div>
                        <div class="text-xs opacity-90">Format thermal 58mm + detail barang</div>
                    </div>
                </button>

                <!-- Option 2: ESC/POS Text -->
                <button onclick="printESCPOS()"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg flex items-center justify-center gap-3 shadow">
                    <i class="bi bi-file-text text-xl"></i>
                    <div class="text-left">
                        <div class="font-semibold">Text Printer</div>
                        <div class="text-xs opacity-90">Format text + detail barang</div>
                    </div>
                </button>

                <!-- Option 3: PDF Download -->
                <button onclick="downloadThermalPDF()"
                    class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-lg flex items-center justify-center gap-3 shadow">
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

    <!-- Modal Ubah Metode Pembayaran -->
    <div id="editPaymentMethodModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
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
                                class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" step="0.01"
                                min="0" value="0">
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Jumlah Transfer</label>
                            <input type="number" name="transfer_amount" id="edit_transfer_amount"
                                class="border rounded px-3 py-2 w-full focus:ring focus:ring-blue-300" step="0.01"
                                min="0" value="0">
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
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
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
        document.addEventListener('submit', function (e) {
            if (e.target.classList.contains('upload-proof-form')) {
                const fileInput = e.target.querySelector('input[type="file"]');
                if (!fileInput.files.length) {
                    e.preventDefault();
                    alert('Harap pilih file bukti terlebih dahulu.');
                    return;
                }
            }
        });
        // Global variables untuk print modal
        let currentPaymentId = null;
        let currentPaymentData = null;
        const RECEIPT_CHAR_WIDTH = 32; // 58mm thermal = 32 karakter
        const RECEIPT_MAX_WIDTH = 32; // Max karakter per baris

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

        // Calculate total paid from all payments
        function calculateTotalPaid(payments) {
            if (!payments || payments.length === 0) return 0;
            return payments.reduce((total, payment) => total + (parseFloat(payment.amount) || 0), 0);
        }

        // Calculate remaining amount
        function calculateRemaining(grandTotal, totalPaid) {
            return Math.max(0, parseFloat(grandTotal) - totalPaid);
        }

        // 1. PURE HTML THERMAL PRINTING
        function printThermalHTML() {
            if (!currentPaymentData) return;

            showLoading('Menyiapkan cetakan thermal...');

            const salesOrder = {!! json_encode($salesOrder) !!};
            const payment = currentPaymentData;
            const allPayments = {!! json_encode($salesOrder->payments->map(function ($payment) {
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

            const totalPaid = calculateTotalPaid(allPayments);
            const remaining = calculateRemaining(salesOrder.grand_total, totalPaid);

            const textReceipt = buildThermalReceiptText(salesOrder, payment, totalPaid, remaining);

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
            const allPayments = {!! json_encode($salesOrder->payments->map(function ($payment) {
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
            window.open('{{ route("owner.sales.printNota", ":paymentId") }}'.replace(':paymentId', currentPaymentId), '_blank');
            closePrintModal();
        }

        // Build thermal receipt text
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
            if (salesOrder.shipping_cost > 0) {
                lines.push(alignLeftRight('Ongkir', formatCurrency(salesOrder.shipping_cost), RECEIPT_MAX_WIDTH));
            }
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

        // Helper functions
        function centerText(text, width) {
            const textStr = String(text || '').trim();
            if (textStr.length >= width) return textStr;

            const leftPadding = Math.floor((width - textStr.length) / 2);
            const rightPadding = width - textStr.length - leftPadding;

            return ' '.repeat(Math.max(0, leftPadding)) + textStr + ' '.repeat(Math.max(0, rightPadding));
        }

        function alignLeftRight(left, right, width) {
            const leftStr = String(left || '');
            const rightStr = String(right || '');

            if (leftStr.length + rightStr.length > width) {
                return leftStr + '\n' + ' '.repeat(width - rightStr.length) + rightStr;
            }

            const middleSpaces = width - leftStr.length - rightStr.length;
            return leftStr + ' '.repeat(Math.max(0, middleSpaces)) + rightStr;
        }

        function divider(width, char = '-') {
            return char.repeat(width);
        }

        function wrapText(text, maxWidth) {
            const words = String(text || '').split(' ');
            const lines = [];
            let currentLine = '';

            words.forEach(word => {
                if (word.length > maxWidth) {
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

            lines.push(keyPart + valueLines[0]);

            for (let i = 1; i < valueLines.length; i++) {
                lines.push(' '.repeat(keyPart.length) + valueLines[i]);
            }
        }

        function formatCurrency(num) {
            if (num === null || num === undefined || num === '' || isNaN(num)) {
                return 'Rp 0';
            }

            const formatted = parseFloat(num).toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });

            return 'Rp ' + formatted;
        }

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

        function showLoading(message = 'Loading...') {
            const loading = document.getElementById('loading');
            if (loading) {
                const span = loading.querySelector('span');
                if (span) span.textContent = message;
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
            toast.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 12px 20px; border-radius: 8px; color: white; font-weight: 500; z-index: 10000; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
            if (type === 'success') toast.style.background = '#10b981';
            else if (type === 'error') toast.style.background = '#ef4444';
            else toast.style.background = '#3b82f6';

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        function getPaymentById(id) {
            const payments = {!! json_encode($salesOrder->payments->map(function ($p) {
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
        document.getElementById('edit_payment_method').addEventListener('change', function () {
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
        document.getElementById('editPaymentMethodForm').addEventListener('submit', function (e) {
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
        document.getElementById('editPaymentMethodModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeEditPaymentMethodModal();
            }
        });

        // Close print modal when clicking outside
        document.getElementById('printModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closePrintModal();
            }
        });

        // Escape key to close modals
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (!document.getElementById('editPaymentMethodModal').classList.contains('hidden')) {
                    closeEditPaymentMethodModal();
                }
                if (!document.getElementById('printModal').classList.contains('hidden')) {
                    closePrintModal();
                }
            }
        });

        // ✅ FUNGSI UNTUK MANAGE PURCHASE ORDER TERKAIT (READ-ONLY untuk Owner)
        function loadRelatedPO() {
            const salesOrderId = {{ $salesOrder->id }};

            // ✅ GUNAKAN ROUTE OWNER YANG SUDAH DITAMBAHKAN
            fetch(`/owner/sales/${salesOrderId}/related-po`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(async response => {
                    // ✅ PERBAIKI: Cek content-type sebelum parse JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        // Jika bukan JSON, coba ambil text untuk debug
                        const text = await response.text();
                        console.error('Non-JSON response:', text.substring(0, 200));
                        throw new Error('Server returned non-JSON response. Status: ' + response.status + '. Response: ' + text.substring(0, 100));
                    }
                    if (!response.ok) {
                        // Coba parse JSON error jika ada
                        try {
                            const errorData = await response.json();
                            throw new Error(errorData.error || errorData.message || 'Network response was not ok: ' + response.status);
                        } catch (e) {
                            throw new Error('Network response was not ok: ' + response.status);
                        }
                    }
                    return response.json();
                })
                .then(data => {
                    const poSection = document.getElementById('po-related-section');

                    if (data.exists) {
                        // Tampilkan info PO terkait (read-only untuk Owner)
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
                            </div>
                        </div>
                    </div>
                `;
                    } else {
                        // Tidak ada PO terkait (read-only, tidak bisa create)
                        poSection.innerHTML = `
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h4 class="font-semibold text-yellow-800 mb-2">Belum ada Purchase Order Terkait</h4>
                        <p class="text-sm text-gray-600">Sales order ini belum memiliki Purchase Order terkait.</p>
                    </div>
                `;
                    }
                })
                .catch(error => {
                    console.error('Error loading related PO:', error);
                    const poSection = document.getElementById('po-related-section');
                    if (poSection) {
                        poSection.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-red-700 font-semibold mb-2">Error memuat informasi PO terkait</p>
                        <p class="text-sm text-red-600">${error.message}</p>
                        <p class="text-xs text-gray-500 mt-2">Pastikan shift sudah aktif atau hubungi administrator.</p>
                    </div>
                `;
                    }
                });
        }

        // ✅ LOAD RELATED PO SAAT PAGE LOAD
        document.addEventListener('DOMContentLoaded', function () {
            loadRelatedPO();
        });
    </script>
</body>

</html>