<?php

namespace App\Filament\Pages;

use App\Models\OsisPeriod;
use App\Models\Suggestion;
use App\Services\LayananSaran;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Saran dari halaman publik Kotak Saran, per periode. */
class KotakSaran extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.kotak-saran';

    protected static ?string $slug = 'kotak-saran';

    protected static ?string $title = 'Kotak Saran';

    protected static ?string $navigationLabel = 'Kotak Saran';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string | UnitEnum | null $navigationGroup = 'Organisasi';

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('kelola-organisasi') ?? false;
    }

    /** Jumlah saran periode aktif yang belum ditanggapi. */
    public static function getNavigationBadge(): ?string
    {
        $belum = Suggestion::query()->belumDitanggapi()->count();

        return $belum ? (string) $belum : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function getSubheading(): ?string
    {
        return 'Saran siswa untuk OSIM. Hanya pengurus yang sedang menjabat yang bisa menanggapi.';
    }

    protected function getViewData(): array
    {
        $dipilih = (int) ($this->getTableFilterState('periode')['value'] ?? 0);

        return [
            'riwayat' => OsisPeriod::query()->has('suggestions')->orWhere('status', 'aktif')
                ->withCount(['suggestions', 'suggestions as ditanggapi_count' => fn ($q) => $q->whereNotNull('ditanggapi_pada')])
                ->orderByDesc('nama')->get()
                ->map(fn (OsisPeriod $p) => [
                    'id' => $p->id,
                    'periode' => $p->nama,
                    'aktif' => $p->adalahAktif(),
                    'dipilih' => $p->id === $dipilih,
                    'total' => $p->suggestions_count,
                    'ditanggapi' => $p->ditanggapi_count,
                ])->all(),
        ];
    }

    /** Pilih periode dari kartu riwayat = setel filter tabel. */
    public function lihatPeriode(int $periode): void
    {
        $this->tableFilters['periode']['value'] = (string) $periode;
        $this->resetPage();
    }

    public function table(Table $table): Table
    {
        return $table
            // Semua periode dibaca lewat filter; bawaan periode aktif.
            ->query(Suggestion::query()->semuaPeriode()->with(['penanggap', 'periode'])->latest()->latest('id'))
            ->columns([
                TextColumn::make('isi')->label('Saran')->wrap()->lineClamp(3)->weight('medium')
                    ->description(fn (Suggestion $record) => $record->tanggapan ? 'Tanggapan: '.str($record->tanggapan)->limit(80) : null),
                TextColumn::make('nama')->label('Pengirim')
                    ->state(fn (Suggestion $record) => $record->pengirim())
                    ->color(fn (Suggestion $record) => $record->anonim() ? 'gray' : null)
                    ->icon(fn (Suggestion $record) => $record->anonim() ? 'heroicon-m-eye-slash' : 'heroicon-m-user'),
                TextColumn::make('created_at')->label('Dikirim')->since()
                    ->tooltip(fn (Suggestion $record) => $record->created_at->translatedFormat('j F Y, H:i')),
                TextColumn::make('status')->label('Status')->badge()
                    ->state(fn (Suggestion $record) => $record->sudahDitanggapi() ? 'Ditanggapi' : 'Belum ditanggapi')
                    ->color(fn (string $state) => $state === 'Ditanggapi' ? 'success' : 'warning'),
            ])
            ->filters([
                SelectFilter::make('periode')->label('Periode')
                    ->options(fn () => OsisPeriod::query()->orderByDesc('nama')->pluck('nama', 'id')->all())
                    ->default(fn () => OsisPeriod::aktif()?->id)
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn ($q, $id) => $q->where('suggestions.periode_id', $id))),
                SelectFilter::make('status')->label('Status')
                    ->options(['belum' => 'Belum ditanggapi', 'sudah' => 'Ditanggapi'])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'belum' => $query->whereNull('ditanggapi_pada'),
                        'sudah' => $query->whereNotNull('ditanggapi_pada'),
                        default => $query,
                    }),
            ])
            ->recordActions([
                Action::make('lihat')
                    ->label('Riwayat')
                    ->icon(Heroicon::OutlinedClock)
                    ->color('gray')
                    ->modalHeading('Riwayat saran')
                    ->modalContent(fn (Suggestion $record) => view('filament.saran-detail', ['saran' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalWidth('lg'),
                Action::make('tanggapi')
                    ->label(fn (Suggestion $record) => $record->sudahDitanggapi() ? 'Ubah tanggapan' : 'Tanggapi')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->color(fn (Suggestion $record) => $record->sudahDitanggapi() ? 'gray' : 'primary')
                    // Hanya pengurus menjabat & saran periode aktif.
                    ->visible(fn (Suggestion $record) => auth()->user()?->can('tanggapi-saran', $record) ?? false)
                    ->modalHeading('Tanggapi saran')
                    ->modalDescription(fn (Suggestion $record) => '"'.str($record->isi)->limit(160).'" — '.$record->pengirim())
                    ->modalSubmitActionLabel('Kirim tanggapan')
                    ->fillForm(fn (Suggestion $record) => ['tanggapan' => $record->tanggapan])
                    ->schema([
                        Textarea::make('tanggapan')->label('Tanggapan pengurus')->rows(4)
                            ->required()->minLength(5)->maxLength(1000)
                            ->placeholder('Contoh: Terima kasih, usulan ini kami bahas di rapat pleno Jumat ini.')
                            ->helperText('Tanggapan tercatat atas namamu dan tersimpan di riwayat periode ini.'),
                    ])
                    ->action(function (Suggestion $record, array $data) {
                        try {
                            app(LayananSaran::class)->tanggapi($record, $data['tanggapan'], auth()->user());
                            Notification::make()->title('Tanggapan terkirim')->success()->send();
                        } catch (AuthorizationException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->emptyStateHeading('Belum ada saran')
            ->emptyStateDescription('Saran dari halaman publik Kotak Saran akan muncul di sini.');
    }
}
