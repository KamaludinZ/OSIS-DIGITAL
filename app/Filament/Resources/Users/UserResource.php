<?php

namespace App\Filament\Resources\Users;

use App\Enums\Peran;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;

/**
 * Akun login dibuat & direset hanya oleh admin: admin, pengurus, panitia,
 * dan kandidat (untuk menanggapi tanya jawab). Siswa & GTK tidak punya akun.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $slug = 'akun';

    protected static ?string $modelLabel = 'akun';

    protected static ?string $pluralModelLabel = 'akun pengguna';

    protected static ?string $navigationLabel = 'Akun Pengguna';

    protected static ?string $recordTitleAttribute = 'name';

    /** Peran yang boleh diberi akun. */
    public static function peranAkun(): array
    {
        return collect([Peran::Admin, Peran::Pengurus, Peran::Panitia, Peran::Kandidat])
            ->mapWithKeys(fn (Peran $p) => [$p->value => $p->label()])->all();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('kelola-akun') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('role', array_keys(self::peranAkun()));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(100),
            TextInput::make('email')->label('Email')->email()->required()->maxLength(150)->unique(ignoreRecord: true),
            Select::make('role')->label('Peran')->options(self::peranAkun())->required()->live()
                // Admin tidak bisa menurunkan perannya sendiri (agar tidak terkunci).
                ->disabled(fn (?User $record) => $record?->is(auth()->user()) ?? false),
            Select::make('candidate_id')->label('Kandidat yang diwakili')
                ->options(fn () => Candidate::query()
                    ->where('election_id', Election::aktif()?->id)
                    ->orderBy('nomor_urut')->get()
                    ->mapWithKeys(fn (Candidate $c) => [$c->id => 'No. '.$c->nomor_urut.' · '.$c->namaTampil()]))
                ->visible(fn (Get $get) => $get('role') === Peran::Kandidat->value || $get('role') === Peran::Kandidat)
                ->required(fn (Get $get) => $get('role') === Peran::Kandidat->value || $get('role') === Peran::Kandidat)
                ->unique(ignoreRecord: true)
                ->validationMessages(['unique' => 'Kandidat ini sudah punya akun. Reset kata sandinya saja.'])
                ->helperText('Akun ini hanya bisa menanggapi pertanyaan untuk kandidat tersebut.'),
            \Filament\Forms\Components\Toggle::make('aktif')->label('Akun aktif')->default(true)->inline(false)
                ->helperText('Akun nonaktif tidak bisa masuk dan langsung keluar dari sesi yang berjalan.')
                ->disabled(fn (?User $record) => $record?->is(auth()->user()) ?? false),
            TextInput::make('password')->label(fn (string $operation) => $operation === 'create' ? 'Kata sandi' : 'Kata sandi baru')
                ->password()->revealable()
                ->rule(Password::min(8))
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn (?string $state) => filled($state))
                ->helperText(fn (string $operation) => $operation === 'edit' ? 'Isi hanya untuk mereset kata sandi. Sampaikan langsung ke pemilik akun.' : 'Minimal 8 karakter.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('kandidat'))
            ->columns([
                TextColumn::make('name')->label('Nama')->weight('bold')->searchable(['name', 'email'])->description(fn (User $r) => $r->email),
                TextColumn::make('role')->label('Peran')->badge()
                    ->formatStateUsing(fn (Peran $state) => $state->label())
                    ->color(fn (Peran $state) => match ($state) { Peran::Admin => 'danger', Peran::Panitia => 'warning', Peran::Kandidat => 'info', default => 'primary' }),
                TextColumn::make('kandidat.nomor_urut')->label('Kandidat')->formatStateUsing(fn ($state, User $r) => 'No. '.$state.' · '.$r->kandidat?->nama_ketua)->placeholder('—'),
                \Filament\Tables\Columns\IconColumn::make('aktif')->label('Aktif')->boolean(),
                TextColumn::make('terakhir_masuk_pada')->label('Terakhir masuk')->since()->sortable()->placeholder('Belum pernah'),
            ])
            ->filters([
                SelectFilter::make('role')->label('Peran')->options(self::peranAkun()),
                \Filament\Tables\Filters\TernaryFilter::make('aktif')->label('Status akun')->trueLabel('Aktif')->falseLabel('Nonaktif'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (User $record) => $record->is(auth()->user())),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
