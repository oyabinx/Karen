<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->timestamps();

            $table->unique(['event_id', 'vehicle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_vehicles');
    }
};
