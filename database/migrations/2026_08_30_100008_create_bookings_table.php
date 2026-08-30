<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('vehicle_id')->constrained('vehicles');
            // Peminjaman berbasis hari penuh 00:00–24:00 (tidak ada satuan jam)
            $table->date('start_date');
            $table->date('end_date');
            $table->string('address');
            $table->string('purpose');
            $table->enum('status', ['dipinjam', 'menunggu_penggantian', 'dikembalikan', 'dibatalkan'])->default('dipinjam');
            $table->timestamp('returned_at')->nullable();
            $table->boolean('auto_returned')->default(false);
            // Terisi bila mobil diganti karena maintenance/event (jejak audit)
            $table->foreignId('original_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->timestamps();

            $table->index(['vehicle_id', 'start_date', 'end_date']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
