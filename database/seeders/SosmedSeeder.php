<?php

namespace Database\Seeders;

use App\Enums\PlatformSosmed;
use App\Models\SocialPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * DATA CONTOH konten media sosial OSIM. Thumbnail sintetis bertanda "CONTOH"
 * dan tautan profil fiktif — ganti dengan konten asli lewat /kelola/sosmed.
 */
class SosmedSeeder extends Seeder
{
    public function run(): void
    {
        $konten = [
            ['yt-1', PlatformSosmed::YouTube, 'Keseruan Classmeeting Hari Pertama', 'https://www.youtube.com/shorts/contohOSIM01', 'potret', 128],
            ['yt-2', PlatformSosmed::YouTube, 'Cara Memilih Ketua OSIM dalam 30 Detik', 'https://www.youtube.com/shorts/contohOSIM02', 'potret', 342],
            ['yt-3', PlatformSosmed::YouTube, 'Dokumentasi Upacara Hari Santri', 'https://www.youtube.com/watch?v=contohOSIM07', 'lanskap', 205],
            ['ig-1', PlatformSosmed::Instagram, 'Gladi Bersih Pentas Seni Tahunan', 'https://www.instagram.com/p/contohOSIM03/', 'lanskap', 87],
            ['ig-2', PlatformSosmed::Instagram, 'Rekap Bank Sampah Pekan Ini', 'https://www.instagram.com/p/contohOSIM04/', 'potret', 54],
            ['tt-1', PlatformSosmed::TikTok, 'Cuplikan Debat Pasangan Calon', 'https://www.tiktok.com/@osim.contoh/video/7000000000000000001', 'potret', 511],
            ['tt-2', PlatformSosmed::TikTok, 'Aksi Jumat Bersih Kelas VIII', 'https://www.tiktok.com/@osim.contoh/video/7000000000000000002', 'potret', 96],
        ];

        foreach ($konten as $i => [$berkas, $platform, $judul, $tautan, $orientasi, $klik]) {
            $tujuan = "sosmed/contoh-{$berkas}.png";
            Storage::disk('public')->put($tujuan, file_get_contents(database_path("seeders/aset/sosmed/contoh-{$berkas}.png")));

            $post = SocialPost::updateOrCreate(['tautan' => $tautan], [
                'platform' => $platform,
                'judul' => $judul,
                'thumbnail' => $tujuan,
                'orientasi' => $orientasi,
                'dipublikasikan' => true,
            ]);
            $post->forceFill(['jumlah_klik' => $klik, 'created_at' => now()->subDays(count($konten) - $i)])->save();
        }

        // Akun resmi contoh untuk periode aktif (tautan fiktif).
        if (\App\Models\OsisPeriod::aktif()) {
            foreach ([
                [PlatformSosmed::Instagram, '@osim.contoh', 'https://www.instagram.com/osim.contoh'],
                [PlatformSosmed::YouTube, 'OSIM MTsN 2 Kota Malang (contoh)', 'https://www.youtube.com/@osim.contoh'],
                [PlatformSosmed::TikTok, '@osim.contoh', 'https://www.tiktok.com/@osim.contoh'],
            ] as $urutan => [$platform, $nama, $tautan]) {
                \App\Models\SocialLink::updateOrCreate(['platform' => $platform], ['nama_akun' => $nama, 'tautan' => $tautan, 'urutan' => $urutan]);
            }
        }
    }
}
