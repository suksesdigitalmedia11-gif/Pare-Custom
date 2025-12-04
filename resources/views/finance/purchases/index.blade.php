<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Daftar Pembelian - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet" />
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
            transition: width .2s;
        }

        .hover-link:hover .nav-text::after {
            width: 100%;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex">
        <button
            class="fixed text-white text-3xl top-5 left-4 p-2 rounded-md bg-gray-700 lg:hidden focus:outline-none z-50"
            onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>

        <x-navbar-finance></x-navbar-finance>

        <div class="flex-1 lg:w-5/6">
            <x-navbar-top-finance></x-navbar-top-finance>

            <div class="p-4 lg:p-8">
                <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-700">Daftar Pembelian</h2>
                        <a href="{{ route('finance.purchases.create') }}"
                            class="bg-[#005281] text-white px-4 py-2 rounded-md hover:opacity-90">Buat Pembelian</a>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <div class="flex flex-col space-y-4 mb-6">
                        <!-- Filter Tabs Row -->
                        <div class="w-full">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('finance.purchases.index', ['group' => 'todo']) }}"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap {{ ($group ?? '') === 'todo' ? 'bg-[#005281] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                    Butuh Diproses
                                </a>
                                <a href="{{ route('finance.purchases.index', ['group' => 'request_kain']) }}"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap {{ ($group ?? '') === 'request_kain' ? 'bg-[#005281] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                    Request Kain
                                </a>
                                <a href="{{ route('finance.purchases.index', ['group' => 'in_progress']) }}"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap {{ ($group ?? '') === 'in_progress' ? 'bg-[#005281] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                    Dalam Proses
                                </a>
                                <a href="{{ route('finance.purchases.index', ['group' => 'completed']) }}"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap {{ ($group ?? '') === 'completed' ? 'bg-[#005281] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                    Selesai
                                </a>
                                <a href="{{ route('finance.purchases.index', ['group' => 'cancelled']) }}"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap {{ ($group ?? '') === 'cancelled' ? 'bg-[#005281] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                    Dibatalkan
                                </a>
                            </div>
                        </div>

                        <!-- Search and Filter Row -->
                        <div class="w-full">
                            <form method="GET" class="flex flex-col lg:flex-row gap-3">
                                <input type="hidden" name="group" value="{{ $group }}" />

                                <!-- Search Input -->
                                <div class="flex-1 lg:max-w-xs">
                                    <input type="text" name="q" value="{{ $q }}" placeholder="Cari No/Supplier"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#005281] focus:border-transparent" />
                                </div>

                                <!-- Filter Controls -->
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <!-- Type Filter -->
                                    <select name="type"
                                        class="border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#005281] focus:border-transparent min-w-[140px]">
                                        <option value="">Semua Tipe</option>
                                        <option value="kain" @selected(($type ?? '') === 'kain')>Pembelian Kain</option>
                                        <option value="produk_jadi" @selected(($type ?? '') === 'produk_jadi')>Pembelian
                                            Produk Jadi</option>
                                    </select>

                                    <!-- Status Filter -->
                                    <select name="status"
                                        class="border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#005281] focus:border-transparent min-w-[140px]">
                                        <option value="">Semua Status</option>
                                        @foreach(['draft', 'pending', 'request_kain', 'payment', 'proses_jahit', 'printing', 'selesai'] as $st)
                                            <option value="{{ $st }}" @selected($status == $st)>
                                                {{ ucfirst(str_replace('_', ' ', $st)) }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <!-- Filter Button -->
                                    <button type="submit"
                                        class="bg-[#005281] hover:bg-[#004070] text-white px-6 py-2 rounded-lg font-medium transition-colors min-w-[80px]">
                                        Filter
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead>
                                <tr class="border-b text-gray-600">
                                    <th class="px-3 py-2">No. Pembelian</th>
                                    <th class="px-3 py-2">Tanggal</th>
                                    <th class="px-3 py-2">Tipe</th>
                                    <th class="px-3 py-2">Supplier</th>
                                    <th class="px-3 py-2">Jumlah</th>
                                    <th class="px-3 py-2">Status</th>
                                    <th class="px-3 py-2">Progress</th>
                                    <th class="px-3 py-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchases as $p)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-3 py-2">
                                            <a class="text-[#005281] hover:underline"
                                                href="{{ route('finance.purchases.show', $p) }}">{{ $p->po_number }}</a>
                                        </td>
                                        <td class="px-3 py-2">{{ \Carbon\Carbon::parse($p->order_date)->format('d M Y') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <span
                                                class="px-2 py-1 rounded text-xs {{ $p->purchase_type === 'kain' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                                {{ $p->purchase_type === 'kain' ? 'Kain' : 'Produk Jadi' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2">{{ $p->supplier?->name ?? '-' }}</td>
                                        <td class="px-3 py-2">Rp {{ number_format($p->grand_total, 0, ',', '.') }}</td>
                                        <td class="px-3 py-2">
                                            <span class="px-2 py-1 rounded text-xs
                                            @if($p->status === 'draft') bg-gray-100 text-gray-800
                                            @elseif($p->status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($p->status === 'request_kain') bg-blue-100 text-blue-800
                                            @elseif($p->status === 'payment') bg-purple-100 text-purple-800
                                            @elseif($p->status === 'proses_jahit') bg-indigo-100 text-indigo-800
                                            @elseif($p->status === 'printing') bg-orange-100 text-orange-800
                                            @elseif($p->status === 'selesai') bg-green-100 text-green-800
                                            @elseif($p->status === 'cancelled') bg-red-100 text-red-800
                                            @endif">
                                                {{ $p->getStatusLabel() }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2">
                                            @if($p->purchase_type === 'kain')
                                                <!-- Progress bar untuk kain -->
                                                @php
                                                    $steps = ['draft', 'pending', 'request_kain', 'payment', 'proses_jahit', 'printing', 'selesai'];
                                                    $currentIndex = array_search($p->status, $steps);
                                                    $progress = $currentIndex !== false ? (($currentIndex + 1) / count($steps)) * 100 : 0;
                                                @endphp
                                                <div class="w-24 bg-gray-200 rounded-full h-2">
                                                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                                        style="width: {{ $progress }}%"></div>
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1">{{ round($progress) }}%</div>
                                            @else
                                                <!-- Progress bar untuk produk jadi -->
                                                @php
                                                    $steps = ['draft', 'pending', 'request_kain', 'payment', 'printing', 'selesai'];
                                                    $currentIndex = array_search($p->status, $steps);
                                                    $progress = $currentIndex !== false ? (($currentIndex + 1) / count($steps)) * 100 : 0;
                                                @endphp
                                                <div class="w-24 bg-gray-200 rounded-full h-2">
                                                    <div class="bg-green-600 h-2 rounded-full transition-all duration-300"
                                                        style="width: {{ $progress }}%"></div>
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1">{{ round($progress) }}%</div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
    <div class="flex flex-wrap gap-1">
        @php
            $availableStatuses = $p->getNextAvailableStatuses();
        @endphp
        
        <!-- Draft: Semua role bisa submit -->
        @if($p->status === 'draft')
            <form method="POST" action="{{ route('finance.purchases.submit', $p) }}" class="inline">
                @csrf
                <button class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded">Ajukan</button>
            </form>
        @endif

        <!-- Approve: Finance/Owner -->
        @if($p->status === 'pending' && in_array(auth()->user()->usertype, ['finance', 'owner']))
            <form method="POST" action="{{ route('finance.purchases.approve', $p) }}" class="inline">
                @csrf
                <button class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">Approve</button>
            </form>
        @endif

        <!-- Payment: Finance/Owner -->
        @if($p->status === 'request_kain' && in_array(auth()->user()->usertype, ['finance', 'owner']))
            <button onclick="openModal('payment-modal-{{ $p->id }}')" class="px-2 py-1 text-xs bg-purple-600 text-white rounded hover:bg-purple-700">Payment</button>
        @endif

        <!-- Workflow Status: Selesai saja untuk Finance -->
        @if(count($availableStatuses) > 0 && !in_array($p->status, ['draft', 'pending', 'request_kain']))
            @foreach($availableStatuses as $nextStatus)
                @if($nextStatus === 'selesai' && in_array(auth()->user()->usertype, ['finance', 'owner']))
                    <form method="POST" action="{{ route('finance.purchases.update-status', $p) }}" class="inline">
                        @csrf
                        <input type="hidden" name="new_status" value="{{ $nextStatus }}">
                        <button class="px-2 py-1 text-xs bg-[#005281] text-white rounded hover:opacity-90" 
                                onclick="return confirm('Update status ke {{ ucfirst(str_replace('_', ' ', $nextStatus)) }}?')">
                            {{ ucfirst(str_replace('_', ' ', $nextStatus)) }}
                        </button>
                    </form>
                @endif
            @endforeach
        @endif

        <!-- Cancel: Sebelum produksi -->
        @if(!in_array($p->status, ['selesai', 'cancelled', 'payment', 'proses_jahit', 'printing']))
            <form method="POST" action="{{ route('finance.purchases.cancel', $p) }}" class="inline" onsubmit="return confirm('Batalkan pembelian ini?')">
                @csrf @method('PATCH')
                <button class="px-2 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">Batalkan</button>
            </form>
        @endif
    </div>

    <!-- Modal Payment -->
    @if($p->status === 'request_kain' && in_array(auth()->user()->usertype, ['finance', 'owner']))
    <div id="payment-modal-{{ $p->id }}" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Proses Pembayaran {{ $p->po_number }}</h3>
            <form action="{{ route('finance.purchases.payment', $p->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Upload Faktur (PDF/JPG/PNG)</label>
                    <input type="file" name="invoice_file" accept=".pdf,.jpg,.jpeg,.png" required class="w-full border rounded p-2 text-gray-900" />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Upload Bukti Pembayaran (PDF/JPG/PNG)</label>
                    <input type="file" name="payment_proof_file" accept=".pdf,.jpg,.jpeg,.png" required class="w-full border rounded p-2 text-gray-900" />
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('payment-modal-{{ $p->id }}')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded hover:opacity-90">Submit</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">{{ $purchases->withQueryString()->links() }}</div>
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