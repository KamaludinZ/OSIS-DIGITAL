<?php

namespace App\Services;

use App\Enums\PlatformSosmed;
use App\Models\SocialPost;

/** Konten media sosial OSIM untuk Beranda, dikelompokkan per platform. */
class LayananSosmed
{
    /**
     * @return array<string, array{label: string, konten: array}>
     */
    public function perPlatform(int $batas = 12): array
    {
        $hasil = [];

        foreach (PlatformSosmed::cases() as $platform) {
            $hasil[$platform->value] = [
                'label' => $platform->label(),
                'konten' => SocialPost::query()
                    ->tayang()
                    ->where('platform', $platform)
                    ->limit($batas)
                    ->get()
                    ->map(fn (SocialPost $p) => [
                        'id' => $p->id,
                        'judul' => $p->judul,
                        'thumbnail' => $p->urlThumbnail(),
                        'orientasi' => $p->orientasi,
                        'jumlah_klik' => $p->jumlah_klik,
                        // Thumbnail bawaan seeder ditandai sebagai contoh di halaman.
                        'contoh' => str_starts_with($p->thumbnail, 'sosmed/contoh-'),
                    ])
                    ->all(),
            ];
        }

        return $hasil;
    }

    /**
     * Catat satu kali buka per sesi browser, agar angka tidak bisa
     * dinaikkan dengan menekan berulang.
     */
    public function catatKlik(SocialPost $post): void
    {
        $sudah = session('sosmed_dibuka', []);

        if (in_array($post->id, $sudah, true)) {
            return;
        }

        $post->increment('jumlah_klik');
        session(['sosmed_dibuka' => [...$sudah, $post->id]]);
    }
}
