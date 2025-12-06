<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_order_items', 'requires_design')) {
                $table->boolean('requires_design')
                    ->default(false)
                    ->after('product_type');
            }

            if (!Schema::hasColumn('sales_order_items', 'design_status')) {
                $table->enum('design_status', ['pending', 'in_progress', 'waiting_customer', 'approved', 'rejected'])
                    ->default('pending')
                    ->after('requires_design');
            }

            if (!Schema::hasColumn('sales_order_items', 'design_brief')) {
                $table->text('design_brief')->nullable()->after('design_status');
            }

            if (!Schema::hasColumn('sales_order_items', 'design_notes')) {
                $table->text('design_notes')->nullable()->after('design_brief');
            }

            if (!Schema::hasColumn('sales_order_items', 'design_reference_path')) {
                $table->string('design_reference_path')->nullable()->after('design_notes');
            }

            if (!Schema::hasColumn('sales_order_items', 'design_preview_path')) {
                $table->string('design_preview_path')->nullable()->after('design_reference_path');
            }

            if (!Schema::hasColumn('sales_order_items', 'design_feedback')) {
                $table->text('design_feedback')->nullable()->after('design_preview_path');
            }

            if (!Schema::hasColumn('sales_order_items', 'design_confirmed_at')) {
                $table->timestamp('design_confirmed_at')->nullable()->after('design_feedback');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('sales_order_items', 'design_confirmed_at')) {
                $table->dropColumn('design_confirmed_at');
            }

            if (Schema::hasColumn('sales_order_items', 'design_feedback')) {
                $table->dropColumn('design_feedback');
            }

            if (Schema::hasColumn('sales_order_items', 'design_preview_path')) {
                $table->dropColumn('design_preview_path');
            }

            if (Schema::hasColumn('sales_order_items', 'design_reference_path')) {
                $table->dropColumn('design_reference_path');
            }

            if (Schema::hasColumn('sales_order_items', 'design_notes')) {
                $table->dropColumn('design_notes');
            }

            if (Schema::hasColumn('sales_order_items', 'design_brief')) {
                $table->dropColumn('design_brief');
            }

            if (Schema::hasColumn('sales_order_items', 'design_status')) {
                $table->dropColumn('design_status');
            }

            if (Schema::hasColumn('sales_order_items', 'requires_design')) {
                $table->dropColumn('requires_design');
            }
        });
    }
};

