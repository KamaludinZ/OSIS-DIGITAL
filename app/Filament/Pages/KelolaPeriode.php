<?php

namespace App\Filament\Pages;

use App\Support\DataTiruanPeriode;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
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
        return auth()->user()?->can('kelola-organisasi') ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Hanya satu periode yang aktif. Data kandidat, saran, struktur, dan media sosial mengikuti periode aktif.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tambah')
                ->label('Tambah periode')
                ->icon(Heroicon::OutlinedPlus)
                ->modalHeading('Tambah periode')
                ->modalDescription('Periode baru disiapkan dengan data kosong. Aktifkan setelah kepengurusan baru siap.')
                ->modalSubmitActionLabel('Simpan')
                ->schema(self::formPeriode())
                ->action(function (array $data) {
                    $semua = DataTiruanPeriode::semua();
                    $id = (max(array_keys($semua) ?: [0])) + 1;
                    $semua = [$id => ['id' => $id, ...self::isian($data), 'status' => 'disiapkan', 'pengurus' => 0, 'kandidat' => 0, 'saran' => 0]] + $semua;
                    DataTiruanPeriode::simpan($semua);
                    Notification::make()->title('Periode '.$data['nama'].' disiapkan')->success()->send();
                }),
        ];
    }

    /** Isian nama + rentang tanggal, dipakai form tambah & ubah. */
    public static function formPeriode(?int $kecuali = null): array
    {
        return [
            TextInput::make('nama')->label('Nama periode')->placeholder('Contoh: 2027/2028')->required()
                ->regex('/^\\d{4}\\/\\d{4}$/')
                ->rule(fn () => function (string $attribute, $value, \Closure $fail) use ($kecuali) {
                    $kembar = collect(DataTiruanPeriode::semua())->except($kecuali)->contains('nama', $value);
                    if ($kembar) {
                        $fail('Periode '.$value.' sudah ada.');
                    }
                })
                ->validationMessages(['regex' => 'Format periode: 2027/2028.'])
                ->columnSpanFull(),
            DatePicker::make('mulai')->label('Mulai menjabat')->required(),
            DatePicker::make('selesai')->label('Selesai menjabat')->required()->after('mulai')
                ->validationMessages(['after' => 'Tanggal selesai harus sesudah tanggal mulai.']),
        ];
    }

    /** @return array{nama: string, mulai: string, selesai: string} */
    private static function isian(array $data): array
    {
        return [
            'nama' => $data['nama'],
            'mulai' => Carbon::parse($data['mulai'])->toDateString(),
            'selesai' => Carbon::parse($data['selesai'])->toDateString(),
        ];
    }

    protected function getViewData(): array
    {
        return ['aktif' => DataTiruanPeriode::aktif()];
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => collect(DataTiruanPeriode::semua())
                ->sortBy([fn ($a, $b) => DataTiruanPeriode::URUTAN[$a['status']] <=> DataTiruanPeriode::URUTAN[$b['status']], ['nama', 'desc']])
                ->all())
            ->columns([
                TextColumn::make('nama')->label('Periode')->weight('bold')
                    ->description(fn (array $record) => self::rentang($record)),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state) => DataTiruanPeriode::STATUS[$state][0])
                    ->color(fn (string $state) => DataTiruanPeriode::STATUS[$state][1])
                    ->icon(fn (string $state) => DataTiruanPeriode::STATUS[$state][2])
                    ->tooltip(fn (string $state) => DataTiruanPeriode::STATUS[$state][3]),
                TextColumn::make('pengurus')->label('Pengurus')->numeric(),
                TextColumn::make('kandidat')->label('Kandidat')->numeric(),
                TextColumn::make('saran')->label('Saran')->numeric(),
            ])
            ->recordClasses(fn (array $record) => $record['status'] === 'aktif' ? 'bg-hijau-50' : null)
            ->recordActions([
                Action::make('ubah')
                    ->label('Ubah')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->modalHeading(fn (array $record) => 'Ubah periode '.$record['nama'])
                    ->modalSubmitActionLabel('Simpan perubahan')
                    ->fillForm(fn (array $record) => $record)
                    ->schema(fn (array $record) => self::formPeriode($record['id']))
                    ->action(function (array $record, array $data) {
                        $semua = DataTiruanPeriode::semua();
                        $semua[$record['id']] = [...$semua[$record['id']], ...self::isian($data)];
                        DataTiruanPeriode::simpan($semua);
                        Notification::make()->title('Periode '.$data['nama'].' diperbarui')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Belum ada periode')
            ->emptyStateDescription('Buat periode kepengurusan pertama, misalnya 2026/2027.');
    }

    /** @param  array<string, mixed>  $periode */
    public static function rentang(array $periode): string
    {
        return Carbon::parse($periode['mulai'])->translatedFormat('j M Y').' – '.Carbon::parse($periode['selesai'])->translatedFormat('j M Y');
    }
}
