<?php

namespace App\Support;

use App\Enums\FormatKandidat;
use App\Models\Election;
use Illuminate\Support\Str;

/** Sebutan peserta pemilihan mengikuti format pemilihan aktif. */
class Istilah
{
    public static function format(): FormatKandidat
    {
        return once(fn () => Election::aktif()?->format_kandidat ?? FormatKandidat::KetuaWakil);
    }

    /** "pasangan calon" / "calon ketua". */
    public static function calon(): string
    {
        return self::format()->istilah();
    }

    /** "Pasangan Calon" / "Calon Ketua". */
    public static function judul(): string
    {
        return Str::title(self::calon());
    }
}
