<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\SalesOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class InvoiceService
{
    /**
     * Data Profil Toko & Rekening Pare Custom
     */
    public const STORE_INFO = [
        'name' => 'PARE CUSTOM',
        'company' => 'Pare Digital Custom',
        'address_lines' => [
            'Jl. Pb. Sudirman No.37, Perdana,',
            'Pare, Kec. Pare, Kabupaten Kediri,',
            'Jawa Timur 64211',
        ],
        'social' => '@parecustom',
        'bank' => [
            'name' => 'Mandiri',
            'account_name' => 'Pare Digital Custom',
            'account_number' => '1710017118889',
        ],
    ];

    /**
     * Siapkan seluruh data yang dibutuhkan untuk invoice PDF & WhatsApp
     */
    public function prepareInvoiceData(SalesOrder $salesOrder, ?Payment $payment = null): array
    {
        $salesOrder->loadMissing(['customer', 'items', 'creator', 'payments']);

        $customer = $salesOrder->customer;
        $customerName = $customer->name ?? 'Umum';
        $customerPhone = $customer->phone ?? '';

        // Kasir / Admin
        $kasirName = $payment?->creator?->name 
            ?? $salesOrder->creator?->name 
            ?? auth()->user()?->name 
            ?? 'Admin';

        // Tanggal Invoice (ambil dari tanggal bayar jika ada, atau order_date)
        $invoiceDate = $payment && $payment->paid_at
            ? Carbon::parse($payment->paid_at)->format('d/m/Y')
            : ($salesOrder->order_date ? Carbon::parse($salesOrder->order_date)->format('d/m/Y') : now()->format('d/m/Y'));

        // Catatan Pembayaran
        $paymentDate = $payment && $payment->paid_at
            ? Carbon::parse($payment->paid_at)->format('d/m/Y H:i')
            : now()->format('d/m/Y H:i');

        $paymentMethod = $payment ? ucfirst($payment->method) : ucfirst($salesOrder->payment_method ?? 'Cash');
        $paymentRef = $payment?->reference_number ?: ($payment?->reference ?: '-');
        $paymentAmount = $payment ? (float) $payment->amount : (float) $salesOrder->paid_total;

        // Hitung total bayar kumulatif sampai pembayaran ini
        if ($payment) {
            $cumulativePaid = $salesOrder->payments
                ->where('paid_at', '<=', $payment->paid_at)
                ->sum('amount');
            if ($cumulativePaid <= 0) {
                $cumulativePaid = $paymentAmount;
            }
        } else {
            $cumulativePaid = (float) $salesOrder->paid_total;
        }

        $sisaTagihan = max(0, (float) $salesOrder->grand_total - $cumulativePaid);

        // Ambil Base64 Logo untuk Dompdf agar tidak pernah broken
        $logoBase64 = $this->getLogoBase64();

        return [
            'store' => self::STORE_INFO,
            'salesOrder' => $salesOrder,
            'payment' => $payment,
            'customer' => $customer,
            'customerName' => $customerName,
            'customerPhone' => $customerPhone,
            'kasirName' => $kasirName,
            'invoiceNumber' => $salesOrder->so_number,
            'invoiceDate' => $invoiceDate,
            'items' => $salesOrder->items,
            'subtotal' => (float) $salesOrder->subtotal,
            'discountTotal' => (float) $salesOrder->discount_total,
            'shippingCost' => (float) ($salesOrder->shipping_cost ?? 0),
            'grandTotal' => (float) $salesOrder->grand_total,
            'paymentDate' => $paymentDate,
            'paymentMethod' => $paymentMethod,
            'paymentRef' => $paymentRef,
            'paymentAmount' => $paymentAmount,
            'cumulativePaid' => $cumulativePaid,
            'sisaTagihan' => $sisaTagihan,
            'logoBase64' => $logoBase64,
        ];
    }

    /**
     * Dapatkan logo dalam format base64 data URI
     */
    public function getLogoBase64(): ?string
    {
        $paths = [
            public_path('assets/logo-invoice.png'),
            public_path('assets/logo-transparent.png'),
            public_path('assets/logo.png'),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = File::get($path);
                return 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }

        return null;
    }

    /**
     * Buat instance PDF Dompdf siap download atau stream
     */
    public function buildInvoicePdf(SalesOrder $salesOrder, ?Payment $payment = null): \Barryvdh\DomPDF\PDF
    {
        $data = $this->prepareInvoiceData($salesOrder, $payment);

        $pdf = Pdf::loadView('pdf.customer_invoice', $data);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Helvetica',
            'dpi' => 150,
        ]);

        return $pdf;
    }

    /**
     * Download Invoice PDF
     */
    public function downloadPdf(SalesOrder $salesOrder, ?Payment $payment = null, ?string $filename = null)
    {
        $pdf = $this->buildInvoicePdf($salesOrder, $payment);
        $filename = $filename ?: 'Invoice_' . $salesOrder->so_number . ($payment ? '_PAY' . $payment->id : '') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Preview (stream inline) Invoice PDF di browser
     */
    public function previewPdf(SalesOrder $salesOrder, ?Payment $payment = null, ?string $filename = null)
    {
        $pdf = $this->buildInvoicePdf($salesOrder, $payment);
        $filename = $filename ?: 'Invoice_' . $salesOrder->so_number . '.pdf';
        return $pdf->stream($filename);
    }

    /**
     * Buat template pesan WhatsApp yang rapi dan profesional untuk customer
     */
    public function buildWhatsAppMessage(SalesOrder $salesOrder, ?Payment $payment = null, ?string $invoiceUrl = null): string
    {
        $data = $this->prepareInvoiceData($salesOrder, $payment);

        $msg = "Halo Kak *{$data['customerName']}*,\n";
        $msg .= "Terima kasih telah bertransaksi di *Pare Custom*! 🙏\n\n";
        $msg .= "Berikut rincian nota & pembayaran Anda:\n";
        $msg .= "📄 *No. Invoice:* {$data['invoiceNumber']}\n";
        $msg .= "📅 *Tanggal:* {$data['paymentDate']}\n";
        $msg .= "👤 *Admin/Kasir:* {$data['kasirName']}\n";
        $msg .= "💰 *Pembayaran:* Rp " . number_format($data['paymentAmount'], 0, ',', '.') . " (" . $data['paymentMethod'] . ")\n";
        
        if ($data['paymentRef'] !== '-') {
            $msg .= "🔖 *Ref:* {$data['paymentRef']}\n";
        }

        $msg .= "\n*Ringkasan Transaksi:*\n";
        $msg .= "• Subtotal: Rp " . number_format($data['subtotal'], 0, ',', '.') . "\n";
        if ($data['discountTotal'] > 0) {
            $msg .= "• Diskon: Rp " . number_format($data['discountTotal'], 0, ',', '.') . "\n";
        }
        if ($data['shippingCost'] > 0) {
            $msg .= "• Ongkir: Rp " . number_format($data['shippingCost'], 0, ',', '.') . "\n";
        }
        $msg .= "• *Grand Total:* Rp " . number_format($data['grandTotal'], 0, ',', '.') . "\n";
        $msg .= "• *Total Terbayar:* Rp " . number_format($data['cumulativePaid'], 0, ',', '.') . "\n";
        
        if ($data['sisaTagihan'] > 0) {
            $msg .= "• *Sisa Tagihan:* Rp " . number_format($data['sisaTagihan'], 0, ',', '.') . "\n";
            $msg .= "\n_Mohon lakukan pelunasan ke rekening resmi kami:_\n";
            $msg .= "🏦 *Bank {$data['store']['bank']['name']}*\n";
            $msg .= "💳 No. Rek: *{$data['store']['bank']['account_number']}*\n";
            $msg .= "A/N: *{$data['store']['bank']['account_name']}*\n";
        } else {
            $msg .= "• *Status:* ✅ *LUNAS*\n";
        }

        if ($invoiceUrl) {
            $msg .= "\n📥 *Unduh Invoice Resmi (PDF):*\n{$invoiceUrl}\n";
        }

        $msg .= "\nTerima kasih atas kepercayaan Anda kepada *Pare Custom*! ✨\n";
        $msg .= "_{$data['store']['address_lines'][0]} {$data['store']['address_lines'][1]}_\n";
        $msg .= "Instagram: {$data['store']['social']}";

        return $msg;
    }

    /**
     * Format nomor telepon menjadi standar internasional WhatsApp (+62 -> 628...)
     */
    public function formatPhoneNumber(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        // Hapus karakter non-digit
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62' . substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '62' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Buat URL WhatsApp wa.me siap klik
     */
    public function buildWhatsAppUrl(SalesOrder $salesOrder, ?Payment $payment = null, ?string $invoiceUrl = null, ?string $overridePhone = null): string
    {
        $phone = $overridePhone ?: ($salesOrder->customer->phone ?? '');
        $formattedPhone = $this->formatPhoneNumber($phone);
        $message = $this->buildWhatsAppMessage($salesOrder, $payment, $invoiceUrl);

        if (empty($formattedPhone)) {
            return 'https://api.whatsapp.com/send?text=' . urlencode($message);
        }

        return 'https://api.whatsapp.com/send?phone=' . $formattedPhone . '&text=' . urlencode($message);
    }
}
