<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            // 1 keluhan per peminjaman (diisi saat pengembalian)
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->text('message');
            // Ditandai pengurus setelah ditindaklanjuti
            $table->boolean('resolved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
