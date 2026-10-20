<?php

namespace App\Support;

/** Susunan menu utama halaman publik, mengikuti sakelar mode pemilihan. */
class MenuPublik
{
    /**
     * label_pendek dipakai tab bar HP yang sempit.
     *
     * @return list<array{rute: string, label: string, label_pendek: string, ikon: string, pola: string}>
     */
    public static function untuk(bool $modePemilihan): array
    {
        $beranda = ['rute' => 'home', 'label' => 'Beranda', 'label_pendek' => 'Beranda', 'ikon' => 'home', 'pola' => 'home'];

        $menu = $modePemilihan
            ? [
                $beranda,
                ['rute' => 'bilik-suara', 'label' => 'Bilik Suara', 'label_pendek' => 'Bilik Suara', 'ikon' => 'check-badge', 'pola' => 'bilik-suara*'],
                ['rute' => 'kandidat.index', 'label' => 'Kandidat', 'label_pendek' => 'Kandidat', 'ikon' => 'user-group', 'pola' => 'kandidat.*'],
                ['rute' => 'hasil', 'label' => 'Hasil', 'label_pendek' => 'Hasil', 'ikon' => 'chart-bar', 'pola' => 'hasil'],
            ]
            : [
                $beranda,
                ['rute' => 'struktur', 'label' => 'Struktur Organisasi', 'label_pendek' => 'Struktur', 'ikon' => 'rectangle-group', 'pola' => 'struktur'],
                ['rute' => 'saran', 'label' => 'Kotak Saran', 'label_pendek' => 'Saran', 'ikon' => 'inbox-arrow-down', 'pola' => 'saran*'],
            ];

        // Satu rute hanya muncul sekali walau susunan menu diubah kelak.
        return array_values(collect($menu)->unique('rute')->all());
    }

    /** Alamat item menu; halaman yang belum dibangun mengarah ke "#". */
    public static function url(array $item): string
    {
        return \Illuminate\Support\Facades\Route::has($item['rute']) ? route($item['rute']) : '#';
    }
}
