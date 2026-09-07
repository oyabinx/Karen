<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log aktivitas (keputusan user pasca-UAT 03): "siapa mengubah apa,
     * kapan" — penting karena pengelolaan kendaraan dipegang lebih dari
     * satu akun pengurus. Field sensitif (password, kredensial Google)
     * TIDAK pernah dicatat isinya.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // null = Sistem (scheduler)
            $table->string('action', 20); // created|updated|deleted|restored|system
            $table->string('model_type', 100)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('model_label', 150)->nullable(); // mis. "Kendaraan Avanza B 1234 XYZ"
            $table->text('description'); // kalimat manusiawi Indonesia
            $table->json('changes')->nullable(); // [kolom => [lama, baru]] — tanpa field sensitif
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['user_id']);
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
