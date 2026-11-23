<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoiceNumber }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 14mm 16mm 12mm 16mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #111827;
            font-size: 8.5pt;
            line-height: 1.35;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        /* Utilities */
        .w-100 { width: 100%; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .text-muted { color: #4b5563; }
        .text-blue { color: #0068b6; }
        .text-magenta { color: #d5007f; }

        /* Header Layout */
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
            padding: 0;
        }

        .logo-img {
            max-height: 52px;
            width: auto;
            display: block;
        }

        .address-box {
            margin-top: 6px;
        }

        .address-pin {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: #0068b6;
            margin-right: 4px;
            vertical-align: 1px;
        }

        .address-text {
            font-size: 8pt;
            line-height: 1.35;
            color: #4b5563;
        }

        .invoice-title {
            font-size: 26pt;
            font-weight: bold;
            color: #111827;
            letter-spacing: 0.5px;
            line-height: 1;
            margin-bottom: 8px;
        }

        .invoice-meta-container {
            border-left: 2px solid #0068b6;
            padding-left: 10px;
            margin-top: 4px;
            display: inline-block;
            text-align: left;
        }

        .meta-table {
            border-collapse: collapse;
            font-size: 8.5pt;
        }

        .meta-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .meta-table .label {
            color: #4b5563;
            width: 95px;
        }

        .meta-table .colon {
            width: 12px;
            text-align: center;
            color: #111827;
        }

        .meta-table .val {
            color: #111827;
        }

        /* Tri-color Brand Stripe */
        .brand-stripe {
            width: 100%;
            height: 3px;
            margin: 12px 0 14px 0;
            border-collapse: collapse;
        }

        .brand-stripe td {
            height: 3px;
            padding: 0;
        }

        .stripe-yellow { background-color: #ffd119; width: 28%; }
        .stripe-magenta { background-color: #d5007f; width: 34%; }
        .stripe-blue { background-color: #0b76c5; width: 38%; }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #0068b6;
            margin-bottom: 14px;
        }

        .items-table th {
            background-color: #0068b6;
            color: #ffffff;
            font-weight: bold;
            font-size: 8.5pt;
            padding: 5px 6px;
            border: 0.5px solid #0068b6;
        }

        .items-table td {
            font-size: 8pt;
            padding: 5px 6px;
            border: 0.5px solid #94a3b8;
            vertical-align: middle;
        }

        .items-table tbody tr.row-even {
            background-color: #fbfdff;
        }

        .items-table tbody tr.row-odd {
            background-color: #ffffff;
        }

        /* Bottom Cards Section */
        .bottom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 12px 0;
            margin-bottom: 12px;
        }

        .bottom-table > tbody > tr > td {
            vertical-align: top;
            padding: 0;
            width: 50%;
        }

        .card-box {
            border: 0.9px solid #0068b6;
            border-radius: 6px;
            overflow: hidden;
            background-color: #ffffff;
        }

        .card-header-blue {
            background-color: #0068b6;
            color: #ffffff;
            font-weight: bold;
            font-size: 8.5pt;
            padding: 5px 8px;
            letter-spacing: 0.3px;
        }

        .card-body {
            padding: 7px 9px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }

        .info-table td {
            padding: 2.5px 0;
            vertical-align: top;
        }

        .info-table .info-label {
            color: #4b5563;
            width: 80px;
        }

        .info-table .info-colon {
            width: 12px;
            text-align: center;
            color: #111827;
        }

        .info-table .info-val {
            color: #111827;
        }

        .card-divider {
            border-top: 0.6px solid #cbd5e1;
            margin: 6px 0;
        }

        .sub-header-magenta {
            color: #d5007f;
            font-weight: bold;
            font-size: 8pt;
            margin-bottom: 3px;
            letter-spacing: 0.3px;
        }

        /* Totals Card */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }

        .totals-table td {
            padding: 5px 9px;
            border-bottom: 0.6px solid #cbd5e1;
        }

        .totals-table tr:last-child td {
            border-bottom: none;
        }

        .row-grand-total {
            background-color: #d5007f;
            color: #ffffff;
        }

        .row-grand-total td {
            color: #ffffff !important;
            font-weight: bold;
            font-size: 9.5pt;
            border-bottom: 0.6px solid #cbd5e1 !important;
            padding: 6px 9px !important;
        }

        .row-sisa td {
            color: #0068b6 !important;
            font-weight: bold;
            font-size: 9.5pt;
            padding: 6px 9px !important;
        }

        /* Footer */
        .footer-line {
            border-top: 0.8px solid #0b76c5;
            margin-top: 14px;
            padding-top: 6px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            padding: 0;
            vertical-align: middle;
        }

        .footer-center {
            text-align: center;
        }

        .footer-thanks {
            font-weight: bold;
            font-size: 9pt;
            color: #111827;
        }

        .footer-social {
            font-size: 8pt;
            color: #4b5563;
            margin-top: 1px;
        }

        .footer-id {
            font-size: 6.5pt;
            color: #94a3b8;
            text-align: right;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <!-- Left: Logo & Address -->
            <td style="width: 50%;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo Pare Custom" class="logo-img">
                @else
                    <div style="font-size: 16pt; font-weight: bold; color: #0068b6; margin-bottom: 4px;">{{ $store['name'] }}</div>
                @endif

                <div class="address-box">
                    <span class="address-pin"></span>
                    <span class="address-text">
                        {{ $store['address_lines'][0] }}<br>
                        {{ $store['address_lines'][1] }}<br>
                        {{ $store['address_lines'][2] }}
                    </span>
                </div>
            </td>

            <!-- Right: INVOICE title & Meta -->
            <td style="width: 50%; text-align: right;">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-meta-container">
                    <table class="meta-table">
                        <tr>
                            <td class="label">No. Invoice</td>
                            <td class="colon">:</td>
                            <td class="val font-bold">{{ $invoiceNumber }}</td>
                        </tr>
                        <tr>
                            <td class="label">Tanggal Invoice</td>
                            <td class="colon">:</td>
                            <td class="val">{{ $invoiceDate }}</td>
                        </tr>
                        <tr>
                            <td class="label">Kasir / Admin</td>
                            <td class="colon">:</td>
                            <td class="val">{{ $kasirName }}</td>
                        </tr>
                        <tr>
                            <td class="label">Customer</td>
                            <td class="colon">:</td>
                            <td class="val font-bold">{{ $customerName }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Tri-color Brand Stripe -->
    <table class="brand-stripe">
        <tr>
            <td class="stripe-yellow"></td>
            <td class="stripe-magenta"></td>
            <td class="stripe-blue"></td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%;" class="text-center">No</th>
                <th style="width: 52%;" class="text-left">Deskripsi Item</th>
                <th style="width: 8%;" class="text-center">Qty</th>
                <th style="width: 17%;" class="text-right">Harga Satuan</th>
                <th style="width: 17%;" class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
                <tr class="{{ $index % 2 == 0 ? 'row-odd' : 'row-even' }}">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-left">
                        {{ $item->product_name }}
                    </td>
                    <td class="text-center">{{ $item->qty }}</td>
                    <td class="text-right">Rp {{ number_format($item->sale_price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->line_total ?? ($item->qty * $item->sale_price), 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Bottom Section: 2 Cards Side-by-Side -->
    <table class="bottom-table">
        <tr>
            <!-- Left Card: Bank & Payment Note -->
            <td>
                <div class="card-box">
                    <div class="card-header-blue">REKENING PEMBAYARAN</div>
                    <div class="card-body">
                        <table class="info-table">
                            <tr>
                                <td class="info-label">Bank</td>
                                <td class="info-colon">:</td>
                                <td class="info-val font-bold">{{ $store['bank']['name'] }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Atas Nama</td>
                                <td class="info-colon">:</td>
                                <td class="info-val font-bold">{{ $store['bank']['account_name'] }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">No. Rekening</td>
                                <td class="info-colon">:</td>
                                <td class="info-val font-bold text-blue">{{ $store['bank']['account_number'] }}</td>
                            </tr>
                        </table>

                        <div class="card-divider"></div>

                        <div class="sub-header-magenta">CATATAN PEMBAYARAN</div>
                        <table class="info-table">
                            <tr>
                                <td class="info-label" style="font-size: 7.5pt;">Tanggal</td>
                                <td class="info-colon" style="font-size: 7.5pt;">:</td>
                                <td class="info-val" style="font-size: 7.5pt;">{{ $paymentDate }}</td>
                            </tr>
                            <tr>
                                <td class="info-label" style="font-size: 7.5pt;">Metode</td>
                                <td class="info-colon" style="font-size: 7.5pt;">:</td>
                                <td class="info-val" style="font-size: 7.5pt;">{{ $paymentMethod }}</td>
                            </tr>
                            <tr>
                                <td class="info-label" style="font-size: 7.5pt;">Ref</td>
                                <td class="info-colon" style="font-size: 7.5pt;">:</td>
                                <td class="info-val" style="font-size: 7.5pt;">{{ $paymentRef }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>

            <!-- Right Card: Totals & Balance -->
            <td>
                <div class="card-box">
                    <table class="totals-table">
                        <tr>
                            <td class="text-left text-muted">Subtotal</td>
                            <td class="text-right font-bold">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @if($discountTotal > 0)
                            <tr>
                                <td class="text-left text-muted">Diskon</td>
                                <td class="text-right font-bold">Rp {{ number_format($discountTotal, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if($shippingCost > 0)
                            <tr>
                                <td class="text-left text-muted">Ongkir</td>
                                <td class="text-right font-bold">Rp {{ number_format($shippingCost, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr class="row-grand-total">
                            <td class="text-left">Grand Total</td>
                            <td class="text-right">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-left text-muted">Pembayaran ({{ $paymentMethod }})</td>
                            <td class="text-right font-bold">Rp {{ number_format($paymentAmount, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="row-sisa">
                            <td class="text-left">Sisa Tagihan</td>
                            <td class="text-right">Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Footer Section -->
    <div class="footer-line">
        <table class="footer-table">
            <tr>
                <td style="width: 25%;"></td>
                <td style="width: 50%;" class="footer-center">
                    <div class="footer-thanks">&copy; Terima kasih atas kepercayaan Anda.</div>
                    <div class="footer-social">{{ $store['social'] }}</div>
                </td>
                <td style="width: 25%;" class="footer-id">
                    Invoice {{ $invoiceNumber }}
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
