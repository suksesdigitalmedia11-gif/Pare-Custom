<?php

namespace App\Services;

use App\Models\NumberSequence;
use Illuminate\Support\Facades\DB;

class NumberGenerator
{
    private const MAX_SEQUENCE = 9999;

    public function generateSalesOrderNumber(): string
    {
        $seq = $this->next('so');
        return 'SAL' . now()->format('ymd') . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function generatePurchaseOrderNumber(): string
    {
        $seq = $this->next('po');
        return 'PO' . now()->format('ymd') . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get next sequence for a key with locking to avoid duplicates.
     */
    private function next(string $key): int
    {
        return DB::transaction(function () use ($key) {
            $sequence = NumberSequence::lockForUpdate()->firstOrCreate(
                ['key' => $key],
                ['last_number' => 0]
            );

            $next = $sequence->last_number + 1;
            if ($next > self::MAX_SEQUENCE) {
                $next = 1;
            }

            $sequence->update(['last_number' => $next]);

            return $next;
        });
    }
}

