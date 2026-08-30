<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bidang_id')->constrained('bidang');
            $table->string('name', 100);
            $table->timestamps();

            $table->unique(['bidang_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seksi');
    }
};
