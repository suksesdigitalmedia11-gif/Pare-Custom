<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Shift;


class CheckActiveShift
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
    
        // OWNER, KEPALA TOKO, FINANCE: boleh akses SEMUA (GET/POST/PUT/DELETE) tanpa shift aktif
        if ($user->hasRole('owner') || $user->hasRole('kepala_toko') || $user->hasRole('finance')) {
            return $next($request);
        }
    
        // ROLE LAIN: hanya boleh akses READ (GET) tanpa shift aktif
        if ($request->isMethod('get')) {
            return $next($request);
        }
    
        // Untuk ACTION (POST/PUT/DELETE): wajib shift aktif
        $activeShift = Shift::where('user_id', $user->id)->whereNull('end_time')->first();
        if (!$activeShift) {
            $route = match($user->usertype) {
                'admin' => 'admin.shift.dashboard',
                'finance' => 'finance.shift.dashboard', 
                default => 'dashboard'
            };
            return redirect()->route($route)->with('error', 'Silakan mulai shift terlebih dahulu untuk melakukan aksi ini.');
        }
    
        return $next($request);
    }
}