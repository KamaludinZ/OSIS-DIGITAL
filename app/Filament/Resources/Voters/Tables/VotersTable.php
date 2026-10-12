<?php

namespace App\Filament\Resources\Voters\Tables;

use App\Filament\Resources\Voters\Schemas\VoterForm;
use App\Models\Voter;
use App\Services\LayananPemilihan;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class VotersTable
{
    public static function configure(Table $table): Table
    {
        $pemilihan = app(LayananPemilihan::class)->pemilihanAktif();

        return $table
            ->defaultSort('nama')
            ->poll('30s') // kolom "Sudah memilih" ikut berubah selama pemungutan suara
            ->modifyQueryUsing(fn (Builder $query) => $query->withExists(['votes as sudah_memilih' => fn ($v) => $v->where('election_id', $pemilihan?->id ?? 0)]))
            ->columns([
                TextColumn::make('nama')->label('Nama')->weight('bold')->searchable(['nama', 'nomor_induk', 'kelas', 'jabatan'])->sortable()
                    ->description(fn (Voter $r) => $r->jenis->labelNomorInduk().' '.$r->nomor_induk),
                TextColumn::make('jenis')->label('Jenis')->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->value === 'siswa' ? 'primary' : 'warning'),
                TextColumn::make('kelas')->label('Kelas / jabatan')->sortable()
                    ->state(fn (Voter $r) => $r->kelas ?? $r->jabatan ?? '—'),
                TextColumn::make('verifikasi')->label('Verifikasi')->badge()
                    ->formatStateUsing(fn (string $state) => VoterForm::$labelVerifikasi[$state] ?? $state)
                    ->color(fn (string $state) => ['terverifikasi' => 'success', 'menunggu' => 'warning', 'ditolak' => 'danger'][$state] ?? 'gray'),
                TextColumn::make('kode_pilih')->label('Kode pilih')
                    ->fontFamily('mono')->weight('bold')->copyable()->copyMessage('Kode disalin')
                    ->placeholder('Belum dibuat'),
                IconColumn::make('sudah_memilih')->label('Sudah memilih')->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckBadge)->falseIcon(Heroicon::OutlinedMinus)
                    ->trueColor('success')->falseColor('gray'),
            ])
            ->filters([
                SelectFilter::make('jenis')->label('Jenis')->options(['siswa' => 'Siswa', 'gtk' => 'GTK']),
                SelectFilter::make('tingkat')->label('Tingkat')
                    ->options(['VII' => 'Kelas VII', 'VIII' => 'Kelas VIII', 'IX' => 'Kelas IX'])
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn ($q, $t) => $q->where('kelas', 'like', $t.'-%'))),
                SelectFilter::make('verifikasi')->label('Verifikasi')->options(VoterForm::$labelVerifikasi),
                TernaryFilter::make('sudah_memilih')->label('Status memilih')->placeholder('Semua')
                    ->trueLabel('Sudah memilih')->falseLabel('Belum memilih')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('votes', fn ($v) => $v->where('election_id', $pemilihan?->id ?? 0)),
                        false: fn (Builder $query) => $query->whereDoesntHave('votes', fn ($v) => $v->where('election_id', $pemilihan?->id ?? 0)),
                    ),
                TernaryFilter::make('kode')->label('Kode pilih')->placeholder('Semua')
                    ->trueLabel('Sudah ada')->falseLabel('Belum dibuat')
                    ->queries(true: fn (Builder $query) => $query->whereNotNull('kode_pilih'), false: fn (Builder $query) => $query->whereNull('kode_pilih')),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                Action::make('verifikasiSatu')->label('Verifikasi')->icon(Heroicon::OutlinedShieldCheck)->color('success')
                    ->visible(fn (Voter $record) => $record->verifikasi !== 'terverifikasi')
                    ->action(function (Voter $record) {
                        $record->update(['verifikasi' => 'terverifikasi']);
                        Notification::make()->title($record->nama.' terverifikasi')->success()->send();
                    }),
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('tolak')->label('Tolak verifikasi')->icon(Heroicon::OutlinedNoSymbol)->color('danger')
                        ->visible(fn (Voter $record) => $record->verifikasi !== 'ditolak')
                        ->requiresConfirmation()
                        ->modalDescription('Pemilih yang ditolak tidak bisa memakai kode pilihnya.')
                        ->action(function (Voter $record) {
                            $record->update(['verifikasi' => 'ditolak']);
                            Notification::make()->title($record->nama.' ditolak')->warning()->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('verifikasi')->label('Ubah verifikasi')->icon(Heroicon::OutlinedShieldCheck)
                        ->schema([Select::make('verifikasi')->label('Status')->options(VoterForm::$labelVerifikasi)->required()])
                        ->action(function (Collection $records, array $data) {
                            $records->each->update(['verifikasi' => $data['verifikasi']]);
                            Notification::make()->title($records->count().' pemilih diubah menjadi "'.VoterForm::$labelVerifikasi[$data['verifikasi']].'"')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('kodeUlang')->label('Buat ulang kode pilih')->icon(Heroicon::OutlinedKey)
                        ->requiresConfirmation()
                        ->modalDescription('Kode lama tidak berlaku lagi. Kartu pemilih yang sudah dicetak untuk pemilih ini perlu dicetak ulang.')
                        ->action(function (Collection $records) {
                            $records->each->beriKode();
                            Notification::make()->title($records->count().' kode pilih dibuat ulang')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->searchPlaceholder('Cari nama, NISN, atau NIK')
            ->defaultPaginationPageOption(25)
            ->emptyStateIcon(Heroicon::OutlinedIdentification)
            ->emptyStateHeading(fn ($livewire) => filled($livewire->getTableSearch()) || filled(array_filter($livewire->getTableFiltersForm()->getRawState(), fn ($f) => filled(array_filter((array) $f, fn ($v) => $v !== null && $v !== ''))))
                ? 'Tidak ada pemilih yang cocok'
                : 'Belum ada data pemilih')
            ->emptyStateDescription(fn ($livewire) => filled($livewire->getTableSearch())
                ? 'Coba kata kunci lain atau hapus penyaring.'
                : 'Impor data siswa (NISN) dan GTK (NIK) dari CSV/Excel lewat tombol "Impor data" di atas, lalu buat kode pilih.')
            ->emptyStateActions([
                \Filament\Actions\Action::make('templatSiswa')->label('Templat siswa (CSV)')->icon(Heroicon::OutlinedArrowDownTray)->url('/templat/pemilih-siswa.csv')->color('gray'),
                \Filament\Actions\Action::make('templatGtk')->label('Templat GTK (CSV)')->icon(Heroicon::OutlinedArrowDownTray)->url('/templat/pemilih-gtk.csv')->color('gray'),
            ]);
    }
}
