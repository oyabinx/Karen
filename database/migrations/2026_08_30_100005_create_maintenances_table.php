<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('note')->nullable();
            $table->enum('status', ['terjadwal', 'selesai'])->default('terjadwal');
            // Diisi pengurus saat perawatan selesai (input nota & anggaran)
            $table->string('workshop_name', 100)->nullable();
            $table->string('nota_number', 50)->nullable();
            $table->date('nota_date')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
