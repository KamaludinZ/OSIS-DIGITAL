<?php

namespace App\Filament\Resources\WorkPrograms;

use App\Filament\Resources\WorkPrograms\Pages\CreateWorkProgram;
use App\Filament\Resources\WorkPrograms\Pages\EditWorkProgram;
use App\Filament\Resources\WorkPrograms\Pages\ListWorkPrograms;
use App\Filament\Resources\WorkPrograms\Schemas\WorkProgramForm;
use App\Filament\Resources\WorkPrograms\Tables\WorkProgramsTable;
use App\Models\WorkProgram;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorkProgramResource extends Resource
{
    protected static ?string $model = WorkProgram::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Organisasi';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'program kerja';

    protected static ?string $pluralModelLabel = 'program kerja';

    protected static ?string $navigationLabel = 'Program Kerja';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return WorkProgramForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkProgramsTable::configure($table);
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
            'index' => ListWorkPrograms::route('/'),
            'create' => CreateWorkProgram::route('/create'),
            'edit' => EditWorkProgram::route('/{record}/edit'),
        ];
    }
}
