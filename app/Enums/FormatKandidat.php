<?php

namespace App\Enums;

enum FormatKandidat: string
{
    case KetuaWakil = 'ketua_wakil';
    case Ketua = 'ketua';

    public function label(): string
    {
        return match ($this) {
            self::KetuaWakil => 'Ketua + wakil (berpasangan)',
            self::Ketua => 'Ketua saja',
        };
    }

    /** Sebutan untuk satu peserta pemilihan: "pasangan calon" / "calon ketua". */
    public function istilah(): string
    {
        return match ($this) {
            self::KetuaWakil => 'pasangan calon',
            self::Ketua => 'calon ketua',
        };
    }
}
