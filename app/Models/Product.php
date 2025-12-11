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
        if ($product->isDirty('name')) {
            DB::table('sales_order_items')
                ->where('product_id', $product->id)
                ->update(['product_name' => $product->name]);

            DB::table('purchase_order_items')
                ->where('product_id', $product->id)
                ->update(['product_name' => $product->name]);
        }

        // Sinkronisasi harga modal ke histori pembelian saat cost_price berubah
        if ($product->isDirty('cost_price')) {
            $purchaseOrderIds = DB::table('purchase_order_items')
                ->where('product_id', $product->id)
                ->pluck('purchase_order_id')
                ->unique();

            DB::table('purchase_order_items')
                ->where('product_id', $product->id)
                ->update([
                    'cost_price' => $product->cost_price,
                    'line_total' => DB::raw('(qty * ' . (float) $product->cost_price . ') - discount'),
                ]);

            foreach ($purchaseOrderIds as $poId) {
                $items = DB::table('purchase_order_items')
                    ->where('purchase_order_id', $poId)
                    ->get(['cost_price', 'qty', 'discount']);

                $subtotal = 0;
                $discountTotal = 0;
                foreach ($items as $item) {
                    $subtotal += ((float)$item->cost_price * (int)$item->qty);
                    $discountTotal += (float)$item->discount;
                }
                $grandTotal = $subtotal - $discountTotal;

                DB::table('purchase_orders')
                    ->where('id', $poId)
                    ->update([
                        'subtotal' => $subtotal,
                        'discount_total' => $discountTotal,
                        'grand_total' => $grandTotal,
                    ]);
            }
        }
    });
}
}
