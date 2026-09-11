<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * UAT 04 rev-2:
     * - Dokumen ditimpa di tempat (bukan versi baru) → regenerated_at
     * - bend26 menjadi dokumen BULANAN per pos → kolom period (YYYY-MM)
     * - kartu_inventaris diganti kartu_pemeliharaan
     */
    public function up(): void
    {
        Schema::table('generated_documents', function (Blueprint $table) {
            $table->timestamp('regenerated_at')->nullable()->after('version');
            $table->string('period', 7)->nullable()->after('post'); // YYYY-MM untuk bend26 bulanan
        });

        // Ganti nilai lama sebelum perubahan enum (MySQL tidak bisa dalam satu ALTER aman)
        DB::table('generated_documents')->where('type', 'kartu_inventaris')->update(['type' => 'kartu_pemeliharaan']);

        Schema::table('generated_documents', function (Blueprint $table) {
            $table->enum('type', ['bend26', 'draft_nota', 'kartu_pemeliharaan'])->change();
        });
    }

    public function down(): void
    {
        DB::table('generated_documents')->where('type', 'kartu_pemeliharaan')->update(['type' => 'kartu_inventaris']);

        Schema::table('generated_documents', function (Blueprint $table) {
            $table->enum('type', ['bend26', 'draft_nota', 'kartu_inventaris'])->change();
            $table->dropColumn(['regenerated_at', 'period']);
        });
    }
};
