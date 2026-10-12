<?php

namespace App\Enums;

enum JenisPemilih: string
{
    case Siswa = 'siswa';
    case Gtk = 'gtk';

    public function label(): string
    {
        return $this === self::Siswa ? 'Siswa' : 'GTK';
    }

    /** Nama nomor induk yang dipakai untuk verifikasi. */
    public function labelNomorInduk(): string
    {
        return $this === self::Siswa ? 'NISN' : 'NIK';
    }
}
