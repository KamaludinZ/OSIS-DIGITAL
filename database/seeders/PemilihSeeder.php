<?php

namespace Database\Seeders;

use App\Enums\JenisPemilih;
use App\Models\Voter;
use Illuminate\Database\Seeder;

/**
 * DATA CONTOH pemilih (siswa & GTK) beserta kode pilih.
 * Dua pemilih memakai kode tetap agar mudah dicoba:
 *   - Budi Santoso (siswa, NISN 0091234501, lahir 2012-08-17): kode 246810
 *   - Ibu Rahma Dewi (GTK, NIK 3573014101850001, lahir 1985-01-01): kode 135790
 */
class PemilihSeeder extends Seeder
{
    public function run(): void
    {
        $tetap = [
            ['siswa', '0091234501', 'Budi Santoso', 'VII-C', null, '2012-08-17', '246810'],
            ['siswa', '0091234502', 'Citra Lestari', 'VII-E', null, '2012-03-09', null],
            ['gtk', '3573014101850001', 'Ibu Rahma Dewi', null, 'Guru Bahasa Indonesia', '1985-01-01', '135790'],
        ];

        foreach ($tetap as [$jenis, $induk, $nama, $kelas, $jabatan, $lahir, $kode]) {
            $p = Voter::updateOrCreate(['jenis' => $jenis, 'nomor_induk' => $induk], [
                'nama' => $nama, 'kelas' => $kelas, 'jabatan' => $jabatan, 'tanggal_lahir' => $lahir, 'verifikasi' => 'terverifikasi',
            ]);
            $kode ? $p->forceFill(['kode_pilih' => $kode, 'kode_dibuat_pada' => now()])->save() : ($p->kode_pilih ?: $p->beriKode());
        }

        $depan = ['Ahmad', 'Siti', 'Muhammad', 'Nur', 'Rizky', 'Aisyah', 'Fajar', 'Zahra', 'Dimas', 'Aulia', 'Hafidz', 'Nabila', 'Bagas', 'Keisha', 'Ilham'];
        $belakang = ['Pratama', 'Rahmawati', 'Hidayat', 'Kusuma', 'Ramadhan', 'Putri', 'Saputra', 'Azzahra', 'Maulana'];
        $kelas = ['VII-A', 'VII-B', 'VIII-A', 'VIII-B', 'IX-A', 'IX-B'];

        for ($i = 0; $i < 42; $i++) {
            $p = Voter::updateOrCreate(['jenis' => JenisPemilih::Siswa, 'nomor_induk' => '00912346'.str_pad((string) $i, 2, '0', STR_PAD_LEFT)], [
                'nama' => $depan[$i % 15].' '.$belakang[($i * 2) % 9],
                'kelas' => $kelas[$i % 6],
                'tanggal_lahir' => now()->subYears(12 + $i % 3)->subDays($i * 7)->toDateString(),
                'verifikasi' => $i % 13 === 4 ? 'menunggu' : 'terverifikasi',
            ]);
            if ($p->verifikasi === 'terverifikasi' && ! $p->kode_pilih) {
                $p->beriKode();
            }
        }

        foreach (['Bapak Agus Salim' => 'Guru PAI', 'Ibu Lilis Suryani' => 'Guru IPA', 'Bapak Hendra Wijaya' => 'Tenaga Kependidikan'] as $i => $jabatan) {
            $p = Voter::updateOrCreate(['jenis' => JenisPemilih::Gtk, 'nomor_induk' => '35730141018600'.str_pad((string) (crc32($i) % 100), 2, '0', STR_PAD_LEFT)], [
                'nama' => $i, 'jabatan' => $jabatan, 'tanggal_lahir' => '1986-01-01', 'verifikasi' => 'terverifikasi',
            ]);
            $p->kode_pilih ?: $p->beriKode();
        }
    }
}
