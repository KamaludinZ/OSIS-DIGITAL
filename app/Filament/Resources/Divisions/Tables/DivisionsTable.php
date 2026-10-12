<?php

namespace App\Filament\Resources\Divisions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DivisionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nama')
            ->columns([
                TextColumn::make('nama')
                    ->label('Divisi')
                    ->weight('bold')
                    ->searchable(['nama', 'deskripsi'])
                    ->sortable()
                    ->description(fn ($record) => str($record->deskripsi)->limit(70)),
                TextColumn::make('work_programs_count')
                    ->label('Program kerja')
                    ->counts('workPrograms')
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('events_count')
                    ->label('Kegiatan')
                    ->counts('events')
                    ->badge()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->searchPlaceholder('Cari divisi')
            ->emptyStateHeading('Belum ada divisi')
            ->emptyStateDescription('Tambahkan divisi OSIM pertama, misalnya Divisi Keagamaan.');
    }
}
