<?php

namespace App\Filament\Pages;

use App\Support\DataTiruanOrganisasi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Daftar anggota pengurus OSIM. Fase frontend: memakai data tiruan;
 * diganti Resource berbasis tabel members di layer backend.
 */
class Anggota extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.anggota';

    protected static ?string $title = 'Anggota Pengurus';

    protected static ?string $navigationLabel = 'Anggota';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string | UnitEnum | null $navigationGroup = 'Organisasi';

    protected static ?int $navigationSort = 0;

    public function table(Table $table): Table
    {
        $opsiDivisi = collect(DataTiruanOrganisasi::anggota())->pluck('divisi', 'divisi')->all();

        return $table
            ->records(fn (?string $search, array $filters): Collection => collect(DataTiruanOrganisasi::anggota())
                ->when(filled($search), fn ($c) => $c->filter(fn ($a) => Str::contains(Str::lower($a['nama'].' '.$a['kelas'].' '.$a['jabatan'].' '.$a['divisi']), Str::lower($search))))
                ->when(filled($d = $filters['divisi']['value'] ?? null), fn ($c) => $c->where('divisi', $d))
                ->keyBy('id'))
            ->columns([
                TextColumn::make('nama')->label('Nama')->weight('bold'),
                TextColumn::make('kelas')->label('Kelas')->badge()->color('gray'),
                TextColumn::make('jabatan')->label('Jabatan')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Anggota' => 'gray',
                        'Koordinator' => 'warning',
                        default => 'primary',
                    }),
                TextColumn::make('divisi')->label('Divisi'),
            ])
            ->filters([
                SelectFilter::make('divisi')->label('Divisi')->options($opsiDivisi),
            ])
            ->searchable()
            ->searchPlaceholder('Cari anggota')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Tidak ada anggota yang cocok');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tambah')
                ->label('Tambah anggota')
                ->icon(Heroicon::OutlinedUserPlus)
                ->modalHeading('Tambah anggota pengurus')
                ->modalSubmitActionLabel('Simpan')
                ->schema([
                    TextInput::make('nama')->label('Nama siswa')->required()->maxLength(100),
                    TextInput::make('kelas')->label('Kelas')->placeholder('Contoh: VIII-B')->required()->maxLength(10),
                    Select::make('divisi')->label('Divisi')->required()
                        ->options(collect(DataTiruanOrganisasi::divisi())->pluck('nama', 'nama')->prepend('Pengurus Inti', 'Pengurus Inti')),
                    TextInput::make('jabatan')->label('Jabatan')->default('Anggota')->required()->maxLength(50),
                ])
                ->action(fn (array $data) => Notification::make()
                    ->title('Data tiruan: '.$data['nama'].' belum tersimpan')
                    ->body('Penyimpanan anggota aktif setelah tabel anggota dibuat (layer backend).')
                    ->warning()
                    ->send()),
        ];
    }
}
