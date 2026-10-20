<?php

namespace App\Filament\Pages;

use App\Support\DataTiruanStruktur;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

/** Satu gambar bagan struktur organisasi untuk periode aktif. */
class StrukturOrganisasi extends Page
{
    protected string $view = 'filament.pages.struktur-organisasi';

    protected static ?string $slug = 'struktur-organisasi';

    protected static ?string $title = 'Struktur Organisasi';

    protected static ?string $navigationLabel = 'Struktur Organisasi';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string | UnitEnum | null $navigationGroup = 'Organisasi';

    protected static ?int $navigationSort = 0;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('kelola-organisasi') ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Bagan yang tampil di halaman publik Struktur Organisasi untuk periode '.(\App\Models\OsisPeriod::aktif()?->nama ?? 'aktif').'.';
    }

    protected function getViewData(): array
    {
        return ['struktur' => DataTiruanStruktur::aktif()];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('unggah')
                ->label(fn () => DataTiruanStruktur::aktif() ? 'Ganti gambar' : 'Unggah gambar')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->modalHeading(fn () => DataTiruanStruktur::aktif() ? 'Ganti bagan struktur' : 'Unggah bagan struktur')
                ->modalDescription(fn () => DataTiruanStruktur::aktif()
                    ? 'Gambar yang sekarang tampil di halaman publik akan diganti. Periode lain tidak terpengaruh.'
                    : 'Satu gambar untuk periode aktif.')
                ->modalSubmitActionLabel('Simpan')
                ->schema([
                    \Filament\Schemas\Components\View::make('filament.struktur-saat-ini')
                        ->visible(fn () => DataTiruanStruktur::aktif() !== null),
                    FileUpload::make('gambar')->label('Gambar bagan')
                        ->helperText('JPG, PNG, atau WebP. Maks. 5 MB. Pastikan tulisan masih terbaca saat diperkecil.')
                        ->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->disk('public')->directory('struktur')->visibility('public')
                        ->maxSize(5120)->required(),
                    self::pemilihRasio(),
                ])
                ->fillForm(fn () => ['rasio' => DataTiruanStruktur::aktif()['rasio'] ?? '4:3'])
                ->action(function (array $data) {
                    DataTiruanStruktur::simpan([
                        'gambar' => Storage::disk('public')->url($data['gambar']),
                        'rasio' => $data['rasio'],
                    ]);
                    Notification::make()->title('Bagan struktur disimpan')->success()->send();
                }),
            Action::make('hapus')
                ->label('Hapus gambar')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->visible(fn () => DataTiruanStruktur::aktif() !== null)
                ->requiresConfirmation()
                ->modalHeading('Hapus bagan struktur?')
                ->modalDescription('Halaman publik Struktur Organisasi akan menampilkan "Bagan struktur belum diunggah" sampai gambar baru diunggah. Bagan periode lain tidak terpengaruh.')
                ->modalSubmitActionLabel('Ya, hapus')
                ->action(function () {
                    DataTiruanStruktur::simpan(null);
                    Notification::make()->title('Bagan struktur dihapus')->success()->send();
                }),
            Action::make('ubahRasio')
                ->label('Ubah rasio')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->color('gray')
                ->visible(fn () => DataTiruanStruktur::aktif() !== null)
                ->modalHeading('Ubah rasio tampilan')
                ->modalDescription('Gambar tidak diunggah ulang; hanya bentuk bingkainya di halaman publik.')
                ->modalSubmitActionLabel('Simpan')
                ->fillForm(fn () => ['rasio' => DataTiruanStruktur::aktif()['rasio']])
                ->schema([self::pemilihRasio()])
                ->action(function (array $data) {
                    DataTiruanStruktur::simpan(['gambar' => DataTiruanStruktur::aktif()['gambar'], 'rasio' => $data['rasio']]);
                    Notification::make()->title('Rasio tampilan diubah ke '.$data['rasio'])->success()->send();
                }),
        ];
    }

    /** Pilihan bentuk bingkai bagan: 4:3 untuk bagan melebar, 3:4 untuk bagan memanjang ke bawah. */
    public static function pemilihRasio(): ToggleButtons
    {
        return ToggleButtons::make('rasio')->label('Rasio tampilan')
            ->options(DataTiruanStruktur::RASIO)
            ->icons(['4:3' => Heroicon::OutlinedComputerDesktop, '3:4' => Heroicon::OutlinedDevicePhoneMobile])
            ->inline()->required()->default('4:3')
            ->helperText('Lebar (4:3) cocok untuk bagan yang melebar ke samping; Tinggi (3:4) untuk bagan yang memanjang ke bawah dan enak dibaca di ponsel.');
    }
}
