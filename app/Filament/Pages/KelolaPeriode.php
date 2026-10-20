<?php

namespace App\Filament\Pages;

use App\Support\DataTiruanPeriode;
use BackedEnum;
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

    protected function getViewData(): array
    {
        return ['aktif' => DataTiruanPeriode::aktif()];
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => DataTiruanPeriode::semua())
            ->columns([
                TextColumn::make('nama')->label('Periode')->weight('bold')
                    ->description(fn (array $record) => self::rentang($record)),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state) => DataTiruanPeriode::STATUS[$state][0])
                    ->color(fn (string $state) => DataTiruanPeriode::STATUS[$state][1]),
                TextColumn::make('pengurus')->label('Pengurus')->numeric(),
                TextColumn::make('kandidat')->label('Kandidat')->numeric(),
                TextColumn::make('saran')->label('Saran')->numeric(),
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
