<?php

namespace App\Filament\Pages;

use App\Exceptions\PeriodeDitolak;
use App\Models\OrganizationStructure;
use App\Services\LayananStruktur;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
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
        return ['struktur' => self::struktur()];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('unggah')
                ->label(fn () => self::struktur() ? 'Ganti gambar' : 'Unggah gambar')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->modalHeading(fn () => self::struktur() ? 'Ganti bagan struktur' : 'Unggah bagan struktur')
                ->modalDescription(fn () => self::struktur()
                    ? 'Gambar yang sekarang tampil di halaman publik akan diganti. Periode lain tidak terpengaruh.'
                    : 'Satu gambar untuk periode aktif.')
                ->modalSubmitActionLabel('Simpan')
                ->schema([
                    \Filament\Schemas\Components\View::make('filament.struktur-saat-ini')
                        ->visible(fn () => self::struktur() !== null),
                    FileUpload::make('gambar')->label('Gambar bagan')
                        ->helperText('JPG, PNG, atau WebP. Maks. 5 MB. Pastikan tulisan masih terbaca saat diperkecil.')
                        ->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->disk('public')->directory('struktur')->visibility('public')
                        ->maxSize(5120)->required()
                        ->validationMessages([
                            'max' => 'Ukuran gambar maksimal 5 MB. Perkecil atau kompres dulu.',
                            'image' => 'Berkas harus berupa gambar JPG, PNG, atau WebP.',
                            'mimetypes' => 'Berkas harus berupa gambar JPG, PNG, atau WebP.',
                            'required' => 'Pilih gambar bagan yang akan diunggah.',
                        ]),
                    self::pemilihRasio(),
                ])
                ->fillForm(fn () => ['rasio' => self::struktur()?->rasio ?? '4:3'])
                ->disabled(fn () => \App\Models\OsisPeriod::aktif() === null)
                ->tooltip(fn () => \App\Models\OsisPeriod::aktif() === null ? 'Aktifkan periode OSIM dulu.' : null)
                ->action(fn (array $data) => self::jalankan(
                    fn () => app(LayananStruktur::class)->unggah($data['gambar'], $data['rasio'], auth()->user()),
                    'Bagan struktur disimpan',
                )),
            Action::make('hapus')
                ->label('Hapus gambar')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->visible(fn () => self::struktur() !== null)
                ->requiresConfirmation()
                ->modalHeading('Hapus bagan struktur?')
                ->modalDescription('Halaman publik Struktur Organisasi akan menampilkan "Bagan struktur belum diunggah" sampai gambar baru diunggah. Bagan periode lain tidak terpengaruh.')
                ->modalSubmitActionLabel('Ya, hapus')
                ->action(fn () => self::jalankan(fn () => app(LayananStruktur::class)->hapus(), 'Bagan struktur dihapus')),
            Action::make('ubahRasio')
                ->label('Ubah rasio')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->color('gray')
                ->visible(fn () => self::struktur() !== null)
                ->modalHeading('Ubah rasio tampilan')
                ->modalDescription('Gambar tidak diunggah ulang; hanya bentuk bingkainya di halaman publik.')
                ->modalSubmitActionLabel('Simpan')
                ->fillForm(fn () => ['rasio' => self::struktur()?->rasio])
                ->schema([self::pemilihRasio()])
                ->action(fn (array $data) => self::jalankan(
                    fn () => app(LayananStruktur::class)->ubahRasio($data['rasio']),
                    'Rasio tampilan diubah ke '.$data['rasio'],
                )),
        ];
    }

    private static function struktur(): ?OrganizationStructure
    {
        return app(LayananStruktur::class)->aktif();
    }

    /** Jalankan aksi layanan; penolakan aturan periode tampil sebagai pemberitahuan. */
    private static function jalankan(\Closure $aksi, string $berhasil): void
    {
        try {
            $aksi();
            Notification::make()->title($berhasil)->success()->send();
        } catch (PeriodeDitolak $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    /** Pilihan bentuk bingkai bagan: 4:3 untuk bagan melebar, 3:4 untuk bagan memanjang ke bawah. */
    public static function pemilihRasio(): ToggleButtons
    {
        return ToggleButtons::make('rasio')->label('Rasio tampilan')
            ->options(OrganizationStructure::RASIO)
            ->icons(['4:3' => Heroicon::OutlinedComputerDesktop, '3:4' => Heroicon::OutlinedDevicePhoneMobile])
            ->inline()->required()->default('4:3')
            ->helperText('Lebar (4:3) cocok untuk bagan yang melebar ke samping; Tinggi (3:4) untuk bagan yang memanjang ke bawah dan enak dibaca di ponsel.');
    }
}
