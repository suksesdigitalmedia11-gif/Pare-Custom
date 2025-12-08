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
        body { font-family: 'Raleway', sans-serif; }
        .nav-text { position: relative; display: inline-block; }
        .nav-text::after { content: ''; position: absolute; width: 0; height: 2px; bottom: -2px; left: 0; background-color: #e17f12; transition: width .2s; }
        .hover-link:hover .nav-text::after { width: 100%; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <button class="fixed text-white text-3xl top-5 left-4 p-2 rounded-md bg-gray-700 lg:hidden focus:outline-none z-50" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <x-navbar-kepala-toko></x-navbar-kepala-toko>
        <div class="flex-1 lg:w-5/6">
            <x-navbar-top-kepala-toko></x-navbar-top-kepala-toko>

            <div class="p-4 lg:p-8">
                <!-- Header -->
                <div class="bg-white p-4 lg:p-6 rounded-xl shadow-lg mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <h2 class="text-xl lg:text-2xl font-semibold text-gray-700">Daftar Pembelian</h2>
                        <a href="{{ route('kepala-toko.purchases.create') }}" 
                           class="bg-[#005281] text-white px-3 lg:px-4 py-2 rounded-md hover:opacity-90 inline-flex items-center text-sm">
                            <i class="bi bi-plus-lg mr-2"></i> <span class="hidden sm:inline">Buat Pembelian</span>
                            <span class="sm:hidden">Buat</span>
                        </a>
                    </div>
                </div>

                <!-- Filters & Table -->
                <div class="bg-white p-4 lg:p-6 rounded-xl shadow-lg">
                    <!-- Filter Tabs -->
                    <div class="mb-4">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('kepala-toko.purchases.index', ['group' => 'todo']) }}" 
                               class="px-3 lg:px-4 py-2 rounded-lg text-xs lg:text-sm font-medium transition-colors whitespace-nowrap {{ ($group ?? '')==='todo' ? 'bg-[#005281] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Butuh Diproses
            </a>
            <a href="{{ route('kepala-toko.purchases.index', ['group' => 'request_kain']) }}" 
                               class="px-3 lg:px-4 py-2 rounded-lg text-xs lg:text-sm font-medium transition-colors whitespace-nowrap {{ ($group ?? '')==='request_kain' ? 'bg-[#005281] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Request Kain
            </a>
            <a href="{{ route('kepala-toko.purchases.index', ['group' => 'in_progress']) }}" 
                               class="px-3 lg:px-4 py-2 rounded-lg text-xs lg:text-sm font-medium transition-colors whitespace-nowrap {{ ($group ?? '')==='in_progress' ? 'bg-[#005281] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Dalam Proses
            </a>
            <a href="{{ route('kepala-toko.purchases.index', ['group' => 'completed']) }}" 
                               class="px-3 lg:px-4 py-2 rounded-lg text-xs lg:text-sm font-medium transition-colors whitespace-nowrap {{ ($group ?? '')==='completed' ? 'bg-[#005281] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Selesai
            </a>
            <a href="{{ route('kepala-toko.purchases.index', ['group' => 'cancelled']) }}" 
                               class="px-3 lg:px-4 py-2 rounded-lg text-xs lg:text-sm font-medium transition-colors whitespace-nowrap {{ ($group ?? '')==='cancelled' ? 'bg-[#005281] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Dibatalkan
            </a>
        </div>
    </div>

                    <!-- Search and Filter -->
                    <form method="GET" class="mb-6">
            <input type="hidden" name="group" value="{{ $group }}" />
                        <div class="flex flex-col lg:flex-row gap-3">
            <div class="flex-1 lg:max-w-xs">
                <input type="text" 
                       name="q" 
                       value="{{ $q }}" 
                       placeholder="Cari No/Supplier" 
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#005281] focus:border-transparent" />
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <select name="type" 
                                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#005281] focus:border-transparent min-w-[140px]">
                    <option value="">Semua Tipe</option>
                    <option value="kain" @selected(($type ?? '') === 'kain')>Pembelian Kain</option>
                    <option value="produk_jadi" @selected(($type ?? '') === 'produk_jadi')>Pembelian Produk Jadi</option>
                </select>
                <select name="status" 
                                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#005281] focus:border-transparent min-w-[140px]">
                    <option value="">Semua Status</option>
                    @foreach(['draft','pending','request_kain','payment','proses_jahit','printing','selesai'] as $st)
                    <option value="{{ $st }}" @selected($status==$st)>
                        {{ ucfirst(str_replace('_', ' ', $st)) }}
                    </option>
                    @endforeach
                </select>
                <button type="submit" 
                                        class="bg-[#005281] hover:bg-[#004070] text-white px-4 lg:px-6 py-2 rounded-lg text-sm font-medium transition-colors">
                    Filter
                </button>
                            </div>
            </div>
        </form>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b-2 border-gray-200 bg-gray-50">
                                    <th class="px-3 lg:px-4 py-3 text-left font-semibold text-gray-700">No. Pembelian</th>
                                    <th class="px-3 lg:px-4 py-3 text-left font-semibold text-gray-700 hidden sm:table-cell">Tanggal</th>
                                    <th class="px-3 lg:px-4 py-3 text-left font-semibold text-gray-700">Tipe</th>
                                    <th class="px-3 lg:px-4 py-3 text-left font-semibold text-gray-700 hidden md:table-cell">Supplier</th>
                                    <th class="px-3 lg:px-4 py-3 text-left font-semibold text-gray-700 hidden lg:table-cell">Jumlah</th>
                                    <th class="px-3 lg:px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                                    <th class="px-3 lg:px-4 py-3 text-left font-semibold text-gray-700 hidden lg:table-cell">Progress</th>
                                    <th class="px-3 lg:px-4 py-3 text-left font-semibold text-gray-700 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchases as $p)
                                <tr class="border-b hover:bg-gray-50 transition-colors">
                                    <td class="px-3 lg:px-4 py-3">
                                        <a class="text-[#005281] hover:underline font-medium" href="{{ route('kepala-toko.purchases.show', $p) }}">{{ $p->po_number }}</a>
                                        <div class="text-xs text-gray-500 sm:hidden mt-1">{{ \Carbon\Carbon::parse($p->order_date)->format('d M Y') }}</div>
                                    </td>
                                    <td class="px-3 lg:px-4 py-3 hidden sm:table-cell">{{ \Carbon\Carbon::parse($p->order_date)->format('d M Y') }}</td>
                                    <td class="px-3 lg:px-4 py-3">
                                        <span class="px-2 py-1 rounded text-xs font-medium {{ $p->purchase_type === 'kain' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                            {{ $p->purchase_type === 'kain' ? 'Kain' : 'Produk Jadi' }}
                                        </span>
                                    </td>
                                    <td class="px-3 lg:px-4 py-3 hidden md:table-cell">
                                        <div class="text-sm">{{ $p->supplier?->name ?? '-' }}</div>
                                        <div class="text-xs text-gray-500 lg:hidden">Rp {{ number_format($p->grand_total,0,',','.') }}</div>
                                    </td>
                                    <td class="px-3 lg:px-4 py-3 hidden lg:table-cell font-medium">Rp {{ number_format($p->grand_total,0,',','.') }}</td>
                                    <td class="px-3 lg:px-4 py-3">
                                        <span class="px-2 py-1 rounded text-xs font-medium
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
                                    <td class="px-3 lg:px-4 py-3 hidden lg:table-cell">
                                        @if($p->purchase_type === 'kain')
                                            @php
                                                $steps = ['draft', 'pending', 'request_kain', 'payment', 'proses_jahit', 'printing', 'selesai'];
                                                $currentIndex = array_search($p->status, $steps);
                                                $progress = $currentIndex !== false ? (($currentIndex + 1) / count($steps)) * 100 : 0;
                                            @endphp
                                            <div class="flex items-center gap-2">
                                                <div class="flex-1 bg-gray-200 rounded-full h-2 max-w-[80px]">
                                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
                                                </div>
                                                <span class="text-xs text-gray-600 font-medium">{{ round($progress) }}%</span>
                                            </div>
                                        @else
                                            @php
                                                $steps = ['draft', 'pending', 'request_kain', 'payment', 'printing', 'selesai'];
                                                $currentIndex = array_search($p->status, $steps);
                                                $progress = $currentIndex !== false ? (($currentIndex + 1) / count($steps)) * 100 : 0;
                                            @endphp
                                            <div class="flex items-center gap-2">
                                                <div class="flex-1 bg-gray-200 rounded-full h-2 max-w-[80px]">
                                                <div class="bg-green-600 h-2 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
                                                </div>
                                                <span class="text-xs text-gray-600 font-medium">{{ round($progress) }}%</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 lg:px-4 py-3">
                                        <div class="flex items-center justify-center gap-1 flex-wrap">
                                            <!-- Detail Button -->
                                            <a href="{{ route('kepala-toko.purchases.show', $p) }}" 
                                               class="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors inline-flex items-center" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
        
        <!-- Draft: Submit -->
        @if($p->status === 'draft')
            <form method="POST" action="{{ route('kepala-toko.purchases.submit', $p) }}" class="inline">
                @csrf
                                                    <button class="px-2 py-1 text-xs bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors" title="Ajukan">
                                                        <i class="bi bi-send"></i>
                                                    </button>
            </form>
        @endif

        <!-- Workflow Status: Kain Diterima, Printing, Jahit, Selesai -->
                                            @php
                                                $availableStatuses = $p->getNextAvailableStatuses();
                                            @endphp
        @if(count($availableStatuses) > 0 && !in_array($p->status, ['draft', 'pending', 'request_kain']))
            @foreach($availableStatuses as $nextStatus)
                @if(in_array($nextStatus, ['proses_jahit', 'printing', 'selesai']) && in_array(auth()->user()->usertype, ['kepala_toko', 'owner']))
                    <form method="POST" action="{{ route('kepala-toko.purchases.update-status', $p) }}" class="inline">
                        @csrf
                        <input type="hidden" name="new_status" value="{{ $nextStatus }}">
                                                            <button class="px-2 py-1 text-xs bg-[#005281] text-white rounded hover:opacity-90 transition-colors" 
                                                                    onclick="return confirm('Update status ke {{ ucfirst(str_replace('_', ' ', $nextStatus)) }}?')"
                                                                    title="{{ ucfirst(str_replace('_', ' ', $nextStatus)) }}">
                                                                <i class="bi bi-arrow-right-circle"></i>
                        </button>
                    </form>
                @endif
            @endforeach
        @endif

        <!-- Cancel -->
        @if(!in_array($p->status, ['selesai', 'cancelled', 'payment', 'proses_jahit', 'printing']))
            <form method="POST" action="{{ route('kepala-toko.purchases.cancel', $p) }}" class="inline" onsubmit="return confirm('Batalkan pembelian ini?')">
                @csrf @method('PATCH')
                                                    <button class="px-2 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700 transition-colors" title="Batalkan">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
            </form>
        @endif
    </div>
</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">{{ $purchases->withQueryString()->links() }}</div>
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
