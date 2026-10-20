<?php

namespace App\Filament\Resources\Elections\Pages;

use App\Filament\Resources\Elections\ElectionResource;
use App\Exceptions\PeriodeDitolak;
use App\Services\LayananModePemilihan;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ListElections extends ListRecords
{
    protected static string $resource = ElectionResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->aksiUbahMode(), CreateAction::make()];
    }

    /** Satu tombol sakelar; label, warna, dan ikon mengikuti mode saat ini. */
    protected function aksiUbahMode(): Action
    {
        return Action::make('ubahMode')
            ->visible(fn () => auth()->user()?->can('ubah-mode-pemilihan') ?? false)
            ->label(fn () => self::modeAktif() ? 'Matikan mode pemilihan' : 'Nyalakan mode pemilihan')
            ->icon(fn () => self::modeAktif() ? Heroicon::OutlinedPauseCircle : Heroicon::OutlinedPlayCircle)
            ->color(fn () => self::modeAktif() ? 'danger' : 'success')
            // Konfirmasi berisi dampak agar tidak salah tekan.
            ->requiresConfirmation()
            ->modalIcon(fn () => self::modeAktif() ? Heroicon::OutlinedPauseCircle : Heroicon::OutlinedPlayCircle)
            ->modalHeading(fn () => self::modeAktif() ? 'Matikan mode pemilihan?' : 'Nyalakan mode pemilihan?')
            ->modalDescription(null)
            ->modalContent(fn () => view('filament.dampak-mode-pemilihan', ['menyalakan' => ! self::modeAktif()]))
            ->modalSubmitActionLabel(fn () => self::modeAktif() ? 'Ya, matikan' : 'Ya, nyalakan')
            ->authorize('ubah-mode-pemilihan')
            ->disabled(fn () => \App\Models\OsisPeriod::aktif() === null)
            ->tooltip(fn () => \App\Models\OsisPeriod::aktif() === null ? 'Aktifkan periode OSIM dulu.' : null)
            ->action(function () {
                try {
                    $aktif = app(LayananModePemilihan::class)->balik(auth()->user());
                    Notification::make()->title($aktif ? 'Mode pemilihan dinyalakan' : 'Mode pemilihan dimatikan')->success()->send();
                } catch (PeriodeDitolak $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }

    private static function modeAktif(): bool
    {
        return app(LayananModePemilihan::class)->aktif();
    }

    /** Kartu sakelar mode pemilihan di atas daftar periode pemilihan. */
    public function content(Schema $schema): Schema
    {
        $isi = parent::content($schema);

        return $isi->components([View::make('filament.mode-pemilihan'), ...$isi->getComponents(withHidden: true)]);
    }
}
