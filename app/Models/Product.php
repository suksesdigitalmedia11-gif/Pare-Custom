<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory;

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
protected static function booted(): void
{
    // Sinkronisasi nama produk ke transaksi lama (sales/purchases) saat produk diubah
    static::updated(function (Product $product): void {
        if (!$product->isDirty('name')) {
            return;
        }

        DB::table('sales_order_items')
            ->where('product_id', $product->id)
            ->update(['product_name' => $product->name]);

        DB::table('purchase_order_items')
            ->where('product_id', $product->id)
            ->update(['product_name' => $product->name]);
    });
}
}
