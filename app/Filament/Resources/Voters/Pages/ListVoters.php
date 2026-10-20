<?php

namespace App\Filament\Resources\Voters\Pages;

use App\Enums\JenisPemilih;
use App\Filament\Resources\Voters\VoterResource;
use App\Models\Voter;
use App\Services\ImporPemilih;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListVoters extends ListRecords
{
    protected static string $resource = VoterResource::class;

    public function getSubheading(): ?string
    {
        $s = app(\App\Services\LayananPartisipasi::class)->statistik(app(\App\Services\LayananPemilihan::class)->pemilihanAktif());
        $menunggu = $s['menunggu'] ? " ({$s['menunggu']} menunggu)" : '';

        return "{$s['total']} pemilih terdaftar · {$s['terverifikasi']} terverifikasi{$menunggu} · {$s['berkode']} sudah punya kode pilih · {$s['memilih']} sudah memilih";
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                $this->aksiImpor(JenisPemilih::Siswa),
                $this->aksiImpor(JenisPemilih::Gtk),
            ])->label('Impor data')->icon(Heroicon::OutlinedArrowUpTray)->button()->color('gray'),

            Action::make('buatKode')
                ->label('Buat kode pilih')
                ->icon(Heroicon::OutlinedKey)
                ->schema([
                    Toggle::make('ulang')->label('Buat ulang SEMUA kode (kode lama tidak berlaku)')
                        ->helperText('Biarkan mati untuk hanya membuat kode bagi pemilih yang belum punya.'),
                ])
                ->modalDescription('Kode 6 digit dibuat untuk setiap pemilih terverifikasi.')
                ->action(function (array $data) {
                    $sasaran = Voter::terverifikasi()->when(! ($data['ulang'] ?? false), fn ($q) => $q->whereNull('kode_pilih'))->get();
                    $sasaran->each->beriKode();
                    Notification::make()->title($sasaran->count().' kode pilih dibuat')->success()->send();
                }),

            Action::make('cetakKartu')
                ->label('Cetak kartu pemilih')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->schema([
                    Select::make('kelompok')->label('Kelompok')->placeholder('Semua pemilih')
                        ->options(fn () => Voter::query()->whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas', 'kelas')->prepend('GTK', 'gtk')->all()),
                ])
                ->action(fn (array $data) => $this->redirect(route('kartu-pemilih', array_filter(['kelompok' => $data['kelompok'] ?? null])), navigate: false)),

            CreateAction::make()->label('Tambah pemilih'),
        ];
    }

    /** Dipanggil dari tombol di notifikasi hasil impor. */
    #[\Livewire\Attributes\On('buat-kode-pemilih-baru')]
    public function buatKodeUntukPemilihBaru(): void
    {
        $sasaran = Voter::terverifikasi()->whereNull('kode_pilih')->get();
        $sasaran->each->beriKode();
        Notification::make()->title($sasaran->count().' kode pilih dibuat')->success()->send();
    }

    private function aksiImpor(JenisPemilih $jenis): Action
    {
        $siswa = $jenis === JenisPemilih::Siswa;

        return Action::make('impor'.ucfirst($jenis->value))
            ->label('Impor data '.($siswa ? 'siswa' : 'GTK'))
            ->icon($siswa ? Heroicon::OutlinedAcademicCap : Heroicon::OutlinedBriefcase)
            ->modalHeading('Impor data '.($siswa ? 'siswa' : 'GTK'))
            ->modalSubmitActionLabel('Impor')
            ->schema([
                \Filament\Schemas\Components\View::make('filament.panduan-kolom-impor')->viewData(['jenis' => $jenis->value]),
                FileUpload::make('berkas')->label('Berkas CSV / Excel (.xlsx)')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->maxSize(5120)
                    ->storeFiles(false)
                    ->live()
                    ->required(),
                Text::make(fn (Get $get) => ($berkas = self::berkasDari($get('berkas')))
                    ? new \Illuminate\Support\HtmlString(view('filament.pratinjau-impor', [
                        'p' => app(ImporPemilih::class)->pratinjau($berkas->getRealPath(), $jenis, $berkas->getClientOriginalName()),
                        'jenis' => $jenis->value,
                    ])->render())
                    : '')
                    ->visible(fn (Get $get) => self::berkasDari($get('berkas')) !== null),
            ])
            ->action(function (array $data) use ($jenis) {
                $berkas = self::berkasDari($data['berkas']);
                $hasil = app(ImporPemilih::class)->impor($berkas->getRealPath(), $jenis, $berkas->getClientOriginalName());
                // Panel "Impor terakhir" muncul lagi untuk log baru ini.
                session()->forget('impor_pemilih_ditutup');

                $judul = "Impor selesai: {$hasil['tambah']} ditambahkan, {$hasil['perbarui']} diperbarui".($hasil['sama'] ? ", {$hasil['sama']} tidak berubah" : '');
                $tanpaKode = Voter::terverifikasi()->whereNull('kode_pilih')->count();
                $baris = [];

                if ($hasil['gagal']) {
                    $baris[] = count($hasil['gagal']).' baris dilewati: '.implode('; ', array_slice($hasil['gagal'], 0, 5)).(count($hasil['gagal']) > 5 ? '; …' : '');
                }
                if ($tanpaKode) {
                    $baris[] = "{$tanpaKode} pemilih terverifikasi belum punya kode pilih.";
                }

                $notif = Notification::make()
                    ->title($judul)
                    ->body($baris ? implode(' ', $baris) : null)
                    ->persistent();
                $hasil['gagal'] ? $notif->warning() : $notif->success();

                if ($tanpaKode) {
                    $notif->actions([
                        Action::make('buatKodeSekarang')->label('Buat kode pilih')->button()
                            ->dispatch('buat-kode-pemilih-baru'),
                    ]);
                }

                $notif->send();
            });
    }

    /** Log impor terakhir akun ini (dari voter_imports), kecuali sudah ditutup. */
    public static function ringkasanImporTerakhir(): ?array
    {
        $log = \App\Models\VoterImport::query()->where('user_id', auth()->id())->latest('id')->first();

        if (! $log || session('impor_pemilih_ditutup') === $log->id) {
            return null;
        }

        return [
            'tambah' => $log->tambah, 'perbarui' => $log->perbarui, 'sama' => $log->sama, 'gagal' => $log->galat ?? [],
            'jenis' => $log->jenis, 'berkas' => $log->nama_berkas, 'waktu' => $log->created_at->toDateTimeString(),
        ];
    }

    public function tutupRingkasanImpor(): void
    {
        session(['impor_pemilih_ditutup' => \App\Models\VoterImport::query()->where('user_id', auth()->id())->max('id')]);
    }

    /** Panel ringkasan impor terakhir di atas tabel pemilih. */
    public function content(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        $isi = parent::content($schema);

        return $isi->components([
            \Filament\Schemas\Components\View::make('filament.ringkasan-impor')
                ->viewData(fn () => ['ringkasan' => self::ringkasanImporTerakhir()]),
            ...$isi->getComponents(withHidden: true),
        ]);
    }

    /** Ambil berkas unggahan sementara dari state FileUpload (bisa berupa array). */
    public static function berkasDari(mixed $state): ?TemporaryUploadedFile
    {
        $berkas = is_array($state) ? collect($state)->first() : $state;

        return $berkas instanceof TemporaryUploadedFile ? $berkas : null;
    }
}
