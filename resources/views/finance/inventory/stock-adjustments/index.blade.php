<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Penyesuaian Stok - Custom Pare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body {
            font-family: 'Raleway', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex">
        <x-navbar-finance></x-navbar-finance>
        <div class="flex-1">
            <x-navbar-top-finance></x-navbar-top-finance>
            <div class="p-4 lg:p-8">
                <!-- Content Start -->
                <div class="px-6 py-8">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Penyesuaian Stok</h1>
                            <p class="text-sm text-gray-500 mt-1">Riwayat penyesuaian stok manual (Masuk & Keluar)</p>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div
                            class="p-6 border-b border-gray-100 flex flex-col sm:flex-row gap-4 justify-between items-center">
                            <!-- Search -->
                            <form action="{{ route('finance.inventory.stock-adjustments.index') }}" method="GET"
                                class="w-full sm:w-96 relative">
                                <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="search" value="{{ request('search') }}"
                                    placeholder="Cari No. Dokumen atau User..."
                                    class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </form>

                            <!-- Filter Type -->
                            <div class="flex gap-2">
                                <a href="{{ route('finance.inventory.stock-adjustments.index') }}"
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium {{ !request('type') ? 'bg-gray-100 text-gray-800' : 'text-gray-500 hover:bg-gray-50' }}">
                                    Semua
                                </a>
                                <a href="{{ route('finance.inventory.stock-adjustments.index', ['type' => 'in']) }}"
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium {{ request('type') === 'in' ? 'bg-green-100 text-green-700' : 'text-gray-500 hover:bg-gray-50' }}">
                                    <i class="bi bi-arrow-down"></i> Masuk
                                </a>
                                <a href="{{ route('finance.inventory.stock-adjustments.index', ['type' => 'out']) }}"
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium {{ request('type') === 'out' ? 'bg-red-100 text-red-700' : 'text-gray-500 hover:bg-gray-50' }}">
                                    <i class="bi bi-arrow-up"></i> Keluar
                                </a>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr
                                        class="bg-gray-50/50 text-xs text-gray-500 uppercase tracking-wider border-b border-gray-100">
                                        <th class="px-6 py-4 font-semibold">No. Dokumen</th>
                                        <th class="px-6 py-4 font-semibold">Tanggal</th>
                                        <th class="px-6 py-4 font-semibold">Tipe</th>
                                        <th class="px-6 py-4 font-semibold">Alasan</th>
                                        <th class="px-6 py-4 font-semibold">User</th>
                                        <th class="px-6 py-4 font-semibold text-right">Items</th>
                                        <th class="px-6 py-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse($adjustments as $adj)
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-6 py-4">
                                                <span
                                                    class="font-mono font-medium text-gray-900">{{ $adj->adjustment_number }}</span>
                                            </td>
                                            <td class="px-6 py-4 text-gray-600">
                                                {{ \Carbon\Carbon::parse($adj->date)->format('d M Y') }}
                                            </td>
                                            <td class="px-6 py-4">
                                                @if($adj->type === 'in')
                                                    <span
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                                        <i class="bi bi-arrow-down"></i> Masuk
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                                        <i class="bi bi-arrow-up"></i> Keluar
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-gray-900 font-medium">{{ $adj->reason }}</span>
                                                @if($adj->notes)
                                                    <p class="text-xs text-gray-500 truncate max-w-[200px]">{{ $adj->notes }}
                                                    </p>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600">
                                                {{ $adj->user->name ?? 'Unknown' }}
                                            </td>
                                            <td class="px-6 py-4 text-right font-mono text-gray-600">
                                                {{ $adj->items->count() }}
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <a href="{{ route('finance.inventory.stock-adjustments.show', $adj->id) }}"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors"
                                                    title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-12 text-center">
                                                <div class="flex flex-col items-center justify-center">
                                                    <span class="bg-gray-100 p-4 rounded-full mb-4">
                                                        <i class="bi bi-inbox text-2xl text-gray-400"></i>
                                                    </span>
                                                    <h3 class="text-lg font-medium text-gray-900">Belum ada data</h3>
                                                    <p class="text-gray-500 text-sm mt-1">Belum ada penyesuaian stok yang
                                                        dibuat.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="p-6 border-t border-gray-100">
                            {{ $adjustments->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
                <!-- Content End -->
            </div>
        </div>
    </div>
</body>

</html>