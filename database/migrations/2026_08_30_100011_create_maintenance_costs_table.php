<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_id')->constrained('maintenances')->cascadeOnDelete();
            $table->enum('post', ['servis', 'suku_cadang', 'ac', 'pelumas']);
            // Nilai asli dari nota bengkel
            $table->decimal('raw_amount', 14, 2)->default(0);
            // raw_amount × 1,13 (koefisien pajak) — realisasi anggaran
            $table->decimal('taxed_amount', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['maintenance_id', 'post']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_costs');
    }
};
