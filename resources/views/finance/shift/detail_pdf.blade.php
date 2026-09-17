<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Detail Shift #{{ $shift->id }}</title>
    <style>
        body { 
            font-family: 'DejaVu Sans', Arial, sans-serif; 
            font-size: 10px; 
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header img {
            max-width: 150px;
            height: auto;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            page-break-inside: auto;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .section h2 {
            background-color: #f8f9fa;
            padding: 8px;
            margin: 0 0 10px 0;
            border-left: 4px solid #007bff;
            font-size: 12px;
        }
        .positive {
            color: #28a745;
        }
        .negative {
            color: #dc3545;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .summary-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin: 15px 0;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ asset('https://kasir.parecustom.com/public/assets/logo.png') }}" alt="Pare Custom Logo">
        <h1>LAPORAN DETAIL SHIFT #{{ $shift->id }}</h1>
        <p>Pare Custom - {{ date('d/m/Y H:i') }}</p>
        <p>Dicetak oleh: {{ Auth::user()->name }}</p>
    </div>

    <!-- Informasi Shift -->
    <div class="section">
        <h2>📊 Informasi Shift</h2>
        <table>
            <tr>
                <th width="25%">Kasir</th>
                <td width="25%">{{ $shift->user->name }}</td>
                <th width="25%">Status</th>
                <td width="25%">
                    <span style="display: inline-block; padding: 2px 8px; border-radius: 10px; 
                        {{ $shift->status == 'open' ? 'background-color: #fff3cd; color: #856404;' : 
                           'background-color: #d4edda; color: #155724;' }}">
                        {{ ucfirst($shift->status) }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Waktu Mulai</th>
                <td>{{ $shift->start_time->format('d/m/Y H:i') }}</td>
                <th>Waktu Selesai</th>
                <td>{{ $shift->end_time ? $shift->end_time->format('d/m/Y H:i') : 'Masih Aktif' }}</td>
            </tr>
            <tr>
                <th>Kas Awal</th>
                <td class="text-right">Rp {{ number_format($shift->initial_cash, 0, ',', '.') }}</td>
                <th>Kas Akhir</th>
                <td class="text-right">
                    {{ $shift->final_cash ? 'Rp ' . number_format($shift->final_cash, 0, ',', '.') : '-' }}
                </td>
            </tr>
            <tr>
                <th>Total Kas</th>
                <td class="text-right positive">Rp {{ number_format($shift->cash_total, 0, ',', '.') }}</td>
                <th>Selisih</th>
                <td class="text-right {{ $shift->discrepancy < 0 ? 'negative' : 'positive' }}">
                    {{ $shift->discrepancy ? 'Rp ' . number_format(abs($shift->discrepancy), 0, ',', '.') : '-' }}
                </td>
            </tr>
            <tr>
                <th>Pemasukan Manual</th>
                <td class="text-right positive">Rp {{ number_format($shift->income_total, 0, ',', '.') }}</td>
                <th>Pengeluaran</th>
                <td class="text-right negative">Rp {{ number_format($shift->expense_total, 0, ',', '.') }}</td>
            </tr>
            @if($shift->notes)
            <tr>
                <th>Catatan</th>
                <td colspan="3">{{ $shift->notes }}</td>
            </tr>
            @endif
        </table>
    </div>

    <!-- Detail Pembayaran -->
    @php
        $payments = \App\Models\Payment::where('created_by', $shift->user_id)
            ->where('created_at', '>=', $shift->start_time)
            ->where('created_at', '<=', $shift->end_time ?? now())
            ->with('salesOrder')
            ->get();
        $cashLunas = $cashDp = $cashPelunasan = $transferLunas = $transferDp = $transferPelunasan = 0;
        foreach ($payments as $payment) {
            $so = $payment->salesOrder;
            $isLunasSekaliBayar = ($payment->category === 'pelunasan' && $so->payments->count() === 1);
            if ($payment->method === 'cash') {
                if ($isLunasSekaliBayar) {
                    $cashLunas += $payment->amount;
                } elseif ($payment->category === 'dp') {
                    $cashDp += $payment->amount;
                } else {
                    $cashPelunasan += $payment->amount;
                }
            } elseif ($payment->method === 'transfer') {
                if ($isLunasSekaliBayar) {
                    $transferLunas += $payment->amount;
                } elseif ($payment->category === 'dp') {
                    $transferDp += $payment->amount;
                } else {
                    $transferPelunasan += $payment->amount;
                }
            } elseif ($payment->method === 'split') {
                if ($isLunasSekaliBayar) {
                    $cashLunas += $payment->cash_amount;
                    $transferLunas += $payment->transfer_amount;
                } elseif ($payment->category === 'dp') {
                    $cashDp += $payment->cash_amount;
                    $transferDp += $payment->transfer_amount;
                } else {
                    $cashPelunasan += $payment->cash_amount;
                    $transferPelunasan += $payment->transfer_amount;
                }
            }
        }
        $totalPendapatan = $cashLunas + $cashDp + $cashPelunasan + $transferLunas + $transferDp + $transferPelunasan;
    @endphp
    <div class="section">
        <h2>💸 Detail Pembayaran</h2>
        <table>
            <tr>
                <th width="60%">Kategori</th>
                <th width="40%" class="text-right">Jumlah</th>
            </tr>
            <tr>
                <td>Cash Lunas</td>
                <td class="text-right positive">Rp {{ number_format($cashLunas, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Cash DP</td>
                <td class="text-right positive">Rp {{ number_format($cashDp, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Cash Pelunasan</td>
                <td class="text-right positive">Rp {{ number_format($cashPelunasan, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Transfer Lunas</td>
                <td class="text-right positive">Rp {{ number_format($transferLunas, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Transfer DP</td>
                <td class="text-right positive">Rp {{ number_format($transferDp, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Transfer Pelunasan</td>
                <td class="text-right positive">Rp {{ number_format($transferPelunasan, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Total Pendapatan Penjualan</th>
                <th class="text-right positive">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</th>
            </tr>
        </table>
    </div>

    <!-- Summary Kas -->
    <div class="summary-box">
        <h3 style="margin: 0 0 10px 0; color: #333;">🧮 Ringkasan Kas</h3>
        <table style="border: none; background: none;">
            <tr>
                <td width="40%">Kas Awal</td>
                <td width="10%" class="text-right">Rp</td>
                <td width="20%" class="text-right">{{ number_format($shift->initial_cash, 0, ',', '.') }}</td>
                <td width="30%"></td>
            </tr>
            <tr>
                <td>+ Kas dari Penjualan</td>
                <td class="text-right">Rp</td>
                <td class="text-right positive">+ {{ number_format($cashLunas + $cashDp + $cashPelunasan, 0, ',', '.') }}</td>
                <td></td>
            </tr>
            <tr>
                <td>+ Pemasukan Manual</td>
                <td class="text-right">Rp</td>
                <td class="text-right positive">+ {{ number_format($shift->income_total, 0, ',', '.') }}</td>
                <td></td>
            </tr>
            <tr>
                <td>- Pengeluaran</td>
                <td class="text-right">Rp</td>
                <td class="text-right negative">- {{ number_format($shift->expense_total, 0, ',', '.') }}</td>
                <td></td>
            </tr>
            <tr style="border-top: 2px solid #333;">
                <td><strong>Total Diharapkan</strong></td>
                <td class="text-right"><strong>Rp</strong></td>
                <td class="text-right"><strong>{{ number_format($shift->initial_cash + $cashLunas + $cashDp + $cashPelunasan + $shift->income_total - $shift->expense_total, 0, ',', '.') }}</strong></td>
                <td></td>
            </tr>
            <tr>
                <td>Kas Aktual</td>
                <td class="text-right">Rp</td>
                <td class="text-right">
                    {{ $shift->final_cash ? number_format($shift->final_cash, 0, ',', '.') : '-' }}
                </td>
                <td></td>
            </tr>
            <tr>
                <td><strong>Selisih</strong></td>
                <td class="text-right"><strong>Rp</strong></td>
                <td class="text-right {{ $shift->discrepancy < 0 ? 'negative' : 'positive' }}">
                    <strong>{{ $shift->discrepancy ? number_format(abs($shift->discrepancy), 0, ',', '.') : '-' }}</strong>
                </td>
                <td class="{{ $shift->discrepancy < 0 ? 'negative' : 'positive' }}">
                    {{ $shift->discrepancy < 0 ? '❌ Kurang' : ($shift->discrepancy > 0 ? '✅ Lebih' : '') }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Pemasukan Manual -->
    <div class="section">
        <h2>💰 Pemasukan Manual</h2>
        @if($incomes->isEmpty())
            <p style="text-align: center; color: #666; font-style: italic; padding: 20px;">
                Tidak ada pemasukan manual pada shift ini
            </p>
        @else
            <table>
                <thead>
                    <tr>
                        <th width="60%">Keterangan</th>
                        <th width="20%" class="text-right">Jumlah</th>
                        <th width="20%">Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($incomes as $income)
                        <tr>
                            <td>{{ $income->description }}</td>
                            <td class="text-right positive">Rp {{ number_format($income->amount, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $income->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total Pemasukan Manual</th>
                        <th class="text-right positive">Rp {{ number_format($incomes->sum('amount'), 0, ',', '.') }}</th>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>

    <!-- Penjualan -->
    <div class="section">
        <h2>🛒 Penjualan ({{ $salesOrders->count() }} transaksi)</h2>
        @if($salesOrders->isEmpty())
            <p style="text-align: center; color: #666; font-style: italic; padding: 20px;">
                Tidak ada penjualan pada shift ini
            </p>
        @else
            <table>
                <thead>
                    <tr>
                        <th width="10%">Order ID</th>
                        <th width="25%">Pelanggan</th>
                        <th width="15%" class="text-right">Total</th>
                        <th width="15%" class="text-right">Dibayar (Shift Ini)</th>
                        <th width="15%">Metode</th>
                        <th width="10%">Status</th>
                        <th width="10%">Waktu Order</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salesOrders as $order)
                        @php
                            $shiftPayments = $order->payments->where('created_at', '>=', $shift->start_time)->where('created_at', '<=', $shift->end_time ?? now());
                        @endphp
                        <tr>
                            <td class="text-center">#{{ $order->id }}</td>
                            <td>{{ $order->customer->name ?? 'Umum' }}</td>
                            <td class="text-right">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($shiftPayments->sum('amount'), 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if($shiftPayments->isNotEmpty())
                                    {{ $shiftPayments->first()->method }}
                                    @if($shiftPayments->count() > 1)
                                        <br><small>(+{{ $shiftPayments->count() - 1 }} pembayaran)</small>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                <span style="display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9px;
                                    {{ $order->status == 'completed' ? 'background-color: #d4edda; color: #155724;' : 
                                       ($order->status == 'pending' ? 'background-color: #fff3cd; color: #856404;' : 
                                       'background-color: #f8d7da; color: #721c24;') }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td class="text-center">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Total Penjualan</th>
                        <th class="text-right">Rp {{ number_format($salesOrders->sum('grand_total'), 0, ',', '.') }}</th>
                        <th class="text-right">Rp {{ number_format($salesOrders->sum(function($o) use ($shift) { 
                            return $o->payments->where('created_at', '>=', $shift->start_time)->where('created_at', '<=', $shift->end_time ?? now())->sum('amount'); 
                        }), 0, ',', '.') }}</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>

    <!-- Pengeluaran -->
    <div class="section">
        <h2>💸 Pengeluaran ({{ $expenses->count() }} item)</h2>
        @if($expenses->isEmpty())
            <p style="text-align: center; color: #666; font-style: italic; padding: 20px;">
                Tidak ada pengeluaran pada shift ini
            </p>
        @else
            <table>
                <thead>
                    <tr>
                        <th width="60%">Keterangan</th>
                        <th width="20%" class="text-right">Jumlah</th>
                        <th width="20%">Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenses as $expense)
                        <tr>
                            <td>{{ $expense->description }}</td>
                            <td class="text-right negative">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $expense->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total Pengeluaran</th>
                        <th class="text-right negative">Rp {{ number_format($expenses->sum('amount'), 0, ',', '.') }}</th>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>

    <div class="footer">
        <p>Dicetak pada: {{ date('d/m/Y H:i:s') }}</p>
        <p>© {{ date('Y') }} Pare Custom - Sistem Manajemen Shift</p>
    </div>
</body>
</html>