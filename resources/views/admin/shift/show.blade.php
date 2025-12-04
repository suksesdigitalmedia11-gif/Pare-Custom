<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Detail Shift - Pare Custom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
</head>
<body class="bg-gray-100">
<div class="flex">
    <x-navbar-admin />
    <div class="flex-1 lg:w-5/6">
        <x-navbar-top-admin />
        <div class="p-4 lg:p-8">
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h1 class="text-2xl font-semibold text-gray-800 mb-4">Detail Shift</h1>
                
                <!-- Di bagian tombol action, tambah ini: -->
                <div class="flex flex-wrap gap-4 mb-6">
                    <a href="{{ route('admin.shift.history') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded shadow inline-flex items-center">
                        <i class="bi bi-arrow-left mr-2"></i> Kembali
                    </a>
                    <a href="{{ route('admin.shift.export-detail', $shift) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded shadow inline-flex items-center">
                        <i class="bi bi-file-earmark-excel mr-2"></i> Export Excel
                    </a>
                    <a href="{{ route('admin.shift.export-detail-pdf', $shift) }}" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow inline-flex items-center">
                        <i class="bi bi-file-earmark-pdf mr-2"></i> Export PDF
                    </a>
                    <!-- TOMBOL CETAK NOTA DENGAN ONCLICK -->
                    <button onclick="printShiftSummary()" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded shadow inline-flex items-center">
                        <i class="bi bi-printer mr-2"></i> Cetak Nota
                    </button>
                </div>

                <!-- Informasi Shift -->
                <div class="bg-gray-50 p-6 rounded-lg mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">📊 Informasi Shift</h2>
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Kasir</p>
                            <p class="font-medium">{{ $shift->user->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Waktu Mulai</p>
                            <p class="font-medium">{{ $shift->start_time->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Waktu Selesai</p>
                            <p class="font-medium">{{ $shift->end_time ? $shift->end_time->format('d/m/Y H:i') : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Kas Awal</p>
                            <p class="font-medium">Rp {{ number_format($shift->initial_cash, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Kas</p>
                            <p class="font-medium text-green-600">Rp {{ number_format($shift->cash_total, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Pengeluaran</p>
                            <p class="font-medium text-red-600">Rp {{ number_format($shift->expense_total, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Pemasukan Manual</p>
                            <p class="font-medium text-blue-600">Rp {{ number_format($shift->income_total, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Kas Akhir</p>
                            <p class="font-medium">{{ $shift->final_cash ? 'Rp ' . number_format($shift->final_cash, 0, ',', '.') : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Selisih</p>
                            <p class="font-medium {{ $shift->discrepancy < 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ $shift->discrepancy ? 'Rp ' . number_format($shift->discrepancy, 0, ',', '.') : '-' }}
                            </p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-600">Catatan</p>
                            <p class="font-medium">{{ $shift->notes ?? 'Tidak ada catatan' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Status</p>
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $shift->status == 'open' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                {{ $shift->status == 'open' ? 'Aktif' : 'Selesai' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Pemasukan Manual -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">💰 Pemasukan Manual</h2>
                    @php $incomes = $incomes ?? collect(); @endphp
                    @if($incomes->isEmpty())
                        <p class="text-gray-500 bg-gray-50 p-4 rounded-lg">Tidak ada pemasukan manual pada shift ini.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full table-auto border-collapse">
                                <thead>
                                    <tr class="bg-blue-100">
                                        <th class="px-4 py-2 text-left">Keterangan</th>
                                        <th class="px-4 py-2 text-right">Jumlah</th>
                                        <th class="px-4 py-2 text-left">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($incomes as $income)
                                        <tr class="border-b hover:bg-blue-50">
                                            <td class="px-4 py-2">{{ $income->description }}</td>
                                            <td class="px-4 py-2 text-right text-green-600 font-medium">
                                                Rp {{ number_format($income->amount, 0, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-600">
                                                {{ $income->created_at->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-blue-50 font-semibold">
                                        <td class="px-4 py-2">Total Pemasukan Manual</td>
                                        <td class="px-4 py-2 text-right text-green-600">
                                            Rp {{ number_format($incomes->sum('amount'), 0, ',', '.') }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Penjualan -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">🛒 Penjualan</h2>
                    @if($salesOrders->isEmpty())
                        <p class="text-gray-500 bg-gray-50 p-4 rounded-lg">Tidak ada penjualan pada shift ini.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full table-auto border-collapse">
                                <thead>
                                    <tr class="bg-green-100">
                                        <th class="px-4 py-2">Nomor Penjualan</th>
                                        <th class="px-4 py-2">Pelanggan</th>
                                        <th class="px-4 py-2 text-right">Total</th>
                                        <th class="px-4 py-2 text-right">Dibayar (Shift Ini)</th>
                                        <th class="px-4 py-2">Metode</th>
                                        <th class="px-4 py-2">Kategori Pembayaran</th>
                                        <th class="px-4 py-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($salesOrders as $order)
                                        @php
                                            $shiftPayments = $order->payments->where('created_at', '>=', $shift->start_time)
                                                                          ->where('created_at', '<=', $shift->end_time ?? now());
                                        @endphp
                                        <tr class="border-b hover:bg-green-50">
                                            <td class="px-4 py-2">{{ $order->so_number }}</td>
                                            <td class="px-4 py-2">{{ $order->customer->name ?? 'Umum' }}</td>
                                            <td class="px-4 py-2 text-right">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                                            <td class="px-4 py-2 text-right">Rp {{ number_format($shiftPayments->sum('amount'), 0, ',', '.') }}</td>
                                            <td class="px-4 py-2">
                                                @if($shiftPayments->isNotEmpty())
                                                    {{ $shiftPayments->first()->method }}
                                                    @if($shiftPayments->count() > 1)
                                                        <span class="text-xs text-gray-500">(+{{ $shiftPayments->count() - 1 }})</span>
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="px-4 py-2">
                                                @if($shiftPayments->isNotEmpty())
                                                    @foreach($shiftPayments as $payment)
                                                        <span class="px-2 py-1 rounded text-xs {{ $payment->category == 'pelunasan' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                            {{ $payment->category }}
                                                            @if($payment->method == 'split')
                                                                (Cash: Rp {{ number_format($payment->cash_amount, 0, ',', '.') }}, Transfer: Rp {{ number_format($payment->transfer_amount, 0, ',', '.') }})
                                                            @else
                                                                ({{ $payment->method }}: Rp {{ number_format($payment->amount, 0, ',', '.') }})
                                                            @endif
                                                        </span><br>
                                                    @endforeach
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="px-4 py-2">
                                                <span class="px-2 py-1 rounded text-xs
                                                    {{ $order->status == 'completed' ? 'bg-green-100 text-green-800' : 
                                                       ($order->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                                    {{ $order->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-green-50 font-semibold">
                                        <td colspan="2" class="px-4 py-2">Total Penjualan</td>
                                        <td class="px-4 py-2 text-right">Rp {{ number_format($salesOrders->sum('grand_total'), 0, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-right">Rp {{ number_format($salesOrders->sum(function($order) use ($shift) {
                                            return $order->payments->where('created_at', '>=', $shift->start_time)
                                                                  ->where('created_at', '<=', $shift->end_time ?? now())
                                                                  ->sum('amount');
                                        }), 0, ',', '.') }}</td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Detail Pembayaran -->
                        <div class="mt-4 bg-green-50 p-4 rounded-lg">
                            <h3 class="text-md font-semibold mb-2">Detail Pembayaran</h3>
                            <table class="w-full table-auto">
                                <tbody>
                                    <tr class="border-b">
                                        <td class="px-4 py-2">Cash Lunas</td>
                                        <td class="px-4 py-2 text-right">Rp {{ number_format($cashLunas, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2">Cash DP</td>
                                        <td class="px-4 py-2 text-right">Rp {{ number_format($cashDp, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2">Cash Pelunasan</td>
                                        <td class="px-4 py-2 text-right">Rp {{ number_format($cashPelunasan, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2">Transfer Lunas</td>
                                        <td class="px-4 py-2 text-right">Rp {{ number_format($transferLunas, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2">Transfer DP</td>
                                        <td class="px-4 py-2 text-right">Rp {{ number_format($transferDp, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2">Transfer Pelunasan</td>
                                        <td class="px-4 py-2 text-right">Rp {{ number_format($transferPelunasan, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="bg-gray-100 font-semibold">
                                        <td class="px-4 py-2">Total Pendapatan Penjualan</td>
                                        <td class="px-4 py-2 text-right">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Pengeluaran -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">💸 Pengeluaran</h2>
                    @php $expenses = $expenses ?? collect(); @endphp
                    @if($expenses->isEmpty())
                        <p class="text-gray-500 bg-gray-50 p-4 rounded-lg">Tidak ada pengeluaran pada shift ini.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full table-auto border-collapse">
                                <thead>
                                    <tr class="bg-red-100">
                                        <th class="px-4 py-2 text-left">Keterangan</th>
                                        <th class="px-4 py-2 text-right">Jumlah</th>
                                        <th class="px-4 py-2 text-left">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($expenses as $expense)
                                        <tr class="border-b hover:bg-red-50">
                                            <td class="px-4 py-2">{{ $expense->description }}</td>
                                            <td class="px-4 py-2 text-right text-red-600 font-medium">
                                                Rp {{ number_format($expense->amount, 0, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-600">
                                                {{ $expense->created_at->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-red-50 font-semibold">
                                        <td class="px-4 py-2">Total Pengeluaran</td>
                                        <td class="px-4 py-2 text-right text-red-600">
                                            Rp {{ number_format($expenses->sum('amount'), 0, ',', '.') }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Cash Transfer -->
<div class="mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">💸 Setor/Tukar Tunai</h2>
    @php 
        $cashTransfers = $shift->cashTransfers ?? collect(); 
        $totalCashTransfer = $cashTransfers->sum('amount');
    @endphp
    @if($cashTransfers->isEmpty())
        <p class="text-gray-500 bg-gray-50 p-4 rounded-lg">Tidak ada setor/tukar tunai pada shift ini.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full table-auto border-collapse">
                <thead>
                    <tr class="bg-purple-100">
                        <th class="px-4 py-2 text-left">Keterangan</th>
                        <th class="px-4 py-2 text-left">Jenis</th>
                        <th class="px-4 py-2 text-right">Jumlah</th>
                        <th class="px-4 py-2 text-left">Waktu</th>
                        <th class="px-4 py-2 text-left">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cashTransfers as $transfer)
                        <tr class="border-b hover:bg-purple-50">
                            <td class="px-4 py-2">{{ $transfer->description }}</td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 rounded text-xs 
                                    {{ $transfer->type == 'setor' ? 'bg-blue-100 text-blue-800' : 
                                       ($transfer->type == 'tukar' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ ucfirst($transfer->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right text-purple-600 font-medium">
                                - Rp {{ number_format($transfer->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-600">
                                {{ $transfer->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-600">
                                {{ $transfer->notes ?? '-' }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-purple-50 font-semibold">
                        <td colspan="2" class="px-4 py-2">Total Transfer Tunai</td>
                        <td class="px-4 py-2 text-right text-purple-600">
                            - Rp {{ number_format($totalCashTransfer, 0, ',', '.') }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
</div>

                <!-- Summary Kas -->
                <div class="bg-yellow-50 p-6 rounded-lg">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">🧮 Summary Kas</h2>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <table class="w-full table-auto">
                                <tbody>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Kas Awal</td>
                                        <td class="px-4 py-2 text-right">Rp {{ number_format($shift->initial_cash, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Cash Lunas</td>
                                        <td class="px-4 py-2 text-right text-green-600">+ Rp {{ number_format($cashLunas, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Cash DP</td>
                                        <td class="px-4 py-2 text-right text-green-600">+ Rp {{ number_format($cashDp, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Cash Pelunasan</td>
                                        <td class="px-4 py-2 text-right text-green-600">+ Rp {{ number_format($cashPelunasan, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Transfer Lunas</td>
                                        <td class="px-4 py-2 text-right text-blue-600">+ Rp {{ number_format($transferLunas, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Transfer DP</td>
                                        <td class="px-4 py-2 text-right text-blue-600">+ Rp {{ number_format($transferDp, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Transfer Pelunasan</td>
                                        <td class="px-4 py-2 text-right text-blue-600">+ Rp {{ number_format($transferPelunasan, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Pemasukan Manual</td>
                                        <td class="px-4 py-2 text-right text-blue-600">+ Rp {{ number_format($shift->income_total, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Pengeluaran</td>
                                        <td class="px-4 py-2 text-right text-red-600">- Rp {{ number_format($shift->expense_total, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Setor/Tukar Tunai</td>
                                        <td class="px-4 py-2 text-right text-purple-600">- Rp {{ number_format($totalCashTransfer, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="bg-gray-100 font-semibold">
                                        <td class="px-4 py-2">Total Diharapkan (Tunai)</td>
                                        <td class="px-4 py-2 text-right">
                                            Rp {{ number_format($shift->initial_cash + $cashLunas + $cashDp + $cashPelunasan + $shift->income_total - $shift->expense_total - $totalCashTransfer, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <table class="w-full table-auto">
                                <tbody>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Total Pendapatan Transfer</td>
                                        <td class="px-4 py-2 text-right text-blue-600">+ Rp {{ number_format($transferLunas + $transferDp + $transferPelunasan, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="bg-gray-100 font-semibold">
    <td class="px-4 py-2">Total Diharapkan</td>
    <td class="px-4 py-2 text-right">
        Rp {{ number_format($shift->initial_cash + $totalPendapatan + $shift->income_total - $shift->expense_total - $totalCashTransfer, 0, ',', '.') }}
    </td>
</tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Selisih</td>
                                        <td class="px-4 py-2 text-right {{ $shift->discrepancy < 0 ? 'text-red-600' : 'text-green-600' }}">
                                            {{ $shift->discrepancy ? 'Rp ' . number_format($shift->discrepancy, 0, ',', '.') : '-' }}
                                        </td>
                                    </tr>
                                    @if($shift->discrepancy)
                                        <tr>
                                            <td colspan="2" class="px-4 py-2 text-sm text-gray-600">
                                                {{ $shift->discrepancy < 0 ? '❌ Kurang' : '✅ Lebih' }} 
                                                {{ abs($shift->discrepancy) }} dari yang seharusnya
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printShiftSummary() {
    // Langsung pake backup plan yang lebih reliable
    const textContent = `
PARECUSTOM
SUMMARY SHIFT
--------------------------------
Kasir    : {{ $shift->user->name }}
Mulai    : {{ $shift->start_time->format('d/m/Y H:i') }}
Selesai  : {{ $shift->end_time ? $shift->end_time->format('d/m/Y H:i') : 'Masuk Aktif' }}
--------------------------------
RINGKASAN KAS
--------------------------------
Kas Awal       : Rp {{ number_format($shift->initial_cash, 0, ',', '.') }}
Cash Lunas     : + Rp {{ number_format($cashLunas, 0, ',', '.') }}
Cash DP        : + Rp {{ number_format($cashDp, 0, ',', '.') }}
Cash Pelunasan : + Rp {{ number_format($cashPelunasan, 0, ',', '.') }}
Transfer Total : + Rp {{ number_format($transferLunas + $transferDp + $transferPelunasan, 0, ',', '.') }}
Pemasukan Manual: + Rp {{ number_format($shift->income_total, 0, ',', '.') }}
Pengeluaran    : - Rp {{ number_format($shift->expense_total, 0, ',', '.') }}
--------------------------------
TOTAL DIHARAPKAN: Rp {{ number_format($shift->initial_cash + $totalPendapatan + $shift->income_total - $shift->expense_total, 0, ',', '.') }}
@if($shift->final_cash)
KAS AKTUAL     : Rp {{ number_format($shift->final_cash, 0, ',', '.') }}
SELISIH        : @if($shift->discrepancy < 0)-@else+@endif Rp {{ number_format(abs($shift->discrepancy), 0, ',', '.') }}
@endif
--------------------------------
Terima kasih atas pelayanannya
Dicctak: {{ now()->format('d/m/Y H:i') }}
    `;

    // Tampilkan loading
    const printBtn = event.target;
    const originalText = printBtn.innerHTML;
    printBtn.innerHTML = '<i class="bi bi-printer mr-2"></i> Mencetak...';
    printBtn.disabled = true;

    const printWindow = window.open('', '_blank');
    const html = `
        <html>
            <head>
                <title>Print Shift Summary</title>
                <style>
                    body { 
                        margin: 0; 
                        padding: 10px;
                        font-family: 'Courier New', monospace;
                        font-size: 11px;
                        line-height: 1.2;
                    }
                    pre {
                        margin: 0;
                        white-space: pre-wrap;
                    }
                </style>
            </head>
            <body>
                <pre>${textContent}</pre>
                <script>
                    setTimeout(() => {
                        window.print();
                        setTimeout(() => window.close(), 100);
                    }, 100);
                <\/script>
            </body>
        </html>
    `;
    
    printWindow.document.write(html);
    printWindow.document.close();

    setTimeout(() => {
        printBtn.innerHTML = originalText;
        printBtn.disabled = false;
    }, 3000);
}
</script>
</body>
</html>