<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function isValidTransition(string $newStatus): bool
    {
        $currentStatus = $this->status;
        $transitions = [
            'draft' => ['pending'],
            'pending' => ['request_kain', 'payment'],
            'request_kain' => ['payment', 'proses_jahit'],
            'payment' => ['proses_jahit', 'diterima_toko'],
            'proses_jahit' => ['printing'],
            'printing' => ['diterima_toko'],
            'diterima_toko' => ['selesai'],
        ];
        return in_array($newStatus, $transitions[$currentStatus] ?? []);
    }

    public function isEditable(): bool
    {
        return !in_array($this->status, ['diterima_toko', 'selesai']);
    }
}