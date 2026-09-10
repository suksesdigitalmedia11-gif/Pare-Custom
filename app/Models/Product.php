<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'barcode',
        'category_id',
        'name',
        'cost_price',
        'price',
        'stock_qty',
        'image_path',
        'is_active',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    public function movements()
    {
        return $this->hasMany(\App\Models\StockMovement::class);
    }
    // protected static function booted(): void
    // {
    //     // HISTORY MUST BE IMMUTABLE.
    //     // Modifying past sales/purchase orders when product master data changes is a violation of accounting principles.
    //     // If a product name changes today, the invoice from last year should still show the old name.
    //     // If cost price changes today, the COGS of sold items from last month should not change.
    // }
}
