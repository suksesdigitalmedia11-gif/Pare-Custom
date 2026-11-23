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
        // dilewati agar skema tetap bisa dibangun penuh (dipakai test suite).
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // MySQL doesn't support ALTER ENUM directly, so we need to use MODIFY
        DB::statement("ALTER TABLE sales_order_items MODIFY COLUMN product_type ENUM('regular', 'dtf', 'jersey') DEFAULT 'regular'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // MODIFY COLUMN ... ENUM hanya ada di MySQL. Driver lain (sqlite/postgres)
        // dilewati agar skema tetap bisa dibangun penuh (dipakai test suite).
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Revert back to original enum values
        DB::statement("ALTER TABLE sales_order_items MODIFY COLUMN product_type ENUM('regular', 'dtf') DEFAULT 'regular'");
    }
};

