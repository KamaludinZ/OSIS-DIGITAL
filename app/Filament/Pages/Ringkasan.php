<?php

namespace App\Filament\Pages;

use App\Support\DataTiruanOrganisasi;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/** Halaman utama Ruang OSIM: ringkasan organisasi untuk pengurus. */
class Ringkasan extends Page
{
    protected string $view = 'filament.pages.ringkasan';

    protected static string $routePath = '/';

    protected static ?string $title = 'Ringkasan Organisasi';

    protected static ?string $navigationLabel = 'Ringkasan';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?int $navigationSort = -10;

    public function getSubheading(): ?string
    {
        return 'Kepengurusan OSIM periode '.DataTiruanOrganisasi::periode();
    }

    protected function getViewData(): array
    {
        $divisi = DataTiruanOrganisasi::divisi();
        $program = collect(DataTiruanOrganisasi::programKerja());

        return [
            'inti' => DataTiruanOrganisasi::pengurusInti(),
            'divisi' => $divisi,
            'program' => $program->groupBy('status'),
            'jadwal' => DataTiruanOrganisasi::jadwal(),
            'angka' => [
                ['Anggota pengurus', count(DataTiruanOrganisasi::pengurusInti()) + array_sum(array_column($divisi, 'anggota'))],
                ['Divisi', count($divisi)],
                ['Program berjalan', $program->where('status', 'jalan')->count()],
                ['Kegiatan terjadwal', count(DataTiruanOrganisasi::jadwal())],
            ],
        ];
    }
}
