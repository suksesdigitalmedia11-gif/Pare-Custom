<?php

namespace App\Traits;

use App\Models\SalesOrder;
use App\Models\SalesOrderLog;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Payment;
use App\Services\SalesPurchaseSyncService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HandlesSalesOrderWorkflow
{
    /**
     * Centralized permission check for workflow actions
     */
    protected function canPerformWorkflowAction(string $action): bool
    {
        $user = Auth::user();
        $userType = strtolower($user->usertype ?? $user->role ?? '');

        return match ($action) {
            'pending_to_request_kain' => in_array($userType, ['owner', 'kepala_toko', 'finance', 'admin', 'owner']),
            'request_kain_to_payment' => in_array($userType, ['finance', 'owner', 'kepala_toko', 'admin']),
            'payment_to_proses_jahit' => in_array($userType, ['admin', 'finance', 'kepala_toko', 'owner']),
            'proses_jahit_to_printing' => in_array($userType, ['admin', 'finance', 'kepala_toko', 'owner']),
            'printing_to_diterima_toko' => in_array($userType, ['admin', 'finance', 'kepala_toko', 'owner']),
            'diterima_toko_to_selesai' => in_array($userType, ['admin', 'finance', 'kepala_toko', 'owner']),
            'pending_to_selesai' => in_array($userType, ['admin', 'owner', 'finance', 'kepala_toko']),
            default => false,
        };
    }

    /**
     * Ensure Purchase Order exists for Sales Order (Recovery for legacy/stuck orders)
     */
    protected function ensurePurchaseOrderExists(SalesOrder $salesOrder): ?PurchaseOrder
    {
        $existingPO = PurchaseOrder::where('sales_order_id', $salesOrder->id)->first();
        if ($existingPO) {
            return $existingPO;
        }

        // Logic to create PO from SO if missing for production types
        if (!$salesOrder->add_to_purchase && $salesOrder->order_type !== 'jahit_sendiri') {
            return null;
        }

        return DB::transaction(function () use ($salesOrder) {
            $itemsToPurchase = [];
            foreach ($salesOrder->items as $item) {
                // Legacy data recovery: include all items for production
                $itemsToPurchase[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'sku' => $item->sku,
                    'cost_price' => $item->cost_price ?? 0,
                    'qty' => $item->qty,
                    'discount' => 0,
                ];
            }

            if (empty($itemsToPurchase)) {
                return null;
            }

            $supplier = Supplier::where('name', 'Pre-order Customer')->first() 
                        ?? Supplier::firstOrCreate(['name' => 'Pre-order Customer'], ['is_active' => true]);

            $poNumber = 'PO' . now()->format('ymd') . str_pad((string) (PurchaseOrder::whereDate('created_at', now()->toDateString())->count() + 1), 4, '0', STR_PAD_LEFT);

            $subtotalPo = collect($itemsToPurchase)->sum(fn($i) => $i['cost_price'] * $i['qty']);

            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'order_date' => $salesOrder->order_date ?? now(),
                'supplier_id' => $supplier->id,
                'purchase_type' => $salesOrder->order_type === 'jahit_sendiri' ? 'kain' : 'produk_jadi',
                'deadline' => $salesOrder->deadline,
                'subtotal' => $subtotalPo,
                'discount_total' => 0,
                'grand_total' => $subtotalPo,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'is_paid' => false,
                'created_by' => Auth::id(),
                'sales_order_id' => $salesOrder->id,
            ]);

            foreach ($itemsToPurchase as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'sku' => $item['sku'],
                    'cost_price' => $item['cost_price'],
                    'qty' => $item['qty'],
                    'discount' => $item['discount'],
                    'line_total' => ($item['cost_price'] * $item['qty']) - $item['discount'],
                ]);
            }

            $this->logAction($salesOrder, 'linked_to_purchase_recovery', "Linked to Purchase Order secara otomatis (recovery data lama): {$poNumber}");

            return $purchaseOrder;
        });
    }

    /**
     * Update stock when payment is processed
     */
    protected function updateStockOnPayment(SalesOrder $salesOrder): void
    {
        $hasPO = $salesOrder->hasRelatedPO();
        if (!$hasPO) {
            DB::transaction(function () use ($salesOrder) {
                foreach ($salesOrder->items as $item) {
                    if ($item->product_id) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $initialStock = $product->stock_qty;
                            $product->decrement('stock_qty', $item->qty);
                            
                            \App\Models\StockMovement::create([
                                'product_id' => $product->id,
                                'type' => 'OUTGOING',
                                'ref_code' => $salesOrder->so_number,
                                'initial_qty' => $initialStock,
                                'qty_in' => 0,
                                'qty_out' => $item->qty,
                                'final_qty' => $product->stock_qty,
                                'user_id' => Auth::id(),
                                'notes' => 'Pembayaran SO: ' . $salesOrder->so_number,
                                'moved_at' => \Carbon\Carbon::now(),
                            ]);
                        }
                    }
                }
            });
        }
    }

    /**
     * Shared moveToRequestKain logic
     */
    public function performMoveToRequestKain(SalesOrder $salesOrder)
    {
        if (!$this->canPerformWorkflowAction('pending_to_request_kain')) {
            return back()->withErrors(['error' => 'Anda tidak memiliki izin untuk melakukan aksi ini.']);
        }

        if ($salesOrder->status !== 'pending') {
            return back()->withErrors(['status' => 'Hanya status pending yang bisa dipindah ke request_kain.']);
        }

        if (!$salesOrder->hasRelatedPO()) {
            $this->ensurePurchaseOrderExists($salesOrder);
        }

        if (!$salesOrder->hasRelatedPO()) {
            return back()->withErrors(['error' => 'Gagal membuat/menemukan Purchase Order. Pastikan pesanan ini adalah Pre-Order/Jahit Sendiri.']);
        }

        if ($salesOrder->approved_by === null) {
            return back()->withErrors(['status' => 'Sales order harus di-approve terlebih dahulu.']);
        }

        if ($salesOrder->paid_total <= 0) {
            return back()->withErrors(['payment' => 'Harus ada pembayaran untuk mulai proses.']);
        }

        // Validasi pembayaran transfer/split
        if (in_array($salesOrder->payment_method, ['transfer', 'split'])) {
            $invalidPayments = $salesOrder->payments()
                ->whereNull('proof_path')
                ->where(function ($q) {
                    $q->whereNull('reference_number')
                        ->orWhere('reference_number', '')
                        ->orWhere('reference_number', ' ')
                        ->orWhere('reference_number', 'null')
                        ->orWhere('reference_number', 'NULL');
                })
                ->count();

            if ($invalidPayments > 0) {
                return back()->withErrors(['payment' => 'Semua pembayaran transfer/split harus memiliki bukti pembayaran ATAU no referensi yang valid.']);
            }
        }

        try {
            DB::transaction(function () use ($salesOrder) {
                $this->updateStockOnPayment($salesOrder);
                $salesOrder->update(['status' => 'request_kain']);
                $this->logAction($salesOrder, 'moved_to_request_kain', 'Status berubah ke request_kain');
            });

            $salesOrder->refresh();
            SalesPurchaseSyncService::syncPurchaseFromSales($salesOrder);
            return back()->with('success', 'Status berhasil diubah ke request_kain.');
        } catch (\Exception $e) {
            Log::error('Error moving to request_kain: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Shared moveToPayment logic
     */
    public function performMoveToPayment(SalesOrder $salesOrder)
    {
        if (!$this->canPerformWorkflowAction('request_kain_to_payment')) {
            return back()->withErrors(['error' => 'Anda tidak memiliki izin untuk melakukan aksi ini.']);
        }

        if ($salesOrder->status !== 'request_kain') {
            return back()->withErrors(['status' => 'Hanya status request_kain yang bisa dipindah ke payment.']);
        }

        try {
            DB::transaction(function () use ($salesOrder) {
                $salesOrder->update(['status' => 'payment']);
                $this->logAction($salesOrder, 'moved_to_payment', 'Status berubah ke payment');
            });

            $salesOrder->refresh();
            SalesPurchaseSyncService::syncPurchaseFromSales($salesOrder);
            return back()->with('success', 'Status berhasil diubah ke payment.');
        } catch (\Exception $e) {
            Log::error('Error moving to payment: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Common step logic for subsequent statuses
     */
    public function performMoveToGeneric(SalesOrder $salesOrder, string $newStatus, string $actionKey)
    {
        if (!$this->canPerformWorkflowAction($actionKey)) {
            return back()->withErrors(['error' => 'Anda tidak memiliki izin untuk melakukan aksi ini.']);
        }

        if (!$salesOrder->isValidTransition($newStatus)) {
            return back()->withErrors(['status' => "Transisi status ke {$newStatus} tidak valid."]);
        }

        try {
            DB::transaction(function () use ($salesOrder, $newStatus) {
                $salesOrder->update(['status' => $newStatus]);
                $this->logAction($salesOrder, "moved_to_{$newStatus}", "Status berubah ke {$newStatus}");
                
                if ($newStatus === 'selesai') {
                    $salesOrder->update(['completed_at' => now()]);
                }
            });

            $salesOrder->refresh();
            SalesPurchaseSyncService::syncPurchaseFromSales($salesOrder);
            return back()->with('success', "Status berhasil diubah ke {$newStatus}.");
        } catch (\Exception $e) {
            Log::error("Error moving to {$newStatus}: " . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }
}
