<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MODIFY COLUMN ... ENUM hanya ada di MySQL. Driver lain (sqlite/postgres)
        // tidak mendukungnya, jadi dilewati agar skema tetap bisa dibangun penuh
        // (dipakai oleh test suite yang berjalan di SQLite in-memory).
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN usertype ENUM('owner', 'finance', 'kepala_toko', 'admin', 'editor') NOT NULL DEFAULT 'editor'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Rollback ke enum lama (hanya jika tidak ada data dengan role baru)
        DB::statement("ALTER TABLE users MODIFY COLUMN usertype ENUM('owner') NOT NULL DEFAULT 'owner'");
    }
};