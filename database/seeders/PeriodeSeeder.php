<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\OsisPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * DATA CONTOH periode kepengurusan: tahun ajaran berjalan aktif (mode pemilihan menyala)
 * + satu periode arsip lengkap dengan bagan, saran bertanggapan, dan akun media sosial lama.
 * Aman diulang.
 */
class PeriodeSeeder extends Seeder
{
    public function run(): void
    {
        $berjalan = (int) substr(Member::periodeAktif(), 0, 4);

        $arsip = OsisPeriod::updateOrCreate(['nama' => ($berjalan - 1).'/'.$berjalan], [
            'mulai' => ($berjalan - 1).'-07-01', 'selesai' => $berjalan.'-06-30', 'status' => 'arsip',
        ]);
        $this->isiArsip($arsip);

        if (! OsisPeriod::aktif()) {
            OsisPeriod::updateOrCreate(['nama' => $berjalan.'/'.($berjalan + 1)], [
                'mulai' => $berjalan.'-07-01', 'selesai' => ($berjalan + 1).'-06-30', 'status' => 'aktif', 'mode_pemilihan' => true,
            ]);
        }
    }

    /** Isi arsip ditulis langsung (model menolak menambah data ke periode arsip). */
    private function isiArsip(OsisPeriod $arsip): void
    {
        if (! DB::table('organization_structures')->where('periode_id', $arsip->id)->exists()) {
            Storage::disk('public')->put('struktur/contoh-arsip.svg', file_get_contents(public_path('images/struktur/contoh-tinggi.svg')));
            DB::table('organization_structures')->insert([
                'periode_id' => $arsip->id, 'gambar' => 'struktur/contoh-arsip.svg', 'rasio' => '3:4', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        if (! DB::table('suggestions')->where('periode_id', $arsip->id)->exists()) {
            DB::table('suggestions')->insert([
                'periode_id' => $arsip->id, 'isi' => 'Jadwal piket kelas sebaiknya ditempel di mading. (contoh)',
                'tanggapan' => 'Terima kasih, sudah ditempel di mading tiap lantai.', 'ditanggapi_pada' => now()->subMonths(5),
                'created_at' => now()->subMonths(5)->subDays(2), 'updated_at' => now()->subMonths(5),
            ]);
        }

        DB::table('social_links')->insertOrIgnore([
            'periode_id' => $arsip->id, 'platform' => 'instagram', 'nama_akun' => '@osim.arsip.contoh',
            'tautan' => 'https://www.instagram.com/osim.arsip.contoh', 'urutan' => 0, 'jumlah_klik' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
