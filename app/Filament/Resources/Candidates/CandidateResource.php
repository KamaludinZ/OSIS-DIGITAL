<?php

namespace App\Filament\Resources\Candidates;

use App\Enums\FormatKandidat;
use App\Filament\Resources\Candidates\Pages\CreateCandidate;
use App\Filament\Resources\Candidates\Pages\EditCandidate;
use App\Filament\Resources\Candidates\Pages\ListCandidates;
use App\Models\Candidate;
use App\Models\Election;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

/** Kandidat ketua OSIM (berpasangan dengan wakil atau ketua saja, mengikuti format pemilihan). */
class CandidateResource extends Resource
{
    protected static ?string $model = Candidate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Pemilihan';

    protected static ?int $navigationSort = -1;

    protected static ?string $slug = 'kandidat';

    protected static ?string $modelLabel = 'kandidat';

    protected static ?string $pluralModelLabel = 'kandidat';

    protected static ?string $navigationLabel = 'Kandidat';

    protected static ?string $recordTitleAttribute = 'nama_ketua';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('kelola-pemilihan') ?? false;
    }

    /** Format pemilihan yang sedang dipilih di form. */
    public static function berpasangan(Get $get): bool
    {
        $format = Election::find($get('election_id'))?->format_kandidat;

        return ($format ?? FormatKandidat::KetuaWakil) === FormatKandidat::KetuaWakil;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Nomor urut')->columns(2)->columnSpanFull()->schema([
                Select::make('election_id')->label('Periode pemilihan')
                    ->relationship('election', 'judul')
                    ->default(fn () => Election::aktif()?->id)
                    ->required()->live()
                    // Kandidat tidak boleh dipindah periode setelah ada suara.
                    ->disabled(fn (?Candidate $record) => $record?->votes()->exists() ?? false),
                TextInput::make('nomor_urut')->label('Nomor urut')->integer()->minValue(1)->maxValue(99)->required()
                    ->default(fn (Get $get) => (Candidate::where('election_id', $get('election_id') ?? Election::aktif()?->id)->max('nomor_urut') ?? 0) + 1)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('election_id', $get('election_id')))
                    ->validationMessages(['unique' => 'Nomor urut ini sudah dipakai kandidat lain pada periode yang sama.']),
            ]),
            Section::make(fn (Get $get) => self::berpasangan($get) ? 'Pasangan calon' : 'Calon ketua')->columns(2)->columnSpanFull()->schema([
                TextInput::make('nama_ketua')->label('Nama calon ketua')->required()->maxLength(100),
                TextInput::make('kelas_ketua')->label('Kelas calon ketua')->placeholder('Contoh: VIII-A')->maxLength(20),
                TextInput::make('nama_wakil')->label('Nama calon wakil')->maxLength(100)
                    ->visible(fn (Get $get) => self::berpasangan($get))
                    ->required(fn (Get $get) => self::berpasangan($get)),
                TextInput::make('kelas_wakil')->label('Kelas calon wakil')->placeholder('Contoh: VIII-C')->maxLength(20)
                    ->visible(fn (Get $get) => self::berpasangan($get)),
                FileUpload::make('foto_url')->label(fn (Get $get) => self::berpasangan($get) ? 'Foto berdua (ketua & wakil)' : 'Foto calon ketua')
                    ->helperText('Foto mendatar (4:3) agar tampil penuh di kartu dan bilik suara. Maks. 2 MB.')
                    ->image()->imageEditor()->imageAspectRatio('4:3')
                    ->disk('public')->directory('kandidat')->visibility('public')
                    ->maxSize(2048)->columnSpanFull(),
            ]),
            Section::make('Visi & misi')->columnSpanFull()->schema([
                TextInput::make('slogan')->label('Slogan')->maxLength(120),
                Textarea::make('latar_belakang')->label('Perkenalan singkat')->rows(3)->maxLength(1000),
                Textarea::make('visi')->label('Visi')->rows(3)->maxLength(1000),
                Repeater::make('misi')->label('Misi')
                    ->simple(TextInput::make('isi')->maxLength(300)->placeholder('Satu misi per baris'))
                    ->addActionLabel('Tambah misi')->reorderable()->defaultItems(1),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nomor_urut')
            ->modifyQueryUsing(fn ($query) => $query->with('election')->withCount(['akun', 'questions as menunggu_jawaban' => fn ($q) => $q->whereNull('jawaban')]))
            ->columns([
                TextColumn::make('nomor_urut')->label('No.')->weight('bold')->sortable(),
                ImageColumn::make('foto_url')->label('Foto')->disk('public')->height(44),
                TextColumn::make('nama_ketua')->label('Kandidat')->weight('bold')->searchable(['nama_ketua', 'nama_wakil'])
                    ->formatStateUsing(fn (Candidate $r) => $r->namaTampil())
                    ->description(fn (Candidate $r) => collect([$r->kelas_ketua, $r->kelas_wakil])->filter()->join(' & ')),
                TextColumn::make('election.judul')->label('Periode')->toggleable(),
                TextColumn::make('akun_count')->label('Akun kandidat')->badge()
                    ->formatStateUsing(fn (int $state) => $state ? 'Ada' : 'Belum')
                    ->color(fn (int $state) => $state ? 'success' : 'gray'),
                TextColumn::make('menunggu_jawaban')->label('Pertanyaan menunggu')->numeric(),
            ])
            ->filters([
                SelectFilter::make('election_id')->label('Periode')->relationship('election', 'judul')
                    ->default(fn () => Election::aktif()?->id),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (Candidate $record) => $record->votes()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCandidates::route('/'),
            'create' => CreateCandidate::route('/create'),
            'edit' => EditCandidate::route('/{record}/edit'),
        ];
    }
}
