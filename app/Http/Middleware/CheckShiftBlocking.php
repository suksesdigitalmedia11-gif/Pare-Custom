<?php

namespace App\Http\Middleware;

use App\Models\ShiftAutoClose;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckShiftBlocking
{
    /**
     * Handle an incoming request.
     * Force logout admin/kepala_toko jika ada shift yang di-block
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya cek untuk user yang sudah login
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Hanya cek untuk admin dan kepala_toko
        if (!in_array($user->usertype, ['admin', 'kepala_toko'])) {
            return $next($request);
        }

        // Cek apakah ada shift yang di-block
        $hasBlockedShift = ShiftAutoClose::where('is_blocked', true)
            ->whereDate('auto_closed_date', '>=', now()->subDays(7))
            ->exists();

        if ($hasBlockedShift) {
            // Force logout
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Redirect ke login dengan pesan
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Akses diblokir: Shift tidak ditutup pada hari sebelumnya. Silakan minta approval ke akun Finance untuk mengaktifkan kembali akses login.'
                ]);
        }

        return $next($request);
    }
}
