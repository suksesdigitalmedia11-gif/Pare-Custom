<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftAutoClose extends Model
{
    protected $fillable = [
        'shift_id',
        'auto_closed_date',
        'is_blocked',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'auto_closed_date' => 'date',
        'is_blocked' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
