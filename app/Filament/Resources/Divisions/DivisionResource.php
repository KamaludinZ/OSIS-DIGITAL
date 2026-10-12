<?php

namespace App\Filament\Resources\Divisions;

use App\Filament\Resources\Divisions\Pages\CreateDivision;
use App\Filament\Resources\Divisions\Pages\EditDivision;
use App\Filament\Resources\Divisions\Pages\ListDivisions;
use App\Filament\Resources\Divisions\Schemas\DivisionForm;
use App\Filament\Resources\Divisions\Tables\DivisionsTable;
use App\Models\Division;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DivisionResource extends Resource
{
    protected static ?string $model = Division::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Organisasi';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'divisi';

    protected static ?string $pluralModelLabel = 'divisi';

    protected static ?string $navigationLabel = 'Divisi';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('kelola-organisasi') ?? false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nama', 'deskripsi'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return ['Tugas' => str($record->deskripsi)->limit(60)->toString()];
    }

    public static function form(Schema $schema): Schema
    {
        return DivisionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DivisionsTable::configure($table);
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
            'index' => ListDivisions::route('/'),
            'create' => CreateDivision::route('/create'),
            'edit' => EditDivision::route('/{record}/edit'),
        ];
    }
}
