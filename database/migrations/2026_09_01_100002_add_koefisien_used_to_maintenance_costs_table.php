<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak koefisien yang dipakai saat nota diinput — memastikan
     * nota lama tetap akurat meski admin mengubah koefisien default.
     */
    public function up(): void
    {
        Schema::table('maintenance_costs', function (Blueprint $table) {
            $table->decimal('koefisien_used', 5, 4)->nullable()->after('taxed_amount');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_costs', function (Blueprint $table) {
            $table->dropColumn('koefisien_used');
        });
    }
};
