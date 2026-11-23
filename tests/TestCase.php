<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pengaman: test suite memakai RefreshDatabase yang MENGHAPUS seluruh tabel.
     *
     * Jika config sedang di-cache (`php artisan config:cache`), nilai di
     * phpunit.xml diabaikan dan koneksi bisa mengarah ke MySQL produksi.
     * Lebih baik test gagal keras daripada database produksi terhapus.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite') {
            $this->fail(
                'Test dibatalkan: koneksi database "'.config('database.default').'" bukan sqlite. '
                .'Jalankan `php artisan config:clear` lebih dulu agar phpunit.xml berlaku. '
                .'RefreshDatabase akan menghapus seluruh tabel pada koneksi ini.'
            );
        }
    }
}
