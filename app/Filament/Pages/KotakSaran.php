<?php

namespace App\Filament\Pages;

use App\Support\DataTiruanSaran;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use UnitEnum;

/** Saran dari halaman publik Kotak Saran, per periode. */
class KotakSaran extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.kotak-saran';

    protected static ?string $slug = 'kotak-saran';

    protected static ?string $title = 'Kotak Saran';

    protected static ?string $navigationLabel = 'Kotak Saran';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string | UnitEnum | null $navigationGroup = 'Organisasi';

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('kelola-organisasi') ?? false;
    }

    /** Jumlah saran periode aktif yang belum ditanggapi. */
    public static function getNavigationBadge(): ?string
    {
        $belum = collect(DataTiruanSaran::semua())->where('periode', self::periodeAktif())->whereNull('tanggapan')->count();

        return $belum ? (string) $belum : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function getSubheading(): ?string
    {
        return 'Saran siswa untuk OSIM. Hanya pengurus yang sedang menjabat yang bisa menanggapi.';
    }

    private static function periodeAktif(): string
    {
        return \App\Models\OsisPeriod::aktif()?->nama ?? '2026/2027';
    }

    protected function getViewData(): array
    {
        $dipilih = $this->getTableFilterState('periode')['value'] ?? null;

        return [
            'riwayat' => collect(DataTiruanSaran::semua())->groupBy('periode')->sortKeysDesc()
                ->map(fn ($saran, $periode) => [
                    'periode' => $periode,
                    'aktif' => $periode === self::periodeAktif(),
                    'dipilih' => $periode === $dipilih,
                    'total' => $saran->count(),
                    'ditanggapi' => $saran->whereNotNull('tanggapan')->count(),
                ])->values()->all(),
        ];
    }

    /** Pilih periode dari kartu riwayat = setel filter tabel. */
    public function lihatPeriode(string $periode): void
    {
        $this->tableFilters['periode']['value'] = $periode;
        $this->resetPage();
    }

    /** Data tiruan: gate tanggapi-saran memeriksa periode_id; di sini dicocokkan lewat nama periode. */
    private static function bolehMenanggapi(array $saran): bool
    {
        return (auth()->user()?->can('tanggapi-saran') ?? false) && $saran['periode'] === self::periodeAktif();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?array $filters): array => collect(DataTiruanSaran::semua())
                ->when($filters['periode']['value'] ?? null, fn ($c, $p) => $c->where('periode', $p))
                ->when($filters['status']['value'] ?? null, fn ($c, $s) => $s === 'belum' ? $c->whereNull('tanggapan') : $c->whereNotNull('tanggapan'))
                ->keyBy('id')->all())
            ->columns([
                TextColumn::make('isi')->label('Saran')->wrap()->lineClamp(3)->weight('medium')
                    ->description(fn (array $record) => $record['tanggapan'] ? 'Tanggapan: '.str($record['tanggapan'])->limit(80) : null),
                TextColumn::make('nama')->label('Pengirim')
                    ->state(fn (array $record) => $record['nama'] ? $record['nama'].' · '.$record['kelas'] : 'Anonim')
                    ->color(fn (array $record) => $record['nama'] ? null : 'gray')
                    ->icon(fn (array $record) => $record['nama'] ? 'heroicon-m-user' : 'heroicon-m-eye-slash'),
                TextColumn::make('dikirim')->label('Dikirim')
                    ->formatStateUsing(fn (string $state) => Carbon::parse($state)->diffForHumans())
                    ->tooltip(fn (array $record) => Carbon::parse($record['dikirim'])->translatedFormat('j F Y, H:i')),
                TextColumn::make('status')->label('Status')->badge()
                    ->state(fn (array $record) => $record['tanggapan'] ? 'Ditanggapi' : 'Belum ditanggapi')
                    ->color(fn (string $state) => $state === 'Ditanggapi' ? 'success' : 'warning'),
            ])
            ->filters([
                SelectFilter::make('periode')->label('Periode')
                    ->options(fn () => collect(DataTiruanSaran::semua())->pluck('periode', 'periode')->sortDesc()->all())
                    ->default(self::periodeAktif()),
                SelectFilter::make('status')->label('Status')
                    ->options(['belum' => 'Belum ditanggapi', 'sudah' => 'Ditanggapi']),
            ])
            ->recordActions([
                Action::make('lihat')
                    ->label('Riwayat')
                    ->icon(Heroicon::OutlinedClock)
                    ->color('gray')
                    ->modalHeading('Riwayat saran')
                    ->modalContent(fn (array $record) => view('filament.saran-detail', ['saran' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalWidth('lg'),
                Action::make('tanggapi')
                    ->label(fn (array $record) => $record['tanggapan'] ? 'Ubah tanggapan' : 'Tanggapi')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->color(fn (array $record) => $record['tanggapan'] ? 'gray' : 'primary')
                    // Hanya pengurus menjabat & saran periode aktif (gate tanggapi-saran).
                    ->visible(fn (array $record) => self::bolehMenanggapi($record))
                    ->modalHeading('Tanggapi saran')
                    ->modalDescription(fn (array $record) => '"'.str($record['isi'])->limit(160).'" — '.($record['nama'] ? $record['nama'].' · '.$record['kelas'] : 'Anonim'))
                    ->modalSubmitActionLabel('Kirim tanggapan')
                    ->fillForm(fn (array $record) => ['tanggapan' => $record['tanggapan']])
                    ->schema([
                        Textarea::make('tanggapan')->label('Tanggapan pengurus')->rows(4)
                            ->required()->minLength(5)->maxLength(1000)
                            ->placeholder('Contoh: Terima kasih, usulan ini kami bahas di rapat pleno Jumat ini.')
                            ->helperText('Tanggapan tercatat atas namamu dan tersimpan di riwayat periode ini.'),
                    ])
                    ->action(function (array $record, array $data) {
                        $semua = collect(DataTiruanSaran::semua())->map(fn ($s) => $s['id'] === $record['id']
                            ? [...$s, 'tanggapan' => trim($data['tanggapan']), 'penanggap' => auth()->user()->name, 'ditanggapi' => now()->toDateTimeString()]
                            : $s);
                        DataTiruanSaran::simpan($semua->all());
                        Notification::make()->title('Tanggapan terkirim')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Belum ada saran')
            ->emptyStateDescription('Saran dari halaman publik Kotak Saran akan muncul di sini.');
    }
}
