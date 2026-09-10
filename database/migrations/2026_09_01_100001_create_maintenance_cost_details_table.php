<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rincian nota per pos (skema baru UAT 04):
     * "apa saja yang di servis" / "suku cadang apa yang diganti" —
     * teks + nominal per baris, opsional.
     */
    public function up(): void
    {
        Schema::create('maintenance_cost_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_cost_id')->constrained('maintenance_costs')->cascadeOnDelete();
            $table->string('description', 255);
            $table->decimal('amount', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_cost_details');
    }
};
