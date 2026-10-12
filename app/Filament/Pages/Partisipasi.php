<?php

namespace App\Filament\Pages;

use App\Services\LayananPartisipasi;
use App\Services\LayananPemilihan;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Pantau partisipasi pemilih secara langsung (per kelas & GTK). */
class Partisipasi extends Page
{
    protected string $view = 'filament.pages.partisipasi';

    protected static ?string $title = 'Pantau Partisipasi';

    protected static ?string $navigationLabel = 'Partisipasi';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string | UnitEnum | null $navigationGroup = 'Pemilihan';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('kelola-pemilih') ?? false;
    }

    public function getSubheading(): ?string
    {
        $p = app(LayananPemilihan::class)->ringkasanPemilihan();

        return $p ? $p['judul'].' · diperbarui otomatis tiap 15 detik' : 'Belum ada pemilihan';
    }

    protected function getViewData(): array
    {
        $pemilihan = app(LayananPemilihan::class)->pemilihanAktif();

        return [
            'data' => app(LayananPartisipasi::class)->ringkasan($pemilihan),
            'belum' => app(LayananPartisipasi::class)->belumMemilih($pemilihan),
            'diperbarui' => now(),
        ];
    }
}
