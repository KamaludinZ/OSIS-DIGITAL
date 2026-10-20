<?php

namespace App\Filament\Resources\Elections\Pages;

use App\Filament\Resources\Elections\ElectionResource;
use App\Support\DataTiruanModePemilihan;
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
            ->label(fn () => DataTiruanModePemilihan::aktif() ? 'Matikan mode pemilihan' : 'Nyalakan mode pemilihan')
            ->icon(fn () => DataTiruanModePemilihan::aktif() ? Heroicon::OutlinedPauseCircle : Heroicon::OutlinedPlayCircle)
            ->color(fn () => DataTiruanModePemilihan::aktif() ? 'danger' : 'success')
            // Konfirmasi berisi dampak agar tidak salah tekan.
            ->requiresConfirmation()
            ->modalIcon(fn () => DataTiruanModePemilihan::aktif() ? Heroicon::OutlinedPauseCircle : Heroicon::OutlinedPlayCircle)
            ->modalHeading(fn () => DataTiruanModePemilihan::aktif() ? 'Matikan mode pemilihan?' : 'Nyalakan mode pemilihan?')
            ->modalDescription(null)
            ->modalContent(fn () => view('filament.dampak-mode-pemilihan', ['menyalakan' => ! DataTiruanModePemilihan::aktif()]))
            ->modalSubmitActionLabel(fn () => DataTiruanModePemilihan::aktif() ? 'Ya, matikan' : 'Ya, nyalakan')
            ->action(function () {
                $aktif = ! DataTiruanModePemilihan::aktif();
                DataTiruanModePemilihan::setel($aktif, auth()->user()->name);
                Notification::make()->title($aktif ? 'Mode pemilihan dinyalakan' : 'Mode pemilihan dimatikan')->success()->send();
            });
    }

    /** Kartu sakelar mode pemilihan di atas daftar periode pemilihan. */
    public function content(Schema $schema): Schema
    {
        $isi = parent::content($schema);

        return $isi->components([View::make('filament.mode-pemilihan'), ...$isi->getComponents(withHidden: true)]);
    }
}
