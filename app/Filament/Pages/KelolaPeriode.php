<?php

namespace App\Filament\Pages;

use App\Enums\Peran;
use App\Enums\StatusPeriode;
use App\Exceptions\PeriodeDitolak;
use App\Models\OsisPeriod;
use App\Models\User;
use App\Services\LayananPeriode;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

/** Periode kepengurusan OSIM: satu periode aktif, periode lama diarsipkan. */
class KelolaPeriode extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.kelola-periode';

    protected static ?string $slug = 'periode-osim';

    protected static ?string $title = 'Periode OSIM';

    protected static ?string $navigationLabel = 'Periode OSIM';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string | UnitEnum | null $navigationGroup = 'Organisasi';

    protected static ?int $navigationSort = -1;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', OsisPeriod::class) ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Hanya satu periode yang aktif. Data kandidat, saran, struktur, dan media sosial mengikuti periode aktif.';
    }

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\UbahModePemilihanAction::make()->outlined(),
            Action::make('tambah')
                ->label('Tambah periode')
                ->icon(Heroicon::OutlinedPlus)
                ->authorize(fn () => auth()->user()->can('create', OsisPeriod::class))
                ->modalHeading('Tambah periode')
                ->modalDescription('Periode baru disiapkan dengan data kosong. Aktifkan setelah kepengurusan baru siap.')
                ->modalSubmitActionLabel('Simpan')
                ->schema(self::formPeriode())
                ->action(function (array $data) {
                    app(LayananPeriode::class)->buat($data);
                    Notification::make()->title('Periode '.$data['nama'].' disiapkan')->success()->send();
                }),
        ];
    }

    /** Isian nama + rentang tanggal, dipakai form tambah & ubah. */
    public static function formPeriode(?OsisPeriod $kecuali = null): array
    {
        return [
            TextInput::make('nama')->label('Nama periode')->placeholder('Contoh: 2027/2028')->required()
                ->regex('/^\d{4}\/\d{4}$/')
                ->unique(OsisPeriod::class, 'nama', modifyRuleUsing: fn (Unique $rule) => $kecuali ? $rule->ignore($kecuali->id) : $rule)
                ->rule(fn (Get $get) => self::aturanRentang('nama', $get, $kecuali))
                ->validationMessages(['regex' => 'Format periode: 2027/2028.', 'unique' => 'Periode ini sudah ada.'])
                ->columnSpanFull(),
            DatePicker::make('mulai')->label('Mulai menjabat')->required()
                ->rule(fn (Get $get) => self::aturanRentang('mulai', $get, $kecuali)),
            DatePicker::make('selesai')->label('Selesai menjabat')->required()->after('mulai')
                ->validationMessages(['after' => 'Tanggal selesai harus sesudah tanggal mulai.'])
                ->rule(fn (Get $get) => self::aturanRentang('selesai', $get, $kecuali)),
        ];
    }

    /** Aturan rentang dari LayananPeriode, ditampilkan di bawah isian yang bersangkutan. */
    private static function aturanRentang(string $isian, Get $get, ?OsisPeriod $kecuali): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) use ($isian, $get, $kecuali) {
            $galat = app(LayananPeriode::class)->periksaRentang(
                ['nama' => $get('nama'), 'mulai' => $get('mulai'), 'selesai' => $get('selesai')],
                $kecuali,
            );

            if (isset($galat[$isian])) {
                $fail($galat[$isian]);
            }
        };
    }

    protected function getViewData(): array
    {
        return [
            'aktif' => OsisPeriod::aktif()?->loadCount(['members', 'candidates']),
            'pengurus' => User::query()->where('role', Peran::Pengurus)
                ->orderByDesc('is_aktif')->orderBy('name')->get()
                ->map(fn (User $u) => ['nama' => $u->name, 'email' => $u->email, 'is_aktif' => $u->menjabat()])
                ->all(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(OsisPeriod::query()->withCount(['members', 'candidates'])->urutTampil())
            ->columns([
                TextColumn::make('nama')->label('Periode')->weight('bold')
                    ->description(fn (OsisPeriod $record) => $record->rentang()),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (StatusPeriode $state) => $state->label())
                    ->color(fn (StatusPeriode $state) => $state->warna())
                    ->icon(fn (StatusPeriode $state) => $state->ikon())
                    ->tooltip(fn (StatusPeriode $state) => $state->keterangan()),
                TextColumn::make('members_count')->label('Pengurus')->numeric(),
                TextColumn::make('candidates_count')->label('Kandidat')->numeric(),
            ])
            ->recordClasses(fn (OsisPeriod $record) => $record->adalahAktif() ? 'bg-hijau-50' : null)
            ->recordActions([
                // Arsip hanya-lihat: tidak ada ubah/arsipkan, cukup ringkasan isinya.
                Action::make('lihat')
                    ->label('Lihat')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->visible(fn (OsisPeriod $record) => $record->adalahArsip())
                    ->modalHeading(fn (OsisPeriod $record) => 'Arsip periode '.$record->nama)
                    ->modalContent(fn (OsisPeriod $record) => view('filament.pages.periode-arsip', ['periode' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalWidth('md'),
                Action::make('ubah')
                    ->label('Ubah')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->visible(fn (OsisPeriod $record) => ! $record->adalahArsip())
                    ->authorize(fn (OsisPeriod $record) => auth()->user()->can('update', $record))
                    ->modalHeading(fn (OsisPeriod $record) => 'Ubah periode '.$record->nama)
                    ->modalSubmitActionLabel('Simpan perubahan')
                    ->fillForm(fn (OsisPeriod $record) => ['nama' => $record->nama, 'mulai' => $record->mulai, 'selesai' => $record->selesai])
                    ->schema(fn (OsisPeriod $record) => self::formPeriode($record))
                    ->action(fn (OsisPeriod $record, array $data) => self::jalankan(
                        fn () => app(LayananPeriode::class)->ubah($record, $data),
                        'Periode '.$data['nama'].' diperbarui',
                    )),
                Action::make('aktifkan')
                    ->label('Aktifkan')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (OsisPeriod $record) => $record->status === StatusPeriode::Disiapkan)
                    ->authorize(fn (OsisPeriod $record) => auth()->user()->can('update', $record))
                    ->requiresConfirmation()
                    ->modalIcon(Heroicon::OutlinedCheckCircle)
                    ->modalHeading(fn (OsisPeriod $record) => 'Aktifkan periode '.$record->nama.'?')
                    ->modalDescription(fn (OsisPeriod $record) => ($lama = OsisPeriod::aktif())
                        ? 'Periode '.$lama->nama.' yang sedang aktif otomatis diarsipkan dan hanya bisa dilihat. Data baru (kandidat, saran, struktur, media sosial) masuk ke periode '.$record->nama.'.'
                        : 'Data baru (kandidat, saran, struktur, media sosial) masuk ke periode '.$record->nama.'.')
                    ->modalSubmitActionLabel('Ya, aktifkan')
                    ->action(fn (OsisPeriod $record) => self::jalankan(
                        fn () => app(LayananPeriode::class)->aktifkan($record),
                        'Periode '.$record->nama.' sekarang aktif',
                    )),
                Action::make('arsipkan')
                    ->label('Arsipkan')
                    ->icon(Heroicon::OutlinedArchiveBoxArrowDown)
                    ->color('warning')
                    ->visible(fn (OsisPeriod $record) => ! $record->adalahArsip())
                    ->authorize(fn (OsisPeriod $record) => auth()->user()->can('update', $record))
                    ->requiresConfirmation()
                    ->modalIcon(Heroicon::OutlinedArchiveBoxArrowDown)
                    ->modalHeading(fn (OsisPeriod $record) => 'Arsipkan periode '.$record->nama.'?')
                    ->modalDescription(fn (OsisPeriod $record) => self::dampakArsip($record))
                    ->modalSubmitActionLabel('Ya, arsipkan')
                    ->action(fn (OsisPeriod $record) => self::jalankan(
                        fn () => app(LayananPeriode::class)->arsipkan($record),
                        'Periode '.$record->nama.' diarsipkan',
                    )),
                Action::make('hapus')
                    ->label('Hapus')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->visible(fn (OsisPeriod $record) => $record->status === StatusPeriode::Disiapkan && ! $record->punyaData())
                    ->authorize(fn (OsisPeriod $record) => auth()->user()->can('delete', $record))
                    ->requiresConfirmation()
                    ->modalHeading(fn (OsisPeriod $record) => 'Hapus periode '.$record->nama.'?')
                    ->modalDescription('Periode ini belum pernah aktif dan belum berisi data.')
                    ->action(fn (OsisPeriod $record) => self::jalankan(
                        fn () => app(LayananPeriode::class)->hapus($record),
                        'Periode '.$record->nama.' dihapus',
                    )),
            ])
            ->emptyStateHeading('Belum ada periode')
            ->emptyStateDescription('Buat periode kepengurusan pertama, misalnya 2026/2027.');
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

    /** Penjelasan dampak sebelum periode dipindah ke arsip. */
    public static function dampakArsip(OsisPeriod $periode): string
    {
        $isi = ($periode->members_count ?? $periode->members()->count()).' pengurus dan '
            .($periode->candidates_count ?? $periode->candidates()->count()).' kandidat tetap tersimpan dan hanya bisa dibaca. Arsip tidak bisa diaktifkan kembali.';

        return $periode->adalahAktif()
            ? 'Ini periode yang sedang aktif. Setelah diarsipkan, Ruang OSIM tidak punya periode aktif sampai periode lain diaktifkan, dan data baru belum bisa ditambahkan. '.$isi
            : $isi;
    }
}
