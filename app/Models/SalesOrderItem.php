<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SalesOrderItem extends Model
{
use HasFactory;

protected $fillable = [
        'sales_order_id',
        'product_id',
        'product_name',
        'sku',
        'sale_price',
        'cost_price', // ✅ Harga modal (snapshot saat transaksi)
        'qty',
        'discount',
        'product_type',
        'line_total',
        'requires_design',
        'design_status',
        'design_brief',
        'design_notes',
        'design_reference_path',
        'design_preview_path',
        'design_feedback',
        'design_confirmed_at',
    ];

    protected $casts = [
        'requires_design' => 'boolean',
        'design_confirmed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SalesOrderItem $item) {
            $item->product_type = self::inferProductType($item);

            if (self::shouldMarkAsDesignJob($item)) {
                $item->requires_design = true;
                $item->design_status = $item->design_status ?: 'pending';
            }
        });
    }

public function salesOrder(): BelongsTo
{
return $this->belongsTo(SalesOrder::class);
}

public function product(): BelongsTo
{
    return $this->belongsTo(Product::class)->withTrashed();
}

    public function scopeDesignQueue(Builder $query): Builder
    {
        return $query->where(function (Builder $designQuery) {
            $designQuery->where('requires_design', true)
                ->orWhereIn('product_type', ['dtf', 'jersey'])
                ->orWhereRaw('LOWER(product_name) LIKE ?', ['%dtf%'])
                ->orWhereRaw('LOWER(product_name) LIKE ?', ['%jersey%']);
        });
    }

    public static function designStatusOptions(): array
    {
        return [
            'pending' => 'Menunggu Brief',
            'in_progress' => 'Sedang Dikerjakan',
            'waiting_customer' => 'Menunggu Konfirmasi Customer',
            'approved' => 'Disetujui',
            'rejected' => 'Revisi / Ditolak',
        ];
    }

    protected static function inferProductType(SalesOrderItem $item): string
    {
        if (in_array($item->product_type, ['regular', 'dtf', 'jersey'], true)) {
            return $item->product_type;
        }

        if ($item->product_id) {
            $product = Product::with('category')->find($item->product_id);
            if ($product) {
                $productNameLower = Str::lower($product->name);
                $categoryNameLower = $product->category ? Str::lower($product->category->name) : '';

                // Check for DTF first
                if (Str::contains($productNameLower, 'dtf') || Str::contains($categoryNameLower, 'dtf')) {
                    return 'dtf';
                }

                // Check for Jersey
                if (Str::contains($productNameLower, 'jersey') || Str::contains($categoryNameLower, 'jersey')) {
                    return 'jersey';
                }
            }
        }

        $itemNameLower = Str::lower((string) $item->product_name);
        
        // Check for DTF first
        if (Str::contains($itemNameLower, 'dtf')) {
            return 'dtf';
        }

        // Check for Jersey
        if (Str::contains($itemNameLower, 'jersey')) {
            return 'jersey';
        }

        return 'regular';
    }

    protected static function shouldMarkAsDesignJob(SalesOrderItem $item): bool
    {
        if ($item->requires_design) {
            return true;
        }

        $itemNameLower = Str::lower((string) $item->product_name);
        
        return in_array($item->product_type, ['dtf', 'jersey'], true)
            || Str::contains($itemNameLower, 'dtf')
            || Str::contains($itemNameLower, 'jersey');
    }
}