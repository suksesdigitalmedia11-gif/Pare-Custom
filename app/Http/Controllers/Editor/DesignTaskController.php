<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\SalesOrderItem;
use App\Models\SalesOrderLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DesignTaskController extends Controller
{
    public function update(Request $request, SalesOrderItem $salesOrderItem): RedirectResponse
    {
        $statusOptions = array_keys(SalesOrderItem::designStatusOptions());

        $validated = $request->validate([
            'design_status' => ['required', Rule::in($statusOptions)],
            'design_brief' => ['nullable', 'string'],
            'design_notes' => ['nullable', 'string'],
            'design_feedback' => ['nullable', 'string'],
            'design_reference' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,ai,psd,svg', 'max:5120'],
            'design_preview' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $payload = [
            'design_status' => $validated['design_status'],
            'design_brief' => $validated['design_brief'] ?? null,
            'design_notes' => $validated['design_notes'] ?? null,
            'design_feedback' => $validated['design_feedback'] ?? null,
            'requires_design' => true,
        ];

        if ($request->hasFile('design_reference')) {
            if ($salesOrderItem->design_reference_path) {
                Storage::disk('public')->delete($salesOrderItem->design_reference_path);
            }
            $payload['design_reference_path'] = $request->file('design_reference')->store('design/references', 'public');
        }

        if ($request->hasFile('design_preview')) {
            if ($salesOrderItem->design_preview_path) {
                Storage::disk('public')->delete($salesOrderItem->design_preview_path);
            }
            $payload['design_preview_path'] = $request->file('design_preview')->store('design/previews', 'public');
        }

        if ($payload['design_status'] === 'approved') {
            $payload['design_confirmed_at'] = $salesOrderItem->design_confirmed_at ?? now();
        } else {
            $payload['design_confirmed_at'] = null;
        }

        $salesOrderItem->update($payload);

        SalesOrderLog::create([
            'sales_order_id' => $salesOrderItem->sales_order_id,
            'user_id' => Auth::id(),
            'action' => 'design_update',
            'description' => sprintf(
                'Status desain item %s berubah menjadi %s',
                $salesOrderItem->product_name,
                strtoupper($payload['design_status'])
            ),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Status desain berhasil diperbarui.');
    }
}

