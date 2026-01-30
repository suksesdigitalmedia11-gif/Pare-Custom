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
     * Log action for Sales Order
     */
    protected function logAction(SalesOrder $salesOrder, string $action, string $description): void
    {
        SalesOrderLog::create([
            'sales_order_id' => $salesOrder->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'created_at' => now(),
        ]);
    }

    /**
     * Ensure Purchase Order exists for Sales Order if add_to_purchase is true
     */
    protected function ensurePurchaseOrderExists(SalesOrder $salesOrder): ?PurchaseOrder
    {
        $existingPO = PurchaseOrder::where('sales_order_id', $salesOrder->id)->first();
        if ($existingPO) {
            return $existingPO;
        }

        if (!$salesOrder->add_to_purchase) {
            return null;
        }

        // Logic to create PO from SO if missing
        return DB::transaction(function () use ($salesOrder) {
            $itemsToPurchase = [];
            foreach ($salesOrder->items as $item) {
                if (!empty($item->product_id)) {
                    $product = Product::find($item->product_id);
                    // Create PO item if stock is low OR it's a pre-order type
                    if (!$product || $product->stock_qty < $item->qty || $salesOrder->order_type !== 'beli_jadi') {
                        $itemsToPurchase[] = [
                            'product_id' => $item->product_id,
                            'product_name' => $item->product_name,
                            'sku' => $item->sku,
                            'cost_price' => $item->cost_price ?? 0,
                            'qty' => $item->qty,
                            'discount' => 0,
                        ];
                    }
                } else {
                    $itemsToPurchase[] = [
                        'product_id' => null,
                        'product_name' => $item->product_name,
                        'sku' => $item->sku,
                        'cost_price' => 0,
                        'qty' => $item->qty,
                        'discount' => 0,
                    ];
                }
            }

            if (empty($itemsToPurchase)) {
                return null;
            }

            // Get default supplier or fallback
            $supplier = Supplier::where('name', 'Pre-order Customer')->first() 
                        ?? Supplier::firstOrCreate(['name' => 'Pre-order Customer'], ['is_active' => true]);

            $poNumber = 'PO' . now()->format('ymd') . str_pad((string) (PurchaseOrder::whereDate('created_at', now()->toDateString())->count() + 1), 4, '0', STR_PAD_LEFT);

            $subtotalPo = collect($itemsToPurchase)->sum(fn($i) => $i['cost_price'] * $i['qty']);
            $grandTotalPo = $subtotalPo;

            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'order_date' => now(),
                'supplier_id' => $supplier->id,
                'purchase_type' => $salesOrder->order_type === 'jahit_sendiri' ? 'kain' : 'produk_jadi',
                'deadline' => $salesOrder->deadline,
                'subtotal' => $subtotalPo,
                'discount_total' => 0,
                'grand_total' => $grandTotalPo,
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

            \App\Models\PurchaseOrderLog::create([
                'purchase_order_id' => $purchaseOrder->id,
                'user_id' => Auth::id(),
                'action' => 'created',
                'description' => "Purchase order otomatis dibuat (recovery) dari Sales Order: {$salesOrder->so_number}",
                'created_at' => now(),
            ]);

            $this->logAction($salesOrder, 'linked_to_purchase_recovery', "Linked to Purchase Order secara otomatis (recovery): {$poNumber}");

            return $purchaseOrder;
        });
    }

    /**
     * Update stock when payment is processed
     */
    protected function updateStockOnPayment(SalesOrder $salesOrder): void
    {
        // Only deduct stock for products that don't have a PO (available in shop)
        $hasPO = $salesOrder->hasRelatedPO();
        if (!$hasPO) {
            DB::transaction(function () use ($salesOrder) {
                foreach ($salesOrder->items as $item) {
                    if ($item->product_id) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $initialStock = $product->stock_qty;
                            $newStock = $initialStock - $item->qty;
                            
                            if ($newStock < 0) {
                                Log::warning('Negative stock for product ' . $product->id . ' on SO ' . $salesOrder->so_number . ': New stock ' . $newStock);
                            }
                            
                            $product->stock_qty = $newStock;
                            $product->save();

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
        // Validasi status
        if ($salesOrder->status !== 'pending') {
            return back()->withErrors(['status' => 'Hanya status pending yang bisa dipindah ke request_kain.']);
        }

        // Pastikan PO ada jika add_to_purchase true
        if ($salesOrder->add_to_purchase && !$salesOrder->hasRelatedPO()) {
            $this->ensurePurchaseOrderExists($salesOrder);
        }

        // Jika setelah usaha di atas tetap tidak ada PO, maka ini bukan workflow PO
        if (!$salesOrder->hasRelatedPO()) {
            return back()->withErrors(['error' => 'Gagal membuat/menemukan Purchase Order. Pastikan pesanan ini memang Pre-Order.']);
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
}
