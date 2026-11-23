<?php

namespace App\Providers;

use App\Database\ResilientConnectionFactory;
use Illuminate\Support\ServiceProvider;

/**
 * Mendaftarkan connection factory yang tahan gangguan (resilient).
 *
 * Tujuan: ketika server MySQL menolak koneksi baru karena penuh
 * (mis. error 1040 "Too many connections" pada shared hosting), aplikasi
 * mencoba ulang beberapa kali sebelum menyerah — user tidak langsung
 * melihat halaman error.
 */
class DatabaseResilienceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend('db.factory', function ($factory, $app) {
            return new ResilientConnectionFactory($app);
        });

        // Jika manager DB sudah terlanjur dibuat dengan factory lama, buang
        // agar dibuat ulang memakai factory resilient.
        if ($this->app->resolved('db')) {
            $this->app->forgetInstance('db');
        }
    }
}
