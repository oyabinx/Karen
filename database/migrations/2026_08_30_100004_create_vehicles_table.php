<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('plate_number', 15)->unique();
            // Tahun pembuatan — menentukan besaran anggaran 4 pos maintenance
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('capacity');
            $table->enum('status', ['bisa_dipinjam', 'tidak_bisa_dipinjam'])->default('bisa_dipinjam');
            $table->enum('condition', ['baik', 'perlu_diperiksa'])->default('baik');
            $table->string('photo_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
