<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log impor data pemilih: siapa mengimpor berkas apa, kapan, dan hasilnya.
     * (Indeks unik nomor induk per periode ada di voters: periode_id + jenis + nomor_induk.)
     */
    public function up(): void
    {
        Schema::create('voter_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_id')->nullable()->constrained('osis_periods')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('jenis', 10); // siswa | gtk
            $table->string('nama_berkas', 255);
            $table->unsignedInteger('tambah')->default(0);
            $table->unsignedInteger('perbarui')->default(0);
            $table->unsignedInteger('sama')->default(0);
            $table->unsignedInteger('dilewati')->default(0);
            $table->json('galat')->nullable(); // daftar baris yang dilewati beserta alasannya
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voter_imports');
    }
};
