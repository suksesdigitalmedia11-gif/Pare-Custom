<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Penambahan index untuk meningkatkan performa dashboard dan kueri berat.
     */
    public function up(): void
    {
        // 1. Sales Orders - Index untuk pencarian tanggal dan status
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->index('order_date');
            $table->index('status');
            $table->index('payment_status');
            $table->index('created_at');
        });

        // 2. Sales Order Items - Index untuk pencarian nama produk dan HPP
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->index('product_name');
            $table->index('product_type');
            if (Schema::hasColumn('sales_order_items', 'cost_price')) {
                $table->index('cost_price');
            }
        });

        // 3. Payments - Index untuk audit pembayaran dan tanggal lunas
        Schema::table('payments', function (Blueprint $table) {
            $table->index('method');
            $table->index('category');
            $table->index('paid_at');
            $table->index('created_at');
        });

        // 4. Expenses & Incomes - Index untuk history keuangan
        Schema::table('expenses', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->index('created_at');
        });

        // 5. Advertisement Performances - Index untuk report iklan
        Schema::table('advertisement_performances', function (Blueprint $table) {
            $table->index('date');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropIndex(['order_date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropIndex(['product_name']);
            $table->dropIndex(['product_type']);
            if (Schema::hasColumn('sales_order_items', 'cost_price')) {
                $table->dropIndex(['cost_price']);
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['method']);
            $table->dropIndex(['category']);
            $table->dropIndex(['paid_at']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('advertisement_performances', function (Blueprint $table) {
            $table->dropIndex(['date']);
            $table->dropIndex(['type']);
        });
    }
};
