<?php

namespace App\Filament\Pages;

use App\Models\Voter;
use App\Services\LayananPemilihan;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Satu tempat untuk mencetak kartu pemilih dan laporan hasil pemilihan. */
class CetakLaporan extends Page
{
    protected string $view = 'filament.pages.cetak-laporan';

    protected static ?string $slug = 'cetak-laporan';

    protected static ?string $title = 'Cetak & Laporan';

    protected static ?string $navigationLabel = 'Cetak & Laporan';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedPrinter;

    protected static string | UnitEnum | null $navigationGroup = 'Pemilihan';

    protected static ?int $navigationSort = 4;

    /** Kelompok kartu yang dipilih (kelas / "gtk" / kosong = semua). */
    public ?string $kelompok = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('kelola-pemilih') ?? false;
    }

    protected function getViewData(): array
    {
        $siapCetak = Voter::terverifikasi()->whereNotNull('kode_pilih');

        return [
            'pemilihan' => app(LayananPemilihan::class)->pemilihanAktif(),
            'kelompokList' => (clone $siapCetak)->whereNotNull('kelas')->selectRaw('kelas, count(*) as jumlah')->groupBy('kelas')->orderBy('kelas')->pluck('jumlah', 'kelas')
                ->put('gtk', (clone $siapCetak)->where('jenis', 'gtk')->count())->all(),
            'totalKartu' => $siapCetak->count(),
            'tanpaKode' => Voter::terverifikasi()->whereNull('kode_pilih')->count(),
            'bolehLaporan' => auth()->user()->can('publikasikan-hasil'),
        ];
    }

    public function urlKartu(): string
    {
        return route('kartu-pemilih', array_filter(['kelompok' => $this->kelompok]));
    }
}
