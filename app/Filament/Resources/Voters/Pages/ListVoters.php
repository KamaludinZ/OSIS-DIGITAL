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
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListVoters extends ListRecords
{
    protected static string $resource = VoterResource::class;

    public function getSubheading(): ?string
    {
        $total = Voter::count();
        $berhak = Voter::terverifikasi()->count();
        $berkode = Voter::terverifikasi()->whereNotNull('kode_pilih')->count();

        return "{$total} pemilih terdaftar · {$berhak} terverifikasi · {$berkode} sudah punya kode pilih";
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

    private function aksiImpor(JenisPemilih $jenis): Action
    {
        $siswa = $jenis === JenisPemilih::Siswa;

        return Action::make('impor'.ucfirst($jenis->value))
            ->label('Impor data '.($siswa ? 'siswa' : 'GTK'))
            ->icon($siswa ? Heroicon::OutlinedAcademicCap : Heroicon::OutlinedBriefcase)
            ->modalHeading('Impor data '.($siswa ? 'siswa' : 'GTK'))
            ->modalSubmitActionLabel('Impor')
            ->schema([
                Text::make($siswa
                    ? 'Kolom: nisn, nama, kelas, tanggal_lahir (format 2013-05-21 atau 21/05/2013). Data dengan NISN yang sama akan diperbarui.'
                    : 'Kolom: nik, nama, jabatan, tanggal_lahir. Data dengan NIK yang sama akan diperbarui.'),
                Text::make(fn () => new \Illuminate\Support\HtmlString('<a class="font-bold text-primary-700 underline" href="/templat/pemilih-'.$jenis->value.'.csv" download>Unduh templat CSV</a>')),
                FileUpload::make('berkas')->label('Berkas CSV / Excel (.xlsx)')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->maxSize(5120)
                    ->storeFiles(false)
                    ->required(),
            ])
            ->action(function (array $data) use ($jenis) {
                /** @var TemporaryUploadedFile $berkas */
                $berkas = $data['berkas'];
                $hasil = app(ImporPemilih::class)->impor($berkas->getRealPath(), $jenis, $berkas->getClientOriginalName());

                $judul = "{$hasil['tambah']} ditambahkan, {$hasil['perbarui']} diperbarui";
                $notif = Notification::make()->title($judul);

                if ($hasil['gagal']) {
                    $notif->warning()->body(count($hasil['gagal']).' baris dilewati: '.implode('; ', array_slice($hasil['gagal'], 0, 5)).(count($hasil['gagal']) > 5 ? '; …' : ''))->persistent();
                } else {
                    $notif->success();
                }

                $notif->send();
            });
    }
}
