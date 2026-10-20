<?php

namespace App\Filament\Pages;

use App\Services\LayananHasil;
use App\Services\LayananPemilihan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/** Rekap perolehan suara untuk panitia & admin, termasuk sebelum hasil dipublikasikan. */
class PerolehanSuara extends Page
{
    protected string $view = 'filament.pages.perolehan-suara';

    protected static ?string $slug = 'perolehan-suara';

    protected static ?string $title = 'Perolehan Suara';

    protected static ?string $navigationLabel = 'Perolehan Suara';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string | UnitEnum | null $navigationGroup = 'Pemilihan';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('publikasikan-hasil') ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Diperbarui otomatis tiap 15 detik. Halaman publik Hasil hanya menampilkan perolehan setelah dipublikasikan.';
    }

    protected function getViewData(): array
    {
        $pemilihan = app(LayananPemilihan::class)->pemilihanAktif();

        return [
            'rekap' => app(LayananHasil::class)->rekap($pemilihan),
            'pemenang' => $pemilihan ? app(LayananHasil::class)->pemenang($pemilihan) : null,
        ];
    }

    protected function getHeaderActions(): array
    {
        $pemilihan = fn () => app(LayananPemilihan::class)->pemilihanAktif();

        return [
            Action::make('publikasi')
                ->label(fn () => $pemilihan()?->hasil_publik ? 'Tarik publikasi' : 'Publikasikan hasil')
                ->icon(fn () => $pemilihan()?->hasil_publik ? Heroicon::OutlinedEyeSlash : Heroicon::OutlinedMegaphone)
                ->color(fn () => $pemilihan()?->hasil_publik ? 'gray' : 'success')
                ->visible(fn () => $pemilihan() !== null)
                ->requiresConfirmation()
                ->modalDescription(fn () => $pemilihan()?->hasil_publik
                    ? 'Perolehan per kandidat disembunyikan lagi dari halaman publik Hasil.'
                    : 'Perolehan per kandidat dan pemenang tampil di halaman publik Hasil.')
                ->action(function () use ($pemilihan) {
                    try {
                        $hasil = app(LayananHasil::class)->aturPublikasi($pemilihan(), ! $pemilihan()->hasil_publik);
                        Notification::make()->title($hasil->hasil_publik ? 'Hasil dipublikasikan' : 'Publikasi ditarik')->success()->send();
                    } catch (ValidationException $e) {
                        Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();
                    }
                }),
            Action::make('cetak')->label('Berita acara')->icon(Heroicon::OutlinedPrinter)->color('gray')
                ->url(fn () => route('hasil.laporan'), shouldOpenInNewTab: true)->visible(fn () => $pemilihan() !== null),
            Action::make('csv')->label('Unduh CSV')->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->url(fn () => route('hasil.laporan.csv'))->visible(fn () => $pemilihan() !== null),
        ];
    }
}
