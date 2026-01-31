<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    // Status untuk approval workflow
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_REQUEST_KAIN = 'request_kain';
    const STATUS_CANCELLED = 'canceled';
    
    // Status untuk production workflow - Kain
    const STATUS_PAYMENT = 'payment';
    const STATUS_PROSES_JAHIT = 'proses_jahit';
    const STATUS_PRINTING = 'printing';
    const STATUS_SELESAI = 'selesai';
    
    // Purchase Types
    const TYPE_KAIN = 'kain';
    const TYPE_PRODUK_JADI = 'produk_jadi';
    const STATUS_RETURNED = 'returned';
    const STATUS_PARTIALLY_RETURNED = 'partially_returned';

    protected $fillable = [
        // existing properties
        'po_number',
        'order_date',
        'supplier_id',
        'subtotal',
        'discount_total',
        'grand_total',
        'status',
        'is_paid',
        'created_by',
        'approved_by',
        'approved_at',
        'received_at',
        'received_by',
        'invoice_file',
        'payment_proof_file',
        
        // new properties
        'purchase_type', // 'kain' atau 'produk_jadi'
        'payment_at',
        'payment_by',
        'kain_diterima_at',
        'kain_diterima_by',
        'printing_at',
        'printing_by',
        'jahit_at',
        'jahit_by',
        'selesai_at',
        'selesai_by',
        'deadline', // tambah ini
        'sales_order_id',
        'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'deadline' => 'date', // TAMBAH INI
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'payment_at' => 'datetime',
        'kain_diterima_at' => 'datetime',
        'printing_at' => 'datetime',
        'jahit_at' => 'datetime',
        'selesai_at' => 'datetime',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class)->withDefault([
            'so_number' => 'N/A',
            'customer' => null
        ]);
    }

    // Helper method untuk mendapatkan nama customer dari sales order
    public function getCustomerNameAttribute(): string
    {
        if ($this->salesOrder && $this->salesOrder->customer) {
            return $this->salesOrder->customer->name;
        }
        
        // Fallback: cari dari log description
        $log = $this->logs()->where('description', 'like', '%Dari Penjualan%')->first();
        if ($log) {
            // Extract customer info dari log jika ada
            preg_match('/Dari Penjualan : ([A-Z0-9]+)/', $log->description, $matches);
            if (isset($matches[1])) {
                return "Customer (SO: {$matches[1]})";
            }
        }
        
        return '-';
    }

    // Helper method untuk mengecek apakah purchase berasal dari sales
    public function getIsFromSalesAttribute(): bool
    {
        return !is_null($this->sales_order_id);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault([
            'name' => 'Unknown User',
        ]);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withDefault([
            'name' => 'Unknown User',
        ]);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by')->withDefault([
            'name' => 'Unknown User',
        ]);
    }

    // New relationships for tracking users in production workflow
    public function paymentProcessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_by')->withDefault([
            'name' => 'Unknown User',
        ]);
    }

    public function kainReceiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kain_diterima_by')->withDefault([
            'name' => 'Unknown User',
        ]);
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printing_by')->withDefault([
            'name' => 'Unknown User',
        ]);
    }

    public function tailor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'jahit_by')->withDefault([
            'name' => 'Unknown User',
        ]);
    }

    public function finisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selesai_by')->withDefault([
            'name' => 'Unknown User',
        ]);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    // Helper methods untuk workflow
    public function isKainType(): bool
    {
        return $this->purchase_type === self::TYPE_KAIN;
    }

    public function isProdukJadiType(): bool
    {
        return $this->purchase_type === self::TYPE_PRODUK_JADI;
    }

    public function getNextAvailableStatuses(): array
    {
        $currentStatus = $this->status;
        
        if ($this->isKainType()) {
            return match($currentStatus) {
                self::STATUS_DRAFT => [self::STATUS_PENDING],
                self::STATUS_PENDING => [self::STATUS_REQUEST_KAIN, self::STATUS_CANCELLED],
                self::STATUS_REQUEST_KAIN => [self::STATUS_PAYMENT],
                self::STATUS_PAYMENT => [self::STATUS_PROSES_JAHIT],
                self::STATUS_PROSES_JAHIT => [self::STATUS_PRINTING],
                self::STATUS_PRINTING => [self::STATUS_SELESAI],
                default => []
            };
        }
        
        // Produk Jadi workflow
        return match($currentStatus) {
            self::STATUS_DRAFT => [self::STATUS_PENDING],
            self::STATUS_PENDING => [self::STATUS_REQUEST_KAIN, self::STATUS_CANCELLED],
            self::STATUS_REQUEST_KAIN => [self::STATUS_PAYMENT],
            self::STATUS_PAYMENT => [self::STATUS_PRINTING, self::STATUS_SELESAI],
            self::STATUS_PRINTING => [self::STATUS_SELESAI],
            default => []
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Pending Approval',
            self::STATUS_REQUEST_KAIN => 'Request Kain',
            self::STATUS_PAYMENT => 'Payment',
            self::STATUS_PROSES_JAHIT => 'Proses Jahit',
            self::STATUS_PRINTING => 'Printing',
            self::STATUS_SELESAI => 'Selesai',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_RETURNED => 'Returned (All)',
            self::STATUS_PARTIALLY_RETURNED => 'Selesai & Returned (Partial)',
            default => ucfirst($this->status)
        };
    }

    public function getTypeLabel(?string $type = null): string
    {
        $type = $type ?? $this->purchase_type;
    
        return match ($type) {
            self::TYPE_KAIN => 'Pembelian Kain',
            self::TYPE_PRODUK_JADI => 'Pembelian Produk Jadi',
            default => 'Unknown Type'
        };
    }

    public function cancel()
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return; // sudah dicancel
        }

        // Kembalikan stok jika sudah selesai (baik kain maupun produk jadi)
        if ($this->status === self::STATUS_SELESAI) {
            foreach ($this->items as $item) {
                $product = $item->product;
                if ($product) {
                    $product->stock_qty -= $item->qty;
                    $product->save();

                    // Catat stock movement
                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'purchase_cancel',
                        'quantity' => -$item->qty,
                        'reference_id' => $this->id,
                        'reference_type' => PurchaseOrder::class,
                        'notes' => 'Pembelian dibatalkan: ' . $this->po_number
                    ]);
                }
            }
        }

        $this->status = self::STATUS_CANCELLED;
        $this->save();
    }

    // Method untuk update status dengan tracking user
    public function updateStatus(string $newStatus, int $userId): bool
    {
        $availableStatuses = $this->getNextAvailableStatuses();
        
        if (!in_array($newStatus, $availableStatuses)) {
            return false;
        }

        $this->status = $newStatus;
        
        // Set timestamp dan user berdasarkan status
        match($newStatus) {
            self::STATUS_REQUEST_KAIN => [
                $this->approved_by = $userId,
                $this->approved_at = now()
            ],
            self::STATUS_PAYMENT => [
                $this->payment_by = $userId,
                $this->payment_at = now()
            ],
            self::STATUS_PROSES_JAHIT => [
                $this->kain_diterima_by = $userId,
                $this->kain_diterima_at = now()
            ],
            self::STATUS_PRINTING => [
                $this->printing_by = $userId,
                $this->printing_at = now()
            ],
            self::STATUS_SELESAI => [
                $this->selesai_by = $userId,
                $this->selesai_at = now()
            ],
            default => null
        };

        return $this->save();
    }
    public function purchaseReturns(): HasMany
{
    return $this->hasMany(PurchaseReturn::class);
}
public function getTotalReturnedQty(): int
{
    $total = 0;
    foreach ($this->purchaseReturns()->where('status', 'confirmed')->get() as $return) {
        foreach ($return->items as $item) {
            $total += $item->qty;
        }
    }
    return $total;
}
public function getTotalPurchasedQty(): int
{
    return $this->items->sum('qty');
}
public function updateReturnStatus(): void
{
    $totalReturned = $this->getTotalReturnedQty();
    $totalPurchased = $this->getTotalPurchasedQty();
    
    if ($totalReturned >= $totalPurchased) {
        $this->status = self::STATUS_RETURNED; // All returned
    } elseif ($totalReturned > 0) {
        $this->status = self::STATUS_PARTIALLY_RETURNED; // Some returned
    } else {
        $this->status = self::STATUS_SELESAI; // No returns
    }
    $this->save();
}
// Helper methods untuk return information
public function getReturnedQtyForProduct($productId): int
{
    $total = 0;
    foreach ($this->purchaseReturns()->where('status', 'confirmed')->get() as $return) {
        foreach ($return->items as $item) {
            if ($item->product_id == $productId) {
                $total += $item->qty;
            }
        }
    }
    return $total;
}

public function getTotalReturnedItems(): int
{
    $returnedItems = [];
    foreach ($this->purchaseReturns()->where('status', 'confirmed')->get() as $return) {
        foreach ($return->items as $item) {
            if ($item->qty > 0) {
                $returnedItems[$item->product_id] = true;
            }
        }
    }
    return count($returnedItems);
}
public function logs()
{
    return $this->hasMany(PurchaseOrderLog::class)->orderBy('created_at', 'desc');
}

}