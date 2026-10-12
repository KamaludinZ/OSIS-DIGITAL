<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data pemilih: siswa (NISN) dan GTK (NIK) yang diunggah panitia.
     * Pemilih memilih memakai kode 6 digit, tanpa akun login.
     */
    public function up(): void
    {
        Schema::create('voters', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 10)->index(); // siswa | gtk
            $table->string('nomor_induk', 20); // NISN (siswa) / NIK (GTK)
            $table->string('nama');
            $table->string('kelas', 20)->nullable(); // siswa
            $table->string('jabatan', 100)->nullable(); // GTK
            $table->date('tanggal_lahir');
            $table->string('verifikasi', 15)->default('terverifikasi')->index(); // terverifikasi | menunggu | ditolak
            $table->string('kode_pilih', 6)->nullable()->unique();
            $table->timestamp('kode_dibuat_pada')->nullable();
            $table->timestamp('penanya_terverifikasi_pada')->nullable();
            $table->timestamps();

            $table->unique(['jenis', 'nomor_induk']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voters');
    }
};
