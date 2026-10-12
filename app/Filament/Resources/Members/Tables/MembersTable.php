<?php

namespace App\Filament\Resources\Members\Tables;

use App\Models\Member;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('divisi')->orderByRaw('divisi_id is not null')->orderBy('divisi_id')->orderBy('urutan')->orderBy('nama'))
            ->columns([
                TextColumn::make('nama')->label('Nama')->weight('bold')->searchable(['nama', 'jabatan', 'kelas']),
                TextColumn::make('kelas')->label('Kelas')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('jabatan')->label('Jabatan')->badge()
                    ->color(fn (Member $r) => $r->divisi_id === null ? 'primary' : ($r->jabatan === 'Koordinator' ? 'warning' : 'gray')),
                TextColumn::make('divisi.nama')->label('Divisi')->placeholder('Pengurus Inti')->searchable(),
                TextColumn::make('periode')->label('Periode')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('periode')->label('Periode')
                    ->options(fn () => Member::query()->distinct()->orderByDesc('periode')->pluck('periode', 'periode')->put(Member::periodeAktif(), Member::periodeAktif())->all())
                    ->default(Member::periodeAktif()),
                SelectFilter::make('divisi_id')->label('Divisi')->relationship('divisi', 'nama'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->searchPlaceholder('Cari anggota')
            ->emptyStateHeading('Belum ada anggota pengurus')
            ->emptyStateDescription('Tambahkan pengurus inti dan anggota tiap divisi untuk periode ini.');
    }
}
