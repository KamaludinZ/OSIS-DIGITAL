<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kotak saran per periode. Pengirim anonim: nama & kelas kosong.
     * Tanggapan diisi pengurus yang menjabat; jejak penanggap & waktunya disimpan.
     */
    public function up(): void
    {
        Schema::create('suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_id')->constrained('osis_periods')->restrictOnDelete();
            $table->text('isi');
            $table->string('nama', 100)->nullable();
            $table->string('kelas', 20)->nullable();
            $table->text('tanggapan')->nullable();
            $table->foreignId('ditanggapi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditanggapi_pada')->nullable();
            $table->timestamps();

            $table->index(['periode_id', 'ditanggapi_pada']);
        });

        if (DB::getDriverName() === 'pgsql') {
            // Bernama berarti nama terisi; kelas hanya ada bila nama ada.
            DB::statement('ALTER TABLE suggestions ADD CONSTRAINT suggestions_kelas_check CHECK (kelas IS NULL OR nama IS NOT NULL)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('suggestions');
    }
};
