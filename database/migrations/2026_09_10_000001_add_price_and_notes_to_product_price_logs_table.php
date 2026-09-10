<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_price_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('product_price_logs', 'old_price')) {
                $table->decimal('old_price', 15, 2)->nullable()->after('new_cost_price');
            }
            if (!Schema::hasColumn('product_price_logs', 'new_price')) {
                $table->decimal('new_price', 15, 2)->nullable()->after('old_price');
            }
            if (!Schema::hasColumn('product_price_logs', 'notes')) {
                $table->text('notes')->nullable()->after('source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_price_logs', function (Blueprint $table) {
            $table->dropColumn(['old_price', 'new_price', 'notes']);
        });
    }
};
