<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Detail Pembelian - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Raleway', sans-serif; }
        .nav-text { position: relative; display: inline-block; }
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
        .hover-link:hover .nav-text::after { width: 100%; }
        .timeline-item { position: relative; }
        .timeline-item:before {
            content: '';
            position: absolute;
            left: 15px;
            top: 30px;
            bottom: -10px;
            width: 2px;
            background: #e5e7eb;
        }
        .timeline-item:last-child:before { display: none; }
        .timeline-dot {
            position: absolute;
            left: 11px;
            top: 25px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        .timeline-dot.completed { background: #10b981; }
        .timeline-dot.current { background: #3b82f6; }
        .timeline-dot.pending { background: #d1d5db; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex">

        <x-navbar-admin></x-navbar-admin>

        <div class="flex-1 lg:w-5/6">
            <x-navbar-top-admin></x-navbar-top-admin>

    <div class="p-4 lg:p-8">
        <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-700">Detail Pembelian</h2>
                <a href="{{ route('admin.purchases.index') }}" class="px-4 py-2 border rounded hover:bg-gray-50 transition-colors">Kembali</a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Purchase Info Card -->
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="text-sm text-gray-500">No. Pembelian</div>
                            <div class="text-lg font-semibold">{{ $purchase->po_number }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500">Status</div>
                            <span class="px-3 py-1 rounded text-sm font-medium
                            @if($purchase->status === 'draft') bg-gray-100 text-gray-800
                            @elseif($purchase->status === 'pending') bg-yellow-100 text-yellow-800
                            @elseif($purchase->status === 'request_kain') bg-blue-100 text-blue-800
                            @elseif($purchase->status === 'payment') bg-purple-100 text-purple-800
                            @elseif($purchase->status === 'proses_jahit') bg-indigo-100 text-indigo-800
                            @elseif($purchase->status === 'printing') bg-orange-100 text-orange-800
                            @elseif($purchase->status === 'selesai') bg-green-100 text-green-800
                            @elseif($purchase->status === 'cancelled') bg-red-100 text-red-800
                            @endif">
                                {{ $purchase->getStatusLabel() }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <div class="text-sm text-gray-500">Tipe Pembelian</div>
                            <span class="px-2 py-1 rounded text-sm {{ $purchase->purchase_type === 'kain' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                {{ $purchase->getTypeLabel() }}
                            </span>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Tanggal Pembelian</div>
                            <div>{{ \Carbon\Carbon::parse($purchase->order_date)->format('d M Y') }}</div>
                        </div>
                        @if($purchase->deadline)
                        <div>
                            <div class="text-sm text-gray-500">Deadline</div>
                            <div>{{ \Carbon\Carbon::parse($purchase->deadline)->format('d M Y') }}</div>
                        </div>
                        @endif
                        <div>
                            <div class="text-sm text-gray-500">Supplier</div>
                            <div>{{ $purchase->supplier?->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Dibuat Oleh</div>
                            <div>{{ $purchase->creator->name ?? 'System' }}</div>
                        </div>
                        
                        <div class="md:col-span-2">
                            <div class="text-sm text-gray-500">Keterangan</div>
                            <div class="p-3 bg-gray-50 rounded-lg border italic text-gray-600">
                                {{ $purchase->notes ?? '-' }}
                            </div>
                        </div>
                        
                        <!-- 🔧 FIX: Tampilkan informasi customer jika dari sales -->
                        @if($purchase->is_from_sales)
                        <div class="md:col-span-2 border-t pt-4 mt-4">
                            <div class="text-sm text-gray-500 mb-2">Informasi Customer</div>
                            <div class="bg-blue-50 p-3 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <i class="bi bi-person text-blue-600"></i>
                                    <div>
                                        <div class="font-medium text-blue-800">{{ $purchase->customer_name }}</div>
                                        @if($purchase->salesOrder && $purchase->salesOrder->so_number)
                                        <div class="text-sm text-blue-600">Dari Sales Order: {{ $purchase->salesOrder->so_number }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                            <!-- Financial Summary -->
                            <div class="border-t pt-4">
                                <div class="grid grid-cols-3 gap-4 text-center">
                                    <div>
                                        <div class="text-sm text-gray-500">Subtotal</div>
                                        <div class="font-semibold">Rp {{ number_format($purchase->subtotal,0,',','.') }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">Diskon</div>
                                        <div class="font-semibold text-red-600">Rp {{ number_format($purchase->discount_total,0,',','.') }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">Grand Total</div>
                                        <div class="font-bold text-green-600 text-lg">Rp {{ number_format($purchase->grand_total,0,',','.') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="bg-white p-6 rounded-xl shadow-lg">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Detail Item</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diskon</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($purchase->items as $item)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $item->product_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $item->sku ?? '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">Rp {{ number_format($item->cost_price,0,',','.') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $item->qty }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">Rp {{ number_format($item->discount,0,',','.') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap font-semibold">Rp {{ number_format($item->line_total,0,',','.') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- TAMBAH SECTION RIWAYAT AKTIVITAS -->
<div class="bg-white p-6 rounded-xl shadow-lg mt-6">
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
                @forelse($purchase->logs as $log)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2 border">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2 border">
                            <span class="capitalize">{{ str_replace('_', ' ', $log->action) }}</span>
                        </td>
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

                        <!-- Document Files -->
                        @if($purchase->status !== 'draft' && ($purchase->invoice_file || $purchase->payment_proof_file))
                        <div class="bg-white p-6 rounded-xl shadow-lg">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Dokumen</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @if($purchase->invoice_file)
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-center space-x-2">
                                        <i class="bi bi-file-earmark-pdf text-red-500 text-xl"></i>
                                        <div>
                                            <div class="font-medium">Faktur</div>
                                            <a href="{{ asset('storage/' . $purchase->invoice_file) }}" target="_blank" class="text-blue-600 underline hover:text-blue-800 text-sm">
                                                Lihat File
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if($purchase->payment_proof_file)
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-center space-x-2">
                                        <i class="bi bi-file-earmark-image text-green-500 text-xl"></i>
                                        <div>
                                            <div class="font-medium">Bukti Pembayaran</div>
                                            <a href="{{ asset('storage/' . $purchase->payment_proof_file) }}" target="_blank" class="text-blue-600 underline hover:text-blue-800 text-sm">
                                                Lihat File
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Sidebar: Timeline & Actions -->
                    <div class="space-y-6">
                        <!-- Timeline Card -->
                        <div class="bg-white p-6 rounded-xl shadow-lg">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                Timeline {{ $purchase->purchase_type === 'kain' ? 'Kain' : 'Produk Jadi' }}
                            </h3>
                            
                            <div class="space-y-6">
                                @if($purchase->purchase_type === 'kain')
                                    <!-- Timeline untuk Kain -->
                                    @php
                                        $kainSteps = [
                                            ['status' => 'draft', 'label' => 'Draft', 'user_field' => 'created_by', 'date_field' => 'created_at', 'relation' => 'creator'],
                                            ['status' => 'pending', 'label' => 'Pending', 'user_field' => null, 'date_field' => null, 'relation' => null],
                                            ['status' => 'request_kain', 'label' => 'Request Kain', 'user_field' => 'approved_by', 'date_field' => 'approved_at', 'relation' => 'approver'],
                                            ['status' => 'payment', 'label' => 'Payment', 'user_field' => 'payment_by', 'date_field' => 'payment_at', 'relation' => 'paymentProcessor'],
                                            ['status' => 'proses_jahit', 'label' => 'Proses Jahit', 'user_field' => 'kain_diterima_by', 'date_field' => 'kain_diterima_at', 'relation' => 'kainReceiver'],
                                            ['status' => 'printing', 'label' => 'Printing', 'user_field' => 'printing_by', 'date_field' => 'printing_at', 'relation' => 'printer'],
                                            ['status' => 'selesai', 'label' => 'Selesai', 'user_field' => 'selesai_by', 'date_field' => 'selesai_at', 'relation' => 'finisher'],
                                        ];
                                    @endphp
                                @else
                                    <!-- Timeline untuk Produk Jadi -->
                                    @php
                                        $kainSteps = [
                                            ['status' => 'draft', 'label' => 'Draft', 'user_field' => 'created_by', 'date_field' => 'created_at', 'relation' => 'creator'],
                                            ['status' => 'pending', 'label' => 'Pending', 'user_field' => null, 'date_field' => null, 'relation' => null],
                                            ['status' => 'request_kain', 'label' => 'Request Kain', 'user_field' => 'approved_by', 'date_field' => 'approved_at', 'relation' => 'approver'],
                                            ['status' => 'payment', 'label' => 'Payment', 'user_field' => 'payment_by', 'date_field' => 'payment_at', 'relation' => 'paymentProcessor'],
                                            ['status' => 'printing', 'label' => 'Printing', 'user_field' => 'printing_by', 'date_field' => 'printing_at', 'relation' => 'printer'],
                                            ['status' => 'selesai', 'label' => 'Selesai', 'user_field' => 'selesai_by', 'date_field' => 'selesai_at', 'relation' => 'finisher'],
                                        ];
                                    @endphp
                                @endif

                                @foreach($kainSteps as $index => $step)
                                    @php
                                        $stepStatus = $step['status'];
                                        $isPassed = array_search($purchase->status, array_column($kainSteps, 'status')) >= $index;
                                        $isCurrent = $purchase->status === $stepStatus;
                                        $isCompleted = array_search($purchase->status, array_column($kainSteps, 'status')) > $index;
                                    @endphp
                                    
                                    <div class="timeline-item relative pl-10">
                                        <div class="timeline-dot {{ $isCompleted ? 'completed' : ($isCurrent ? 'current' : 'pending') }}"></div>
                                        <div class="pb-4">
                                            <div class="flex items-center justify-between mb-1">
                                                <div class="font-medium {{ $isCurrent ? 'text-blue-600' : ($isCompleted ? 'text-green-600' : 'text-gray-400') }}">
                                                    {{ $step['label'] }}
                                                </div>
                                                @if($isCurrent)
                                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">Current</span>
                                                @elseif($isCompleted)
                                                    <i class="bi bi-check-circle text-green-500"></i>
                                                @endif
                                            </div>
                                            
                                            @if($step['relation'] && $purchase->{$step['relation']} && ($isCompleted || $isCurrent))
                                                <div class="text-sm text-gray-600">
                                                    <div class="flex items-center space-x-1">
                                                        <i class="bi bi-person text-xs"></i>
                                                        <span>{{ $purchase->{$step['relation']}->name }}</span>
                                                    </div>
                                                    @if($step['date_field'] && $purchase->{$step['date_field']})
                                                        <div class="flex items-center space-x-1 mt-1">
                                                            <i class="bi bi-clock text-xs"></i>
                                                            <span>{{ \Carbon\Carbon::parse($purchase->{$step['date_field']})->format('d M Y H:i') }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Actions Card -->
                        <div class="bg-white p-6 rounded-xl shadow-lg">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Aksi</h3>
                            
                            <div class="space-y-3">

            <!-- Tambah di bagian action buttons -->
            @if(in_array($purchase->status, ['draft', 'pending', 'request_kain']))
<a href="{{ route('admin.purchases.edit', $purchase) }}" class="w-full text-white rounded">
    <div class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded text-center"><i class="bi bi-pencil"></i> Edit</div>
</a>
@endif

    <!-- Draft: Submit -->
    @if($purchase->status === 'draft')
        <form method="POST" action="{{ route('admin.purchases.submit', $purchase) }}">
            @csrf
            <button class="w-full px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors">
                <i class="bi bi-send mr-2"></i>Ajukan
            </button>
        </form>
    @endif
            <!-- TOMBOL RETURN -->
            @if($purchase->status === 'selesai')
        <a href="{{ route('admin.purchase-returns.create', ['purchase' => $purchase->id]) }}" 
   class="w-full inline-flex items-center justify-center px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700 transition-colors">
    <i class="bi bi-arrow-return-left mr-2"></i>Return Pembelian
</a>
        @endif

    <!-- Workflow Status: Printing, Jahit, Selesai -->
            @if(count($availableStatuses) > 0 && !in_array($purchase->status, ['draft', 'pending', 'request_kain']))
        @foreach($availableStatuses as $nextStatus)
            @if(in_array($nextStatus, ['proses_jahit', 'printing', 'selesai']) && in_array(auth()->user()->usertype, ['admin', 'owner']))
                <form method="POST" action="{{ route('admin.purchases.update-status', $purchase) }}">
                    @csrf
                    <input type="hidden" name="new_status" value="{{ $nextStatus }}">
                    <button class="w-full px-4 py-2 bg-[#005281] text-white rounded hover:opacity-90 transition-colors">
                        <i class="bi bi-arrow-right-circle mr-2"></i>{{ ucfirst(str_replace('_', ' ', $nextStatus)) }}
                    </button>
                </form>
            @endif
        @endforeach
    @endif

    <!-- Cancel -->
    @if(!in_array($purchase->status, ['selesai', 'cancelled', 'payment', 'proses_jahit', 'printing']))
        <form method="POST" action="{{ route('admin.purchases.cancel', $purchase) }}">
            @csrf @method('PATCH')
            <button class="w-full px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors" 
                    onclick="return confirm('Apakah Anda yakin ingin membatalkan pembelian ini?')">
                <i class="bi bi-x-circle mr-2"></i>Batalkan
            </button>
        </form>
    @endif
</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const el = document.getElementById('sidebar');
            if (!el) return;
            el.classList.toggle('-translate-x-full');
        }
        function toggleDropdown(btn) {
            const menu = btn.nextElementSibling;
            if (!menu) return;
            if (menu.style.maxHeight && menu.style.maxHeight !== '0px') {
                menu.style.maxHeight = '0px';
                btn.querySelector('i.bi-chevron-down')?.classList.remove('rotate-180');
            } else {
                menu.style.maxHeight = menu.scrollHeight + 'px';
                btn.querySelector('i.bi-chevron-down')?.classList.add('rotate-180');
            }
        }
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
    </script>
</body>
</html>