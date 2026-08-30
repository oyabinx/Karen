<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->enum('post', ['servis', 'suku_cadang', 'ac', 'pelumas']);
            $table->decimal('amount', 14, 2)->default(0);
            // Tahun anggaran
            $table->unsignedSmallInteger('year');
            $table->timestamps();

            $table->unique(['vehicle_id', 'post', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_budgets');
    }
};
