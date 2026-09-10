<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_opname_id',
        'product_id',
        'product_name',
        'sku',
        'system_qty',
        'actual_qty',
        'difference',
    ];

    public function opname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed()->withDefault([
            'name' => 'Produk Dihapus',
            'stock_qty' => 0
        ]);
    }
}
