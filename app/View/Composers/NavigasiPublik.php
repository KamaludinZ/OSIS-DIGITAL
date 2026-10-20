<?php

namespace App\View\Composers;

use App\Services\LayananModePemilihan;
use App\Services\LayananPemilihan;
use App\Support\MenuPublik;
use Illuminate\View\View;

/**
 * Data navigasi layout publik, disusun dari status mode pemilihan:
 * $modePemilihan, $menu (dengan url siap pakai & penanda aktif), dan $bilikDibuka.
 */
class NavigasiPublik
{
    public function __construct(private LayananModePemilihan $mode, private LayananPemilihan $pemilihan) {}

    public function compose(View $view): void
    {
        $modePemilihan = $this->mode->aktif();

        $menu = array_map(fn (array $item) => [
            ...$item,
            'url' => MenuPublik::url($item),
            'aktif' => request()->routeIs($item['pola']),
        ], $this->mode->menu($modePemilihan));

        $view->with([
            'modePemilihan' => $modePemilihan,
            'menu' => $menu,
            // Mode aktif: item Bilik Suara diberi penanda selama bilik suara dibuka.
            'bilikDibuka' => $modePemilihan && ($this->pemilihan->pemilihanAktif()?->sedangDibuka() ?? false),
            // Akun resmi OSIM periode aktif di kaki halaman.
            'akunSosmed' => \App\Support\DataTiruanAkunSosmed::publik(),
        ]);
    }
}
