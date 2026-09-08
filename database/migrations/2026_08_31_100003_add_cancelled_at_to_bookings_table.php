<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Waktu pembatalan peminjaman (usulan user pasca-UAT 03):
     * - Pegawai membatalkan SENDIRI booking yang BELUM dimulai
     *   (tombol "Batalkan Peminjaman" menggantikan "Selesai" bila
     *   hari ini < tanggal mulai);
     * - diisi juga oleh pembatalan pengurus (tanpa pengganti) dan
     *   scheduler (menunggu_penggantian lewat tempo).
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('auto_returned');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('cancelled_at');
        });
    }
};
