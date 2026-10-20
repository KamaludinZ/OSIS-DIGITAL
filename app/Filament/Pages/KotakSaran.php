<?php

namespace App\Filament\Pages;

use App\Support\DataTiruanSaran;
use BackedEnum;
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
            ->emptyStateHeading('Belum ada saran')
            ->emptyStateDescription('Saran dari halaman publik Kotak Saran akan muncul di sini.');
    }
}
