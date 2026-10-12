<?php

namespace Database\Seeders;

use App\Enums\StatusProgramKerja;
use App\Models\Announcement;
use App\Models\Division;
use App\Models\Event;
use App\Models\WorkProgram;
use Illuminate\Database\Seeder;

/**
 * DATA CONTOH untuk pengujian lokal — termasuk angka capaian program kerja
 * yang dikarang. Bukan data asli MTsN 2 Kota Malang; jangan dipakai di produksi.
 */
class PublikasiSeeder extends Seeder
{
    public function run(): void
    {
        $divisi = collect([
            'Panitia Pemilihan' => 'Menyelenggarakan pemilihan ketua OSIM.',
            'Divisi Lingkungan' => 'Kebersihan dan program sekolah hijau.',
            'Divisi Pendidikan' => 'Kegiatan akademik dan pengembangan diri.',
            'Divisi Humas' => 'Komunikasi dan aspirasi siswa.',
            'Divisi Olahraga' => 'Kompetisi dan kegiatan olahraga.',
            'Divisi Kebangsaan' => 'Upacara dan peringatan hari besar nasional.',
            'Divisi Seni & Budaya' => 'Pentas seni dan kegiatan budaya.',
            'Divisi Sosial' => 'Bakti sosial dan penggalangan dana.',
        ])->mapWithKeys(fn ($deskripsi, $nama) => [
            $nama => Division::updateOrCreate(['nama' => $nama], ['deskripsi' => $deskripsi]),
        ]);

        $pengumuman = [
            ['Jadwal Pemilihan Ketua OSIM 2026/2027', 'Pemilihan', 'Pemungutan suara dibuka melalui aplikasi OSIS Digital. Siapkan akun sekolahmu.', 'Panitia Pemilihan', 4],
            ['Pendaftaran Lomba Kebersihan Kelas', 'Kegiatan', 'Setiap kelas wajib mengirim perwakilan untuk briefing lomba kebersihan di aula hari Jumat.', 'Divisi Lingkungan', 6],
            ['Hasil Rapat Pleno OSIM September', 'Organisasi', 'Rapat pleno menyepakati tiga program prioritas semester ganjil dan pembentukan tim media.', 'Sekretaris OSIM', 10],
            ['Penggalangan Dana Peduli Bencana', 'Sosial', 'Donasi dibuka hingga akhir bulan di pos OSIM lantai 1. Terima kasih atas kepeduliannya.', 'Divisi Sosial', 14],
            ['Perubahan Jadwal Upacara Hari Senin', 'Umum', 'Upacara bendera dimajukan pukul 06.45 WIB selama bulan ini.', 'Ketua OSIM', 17],
        ];

        foreach ($pengumuman as [$judul, $kategori, $ringkasan, $penulis, $hariLalu]) {
            Announcement::updateOrCreate(['judul' => $judul], [
                'kategori' => $kategori,
                'ringkasan' => $ringkasan,
                'isi' => $ringkasan."\n\nInformasi lebih lanjut dapat ditanyakan langsung kepada {$penulis} di ruang OSIM pada jam istirahat.\n\nMohon sebarkan pengumuman ini kepada teman sekelas yang belum mengetahuinya.",
                'penulis_label' => $penulis,
                'dipublikasikan' => true,
                'dipublikasikan_pada' => now()->subDays($hariLalu)->setTime(9, 0),
            ]);
        }

        // [nama, mulai (hari dari sekarang), lama (hari), tempat, divisi]
        $agenda = [
            ['Debat Kandidat Ketua OSIM', -3, 1, 'Aula Utama', 'Panitia Pemilihan'],
            ['Pemungutan Suara Ketua OSIM', 0, 1, 'Daring (OSIS Digital)', 'Panitia Pemilihan'],
            ['Lomba Kebersihan Kelas', 7, 5, 'Seluruh kelas', 'Divisi Lingkungan'],
            ['Peringatan Sumpah Pemuda', 16, 1, 'Lapangan Upacara', 'Divisi Kebangsaan'],
            ['Pentas Seni Tahunan', 33, 2, 'Aula Utama', 'Divisi Seni & Budaya'],
        ];

        foreach ($agenda as [$nama, $mulai, $lama, $tempat, $namaDivisi]) {
            Event::updateOrCreate(['nama' => $nama], [
                'deskripsi' => "{$nama} diselenggarakan oleh {$namaDivisi} dan terbuka untuk seluruh siswa. Hadir tepat waktu dan ikuti arahan panitia di lokasi.",
                'tanggal_mulai' => today()->addDays($mulai),
                'tanggal_selesai' => today()->addDays($mulai + $lama - 1),
                'tempat' => $tempat,
                'divisi_id' => $divisi[$namaDivisi]->id,
            ]);
        }

        $program = [
            ['Bank Sampah Sekolah', 'Mengumpulkan dan memilah sampah anorganik dari setiap kelas untuk ditabung dan didaur ulang.', 'Divisi Lingkungan', StatusProgramKerja::Jalan, true, '320 kg sampah terkumpul'],
            ['Kelas Inspirasi Alumni', 'Alumni berbagi pengalaman kuliah dan karier setiap bulan.', 'Divisi Pendidikan', StatusProgramKerja::Selesai, true, '6 sesi, 540 peserta'],
            ['Kotak Aspirasi Digital', 'Saluran aspirasi siswa yang dijawab pengurus setiap minggu.', 'Divisi Humas', StatusProgramKerja::Jalan, true, '87 aspirasi dijawab'],
            ['Turnamen Futsal Antarkelas', 'Kompetisi olahraga untuk mempererat kebersamaan antarkelas.', 'Divisi Olahraga', StatusProgramKerja::Rencana, false, null],
        ];

        foreach ($program as [$nama, $deskripsi, $namaDivisi, $status, $unggulan, $capaian]) {
            WorkProgram::updateOrCreate(['nama' => $nama], [
                'deskripsi' => $deskripsi,
                'divisi_id' => $divisi[$namaDivisi]->id,
                'status' => $status,
                'unggulan' => $unggulan,
                'capaian' => $capaian,
            ]);
        }
    }
}
