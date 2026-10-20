<?php

namespace App\Filament\Pages;

use App\Models\Candidate;
use App\Models\CandidateQuestion;
use App\Services\LayananPemilihan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/**
 * Antrean moderasi tanya jawab semua kandidat pada pemilihan aktif.
 * Panitia menampilkan/menyembunyikan; jawaban tetap hanya ditulis akun kandidat.
 */
class ModerasiTanyaJawab extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.moderasi-tanya-jawab';

    protected static ?string $slug = 'moderasi-tanya-jawab';

    protected static ?string $title = 'Moderasi Tanya Jawab';

    protected static ?string $navigationLabel = 'Moderasi Tanya Jawab';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string | UnitEnum | null $navigationGroup = 'Pemilihan';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('kelola-pemilihan') ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Pertanyaan langsung tampil di profil kandidat; sembunyikan yang tidak sopan atau di luar topik. Jawaban ditulis oleh akun kandidat.';
    }

    public function table(Table $table): Table
    {
        $idPemilihan = app(LayananPemilihan::class)->pemilihanAktif()?->id;

        return $table
            ->query(CandidateQuestion::query()->with(['candidate', 'voter', 'penanya'])
                ->whereHas('candidate', fn ($q) => $q->where('election_id', $idPemilihan)))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('pertanyaan')->label('Pertanyaan')->wrap()->searchable()
                    ->description(fn (CandidateQuestion $r) => ($r->voter?->nama ?? $r->penanya?->name ?? 'Penanya').' · '.$r->created_at->diffForHumans()),
                TextColumn::make('candidate.nomor_urut')->label('Kandidat')->formatStateUsing(fn ($state) => 'No. '.$state)->sortable(),
                TextColumn::make('status_jawab')->label('Tanggapan')->badge()
                    ->state(fn (CandidateQuestion $r) => $r->jawaban ? 'Terjawab' : 'Belum dijawab')
                    ->color(fn (string $state) => $state === 'Terjawab' ? 'success' : 'warning'),
                TextColumn::make('status_tampil')->label('Status')->badge()
                    ->state(fn (CandidateQuestion $r) => $r->ditampilkan ? 'Tampil' : 'Disembunyikan')
                    ->color(fn (string $state) => $state === 'Tampil' ? 'gray' : 'danger'),
            ])
            ->filters([
                TernaryFilter::make('ditampilkan')->label('Status')->trueLabel('Tampil')->falseLabel('Disembunyikan'),
                TernaryFilter::make('jawaban')->label('Tanggapan')->nullable()->trueLabel('Terjawab')->falseLabel('Belum dijawab'),
                SelectFilter::make('candidate_id')->label('Kandidat')
                    ->options(fn () => Candidate::query()->where('election_id', $idPemilihan)->orderBy('nomor_urut')->get()
                        ->mapWithKeys(fn (Candidate $c) => [$c->id => 'No. '.$c->nomor_urut.' · '.$c->namaTampil()])->all()),
            ])
            ->recordActions([
                Action::make('setujui')->label('Tampilkan')->icon(Heroicon::OutlinedEye)->color('success')
                    ->visible(fn (CandidateQuestion $r) => ! $r->ditampilkan)
                    ->authorize(fn (CandidateQuestion $r) => auth()->user()->can('update', $r))
                    ->action(fn (CandidateQuestion $r) => $r->update(['ditampilkan' => true])),
                Action::make('tolak')->label('Sembunyikan')->icon(Heroicon::OutlinedEyeSlash)->color('danger')
                    ->visible(fn (CandidateQuestion $r) => $r->ditampilkan)
                    ->authorize(fn (CandidateQuestion $r) => auth()->user()->can('update', $r))
                    ->requiresConfirmation()
                    ->modalDescription('Pertanyaan tidak lagi tampil di profil kandidat. Bisa ditampilkan kembali kapan saja.')
                    ->action(fn (CandidateQuestion $r) => $r->update(['ditampilkan' => false])),
            ])
            ->toolbarActions([
                BulkAction::make('sembunyikan')->label('Sembunyikan terpilih')->icon(Heroicon::OutlinedEyeSlash)
                    ->action(fn (Collection $records) => $records->each->update(['ditampilkan' => false]))
                    ->deselectRecordsAfterCompletion(),
            ])
            ->emptyStateHeading('Belum ada pertanyaan')
            ->emptyStateDescription('Pertanyaan dari profil kandidat akan muncul di sini.');
    }
}
