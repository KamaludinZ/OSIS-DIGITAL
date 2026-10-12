<?php

namespace Database\Seeders;

use App\Enums\StatusPemilihan;
use App\Models\Election;
use Illuminate\Database\Seeder;

/** Contoh periode pemilihan + tiga pasangan calon untuk pengujian lokal. */
class PemilihanSeeder extends Seeder
{
    public function run(): void
    {
        $pemilihan = Election::updateOrCreate(
            ['judul' => 'Pemilihan Ketua OSIM 2026/2027'],
            [
                // Dibuka lebar supaya bisa dicoba kapan saja saat pengembangan.
                'mulai' => now()->startOfDay(),
                'selesai' => now()->addDays(7)->setTime(15, 0),
                'status' => StatusPemilihan::Berjalan,
                'hasil_publik' => false,
            ],
        );

        $kandidat = [
            [
                'nomor_urut' => 1,
                'nama_ketua' => 'Rizky Pratama', 'kelas_ketua' => 'VIII-B',
                'nama_wakil' => 'Salsabila Putri', 'kelas_wakil' => 'VII-D',
                'slogan' => 'OSIM yang dekat, aktif, dan mendengar.',
                'latar_belakang' => 'Rizky aktif di Rohis dan Paskibra, wakil ketua kelas dua tahun berturut-turut. Salsabila adalah redaktur majalah sekolah.',
                'visi' => 'Mewujudkan OSIM yang dekat dengan siswa, aktif berkegiatan, dan selalu mendengar aspirasi.',
                'misi' => ['Membuka kotak aspirasi digital yang dijawab setiap minggu.', 'Mengadakan forum ketua kelas sebulan sekali.', 'Menghidupkan kembali pentas seni tahunan.'],
                'foto_url' => null,
            ],
            [
                'nomor_urut' => 2,
                'nama_ketua' => 'Nadia Kusuma', 'kelas_ketua' => 'VIII-A',
                'nama_wakil' => 'Fajar Ramadhan', 'kelas_wakil' => 'VII-A',
                'slogan' => 'Bergerak bersama, berprestasi bersama.',
                'latar_belakang' => 'Nadia juara olimpiade sains tingkat kota dan anggota tim debat. Fajar adalah kapten tim futsal sekolah.',
                'visi' => 'OSIM sebagai penggerak prestasi akademik dan non-akademik seluruh siswa.',
                'misi' => ['Membentuk kelompok belajar sebaya untuk persiapan ujian.', 'Menambah kompetisi antarkelas setiap semester.', 'Mendata dan mendukung siswa yang ikut lomba di luar sekolah.'],
                'foto_url' => null,
            ],
            [
                'nomor_urut' => 3,
                'nama_ketua' => 'Dimas Aditya', 'kelas_ketua' => 'VIII-E',
                'nama_wakil' => 'Aulia Rahmawati', 'kelas_wakil' => 'VII-F',
                'slogan' => 'Sekolah hijau, siswa kreatif.',
                'latar_belakang' => 'Dimas penggagas bank sampah kelas dan anggota KIR. Aulia aktif di ekstrakurikuler desain dan teater.',
                'visi' => 'Sekolah yang hijau dan ruang kreatif bagi setiap siswa.',
                'misi' => ['Memperluas program bank sampah ke seluruh kelas.', 'Menyediakan pojok kreatif untuk karya siswa.', 'Mengadakan pameran karya siswa di akhir tahun.'],
                'foto_url' => null,
            ],
        ];

        foreach ($kandidat as $data) {
            $pemilihan->candidates()->updateOrCreate(['nomor_urut' => $data['nomor_urut']], $data);
        }
    }
}
