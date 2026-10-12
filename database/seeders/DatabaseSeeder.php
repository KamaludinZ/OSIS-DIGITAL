<?php

namespace Database\Seeders;

use App\Enums\Peran;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data awal untuk pengembangan lokal. Aman dijalankan berulang
     * (php artisan db:seed) karena memakai updateOrCreate.
     *
     * Akun contoh — semua berkata sandi "password":
     *   admin@osis.test, panitia@osis.test, pengurus@osis.test,
     *   siswa@osis.test, siswa2@osis.test
     */
    public function run(): void
    {
        // Semua isi seeder ini DATA CONTOH (akun berkata sandi "password", kandidat,
        // pengumuman, angka capaian program). Jangan pernah dijalankan di produksi.
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Seeder data contoh dilewati: hanya untuk lingkungan local/testing.');

            return;
        }

        $akun = [
            ['Admin Sekolah', 'admin@osis.test', Peran::Admin, null],
            ['Panitia Pemilihan', 'panitia@osis.test', Peran::Panitia, 'VIII-A'],
            ['Pengurus OSIM', 'pengurus@osis.test', Peran::Pengurus, 'VIII-C'],
            ['Budi Santoso', 'siswa@osis.test', Peran::Siswa, 'VII-C'],
            ['Citra Lestari', 'siswa2@osis.test', Peran::Siswa, 'VII-E'],
        ];

        foreach ($akun as [$nama, $email, $peran, $kelas]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $nama,
                'password' => 'password',
                'role' => $peran,
                'kelas' => $kelas,
                'email_verified_at' => now(),
            ]);
        }

        $this->call([PemilihanSeeder::class, KampanyeSeeder::class, PublikasiSeeder::class, SosmedSeeder::class]);
    }
}
