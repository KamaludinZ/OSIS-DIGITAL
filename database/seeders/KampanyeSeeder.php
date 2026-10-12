<?php

namespace Database\Seeders;

use App\Enums\TipeMateri;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Contoh materi, jadwal, dan tanya jawab kampanye untuk tiap paslon. */
class KampanyeSeeder extends Seeder
{
    public function run(): void
    {
        $janji = [
            1 => ['Kotak aspirasi digital dijawab paling lambat 7 hari.', 'Forum ketua kelas setiap bulan dengan notulen terbuka.', 'Pentas seni tahunan kembali digelar.'],
            2 => ['Kelompok belajar sebaya di setiap angkatan.', 'Kompetisi antarkelas tiap semester.', 'Dana pendamping untuk siswa yang ikut lomba.'],
            3 => ['Bank sampah di seluruh kelas.', 'Pojok kreatif untuk karya siswa.', 'Pameran karya siswa di akhir tahun.'],
        ];

        // [hari dari sekarang, jam, tempat]
        $jadwal = [
            1 => [[2, '10:00', 'Kantin Sekolah'], [-4, '09:30', 'Aula Utama'], [0, '12:15', 'Masjid Sekolah']],
            2 => [[-2, '07:30', 'Lapangan Basket'], [3, '13:00', 'Perpustakaan']],
            3 => [[1, '09:00', 'Taman Sekolah'], [-1, '14:00', 'Ruang Kesenian'], [5, '10:30', 'Laboratorium IPA']],
        ];

        $penanya = \App\Models\Voter::firstOrCreate(
            ['jenis' => 'siswa', 'nomor_induk' => '0091234502'],
            ['nama' => 'Citra Lestari', 'kelas' => 'VII-E', 'tanggal_lahir' => '2012-03-09'],
        );

        // [pertanyaan, jawaban|null, hari lalu]
        $tanya = [
            1 => [
                ['Bagaimana memastikan aspirasi benar-benar ditindaklanjuti?', 'Setiap aspirasi diberi nomor dan statusnya bisa dipantau. Kami laporkan rekapnya tiap bulan di mading digital.', 3],
                ['Apakah pentas seni akan berbayar?', null, 1],
            ],
            2 => [
                ['Kelompok belajar sebaya itu wajib atau sukarela?', 'Sukarela. Kami mulai dari mata pelajaran yang paling banyak diminta lewat survei.', 2],
            ],
            3 => [],
        ];

        foreach (Candidate::all() as $calon) {
            $n = $calon->nomor_urut;

            $calon->materials()->delete();
            $urutan = 0;
            foreach ([1, 2] as $k) {
                $calon->materials()->create([
                    'tipe' => TipeMateri::Poster,
                    'url_konten' => "/images/kampanye/paslon-{$n}-poster-{$k}.svg",
                    'deskripsi' => $k === 1 ? "Poster utama pasangan calon nomor {$n}" : 'Poster program unggulan',
                    'urutan' => $urutan++,
                ]);
            }
            $calon->materials()->create([
                'tipe' => TipeMateri::Video,
                'url_konten' => null,
                'deskripsi' => 'Video perkenalan pasangan calon (segera diunggah panitia).',
                'urutan' => $urutan++,
            ]);
            foreach ($janji[$n] ?? [] as $isi) {
                $calon->materials()->create(['tipe' => TipeMateri::Janji, 'deskripsi' => $isi, 'urutan' => $urutan++]);
            }

            $calon->schedules()->delete();
            foreach ($jadwal[$n] ?? [] as [$hari, $jam, $tempat]) {
                $calon->schedules()->create(['tanggal' => today()->addDays($hari), 'waktu' => $jam, 'tempat' => $tempat]);
            }

            $calon->questions()->delete();
            foreach ($tanya[$n] ?? [] as [$pertanyaan, $jawaban, $hariLalu]) {
                $q = $calon->questions()->create([
                    'voter_id' => $penanya->id,
                    'pertanyaan' => $pertanyaan,
                    'jawaban' => $jawaban,
                    'dijawab_pada' => $jawaban ? now()->subDays($hariLalu)->addHours(3) : null,
                ]);
                $q->forceFill(['created_at' => now()->subDays($hariLalu)])->save();
            }
        }
    }
}
