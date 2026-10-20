<?php

namespace App\Support;

/**
 * Data tiruan akun media sosial resmi OSIM per periode selama fase frontend.
 * Disimpan di sesi agar tambah/ubah bisa dicoba; diganti tabel social_links di layer backend.
 */
class DataTiruanAkunSosmed
{
    private const KUNCI = 'tiruan.akun-sosmed';

    /** @return list<array{id: int, periode: string, platform: string, nama_akun: string, tautan: string}> */
    public static function semua(): array
    {
        return session()->exists(self::KUNCI) ? session(self::KUNCI) : [
            ['id' => 1, 'periode' => '2026/2027', 'platform' => 'instagram', 'nama_akun' => '@osim.mtsn2malang', 'tautan' => 'https://www.instagram.com/osim.mtsn2malang'],
            ['id' => 2, 'periode' => '2026/2027', 'platform' => 'youtube', 'nama_akun' => 'OSIM MTsN 2 Kota Malang', 'tautan' => 'https://www.youtube.com/@osimmtsn2malang'],
            ['id' => 3, 'periode' => '2026/2027', 'platform' => 'tiktok', 'nama_akun' => '@osim.mtsn2', 'tautan' => 'https://www.tiktok.com/@osim.mtsn2'],
            ['id' => 4, 'periode' => '2025/2026', 'platform' => 'instagram', 'nama_akun' => '@osim2526.mtsn2', 'tautan' => 'https://www.instagram.com/osim2526.mtsn2'],
        ];
    }

    public static function simpan(array $semua): void
    {
        session([self::KUNCI => array_values($semua)]);
    }

    public static function periodeAktif(): string
    {
        return \App\Models\OsisPeriod::aktif()?->nama ?? '2026/2027';
    }
}
