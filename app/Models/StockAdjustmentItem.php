<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentItem extends Model
{
    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'product_name',
        'sku',
        'qty'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
