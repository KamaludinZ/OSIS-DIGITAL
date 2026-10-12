<?php

namespace App\Filament\Resources\Candidates\RelationManagers;

use App\Models\CandidateQuestion;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

/**
 * Moderasi tanya jawab: panitia menyembunyikan pertanyaan yang tidak pantas.
 * Jawaban hanya ditulis akun kandidat sendiri, jadi tidak bisa diubah di sini.
 */
class PertanyaanRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Tanya jawab';

    protected static ?string $modelLabel = 'pertanyaan';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['voter', 'penanya']))
            ->columns([
                TextColumn::make('pertanyaan')->label('Pertanyaan')->wrap()->searchable()
                    ->description(fn (CandidateQuestion $r) => ($r->voter?->nama ?? $r->penanya?->name ?? 'Penanya').' · '.$r->created_at->diffForHumans()),
                TextColumn::make('jawaban')->label('Tanggapan kandidat')->wrap()->limit(140)->placeholder('Belum ditanggapi'),
                ToggleColumn::make('ditampilkan')->label('Tampil'),
            ])
            ->filters([
                TernaryFilter::make('ditampilkan')->label('Tampil di publik'),
                TernaryFilter::make('jawaban')->label('Tanggapan')->nullable()->trueLabel('Sudah ditanggapi')->falseLabel('Belum ditanggapi'),
            ])
            ->recordActions([DeleteAction::make()])
            ->toolbarActions([
                BulkAction::make('sembunyikan')->label('Sembunyikan')->icon(Heroicon::OutlinedEyeSlash)
                    ->action(fn (Collection $records) => $records->each->update(['ditampilkan' => false]))
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
