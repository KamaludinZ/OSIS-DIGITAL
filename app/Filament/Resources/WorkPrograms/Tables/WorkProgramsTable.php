<?php

namespace App\Filament\Resources\WorkPrograms\Tables;

use App\Filament\Resources\WorkPrograms\Schemas\WorkProgramForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class WorkProgramsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('nama')
                    ->label('Program')
                    ->weight('bold')
                    ->searchable(['nama', 'deskripsi'])
                    ->description(fn ($record) => $record->capaian ? 'Capaian: '.$record->capaian : null),
                TextColumn::make('divisi.nama')->label('Divisi')->sortable()->searchable(),
                SelectColumn::make('status')
                    ->label('Status')
                    ->options(WorkProgramForm::statusLabel())
                    ->selectablePlaceholder(false)
                    ->sortable(),
                ToggleColumn::make('unggulan')->label('Sorotan'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(WorkProgramForm::statusLabel()),
                SelectFilter::make('divisi_id')->label('Divisi')->relationship('divisi', 'nama'),
                TernaryFilter::make('unggulan')->label('Sorotan Beranda'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->searchPlaceholder('Cari program')
            ->emptyStateHeading('Belum ada program kerja');
    }
}
