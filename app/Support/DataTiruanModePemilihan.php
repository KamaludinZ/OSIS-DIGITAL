<?php

namespace App\Support;

/**
 * Data tiruan sakelar mode pemilihan selama fase frontend.
 * Disimpan di sesi agar tombol bisa dicoba; diganti penyimpanan nyata di layer backend.
 */
class DataTiruanModePemilihan
{
    private const KUNCI = 'tiruan.mode-pemilihan';

    /** @return array{aktif: bool, diubah_pada: string, diubah_oleh: string} */
    public static function status(): array
    {
        return session(self::KUNCI, [
            'aktif' => true,
            'diubah_pada' => now()->subDays(3)->setTime(7, 15)->toDateTimeString(),
            'diubah_oleh' => 'Panitia Pemilihan',
        ]);
    }

    public static function aktif(): bool
    {
        return self::status()['aktif'];
    }

    public static function setel(bool $aktif, string $oleh): void
    {
        session([self::KUNCI => ['aktif' => $aktif, 'diubah_pada' => now()->toDateTimeString(), 'diubah_oleh' => $oleh]]);
    }
}
