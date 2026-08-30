<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            // NULL untuk kartu inventaris (diikat vehicle_id)
            $table->foreignId('maintenance_id')->nullable()->constrained('maintenances')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->cascadeOnDelete();
            $table->enum('type', ['bend26', 'draft_nota', 'kartu_inventaris']);
            // Terisi hanya untuk draft_nota (salah satu pos anggaran)
            $table->enum('post', ['servis', 'suku_cadang', 'ac', 'pelumas'])->nullable();
            $table->string('file_path');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['maintenance_id', 'type']);
            $table->index(['vehicle_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
