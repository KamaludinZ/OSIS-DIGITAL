<?php

namespace App\Filament\Resources\Elections;

use App\Enums\FormatKandidat;
use App\Enums\StatusPemilihan;
use App\Filament\Resources\Elections\Pages\CreateElection;
use App\Filament\Resources\Elections\Pages\EditElection;
use App\Filament\Resources\Elections\Pages\ListElections;
use App\Models\Election;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Periode pemilihan ketua OSIM: jadwal, status, dan format kandidat. */
class ElectionResource extends Resource
{
    protected static ?string $model = Election::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Pemilihan';

    protected static ?int $navigationSort = -2;

    protected static ?string $slug = 'periode-pemilihan';

    protected static ?string $modelLabel = 'periode pemilihan';

    protected static ?string $pluralModelLabel = 'periode pemilihan';

    protected static ?string $navigationLabel = 'Periode Pemilihan';

    protected static ?string $recordTitleAttribute = 'judul';

    public static array $labelStatus = ['draft' => 'Draf (belum dibuka)', 'berjalan' => 'Berjalan', 'selesai' => 'Selesai'];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('kelola-pemilihan') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('judul')->label('Judul pemilihan')->placeholder('Contoh: Pemilihan Ketua OSIM 2026/2027')
                ->required()->maxLength(150)->columnSpanFull(),
            Radio::make('format_kandidat')->label('Format kandidat')
                ->options(collect(FormatKandidat::cases())->mapWithKeys(fn ($f) => [$f->value => $f->label()]))
                ->descriptions([
                    FormatKandidat::KetuaWakil->value => 'Setiap nomor urut berisi calon ketua dan calon wakil.',
                    FormatKandidat::Ketua->value => 'Setiap nomor urut hanya berisi calon ketua.',
                ])
                ->default(FormatKandidat::KetuaWakil->value)->required()
                // Format tidak boleh berubah setelah ada suara masuk.
                ->disabled(fn (?Election $record) => $record?->votes()->exists() ?? false)
                ->helperText(fn (?Election $record) => $record?->votes()->exists()
                    ? 'Terkunci karena suara sudah masuk.'
                    : 'Mengubah ke "Ketua saja" menghapus data calon wakil pada kandidat periode ini.')
                ->columnSpanFull(),
            DateTimePicker::make('mulai')->label('Bilik suara dibuka')->seconds(false)->required(),
            DateTimePicker::make('selesai')->label('Bilik suara ditutup')->seconds(false)->required()->after('mulai'),
            Select::make('status')->label('Status')->options(self::$labelStatus)->default(StatusPemilihan::Draft->value)->required()
                ->helperText('"Berjalan" baru membuka bilik suara di antara jam buka dan tutup.'),
            Toggle::make('hasil_publik')->label('Hasil sudah dipublikasikan')->inline(false)
                ->visible(fn (Get $get) => $get('status') === StatusPemilihan::Selesai->value || $get('status') === StatusPemilihan::Selesai),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('mulai', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->withCount(['candidates', 'votes']))
            ->columns([
                TextColumn::make('judul')->label('Pemilihan')->weight('bold')->searchable()
                    ->description(fn (Election $r) => $r->mulai->translatedFormat('j M Y H:i').' – '.$r->selesai->translatedFormat('j M Y H:i')),
                TextColumn::make('format_kandidat')->label('Format')->badge()->formatStateUsing(fn (FormatKandidat $state) => $state->label()),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (StatusPemilihan $state) => self::$labelStatus[$state->value])
                    ->color(fn (StatusPemilihan $state) => ['draft' => 'gray', 'berjalan' => 'success', 'selesai' => 'warning'][$state->value]),
                TextColumn::make('candidates_count')->label('Kandidat')->numeric(),
                TextColumn::make('votes_count')->label('Suara masuk')->numeric(),
                IconColumn::make('hasil_publik')->label('Hasil publik')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListElections::route('/'),
            'create' => CreateElection::route('/create'),
            'edit' => EditElection::route('/{record}/edit'),
        ];
    }
}
