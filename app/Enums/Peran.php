<?php

namespace App\Enums;

enum Peran: string
{
    case Siswa = 'siswa';
    case Pengurus = 'pengurus';
    case Panitia = 'panitia';
    case Admin = 'admin';
    case Kandidat = 'kandidat';

    public function label(): string
    {
        return match ($this) {
            self::Siswa => 'Siswa',
            self::Pengurus => 'Pengurus OSIM',
            self::Panitia => 'Panitia pemilihan',
            self::Admin => 'Admin',
            self::Kandidat => 'Kandidat',
        };
    }
}
