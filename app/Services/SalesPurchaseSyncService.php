<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLog;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesPurchaseSyncService
{
    /**
     * Sinkron status Purchase Order terkait berdasarkan status Sales Order.
     * Mendukung transisi multi-step jika diperlukan (misalnya draft → pending → request_kain).
     */
    public static function syncPurchaseFromSales(SalesOrder $salesOrder, ?int $userId = null): void
    {
        $purchaseOrder = PurchaseOrder::where('sales_order_id', $salesOrder->id)->first();
        if (!$purchaseOrder) {
            Log::debug('No PO found for SO', ['so_id' => $salesOrder->id, 'so_number' => $salesOrder->so_number]);
            return;
        }

        $userId = $userId ?? Auth::id();

        try {
            DB::transaction(function () use ($salesOrder, $purchaseOrder, $userId) {
                $targetStatus = match ($salesOrder->status) {
                    'request_kain' => PurchaseOrder::STATUS_REQUEST_KAIN,
                    'payment' => PurchaseOrder::STATUS_PAYMENT,
                    'proses_jahit' => PurchaseOrder::STATUS_PROSES_JAHIT,
                    'printing' => PurchaseOrder::STATUS_PRINTING,
                    'diterima_toko', 'selesai' => PurchaseOrder::STATUS_SELESAI,
                    default => null,
                };

                if (!$targetStatus) {
                    Log::debug('No target status for SO status', ['so_status' => $salesOrder->status]);
                    return;
                }

                if ($purchaseOrder->status === $targetStatus) {
                    Log::debug('PO already at target status', [
                        'po_id' => $purchaseOrder->id,
                        'status' => $targetStatus,
                    ]);
                    return;
                }

                $originalStatus = $purchaseOrder->status;
                $currentStatus = $purchaseOrder->status;
                $transitionPath = [];

                // Lakukan transisi bertahap jika diperlukan
                while ($currentStatus !== $targetStatus) {
                    $available = $purchaseOrder->getNextAvailableStatuses();
                    
                    // Jika target status langsung tersedia, gunakan itu
                    if (in_array($targetStatus, $available, true)) {
                        $nextStatus = $targetStatus;
                    } else {
                        // Cari status intermediate yang membawa kita lebih dekat ke target
                        $nextStatus = self::findNextStepTowardsTarget($currentStatus, $targetStatus, $available, $purchaseOrder);
                        
                        if (!$nextStatus) {
                            Log::warning('Cannot find path to target status', [
                                'sales_order_id' => $salesOrder->id,
                                'po_id' => $purchaseOrder->id,
                                'so_status' => $salesOrder->status,
                                'current_po_status' => $currentStatus,
                                'target_po_status' => $targetStatus,
                                'available' => $available,
                            ]);
                            return;
                        }
                    }

                    // Update status
                    if (!$purchaseOrder->updateStatus($nextStatus, $userId)) {
                        Log::warning('PO updateStatus failed during sync', [
                            'po_id' => $purchaseOrder->id,
                            'from' => $currentStatus,
                            'to' => $nextStatus,
                        ]);
                        return;
                    }

                    $transitionPath[] = $nextStatus;
                    $currentStatus = $nextStatus;
                    
                    // Refresh model untuk mendapatkan status terbaru
                    $purchaseOrder->refresh();
                    
                    // Safety check: maksimal 5 langkah untuk menghindari infinite loop
                    if (count($transitionPath) >= 5) {
                        Log::warning('Too many transitions, possible loop', [
                            'po_id' => $purchaseOrder->id,
                            'path' => $transitionPath,
                        ]);
                        break;
                    }
                }

                // Log transisi
                $finalStatus = $purchaseOrder->status;
                $pathDescription = $originalStatus === $finalStatus 
                    ? $originalStatus 
                    : $originalStatus . ' → ' . implode(' → ', $transitionPath);

                PurchaseOrderLog::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'user_id' => $userId,
                    'action' => 'status_synced_from_sales',
                    'description' => "Status disinkron dari Sales Order {$salesOrder->so_number}: {$pathDescription}",
                ]);

                $salesOrder->logs()->create([
                    'user_id' => $userId,
                    'action' => 'po_status_synced',
                    'description' => "Purchase Order {$purchaseOrder->po_number} ikut: {$pathDescription}",
                ]);

                Log::info('PO status synced successfully', [
                    'so_id' => $salesOrder->id,
                    'po_id' => $purchaseOrder->id,
                    'from' => $originalStatus,
                    'to' => $finalStatus,
                    'path' => $transitionPath,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Gagal sinkron status PO dari SO', [
                'so_id' => $salesOrder->id,
                'po_id' => $purchaseOrder->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Helper method untuk menemukan langkah berikutnya menuju target status.
     * Memilih status intermediate yang paling dekat dengan target.
     */
    private static function findNextStepTowardsTarget(
        string $currentStatus,
        string $targetStatus,
        array $availableStatuses,
        PurchaseOrder $purchaseOrder
    ): ?string {
        // Mapping urutan status untuk menentukan arah
        $statusOrder = [
            PurchaseOrder::STATUS_DRAFT => 0,
            PurchaseOrder::STATUS_PENDING => 1,
            PurchaseOrder::STATUS_REQUEST_KAIN => 2,
            PurchaseOrder::STATUS_PAYMENT => 3,
            PurchaseOrder::STATUS_PROSES_JAHIT => 4,
            PurchaseOrder::STATUS_PRINTING => 5,
            PurchaseOrder::STATUS_SELESAI => 6,
        ];

        $currentOrder = $statusOrder[$currentStatus] ?? -1;
        $targetOrder = $statusOrder[$targetStatus] ?? -1;

        if ($currentOrder < 0 || $targetOrder < 0) {
            Log::warning('Invalid status in findNextStepTowardsTarget', [
                'current' => $currentStatus,
                'target' => $targetStatus,
            ]);
            return null;
        }

        // Jika target lebih tinggi dari current, pilih status yang paling dekat dengan target
        if ($targetOrder > $currentOrder) {
            $bestNext = null;
            $closestOrder = PHP_INT_MAX;
            
            foreach ($availableStatuses as $status) {
                // Skip canceled status
                if ($status === PurchaseOrder::STATUS_CANCELLED) {
                    continue;
                }
                
                $statusOrderValue = $statusOrder[$status] ?? -1;
                
                // Pilih status yang:
                // 1. Lebih tinggi dari current
                // 2. Tidak melebihi target
                // 3. Paling dekat dengan target
                if ($statusOrderValue > $currentOrder && $statusOrderValue <= $targetOrder) {
                    $distanceToTarget = $targetOrder - $statusOrderValue;
                    if ($distanceToTarget < $closestOrder) {
                        $bestNext = $status;
                        $closestOrder = $distanceToTarget;
                    }
                }
            }
            
            return $bestNext;
        }

        // Jika target lebih rendah (tidak seharusnya terjadi dalam workflow normal)
        // Atau jika tidak ada yang cocok, return null
        Log::warning('Cannot find next step towards target', [
            'current' => $currentStatus,
            'target' => $targetStatus,
            'available' => $availableStatuses,
        ]);
        return null;
    }
}


