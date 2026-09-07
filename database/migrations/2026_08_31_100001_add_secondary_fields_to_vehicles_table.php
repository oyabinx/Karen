<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data sekunder kendaraan (UAT 03-A11): opsional saat menambah,
     * tampil di Detail Kendaraan; tanggal pajak memicu notifikasi
     * pengurus ≤3 minggu sebelum jatuh tempo.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('nomor_rangka', 50)->nullable()->after('photo_path');
            $table->string('nomor_mesin', 50)->nullable()->after('nomor_rangka');
            $table->date('pajak_tahunan')->nullable()->after('nomor_mesin');
            $table->date('pajak_lima_tahunan')->nullable()->after('pajak_tahunan');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['nomor_rangka', 'nomor_mesin', 'pajak_tahunan', 'pajak_lima_tahunan']);
        });
    }
};
