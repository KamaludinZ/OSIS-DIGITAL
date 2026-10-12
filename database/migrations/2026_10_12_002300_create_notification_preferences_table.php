<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Preferensi pemberitahuan akun (staf & kandidat) per jenis.
     * Tidak ada baris = jenis itu aktif (bawaan menerima).
     */
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('jenis', 30); // pengumuman | pemilihan | pertanyaan
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
