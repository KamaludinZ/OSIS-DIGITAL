<?php

namespace App\Filament\Resources\Events\Tables;

use App\Filament\Resources\Events\Schemas\EventForm;
use App\Models\Event;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal_mulai')
            ->columns([
                TextColumn::make('nama')->label('Kegiatan')->weight('bold')->searchable(['nama', 'tempat']),
                TextColumn::make('tanggal_mulai')->label('Tanggal')->sortable()
                    ->formatStateUsing(fn (Event $record) => EventForm::rentang($record->tanggal_mulai, $record->tanggal_selesai)),
                TextColumn::make('tempat')->label('Tempat')
                    ->badge()
                    ->color(fn (Event $record) => self::adaBentrok($record) ? 'danger' : 'gray')
                    ->icon(fn (Event $record) => self::adaBentrok($record) ? 'heroicon-m-exclamation-triangle' : null)
                    ->tooltip(fn (Event $record) => self::adaBentrok($record) ? 'Bentrok: '.Event::bentrok($record->tanggal_mulai->toDateString(), $record->tanggal_selesai->toDateString(), $record->tempat, $record->id)->pluck('nama')->implode(', ') : null),
                TextColumn::make('divisi.nama')->label('Divisi')->placeholder('—'),
            ])
            ->filters([
                TernaryFilter::make('mendatang')
                    ->label('Waktu')
                    ->placeholder('Semua')
                    ->trueLabel('Mendatang')
                    ->falseLabel('Sudah lewat')
                    ->default(true)
                    ->queries(
                        true: fn (Builder $query) => $query->whereDate('tanggal_selesai', '>=', today()),
                        false: fn (Builder $query) => $query->whereDate('tanggal_selesai', '<', today()),
                    ),
                SelectFilter::make('divisi_id')->label('Divisi')->relationship('divisi', 'nama'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->searchPlaceholder('Cari kegiatan')
            ->emptyStateHeading('Belum ada kegiatan terjadwal');
    }

    private static function adaBentrok(Event $record): bool
    {
        return Event::bentrok($record->tanggal_mulai->toDateString(), $record->tanggal_selesai->toDateString(), $record->tempat, $record->id)->isNotEmpty();
    }
}
