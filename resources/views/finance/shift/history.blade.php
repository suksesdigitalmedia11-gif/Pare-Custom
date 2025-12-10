<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Data Shift - Finance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
</head>
<body class="bg-gray-100">
<div class="flex">
    <x-navbar-finance />
    <div class="flex-1 lg:w-5/6">
        <x-navbar-top-finance />
        <div class="p-4 lg:p-8">
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">📋 Data Semua Shift</h1>
                        <p class="text-gray-600">Monitoring dan reporting data keuangan shift</p>
                    </div>
                    <div class="flex gap-2 mt-4 md:mt-0">
                        <a href="{{ route('finance.shift.dashboard') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded shadow inline-flex items-center">
                            <i class="bi bi-arrow-left mr-2"></i> Kembali
                        </a>
                        <a href="{{ route('finance.shift.export') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow inline-flex items-center">
                            <i class="bi bi-file-earmark-excel mr-2"></i> Export
                        </a>
                    </div>
                </div>

                <!-- Filter Options -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex flex-wrap gap-4 items-center">
                        <span class="font-medium text-blue-800">Filter:</span>
                        <a href="{{ request()->fullUrlWithQuery(['status' => '']) }}" 
                           class="px-3 py-1 rounded-full text-sm {{ !request('status') ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 border border-blue-300' }}">
                            Semua
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'open']) }}" 
                           class="px-3 py-1 rounded-full text-sm {{ request('status') == 'open' ? 'bg-yellow-600 text-white' : 'bg-white text-yellow-600 border border-yellow-300' }}">
                            Aktif
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'closed']) }}" 
                           class="px-3 py-1 rounded-full text-sm {{ request('status') == 'closed' ? 'bg-green-600 text-white' : 'bg-white text-green-600 border border-green-300' }}">
                            Selesai
                        </a>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left">Kasir</th>
                                <th class="px-4 py-3 text-left">Tanggal</th>
                                <th class="px-4 py-3 text-right">Kas Awal</th>
                                <th class="px-4 py-3 text-right">Pendapatan</th>
                                <th class="px-4 py-3 text-right">Pengeluaran</th>
                                <th class="px-4 py-3 text-right">Setor/Tukar</th>
                                <th class="px-4 py-3 text-right">Kas Akhir</th>
                                <th class="px-4 py-3 text-right">Selisih</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($shifts as $shift)
                                @php
                                    $totalCashTransfer = $shift->cashTransfers->sum('amount') ?? 0;
                                @endphp
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mr-2">
                                                <i class="bi bi-person text-gray-600"></i>
                                            </div>
                                            <span class="font-medium">{{ $shift->user->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">{{ $shift->start_time->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 text-right">Rp {{ number_format($shift->initial_cash, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-green-600 font-medium">Rp {{ number_format($shift->cash_total, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-red-600">Rp {{ number_format($shift->expense_total, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-purple-600">Rp {{ number_format($totalCashTransfer, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @if($shift->final_cash)
                                            <span class="font-medium">Rp {{ number_format($shift->final_cash, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if($shift->discrepancy)
                                            <span class="{{ $shift->discrepancy < 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                                                Rp {{ number_format($shift->discrepancy, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium
                                            {{ $shift->status == 'open' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                            {{ $shift->status == 'open' ? 'Aktif' : 'Selesai' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center space-x-2">
                                            <a href="{{ route('finance.shift.show', $shift) }}" 
                                               class="text-blue-600 hover:text-blue-800 p-1 rounded" title="Lihat Detail">
                                                <i class="bi bi-eye-fill"></i>
                                            </a>
                                            <a href="{{ route('finance.shift.export-detail', $shift) }}" 
                                               class="text-green-600 hover:text-green-800 p-1 rounded" title="Export Excel">
                                                <i class="bi bi-file-earmark-excel"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-6 text-center text-gray-500">
                                        <i class="bi bi-inbox text-2xl mb-2 block"></i>
                                        Tidak ada data shift
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $shifts->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>