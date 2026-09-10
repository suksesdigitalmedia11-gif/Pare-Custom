<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id','moved_at','type','ref_code',
        'initial_qty','qty_in','qty_out','final_qty',
        'user_id','notes',
    ];

    protected $casts = [
        'moved_at' => 'datetime',
    ];

    // Tipe yang dipakai sekarang
    public const INCOMING        = 'INCOMING';
    public const OUTGOING        = 'OUTGOING'; // Penjualan standar
    public const OPNAME          = 'OPNAME';
    public const POS_SALE        = 'POS_SALE';
    public const POS_CANCEL      = 'POS_CANCEL';

    public function product() { return $this->belongsTo(Product::class)->withTrashed(); }
    public function user()    { return $this->belongsTo(User::class); }

    /* ===== Helper untuk ledger ===== */

    // Ambil saldo (final_qty) terakhir pada/ sebelum waktu $at
    public static function lastBalance(int $productId, \DateTimeInterface $at): int
    {
        $row = static::where('product_id',$productId)
            ->where('moved_at','<=',$at)
            ->orderByDesc('moved_at')->orderByDesc('id')
            ->first();

        return $row?->final_qty ?? 0;
    }

    // Catat 1 baris pergerakan (auto hitung initial & final)
    public static function record(array $payload): self
    {
        $productId = (int)$payload['product_id'];
        $at        = $payload['moved_at'];

        $initial   = static::lastBalance($productId, $at);
        $in        = (int)($payload['qty_in']  ?? 0);
        $out       = (int)($payload['qty_out'] ?? 0);
        $final     = $initial + $in - $out;

        return static::create([
            'product_id'  => $productId,
            'moved_at'    => $at,
            'type'        => $payload['type'],
            'ref_code'    => $payload['ref_code'] ?? null,
            'initial_qty' => $initial,
            'qty_in'      => $in,
            'qty_out'     => $out,
            'final_qty'   => $final,
            'user_id'     => $payload['user_id'] ?? auth()->id(),
            'notes'       => $payload['notes'] ?? null,
        ]);
    }
}