<?php

namespace App\Filament\Resources\Members;

use App\Filament\Resources\Members\Pages\CreateMember;
use App\Filament\Resources\Members\Pages\EditMember;
use App\Filament\Resources\Members\Pages\ListMembers;
use App\Filament\Resources\Members\Schemas\MemberForm;
use App\Filament\Resources\Members\Tables\MembersTable;
use App\Models\Member;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Organisasi';

    protected static ?int $navigationSort = 0;

    protected static ?string $modelLabel = 'anggota';

    protected static ?string $pluralModelLabel = 'anggota pengurus';

    protected static ?string $navigationLabel = 'Anggota';

    protected static ?string $slug = 'anggota';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function getGloballySearchableAttributes(): array
    {
        return ['nama', 'jabatan', 'kelas', 'divisi.nama'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Jabatan' => $record->jabatan,
            'Divisi' => $record->divisi?->nama ?? 'Pengurus Inti',
        ];
    }

    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('divisi')->where('periode', \App\Models\Member::periodeAktif());
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('kelola-organisasi') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return MemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembersTable::configure($table);
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
            'index' => ListMembers::route('/'),
            'create' => CreateMember::route('/create'),
            'edit' => EditMember::route('/{record}/edit'),
        ];
    }
}
