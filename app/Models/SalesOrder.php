<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'so_number',
        'order_type',
        'order_date',
        'customer_id',
        'subtotal',
        'discount_total',
        'shipping_cost', // ✅ TAMBAH INI
        'grand_total',
        'status',
        'payment_method',
        'payment_status',
        'created_by',
        'approved_by',
        'approved_at',
        'completed_at',
        'deadline',
        'add_to_purchase',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'shipping_cost' => 'decimal:2', // ✅ TAMBAH INI
        'grand_total' => 'decimal:2',
        'order_date' => 'date',
        'deadline' => 'date',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'add_to_purchase' => 'boolean',
    ];

    // === RELASI === 
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_at', 'desc');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SalesOrderLog::class)->orderBy('created_at', 'desc');
    }

    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class, 'sales_order_id');
    }

    /**
     * Cek apakah Sales Order memiliki Purchase Order terkait
     */
    public function hasRelatedPO(): bool
    {
        return $this->purchaseOrder()->exists();
    }

    // === ACCESSOR ===
    public function getPaidTotalAttribute()
    {
        return $this->payments->sum('amount');
    }

    public function getRemainingAmountAttribute()
    {
        return $this->grand_total - $this->paid_total;
    }

    /**
     * Total HPP transaksi ini = Σ (cost_price snapshot × qty).
     * Konsisten dengan formula dashboard: COALESCE(items.cost_price, products.cost_price, 0) × qty.
     */
    public function getTotalHppAttribute(): float
    {
        return (float) $this->items->sum(function ($item) {
            $cost = $item->cost_price;
            // Fallback ke product cost hanya jika snapshot NULL (sama seperti dashboard)
            if ($cost === null) {
                $cost = $item->product ? ($item->product->cost_price ?? 0) : 0;
            }
            return (float) ($cost ?? 0) * (int) $item->qty;
        });
    }

    /**
     * Estimasi Gross Profit per transaksi = Grand Total - Total HPP.
     * Konsisten dengan card Gross Profit dashboard (Omset - HPP).
     */
    public function getEstProfitAttribute(): float
    {
        return (float) $this->grand_total - $this->total_hpp;
    }

    // === Validasi Status ===
    public static function allowedStatuses(): array
    {
        return [
            'draft',
            'pending',
            'request_kain',
            'payment',
            'proses_jahit',
            'printing',
            'diterima_toko',
            'selesai',
        ];
    }

    /**
     * Validasi transisi status berdasarkan workflow baru
     * - Jika ada PO terkait: pending → request_kain → payment → proses_jahit → printing → diterima_toko → selesai
     * - Jika TIDAK ada PO: pending → selesai (setelah approved)
     */
    public function isValidTransition(string $newStatus): bool
    {
        $currentStatus = $this->status;
        
        // Pola deteksi workflow: 
        // 1. Ada PO fisik
        // 2. add_to_purchase dicentang
        // 3. Status saat ini sudah masuk di alur PO (recovery data lama)
        // 4. Tipe order adalah jahit_sendiri (hampir dipastikan butuh PO kain)
        $isPOWorkflow = $this->hasRelatedPO() || 
                         $this->add_to_purchase || 
                         in_array($currentStatus, ['request_kain', 'payment', 'proses_jahit', 'printing', 'diterima_toko']) ||
                         $this->order_type === 'jahit_sendiri';

        // Workflow untuk SO dengan PO terkait (atau seharusnya punya PO)
        if ($isPOWorkflow) {
            $transitions = [
                'draft' => ['pending'],
                'pending' => ['request_kain', 'selesai'], 
                'request_kain' => ['payment'],
                'payment' => ['proses_jahit', 'diterima_toko'],
                'proses_jahit' => ['printing'],
                'printing' => ['diterima_toko'],
                'diterima_toko' => ['selesai'],
            ];
        } else {
            // Workflow untuk SO tanpa PO (lebih singkat)
            $transitions = [
                'draft' => ['pending'],
                'pending' => ['selesai'], 
                'selesai' => [],
            ];
        }

        return in_array($newStatus, $transitions[$currentStatus] ?? []);
    }

    /**
     * Mengambil status desain global dengan prioritas masalah.
     * Prioritas:
     * 1. Rejected (Masalah berat - Revisi)
     * 2. Pending (Masalah potensi - Belum disentuh/Lupa)
     * 3. Waiting Customer (Hambatan eksternal)
     * 4. In Progress (Sedang jalan)
     * 5. Approved (Aman)
     */
    public function getDesignStatusAttribute(): ?string
    {
        $designItems = $this->items->filter(function ($item) {
            return $item->requires_design || in_array($item->product_type, ['dtf', 'jersey']);
        });

        if ($designItems->isEmpty()) {
            return null;
        }

        if ($designItems->contains('design_status', 'rejected')) {
            return 'rejected';
        }

        // PERUBAHAN KRUSIAL: Mengekspos item yang MASIH PENDING (Belum disentuh editor)
        if ($designItems->contains('design_status', 'pending')) {
            return 'pending'; // Dulu 'process', sekarang eksplisit 'pending' agar ketahuan kalau editor belum kerja
        }

        if ($designItems->contains('design_status', 'waiting_customer')) {
            return 'waiting_customer';
        }

        if ($designItems->contains('design_status', 'in_progress')) {
            return 'in_progress';
        }

        if ($designItems->every(fn($item) => $item->design_status === 'approved')) {
            return 'approved';
        }

        return 'pending'; // Fallback default
    }

    /**
     * Mengambil informasi durasi/aging dari status desain saat ini.
     * Berguna untuk mengetahui berapa lama order "mangkrak" atau didiamkan.
     */
    public function getDesignAgingAttribute()
    {
        $status = $this->design_status;

        if (!$status || $status === 'approved')
            return null;

        $designItems = $this->items->filter(function ($item) {
            return $item->requires_design || in_array($item->product_type, ['dtf', 'jersey']);
        });

        // Ambil item yang menyebabkan status global ini
        $targetItems = $designItems->where('design_status', $status);

        // Cari yang paling lama (created_at paling tua untuk pending, updated_at paling tua untuk lainnya)
        if ($status === 'pending') {
            $oldestItem = $targetItems->sortBy('created_at')->first();
            return $oldestItem ? $oldestItem->created_at->diffForHumans() : null;
        } else {
            $oldestItem = $targetItems->sortBy('updated_at')->first();
            return $oldestItem ? $oldestItem->updated_at->diffForHumans() : null;
        }
    }

    /**
     * Mengembalikan ringkasan statistik status desain per item.
     * Contoh: "2 ACC, 1 Revisi"
     */
    public function getDesignStatsAttribute(): array
    {
        $designItems = $this->items->filter(function ($item) {
            return $item->requires_design || in_array($item->product_type, ['dtf', 'jersey']);
        });

        if ($designItems->isEmpty()) {
            return [];
        }

        $stats = $designItems->groupBy('design_status')->map->count();

        // Mapping status ke label pendek
        $labels = [
            'approved' => 'ACC',
            'rejected' => 'Rev',
            'pending' => 'Pend',
            'in_progress' => 'WIP',
            'waiting_customer' => 'Wait',
        ];

        $result = [];
        foreach ($stats as $status => $count) {
            $label = $labels[$status] ?? ucfirst($status);
            $result[$status] = "$count $label";
        }

        return $result;
    }

    /**
     * Menentukan apakah order ini berisiko molor deadline.
     * Logic: Jika deadline < 3 hari lagi DAN desain belum ACC semua.
     */
    public function getDeadlineRiskAttribute(): bool
    {
        // Jika tidak ada deadline atau sudah selesai, aman
        if (!$this->deadline || in_array($this->status, ['diterima_toko', 'selesai'])) {
            return false;
        }

        // Jika desain sudah ACC semua, risiko desain minim (pindah ke risiko produksi, tapi kita fokus desain dulu)
        if ($this->design_status === 'approved') {
            return false;
        }

        // Jika design_status NULL (artinya beli jadi/tidak butuh desain), aman
        if (is_null($this->design_status)) {
            return false;
        }

        $daysUntilDeadline = now()->diffInDays($this->deadline, false);

        // Jika deadline sudah lewat (negatif) atau tinggal 3 hari
        return $daysUntilDeadline <= 3;
    }

    public function isEditable(): bool
    {
        return !in_array($this->status, ['diterima_toko', 'selesai']);
    }
}