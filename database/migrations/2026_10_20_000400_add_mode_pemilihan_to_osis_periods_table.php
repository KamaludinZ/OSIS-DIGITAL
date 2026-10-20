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

        $this->pulihkanIndeksSatuAktif();

        DB::table('osis_periods')->update(['mode_pemilihan' => true]);
    }

    /** SQLite membangun ulang tabel saat ALTER dan kehilangan klausa WHERE indeks parsial. */
    private function pulihkanIndeksSatuAktif(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS osis_periods_satu_aktif');
            DB::statement("CREATE UNIQUE INDEX osis_periods_satu_aktif ON osis_periods (status) WHERE status = 'aktif'");
        }
    }

    public function down(): void
    {
        Schema::table('osis_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mode_diubah_oleh');
            $table->dropColumn(['mode_pemilihan', 'mode_diubah_pada']);
        });

        $this->pulihkanIndeksSatuAktif();
    }
};
