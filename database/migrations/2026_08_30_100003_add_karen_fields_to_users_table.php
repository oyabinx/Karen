<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'pengurus', 'pegawai'])->default('pegawai')->after('name');
            // Nomor HP wajib diisi user saat edit profil (unique, null diperbolehkan sebelum diisi)
            $table->string('phone', 20)->nullable()->unique()->after('role');
            // Dasar hitung kuota bidang; wajib untuk pegawai & pengurus (opsional admin)
            $table->foreignId('seksi_id')->nullable()->constrained('seksi')->after('phone');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['seksi_id']);
            $table->dropColumn(['role', 'phone', 'seksi_id']);
        });
    }
};
