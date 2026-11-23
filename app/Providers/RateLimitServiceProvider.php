<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Definisi named rate limiter aplikasi.
 *
 * Dipakai lewat middleware `throttle:<nama>`:
 *   - `throttle:pos`   → seluruh route web (lihat bootstrap/app.php)
 *   - `throttle:login` → endpoint login (lihat routes/auth.php)
 *
 * Nilai batas berada di config/rate_limit.php (dapat dioverride via .env).
 */
class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('pos', function (Request $request) {
            // Per user bila login, per IP bila tamu → kuota tidak saling makan.
            $key = $request->user()?->getAuthIdentifier() ?: $request->ip();

            return Limit::perMinute((int) config('rate_limit.pos_per_minute'))
                ->by($key);
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute((int) config('rate_limit.login_per_minute'))
                ->by($request->ip());
        });
    }
}
