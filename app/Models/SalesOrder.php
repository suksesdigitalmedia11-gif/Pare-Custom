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
        $hasPO = $this->hasRelatedPO();
        
        // Workflow untuk SO dengan PO terkait
        if ($hasPO) {
            $transitions = [
                'draft' => ['pending'],
                'pending' => ['request_kain'], // Hanya request_kain untuk yang ada PO
                'request_kain' => ['payment'],
                'payment' => ['proses_jahit', 'diterima_toko'], // proses_jahit untuk jahit_sendiri, diterima_toko untuk beli_jadi
                'proses_jahit' => ['printing'],
                'printing' => ['diterima_toko'],
                'diterima_toko' => ['selesai'],
            ];
        } else {
            // Workflow untuk SO tanpa PO (lebih singkat)
            $transitions = [
                'draft' => ['pending'],
                'pending' => ['selesai'], // Langsung selesai setelah approved
                'selesai' => [], // Final status
            ];
        }
        
        return in_array($newStatus, $transitions[$currentStatus] ?? []);
    }

    public function isEditable(): bool
    {
        return !in_array($this->status, ['diterima_toko', 'selesai']);
    }
}