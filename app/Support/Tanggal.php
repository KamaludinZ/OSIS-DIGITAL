<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** Format tanggal & jam Indonesia (WIB) yang seragam di halaman publik dan Ruang OSIM. */
class Tanggal
{
    /** "Senin, 20 Oktober 2026 pukul 11.40 WIB" */
    public static function lengkap(?\DateTimeInterface $waktu): string
    {
        return $waktu ? Carbon::instance($waktu)->locale('id')->translatedFormat('l, j F Y \p\u\k\u\l H.i').' WIB' : '—';
    }

    /** "20 Okt 2026, 11.40" */
    public static function singkat(?\DateTimeInterface $waktu): string
    {
        return $waktu ? Carbon::instance($waktu)->locale('id')->translatedFormat('j M Y, H.i') : '—';
    }

    /** "3 jam yang lalu" */
    public static function relatif(?\DateTimeInterface $waktu): string
    {
        return $waktu ? Carbon::instance($waktu)->locale('id')->diffForHumans() : '—';
    }
}
