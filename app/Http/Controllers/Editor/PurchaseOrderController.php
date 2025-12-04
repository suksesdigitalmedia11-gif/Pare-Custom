<?php

namespace App\Http\Controllers\Editor;
use App\Http\Controllers\Owner\PurchaseOrderController as BaseController;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
class PurchaseOrderController extends BaseController
{
    public function updateWorkflowStatus(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        $validated = $request->validate(['new_status' => 'required|string']);
        if (!in_array($validated['new_status'], ['proses_jahit', 'printing', 'selesai']) && auth()->user()->role !== 'owner') {
            return back()->withErrors(['status' => 'Editor hanya bisa update ke proses jahit, printing, atau selesai.']);
        }
        return parent::updateWorkflowStatus($request, $purchase);
    }
}