<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Anggota pengurus OSIM per periode kepengurusan. */
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kelas', 20)->nullable();
            // Kosong = pengurus inti (ketua, wakil, sekretaris, bendahara).
            $table->foreignId('divisi_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->string('jabatan', 100);
            $table->string('periode', 9)->index(); // contoh: 2025/2026
            // Opsional: tautkan ke data siswa yang diimpor panitia.
            $table->foreignId('voter_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['periode', 'divisi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
