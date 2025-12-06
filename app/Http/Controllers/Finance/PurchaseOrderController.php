<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Owner\PurchaseOrderController as BaseController;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends BaseController
{
    private function logAction(PurchaseOrder $purchaseOrder, string $action, string $description): void
    {
        PurchaseOrderLog::create([
            'purchase_order_id' => $purchaseOrder->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
    public function show(PurchaseOrder $purchase): View
    {
        // Authorization untuk finance
        if (!in_array(auth()->user()->usertype, ['finance', 'owner'])) {
            abort(403, 'Akses ditolak untuk finance');
        }

        // Load relationships yang diperlukan
        $purchase->load([
            'items', 
            'supplier', 
            'creator', 
            'approver', 
            'paymentProcessor',
            'kainReceiver',
            'printer',
            'tailor',
            'finisher',
            'logs.user', // ✅ TAMBAH INI UNTUK LOAD LOGS
            'salesOrder.customer'
        ]);

        return view('finance.purchases.show', compact('purchase'));
    }
    public function index(Request $request): View
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $group = $request->get('group');
        $type = $request->get('type'); // tambahan untuk filter tipe

        $purchases = PurchaseOrder::with(['supplier','creator','approver'])
            ->when($q, function ($query) use ($q) {
                $query->where('po_number', 'like', "%$q%")
                      ->orWhereHas('supplier', fn($qq) => $qq->where('name', 'like', "%$q%"));
            })
            ->when($type, fn($query) => $query->where('purchase_type', $type))
            ->when($group, function ($query) use ($group) {
                return match ($group) {
                    'todo' => $query->whereIn('status', ['draft','pending']),
                    'request_kain' => $query->where('status', 'request_kain'),
                    'in_progress' => $query->whereIn('status', ['payment', 'proses_jahit', 'printing']),
                    'completed' => $query->where('status', 'selesai'),
                    'cancelled' => $query->where('status', 'canceled'),
                    default => $query,
                };
            })
            ->when($status, fn($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15);

        return view('finance.purchases.index', compact('purchases','q','status','group','type'));
    }
    public function approve(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['finance', 'owner'])) {
            return back()->withErrors(['status' => 'Unauthorized']);
        }
        return parent::approve($request, $purchase);
    }

    public function payment(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        if (!in_array(auth()->user()->usertype, ['finance', 'owner'])) {
            return back()->withErrors(['status' => 'Unauthorized']);
        }
        return parent::payment($request, $purchase);
    }

    public function updateWorkflowStatus(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        $validated = $request->validate(['new_status' => 'required|string']);
        if ($validated['new_status'] !== 'selesai' && auth()->user()->usertype !== 'owner') {
            return back()->withErrors(['status' => 'Finance hanya bisa update ke selesai.']);
        }
        return parent::updateWorkflowStatus($request, $purchase);
    }
}