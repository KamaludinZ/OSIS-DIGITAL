<?php

namespace App\Filament\Resources\Voters;

use App\Filament\Resources\Voters\Pages\CreateVoter;
use App\Filament\Resources\Voters\Pages\EditVoter;
use App\Filament\Resources\Voters\Pages\ListVoters;
use App\Filament\Resources\Voters\Schemas\VoterForm;
use App\Filament\Resources\Voters\Tables\VotersTable;
use App\Models\Voter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VoterResource extends Resource
{
    protected static ?string $model = Voter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|\UnitEnum|null $navigationGroup = 'Pemilihan';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'pemilih';

    protected static ?string $pluralModelLabel = 'data pemilih';

    protected static ?string $navigationLabel = 'Data Pemilih';

    protected static ?string $slug = 'data-pemilih';

    protected static ?string $recordTitleAttribute = 'nama';

    /** Data pemilih & kode pilih hanya untuk panitia dan admin. */
    public static function canAccess(): bool
    {
        return auth()->user()?->can('kelola-pemilih') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return VoterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VotersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVoters::route('/'),
            'create' => CreateVoter::route('/create'),
            'edit' => EditVoter::route('/{record}/edit'),
        ];
    }
}
