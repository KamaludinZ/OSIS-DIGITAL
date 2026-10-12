<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Member;
use App\Support\DataTiruanOrganisasi;
use Illuminate\Database\Seeder;

/** DATA CONTOH susunan pengurus OSIM periode berjalan (inti + divisi). */
class OrganisasiSeeder extends Seeder
{
    public function run(): void
    {
        $periode = Member::periodeAktif();
        Member::where('periode', $periode)->delete();

        foreach (DataTiruanOrganisasi::pengurusInti() as $i => $p) {
            Member::create(['nama' => $p['nama'], 'kelas' => $p['kelas'], 'jabatan' => $p['jabatan'], 'periode' => $periode, 'urutan' => $i]);
        }

        foreach (DataTiruanOrganisasi::anggota() as $a) {
            if ($a['divisi'] === 'Pengurus Inti') {
                continue;
            }

            $divisi = Division::firstOrCreate(['nama' => $a['divisi']]);
            Member::create([
                'nama' => $a['nama'], 'kelas' => $a['kelas'], 'divisi_id' => $divisi->id,
                'jabatan' => $a['jabatan'], 'periode' => $periode, 'urutan' => $a['jabatan'] === 'Koordinator' ? 0 : 1,
            ]);
        }
    }
}
