<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\NumberGenerator;

class SalesOrderService
{
    // ✅ STANDARD CREATE LOGIC UNTUK SEMUA ROLE
    public function createOrder(array $data, $user)
    {
        return DB::transaction(function () use ($data, $user) {
            // 1. AUTO-CREATE CUSTOMER JIKA PERLU
            $customerId = $this->resolveCustomer($data);
            
            // 2. GENERATE SO NUMBER
            $soNumber = $this->generateSoNumber();
            
            // 3. CALCULATE TOTALS (ORDER-LEVEL DISCOUNT)
            $totals = $this->calculateTotals($data['items'], $data['discount_total'] ?? 0);
            
            // 4. CREATE SALES ORDER
            $salesOrder = SalesOrder::create([
                'so_number' => $soNumber,
                'order_type' => $data['order_type'],
                'order_date' => $data['order_date'],
                'deadline' => $data['deadline'] ?? null,
                'customer_id' => $customerId,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'grand_total' => $totals['grand_total'],
                'status' => $data['status'] ?? 'pending',
                'payment_method' => $data['payment_method'] ?? null,
                'payment_status' => $data['payment_status'] ?? null,
                'created_by' => $user->id,
                'add_to_purchase' => (bool) ($data['add_to_purchase'] ?? false),
            ]);

            // 5. CREATE ORDER ITEMS
            $this->createOrderItems($salesOrder, $data['items']);

            // 6. CREATE PAYMENT JIKA ADA
            if (($data['status'] ?? '') !== 'draft' && ($data['payment_amount'] ?? 0) > 0) {
                $this->createPayment($salesOrder, $data, $user);
            }

            // 7. AUTO-CREATE PURCHASE ORDER JIKA DICEKLIS
            if (($data['status'] ?? '') !== 'draft' && ($data['add_to_purchase'] ?? false)) {
                $this->createPurchaseOrderFromSales($salesOrder, $data);
            }

            return $salesOrder;
        });
    }

    // ✅ STANDARD CUSTOMER RESOLUTION UNTUK SEMUA ROLE
    private function resolveCustomer(array $data)
    {
        $customerId = $data['customer_id'] ?? null;
        
        if (empty($customerId) && !empty($data['customer_name'])) {
            $existingCustomer = Customer::where('name', $data['customer_name'])->first();
            
            if ($existingCustomer) {
                $customerId = $existingCustomer->id;
            } else {
                $customer = Customer::create([
                    'name' => $data['customer_name'],
                    'phone' => $data['customer_phone'] ?? null,
                    'email' => null,
                    'address' => null,
                    'notes' => 'Auto-created from sales order',
                    'is_active' => true,
                ]);
                $customerId = $customer->id;
            }
        }
        
        return $customerId;
    }

    // ✅ STANDARD TOTALS CALCULATION (ORDER-LEVEL DISCOUNT)
    private function calculateTotals(array $items, $discountTotal)
    {
        $subtotal = collect($items)->reduce(function ($carry, $item) {
            return $carry + ((float)($item['sale_price'] ?? 0) * (int)($item['qty'] ?? 0));
        }, 0);

        $discountTotal = (float)($discountTotal ?? 0);
        $grandTotal = max(0, $subtotal - $discountTotal);

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'grand_total' => $grandTotal,
        ];
    }

    // ✅ STANDARD ORDER ITEMS CREATION
    private function createOrderItems(SalesOrder $salesOrder, array $items)
    {
        foreach ($items as $item) {
            $lineTotal = (float)($item['sale_price'] ?? 0) * (int)($item['qty'] ?? 0);
            
            // ✅ Ambil cost_price dari product saat ini (snapshot untuk locked HPP)
            $costPrice = 0;
            if (!empty($item['product_id'])) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $costPrice = $product->cost_price ?? 0;
                }
            }
            
            SalesOrderItem::create([
                'sales_order_id' => $salesOrder->id,
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product_name'],
                'sku' => $item['sku'] ?? null,
                'sale_price' => $item['sale_price'],
                'cost_price' => $costPrice, // ✅ Snapshot harga modal (locked)
                'qty' => $item['qty'],
                'discount' => 0, // ✅ DISCOUNT SEKARANG ORDER-LEVEL
                'line_total' => $lineTotal,
            ]);
        }
    }

    // ✅ STANDARD PAYMENT CREATION
    private function createPayment(SalesOrder $salesOrder, array $data, $user)
    {
        $paymentAmount = (float)($data['payment_amount'] ?? 0);
        $cashAmount = (float)($data['cash_amount'] ?? 0);
        $transferAmount = (float)($data['transfer_amount'] ?? 0);
        
        // Auto-calculate jika split payment
        if ($data['payment_method'] === 'split' && $paymentAmount === 0) {
            $paymentAmount = $cashAmount + $transferAmount;
        }

        $paymentCategory = ($paymentAmount >= $salesOrder->grand_total) ? 'pelunasan' : 'dp';

        Payment::create([
            'sales_order_id' => $salesOrder->id,
            'method' => $data['payment_method'],
            'status' => $data['payment_status'],
            'category' => $paymentCategory,
            'amount' => $paymentAmount,
            'cash_amount' => $cashAmount,
            'transfer_amount' => $transferAmount,
            'paid_at' => $data['paid_at'] ?? now(),
            'proof_path' => $data['proof_path'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'created_by' => $user->id,
        ]);
    }

    // ✅ STANDARD PURCHASE ORDER CREATION
    private function createPurchaseOrderFromSales(SalesOrder $salesOrder, array $data)
    {
        $itemsToPurchase = $this->getItemsForPurchase($salesOrder);
        
        if (empty($itemsToPurchase)) return;

        $supplier = $this->resolveSupplier($data);
        
        $poNumber = 'PO' . now()->format('ymd') . str_pad(
            (string) (PurchaseOrder::whereDate('created_at', today())->count() + 1), 
            4, '0', STR_PAD_LEFT
        );

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => $poNumber,
            'order_date' => now(),
            'supplier_id' => $supplier->id,
            'purchase_type' => $salesOrder->order_type === 'jahit_sendiri' ? 'kain' : 'produk_jadi',
            'deadline' => $salesOrder->deadline,
            'subtotal' => 0,
            'discount_total' => 0,
            'grand_total' => 0,
            'status' => PurchaseOrder::STATUS_DRAFT,
            'is_paid' => false,
            'created_by' => Auth::id(),
            'sales_order_id' => $salesOrder->id,
        ]);

        $this->createPurchaseOrderItems($purchaseOrder, $itemsToPurchase);
    }

    // ✅ STANDARD SUPPLIER RESOLUTION
    private function resolveSupplier(array $data)
    {
        $supplierId = $data['supplier_id'] ?? null;
        $supplierName = $data['supplier_name'] ?? null;

        if ($supplierId) {
            return Supplier::findOrFail($supplierId);
        } elseif ($supplierName) {
            return Supplier::firstOrCreate(
                ['name' => $supplierName],
                ['is_active' => true]
            );
        } else {
            return Supplier::firstOrCreate(
                ['name' => 'Pre-order Customer'],
                ['is_active' => true]
            );
        }
    }

    // ✅ STANDARD SO NUMBER GENERATION
    private function generateSoNumber(): string
    {
        return app(NumberGenerator::class)->generateSalesOrderNumber();
    }

    private function getItemsForPurchase(SalesOrder $salesOrder)
    {
        $items = [];
        
        foreach ($salesOrder->items as $item) {
            if (!empty($item->product_id)) {
                $product = $item->product;
                if ($product && $product->stock_qty < $item->qty) {
                    $items[] = [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'sku' => $item->sku,
                        'cost_price' => 0,
                        'qty' => $item->qty,
                        'discount' => 0,
                    ];
                }
            } else {
                $items[] = [
                    'product_id' => null,
                    'product_name' => $item->product_name,
                    'sku' => $item->sku,
                    'cost_price' => 0,
                    'qty' => $item->qty,
                    'discount' => 0,
                ];
            }
        }
        
        return $items;
    }

    private function createPurchaseOrderItems(PurchaseOrder $purchaseOrder, array $items)
    {
        foreach ($items as $item) {
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
    }
}