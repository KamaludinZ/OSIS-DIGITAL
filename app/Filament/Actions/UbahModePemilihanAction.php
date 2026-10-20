<?php

namespace App\Filament\Actions;

use App\Exceptions\PeriodeDitolak;
use App\Models\OsisPeriod;
use App\Services\LayananModePemilihan;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/** Tombol sakelar mode pemilihan; label, warna, dan ikon mengikuti mode saat ini. */
class UbahModePemilihanAction
{
    public static function make(): Action
    {
        $aktif = fn () => app(LayananModePemilihan::class)->aktif();

        return Action::make('ubahMode')
            ->visible(fn () => auth()->user()?->can('ubah-mode-pemilihan') ?? false)
            ->authorize('ubah-mode-pemilihan')
            ->label(fn () => $aktif() ? 'Matikan mode pemilihan' : 'Nyalakan mode pemilihan')
            ->icon(fn () => $aktif() ? Heroicon::OutlinedPauseCircle : Heroicon::OutlinedPlayCircle)
            ->color(fn () => $aktif() ? 'danger' : 'success')
            ->disabled(fn () => OsisPeriod::aktif() === null)
            ->tooltip(fn () => OsisPeriod::aktif() === null ? 'Aktifkan periode OSIM dulu.' : null)
            // Konfirmasi berisi dampak agar tidak salah tekan.
            ->requiresConfirmation()
            ->modalIcon(fn () => $aktif() ? Heroicon::OutlinedPauseCircle : Heroicon::OutlinedPlayCircle)
            ->modalHeading(fn () => $aktif() ? 'Matikan mode pemilihan?' : 'Nyalakan mode pemilihan?')
            ->modalDescription(null)
            ->modalContent(fn () => view('filament.dampak-mode-pemilihan', ['menyalakan' => ! $aktif()]))
            ->modalSubmitActionLabel(fn () => $aktif() ? 'Ya, matikan' : 'Ya, nyalakan')
            ->action(function () {
                try {
                    $nyala = app(LayananModePemilihan::class)->balik(auth()->user());
                    Notification::make()->title($nyala ? 'Mode pemilihan dinyalakan' : 'Mode pemilihan dimatikan')->success()->send();
                } catch (PeriodeDitolak $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }
}
