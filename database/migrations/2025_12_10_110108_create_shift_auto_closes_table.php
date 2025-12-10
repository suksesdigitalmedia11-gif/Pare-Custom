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
        Schema::create('shift_auto_closes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->date('auto_closed_date'); // Tanggal shift di-auto-close
            $table->boolean('is_blocked')->default(true); // Status blocking login
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // Finance yang approve
            $table->timestamp('approved_at')->nullable(); // Waktu approve
            $table->text('notes')->nullable(); // Catatan tambahan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_auto_closes');
    }
};
