<?php

namespace App\Filament\Resources\Announcements;

use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Models\Announcement;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Pengumuman OSIM untuk Beranda: draf, tayang sekarang, atau terjadwal. */
class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|\UnitEnum|null $navigationGroup = 'Publikasi';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'pengumuman';

    protected static ?string $modelLabel = 'pengumuman';

    protected static ?string $pluralModelLabel = 'pengumuman';

    protected static ?string $navigationLabel = 'Pengumuman';

    protected static ?string $recordTitleAttribute = 'judul';

    public const KATEGORI = ['Umum', 'Kegiatan', 'Organisasi', 'Pemilihan', 'Keagamaan', 'Sosial', 'Prestasi'];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('kelola-publikasi') ?? false;
    }

    /** draf | terjadwal | tayang */
    public static function status(Announcement $a): string
    {
        return match (true) {
            ! $a->dipublikasikan => 'draf',
            $a->dipublikasikan_pada && $a->dipublikasikan_pada->isFuture() => 'terjadwal',
            default => 'tayang',
        };
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Isi pengumuman')->columnSpan(2)->schema([
                TextInput::make('judul')->label('Judul')->required()->maxLength(150),
                TextInput::make('ringkasan')->label('Ringkasan singkat')->maxLength(200)
                    ->helperText('Tampil di kartu Beranda dan pemberitahuan. Kosongkan untuk memakai awal isi.'),
                Textarea::make('isi')->label('Isi')->required()->rows(12)->maxLength(10000)
                    ->helperText('Pisahkan paragraf dengan satu baris kosong.'),
            ]),
            Section::make('Penayangan')->columnSpan(1)->schema([
                Select::make('kategori')->label('Kategori')->options(array_combine(self::KATEGORI, self::KATEGORI))->default('Umum')->required(),
                TextInput::make('penulis_label')->label('Ditulis oleh')->placeholder('Contoh: Divisi Keagamaan')->maxLength(100)
                    ->helperText('Kosongkan untuk memakai nama akunmu.'),
                Toggle::make('dipublikasikan')->label('Tayangkan')->live()
                    ->helperText('Mati = tersimpan sebagai draf, tidak terlihat siswa.'),
                DateTimePicker::make('dipublikasikan_pada')->label('Jadwal tayang')->seconds(false)
                    ->visible(fn (Get $get) => (bool) $get('dipublikasikan'))
                    ->helperText('Kosongkan untuk tayang sekarang. Isi tanggal mendatang untuk menjadwalkan.'),
            ]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query->orderByRaw('coalesce(dipublikasikan_pada, created_at) desc'))
            ->columns([
                TextColumn::make('judul')->label('Pengumuman')->weight('bold')->searchable(['judul', 'ringkasan', 'isi'])->wrap()
                    ->description(fn (Announcement $r) => str($r->ringkasan ?: $r->isi)->squish()->limit(90)),
                TextColumn::make('kategori')->label('Kategori')->badge()->color('gray'),
                TextColumn::make('status')->label('Status')->badge()
                    ->state(fn (Announcement $r) => self::status($r))
                    ->formatStateUsing(fn (string $state) => ['draf' => 'Draf', 'terjadwal' => 'Terjadwal', 'tayang' => 'Tayang'][$state])
                    ->color(fn (string $state) => ['draf' => 'gray', 'terjadwal' => 'warning', 'tayang' => 'success'][$state]),
                TextColumn::make('dipublikasikan_pada')->label('Tayang')->dateTime('j M Y, H:i')->placeholder('Saat ditayangkan')->sortable(),
                TextColumn::make('penulis_label')->label('Penulis')->state(fn (Announcement $r) => $r->penulis_label ?: ($r->penulis?->name ?? '—'))->toggleable(),
            ])
            ->filters([
                SelectFilter::make('kategori')->options(array_combine(self::KATEGORI, self::KATEGORI)),
                SelectFilter::make('status')->label('Status')->options(['draf' => 'Draf', 'terjadwal' => 'Terjadwal', 'tayang' => 'Tayang'])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'draf' => $query->where('dipublikasikan', false),
                        'terjadwal' => $query->where('dipublikasikan', true)->where('dipublikasikan_pada', '>', now()),
                        'tayang' => $query->tayang(),
                        default => $query,
                    }),
            ])
            ->recordActions([
                Action::make('tayangkan')->label('Tayangkan sekarang')->icon(Heroicon::OutlinedPaperAirplane)->color('success')
                    ->visible(fn (Announcement $r) => self::status($r) !== 'tayang')
                    ->requiresConfirmation()
                    ->modalDescription('Pengumuman langsung tampil di Beranda dan pemberitahuan dikirim.')
                    ->action(function (Announcement $r) {
                        $r->update(['dipublikasikan' => true, 'dipublikasikan_pada' => now()]);
                        Notification::make()->title('Pengumuman ditayangkan')->success()->send();
                    }),
                Action::make('lihat')->label('Lihat')->icon(Heroicon::OutlinedEye)->color('gray')
                    ->visible(fn (Announcement $r) => self::status($r) === 'tayang')
                    ->url(fn (Announcement $r) => route('pengumuman.detail', $r->id), shouldOpenInNewTab: true),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
