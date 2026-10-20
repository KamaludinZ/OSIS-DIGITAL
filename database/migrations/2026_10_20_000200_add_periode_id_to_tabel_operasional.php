<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Data operasional yang dipisah per periode kepengurusan. */
    private const TABEL = ['elections', 'candidates', 'members', 'work_programs', 'events', 'social_posts'];

    /**
     * Tambah periode_id (periode tidak bisa dihapus selama masih punya data), lalu isi data lama:
     * anggota mengikuti kolom teks `periode`-nya, sisanya masuk periode tahun ajaran berjalan.
     */
    public function up(): void
    {
        foreach (self::TABEL as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->foreignId('periode_id')->nullable()->after('id')->constrained('osis_periods')->restrictOnDelete();
            });
        }

        $adaData = collect(self::TABEL)->contains(fn ($tabel) => DB::table($tabel)->exists());
        if (! $adaData) {
            return;
        }

        $berjalan = now()->month >= 7 ? now()->year : now()->year - 1;

        foreach (DB::table('members')->whereNotNull('periode')->distinct()->pluck('periode') as $nama) {
            DB::table('members')->where('periode', $nama)->update(['periode_id' => $this->periode($nama, $berjalan)]);
        }

        $idBerjalan = $this->periode($berjalan.'/'.($berjalan + 1), $berjalan);
        foreach (self::TABEL as $tabel) {
            DB::table($tabel)->whereNull('periode_id')->update(['periode_id' => $idBerjalan]);
        }
    }

    public function down(): void
    {
        foreach (self::TABEL as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dropConstrainedForeignId('periode_id');
            });
        }
    }

    /** id periode bernama "2025/2026"; dibuat bila belum ada (lampau = arsip, berjalan = aktif, depan = disiapkan). */
    private function periode(string $nama, int $berjalan): int
    {
        if ($id = DB::table('osis_periods')->where('nama', $nama)->value('id')) {
            return $id;
        }

        $tahun = (int) substr($nama, 0, 4);

        return DB::table('osis_periods')->insertGetId([
            'nama' => $nama,
            'mulai' => $tahun.'-07-01',
            'selesai' => ($tahun + 1).'-06-30',
            'status' => $tahun < $berjalan ? 'arsip' : ($tahun === $berjalan ? 'aktif' : 'disiapkan'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
