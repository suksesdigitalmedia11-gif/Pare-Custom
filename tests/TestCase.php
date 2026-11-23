<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Paksa koneksi SQLite in-memory SEBELUM trait apa pun berjalan.
     *
     * refreshApplication() dieksekusi paling awal di setUpTheTestEnvironment(),
     * jadi ini berjalan sebelum RefreshDatabase (yang menjalankan migrate:fresh).
     *
     * Penting: bila `php artisan config:cache` sedang aktif, seluruh nilai
     * di phpunit.xml DIABAIKAN — koneksi akan mengarah ke MySQL produksi dan
     * RefreshDatabase akan menghapus seluruh tabelnya. Karena itu sqlite
     * dipaksa di sini, bukan hanya lewat phpunit.xml.
     */
    protected function refreshApplication()
    {
        parent::refreshApplication();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);

        // JANGAN purge koneksi sqlite: koneksi :memory: yang sama harus
        // dipertahankan antar-test, karena RefreshDatabase hanya menjalankan
        // migrasi sekali (RefreshDatabaseState::$migrated). Purge akan
        // menghapus seluruh tabel yang sudah dibuat.
    }

    /**
     * Lapis pengaman kedua: gagal keras jika (entah bagaimana) koneksi
     * bukan sqlite saat trait RefreshDatabase akan dijalankan.
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits()
    {
        if (config('database.default') !== 'sqlite') {
            $this->fail(
                'Test dibatalkan: koneksi database "'.config('database.default').'" bukan sqlite. '
                .'RefreshDatabase akan menghapus seluruh tabel pada koneksi ini.'
            );
        }

        return parent::setUpTraits();
    }
}
