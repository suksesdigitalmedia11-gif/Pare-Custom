<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE sales_orders
            MODIFY COLUMN status ENUM(
                'draft',
                'pending',
                'request_kain',
                'payment',
                'proses_jahit',
                'printing',
                'diterima_toko',
                'selesai',
                'di proses',
                'jadi'
            ) DEFAULT 'pending'
        ");

        DB::statement("UPDATE sales_orders SET status = 'payment' WHERE status = 'di proses'");
        DB::statement("UPDATE sales_orders SET status = 'printing' WHERE status = 'jadi'");

        DB::statement("
            ALTER TABLE purchase_orders
            MODIFY COLUMN status ENUM(
                'draft',
                'pending',
                'approved',
                'request_kain',
                'payment',
                'kain_diterima',
                'proses_jahit',
                'printing',
                'jahit',
                'selesai',
                'canceled',
                'returned',
                'partially_returned'
            ) DEFAULT 'draft'
        ");

        DB::statement("UPDATE purchase_orders SET status = 'request_kain' WHERE status = 'approved'");
        DB::statement("UPDATE purchase_orders SET status = 'proses_jahit' WHERE status = 'kain_diterima'");
        DB::statement("UPDATE purchase_orders SET status = 'printing' WHERE status = 'jahit'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE sales_orders
            MODIFY COLUMN status ENUM(
                'pending',
                'request_kain',
                'proses_jahit',
                'jadi',
                'diterima_toko',
                'di proses',
                'selesai'
            ) DEFAULT 'pending'
        ");

        DB::statement("UPDATE sales_orders SET status = 'di proses' WHERE status = 'payment'");
        DB::statement("UPDATE sales_orders SET status = 'jadi' WHERE status = 'printing'");

        DB::statement("
            ALTER TABLE purchase_orders
            MODIFY COLUMN status ENUM(
                'draft',
                'pending',
                'approved',
                'payment',
                'kain_diterima',
                'printing',
                'jahit',
                'selesai',
                'canceled',
                'returned',
                'partially_returned'
            ) DEFAULT 'draft'
        ");

        DB::statement("UPDATE purchase_orders SET status = 'approved' WHERE status = 'request_kain'");
        DB::statement("UPDATE purchase_orders SET status = 'kain_diterima' WHERE status = 'proses_jahit'");
        DB::statement("UPDATE purchase_orders SET status = 'jahit' WHERE status = 'printing' AND purchase_type = 'kain'");
    }
};
