<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sakelar mode pemilihan per periode: aktif = menu publik Bilik Suara/Kandidat/Hasil,
     * nonaktif = Struktur Organisasi/Kotak Saran. Periode yang sudah ada tetap dalam mode
     * pemilihan agar situs tidak berubah sampai panitia mematikannya.
     */
    public function up(): void
    {
        Schema::table('osis_periods', function (Blueprint $table) {
            $table->boolean('mode_pemilihan')->default(false)->after('status');
            $table->timestamp('mode_diubah_pada')->nullable()->after('mode_pemilihan');
            $table->foreignId('mode_diubah_oleh')->nullable()->after('mode_diubah_pada')->constrained('users')->nullOnDelete();
        });

        DB::table('osis_periods')->update(['mode_pemilihan' => true]);
    }

    public function down(): void
    {
        Schema::table('osis_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mode_diubah_oleh');
            $table->dropColumn(['mode_pemilihan', 'mode_diubah_pada']);
        });
    }
};
