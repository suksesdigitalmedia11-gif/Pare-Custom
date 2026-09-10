<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserOwnerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();

        // Eager-calculate historical records for each user
        foreach ($users as $user) {
            $user->has_history = $user->hasHistoricalRecords();
            $user->historical_counts = $user->getHistoricalCounts();
        }

        return view('owner.user.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('owner.user.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'usertype' => ['required', 'string', 'in:owner,finance,kepala_toko,admin,editor'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'usertype' => $request->usertype,
            'is_active' => $request->boolean('is_active', true),
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('owner.user.index')->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user->has_history = $user->hasHistoricalRecords();
        $user->historical_counts = $user->getHistoricalCounts();

        return view('owner.user.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return view('owner.user.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'usertype' => ['required', 'string', 'in:owner,finance,kepala_toko,admin,editor'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'usertype' => $request->usertype,
            'password' => $request->password ? Hash::make($request->password) : $user->password,
        ];

        // Owner cannot deactivate own account
        if ($user->id !== Auth::id() && $request->has('is_active')) {
            $newActiveStatus = $request->boolean('is_active');
            $updateData['is_active'] = $newActiveStatus;

            // If deactivated, revoke active sessions
            if (!$newActiveStatus) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        }

        $user->update($updateData);

        return redirect()->route('owner.user.index')->with('success', 'Data user berhasil diperbarui.');
    }

    /**
     * Toggle status active/inactive for an employee.
     */
    public function toggleStatus(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('owner.user.index')->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        if (!$user->is_active) {
            // Force logout: delete active session records
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $message = "Akun '{$user->name}' berhasil dinonaktifkan. Akses login karyawan ini telah dicabut seketika.";
        } else {
            $message = "Akun '{$user->name}' berhasil diaktifkan kembali. Akses login telah dipulihkan.";
        }

        return redirect()->route('owner.user.index')->with('success', $message);
    }

    /**
     * Remove the specified resource from storage safely.
     */
    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('owner.user.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Safety Guard: Check if user has historical data
        if ($user->hasHistoricalRecords()) {
            $counts = $user->getHistoricalCounts();
            $details = [];
            if ($counts['shifts'] > 0) $details[] = "{$counts['shifts']} shift kasir";
            if ($counts['sales_orders'] > 0) $details[] = "{$counts['sales_orders']} transaksi penjualan";
            if ($counts['payments'] > 0) $details[] = "{$counts['payments']} pembayaran";
            if ($counts['purchase_orders'] > 0) $details[] = "{$counts['purchase_orders']} PO";
            if ($counts['stock_opnames'] > 0) $details[] = "{$counts['stock_opnames']} stock opname";

            $detailStr = implode(', ', $details);

            return redirect()->route('owner.user.index')->with('error', "Pengguna '{$user->name}' TIDAK DAPAT dihapus permanen karena memiliki riwayat data historis ({$detailStr}). Menghapus pengguna ini akan merusak data toko. Silakan gunakan tombol 'Nonaktifkan Akun' untuk mencabut hak aksesnya dengan aman.");
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('owner.user.index')->with('success', "Pengguna '{$userName}' berhasil dihapus secara permanen (karena belum memiliki riwayat kerja).");
    }
}
