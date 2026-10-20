<?php

namespace App\Support;

/**
 * Data tiruan Struktur Organisasi selama fase frontend: satu gambar bagan per periode.
 * Disimpan di sesi agar aksi di Ruang OSIM bisa dicoba; diganti tabel organization_structures di layer backend.
 */
class DataTiruanStruktur
{
    private const KUNCI = 'tiruan.struktur';

    public const RASIO = ['4:3' => 'Lebar (4:3)', '3:4' => 'Tinggi (3:4)'];

    /** @return array{gambar: string, rasio: string, diperbarui: string}|null */
    public static function aktif(): ?array
    {
        return session()->exists(self::KUNCI) ? session(self::KUNCI) : [
            'gambar' => '/images/struktur/contoh-lebar.svg',
            'rasio' => '4:3',
            'diperbarui' => now()->subDays(5)->toDateTimeString(),
        ];
    }

    /** @param  array{gambar: string, rasio: string}|null  $struktur  null = gambar dihapus */
    public static function simpan(?array $struktur): void
    {
        session([self::KUNCI => $struktur ? [...$struktur, 'diperbarui' => now()->toDateTimeString()] : null]);
    }
}
