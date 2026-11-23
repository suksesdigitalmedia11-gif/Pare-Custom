<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Closing Summary - Shift #{{ $shift->id }}</title>
    <style>
        /* RESET MARGIN DAN PADDING */
        * { 
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: 'DejaVu Sans', Arial, sans-serif; 
            font-size: 11px; 
            background: #fff;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        /* CONTAINER FULL WIDTH TANPA MARGIN */
        #receipt {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0;
        }

        /* HEADER */
        .header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 1px dashed #333;
            padding-bottom: 5px;
            width: 100%;
        }
        .header img {
            max-width: 120px;
            height: auto;
            margin-bottom: 5px;
        }
        .header h1 {
            margin: 0;
            font-size: 14px;
            line-height: 1.2;
        }
        .header p {
            margin: 2px 0;
            font-size: 10px;
            line-height: 1.2;
        }

        /* TABLE FULL WIDTH */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }
        th, td {
            border-bottom: 1px dashed #ccc;
            padding: 3px 2px;
            text-align: left;
            font-size: 10px;
            line-height: 1.2;
        }
        th {
            background-color: #f8f9fa;
        }

        /* SECTION */
        .section {
            margin-bottom: 6px;
            width: 100%;
        }
        .section h2 {
            font-size: 11px;
            margin: 4px 0;
            border-bottom: 1px solid #000;
            line-height: 1.2;
        }

        /* SUMMARY BOX */
        .summary-box {
            font-size: 10px;
            border: 1px solid #ddd;
            padding: 5px;
            margin: 5px 0;
            width: 100%;
        }

        /* FOOTER */
        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: 9px;
            border-top: 1px dashed #333;
            padding-top: 5px;
            width: 100%;
            line-height: 1.2;
        }

        .discrepancy {
            font-weight: bold;
            font-size: 11px;
            text-align: center;
            margin: 6px 0;
            line-height: 1.2;
        }

        /* PRINT STYLING - FULL PAGE */
        @media print {
            @page {
                margin: 0;
                padding: 0;
                size: auto; /* Auto adjust ke ukuran kertas */
            }
            
            body {
                margin: 0;
                padding: 0;
                width: 100%;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            #receipt {
                width: 100%;
                margin: 0;
                padding: 0;
            }
            
            /* Force full width untuk thermal printer */
            table {
                width: 100% !important;
                max-width: 100% !important;
            }
            
            th, td {
                padding: 2px 1px;
                font-size: 9px;
            }
        }

        /* RESPONSIVE UNTUK SEMUA UKURAN KERTAS */
        @media (max-width: 80mm) {
            /* Untuk thermal printer kecil */
            body { font-size: 9px; }
            .header h1 { font-size: 12px; }
            .header p { font-size: 8px; }
            th, td { font-size: 8px; padding: 1px; }
        }

        @media (min-width: 210mm) {
            /* Untuk A4 */
            body { font-size: 12px; }
            .header h1 { font-size: 16px; }
            .header p { font-size: 11px; }
            th, td { font-size: 11px; padding: 4px; }
        }
    </style>
</head>
<body>
    <div id="receipt">
        <div class="header">
            <img src="{{ asset('assets/logo.png') }}" alt="Pare Custom Logo">
            <h1>CLOSING SUMMARY SHIFT</h1>
            <p>Pare Custom - {{ date('d/m/Y H:i') }}</p>
            <p>Shift #{{ $shift->id }}</p>
        </div>

        <!-- Isi Section -->
        <div class="section">
            <h2>📊 Informasi Shift</h2>
            <table>
                <tr><th>Kasir</th><td>{{ $shift->user->name }}</td></tr>
                <tr><th>Mulai</th><td>{{ $shift->start_time?->format('d/m/Y H:i') ?? '-' }}</td></tr>
                <tr><th>Selesai</th><td>{{ $shift->end_time?->format('d/m/Y H:i') ?? 'Belum selesai' }}</td></tr>
                <tr><th>Durasi</th><td>{{ ($shift->start_time && $shift->end_time) ? $shift->start_time->diff($shift->end_time)->format('%h jam %i mnt') : '-' }}</td></tr>
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
                <tr><td>Cash Lunas</td><td style="text-align:right;">Rp {{ number_format($cashLunas,0,',','.') }}</td></tr>
                <tr><td>Cash DP</td><td style="text-align:right;">Rp {{ number_format($cashDp,0,',','.') }}</td></tr>
                <tr><td>Cash Pelunasan</td><td style="text-align:right;">Rp {{ number_format($cashPelunasan,0,',','.') }}</td></tr>
                <tr><td>Transfer Lunas</td><td style="text-align:right;">Rp {{ number_format($transferLunas,0,',','.') }}</td></tr>
                <tr><td>Transfer DP</td><td style="text-align:right;">Rp {{ number_format($transferDp,0,',','.') }}</td></tr>
                <tr><td>Transfer Pelunasan</td><td style="text-align:right;">Rp {{ number_format($transferPelunasan,0,',','.') }}</td></tr>
                <tr><th>Total Pendapatan</th><th style="text-align:right;">Rp {{ number_format($totalPendapatan,0,',','.') }}</th></tr>
            </table>
        </div>

        <div class="section summary-box">
            <h2>🧮 Ringkasan Kas</h2>
            <table style="border:none;">
                <tr><td>Kas Awal</td><td style="text-align:right;">Rp {{ number_format($shift->initial_cash,0,',','.') }}</td></tr>
                <tr><td>Kas Penjualan</td><td style="text-align:right;">Rp {{ number_format($cashLunas+$cashDp+$cashPelunasan,0,',','.') }}</td></tr>
                <tr><td>Pemasukan Manual</td><td style="text-align:right;">Rp {{ number_format($shift->income_total,0,',','.') }}</td></tr>
                <tr><td>Pengeluaran</td><td style="text-align:right;">Rp {{ number_format($shift->expense_total,0,',','.') }}</td></tr>
                <tr><th>Total Diharapkan</th><th style="text-align:right;">Rp {{ number_format($shift->initial_cash + $cashLunas + $cashDp + $cashPelunasan + $shift->income_total - $shift->expense_total,0,',','.') }}</th></tr>
                <tr><td>Kas Aktual</td><td style="text-align:right;">Rp {{ number_format($shift->final_cash,0,',','.') }}</td></tr>
            </table>
            <div class="discrepancy">
                SELISIH: Rp {{ number_format(abs($shift->discrepancy),0,',','.') }}
                <br>
                {{ $shift->discrepancy > 0 ? 'LEBIH' : ($shift->discrepancy < 0 ? 'KURANG' : 'PAS') }}
            </div>
        </div>

        <div class="footer">
            <p>Dicetak: {{ date('d/m/Y H:i:s') }}</p>
            <p>© {{ date('Y') }} Pare Custom</p>
        </div>
    </div>
</body>
</html>