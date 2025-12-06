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
        // MySQL doesn't support ALTER ENUM directly, so we need to use MODIFY
        DB::statement("ALTER TABLE sales_order_items MODIFY COLUMN product_type ENUM('regular', 'dtf', 'jersey') DEFAULT 'regular'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE sales_order_items MODIFY COLUMN product_type ENUM('regular', 'dtf') DEFAULT 'regular'");
    }
};

