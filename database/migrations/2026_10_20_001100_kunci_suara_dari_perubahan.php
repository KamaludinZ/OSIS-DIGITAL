<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Suara yang sudah tercatat tidak bisa diubah, bahkan lewat kueri langsung:
     * trigger basis data menolak UPDATE pada votes (kehadiran) dan ballots (pilihan).
     * Penghapusan tetap mengikuti relasi (mis. pemilihan dihapus sebelum ada suara).
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                CREATE OR REPLACE FUNCTION osim_tolak_ubah_suara() RETURNS trigger AS $$
                BEGIN
                    RAISE EXCEPTION 'Suara yang sudah tercatat tidak bisa diubah.';
                END;
                $$ LANGUAGE plpgsql
            SQL);

            foreach (['votes', 'ballots'] as $tabel) {
                DB::statement("CREATE TRIGGER {$tabel}_tolak_ubah BEFORE UPDATE ON {$tabel} FOR EACH ROW EXECUTE FUNCTION osim_tolak_ubah_suara()");
            }

            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            foreach (['votes', 'ballots'] as $tabel) {
                DB::statement("CREATE TRIGGER {$tabel}_tolak_ubah BEFORE UPDATE ON {$tabel} BEGIN SELECT RAISE(ABORT, 'Suara yang sudah tercatat tidak bisa diubah.'); END");
            }
        }
    }

    public function down(): void
    {
        foreach (['votes', 'ballots'] as $tabel) {
            DB::statement(DB::getDriverName() === 'pgsql'
                ? "DROP TRIGGER IF EXISTS {$tabel}_tolak_ubah ON {$tabel}"
                : "DROP TRIGGER IF EXISTS {$tabel}_tolak_ubah");
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP FUNCTION IF EXISTS osim_tolak_ubah_suara()');
        }
    }
};
