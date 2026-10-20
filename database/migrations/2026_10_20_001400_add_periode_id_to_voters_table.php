<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data pemilih per periode: tiap tahun kepengurusan mengimpor daftar pemilihnya sendiri
     * (kelas berganti). NISN/NIK unik di dalam satu periode, bukan selamanya.
     */
    public function up(): void
    {
        Schema::table('voters', function (Blueprint $table) {
            $table->foreignId('periode_id')->nullable()->after('id')->constrained('osis_periods')->restrictOnDelete();
        });

        Schema::table('voters', function (Blueprint $table) {
            $table->dropUnique(['jenis', 'nomor_induk']);
            $table->unique(['periode_id', 'jenis', 'nomor_induk']);
        });

        if ($aktif = DB::table('osis_periods')->where('status', 'aktif')->value('id')) {
            DB::table('voters')->whereNull('periode_id')->update(['periode_id' => $aktif]);
        }
    }

    public function down(): void
    {
        Schema::table('voters', function (Blueprint $table) {
            $table->dropUnique(['periode_id', 'jenis', 'nomor_induk']);
            $table->unique(['jenis', 'nomor_induk']);
        });

        Schema::table('voters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('periode_id');
        });
    }
};
