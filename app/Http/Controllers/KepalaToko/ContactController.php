<?php

namespace App\Http\Controllers\KepalaToko;

use App\Http\Controllers\Controller;
use App\Imports\CustomerImport;
use App\Imports\SupplierImport;
use App\Exports\CustomerTemplateExport;
use App\Exports\SupplierTemplateExport;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->when($request->get('q'), fn($q) => $q->where('name', 'like', '%'.$request->get('q').'%'))
            ->orderBy('name')
            ->paginate(10, pageName: 'customers_page');

        $suppliers = Supplier::query()
            ->when($request->get('q'), fn($q) => $q->where('name', 'like', '%'.$request->get('q').'%'))
            ->orderBy('name')
            ->paginate(10, pageName: 'suppliers_page');

        return view('kepala-toko.contacts.index', compact('customers', 'suppliers'));
    }

    public function storeCustomer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        Customer::create($validated);

        return redirect()->back()->with('success', 'Pelanggan berhasil ditambahkan');
    }

    public function storeSupplier(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        Supplier::create($validated);

        return redirect()->back()->with('success', 'Pemasok berhasil ditambahkan');
    }

    public function importCustomers(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ]);

        try {
            Excel::import(new CustomerImport, $request->file('file'));
            return redirect()->back()->with('success', 'Pelanggan berhasil diimpor');
        } catch (\Exception $e) {
            Log::error('Kesalahan impor pelanggan: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengimpor pelanggan: ' . $e->getMessage());
        }
    }

    public function importSuppliers(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ]);

        try {
            Excel::import(new SupplierImport, $request->file('file'));
            return redirect()->back()->with('success', 'Pemasok berhasil diimpor');
        } catch (\Exception $e) {
            Log::error('Kesalahan impor pemasok: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengimpor pemasok: ' . $e->getMessage());
        }
    }

    public function downloadCustomerTemplate()
    {
        return Excel::download(new CustomerTemplateExport, 'template_pelanggan.xlsx');
    }

    public function downloadSupplierTemplate()
    {
        return Excel::download(new SupplierTemplateExport, 'template_pemasok.xlsx');
    }
}