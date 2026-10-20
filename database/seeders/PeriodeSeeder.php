<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\OsisPeriod;
use Illuminate\Database\Seeder;

/** DATA CONTOH periode kepengurusan: tahun ajaran berjalan aktif (mode pemilihan menyala) + satu arsip. */
class PeriodeSeeder extends Seeder
{
    public function run(): void
    {
        $berjalan = (int) substr(Member::periodeAktif(), 0, 4);

        OsisPeriod::updateOrCreate(['nama' => ($berjalan - 1).'/'.$berjalan], [
            'mulai' => ($berjalan - 1).'-07-01', 'selesai' => $berjalan.'-06-30', 'status' => 'arsip',
        ]);

        if (! OsisPeriod::aktif()) {
            OsisPeriod::updateOrCreate(['nama' => $berjalan.'/'.($berjalan + 1)], [
                'mulai' => $berjalan.'-07-01', 'selesai' => ($berjalan + 1).'-06-30', 'status' => 'aktif', 'mode_pemilihan' => true,
            ]);
        }
    }
}
