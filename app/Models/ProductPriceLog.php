<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceLog extends Model
{
    protected $fillable = [
        'product_id',
        'old_cost_price',
        'new_cost_price',
        'old_price',
        'new_price',
        'changed_by',
        'changed_at',
        'source',
        'notes',
    ];

    public $timestamps = false;

    protected $casts = [
        'changed_at' => 'datetime',
        'old_cost_price' => 'decimal:2',
        'new_cost_price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
