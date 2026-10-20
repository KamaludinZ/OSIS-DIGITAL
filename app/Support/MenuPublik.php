<?php

namespace App\Support;

/** Susunan menu utama halaman publik, mengikuti sakelar mode pemilihan. */
class MenuPublik
{
    /**
     * @return list<array{rute: string, label: string, ikon: string, pola: string}>
     */
    public static function untuk(bool $modePemilihan): array
    {
        $beranda = ['rute' => 'home', 'label' => 'Beranda', 'ikon' => 'home', 'pola' => 'home'];

        return $modePemilihan
            ? [
                $beranda,
                ['rute' => 'bilik-suara', 'label' => 'Bilik Suara', 'ikon' => 'check-badge', 'pola' => 'bilik-suara*'],
                ['rute' => 'kandidat.index', 'label' => 'Kandidat', 'ikon' => 'user-group', 'pola' => 'kandidat.*'],
                ['rute' => 'hasil', 'label' => 'Hasil', 'ikon' => 'chart-bar', 'pola' => 'hasil'],
            ]
            : [
                $beranda,
                ['rute' => 'struktur', 'label' => 'Struktur Organisasi', 'ikon' => 'rectangle-group', 'pola' => 'struktur'],
                ['rute' => 'saran', 'label' => 'Kotak Saran', 'ikon' => 'inbox-arrow-down', 'pola' => 'saran*'],
            ];
    }
}
