<?php

use App\Exceptions\SuaraDitolak;
use App\Models\Election;
use App\Services\LayananPemilihan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] #[Title('Bilik Suara')] class extends Component {
    public ?array $pemilihan = null;

    public array $kandidat = [];

    public function mount(LayananPemilihan $layanan): void
    {
        $this->pemilihan = $layanan->ringkasanPemilihan();
        $this->kandidat = $layanan->daftarKandidat();
        $this->panduan = ! session('panduan_dilewati', false);

        if (auth()->guest()) {
            // Setelah masuk, kembalikan siswa ke bilik suara.
            session(['url.intended' => route('bilik-suara')]);
        }
    }

    /** Hasil verifikasi kelayakan pemilih untuk pengguna saat ini. */
    #[Computed]
    public function kelayakan(): array
    {
        $pemilihan = $this->pemilihan ? Election::find($this->pemilihan['id']) : null;

        return app(LayananPemilihan::class)->kelayakan(auth()->user(), $pemilihan);
    }

    public function bolehMemilih(): bool
    {
        return $this->bisaMemilih() && $this->kelayakan['boleh'];
    }

    /** Panduan cara memilih tampil sebelum daftar kandidat (bisa dilewati). */
    public bool $panduan = true;

    public function tutupPanduan(): void
    {
        $this->panduan = false;
        session(['panduan_dilewati' => true]);
    }

    public function bukaPanduan(): void
    {
        $this->panduan = true;
    }

    /** ID kandidat yang sedang dipilih (belum dikirim). */
    public ?int $dipilih = null;

    public function pilih(int $id): void
    {
        if (! $this->bolehMemilih() || ! collect($this->kandidat)->contains('id', $id)) {
            return;
        }

        // Tekan kandidat yang sama sekali lagi untuk membatalkan pilihan.
        $this->dipilih = $this->dipilih === $id ? null : $id;
        $this->resetErrorBag('dipilih');
    }

    /** Dialog konfirmasi "Apakah Anda yakin…?" sedang terbuka. */
    public bool $konfirmasi = false;

    /** Suara sudah tersimpan di database. */
    public bool $terkirim = false;

    public function bukaKonfirmasi(): void
    {
        if (! $this->bolehMemilih() || $this->terkirim) {
            return;
        }

        if ($this->calonTerpilih() === null) {
            $this->addError('dipilih', 'Pilih salah satu pasangan calon terlebih dahulu.');

            return;
        }

        $this->konfirmasi = true;
    }

    public function kirimSuara(): void
    {
        if ($this->terkirim || ! $this->konfirmasi) {
            return;
        }

        unset($this->kelayakan);

        if (! $this->bolehMemilih()) {
            $this->konfirmasi = false;
            $this->addError('dipilih', $this->kelayakan['pesan']);

            return;
        }

        if ($this->calonTerpilih() === null) {
            $this->konfirmasi = false;
            $this->addError('dipilih', 'Pilihan tidak ditemukan. Silakan pilih ulang.');

            return;
        }

        try {
            $suara = app(LayananPemilihan::class)->kirimSuara(
                auth()->user(),
                Election::findOrFail($this->pemilihan['id']),
                $this->dipilih,
            );
        } catch (SuaraDitolak $e) {
            $this->konfirmasi = false;
            $this->addError('dipilih', $e->getMessage());
            unset($this->kelayakan);

            return;
        }

        $this->konfirmasi = false;
        $this->terkirim = true;

        $this->redirectRoute('bilik-suara.bukti', navigate: true);
    }

    public function langkahAktif(): int
    {
        return match (true) {
            $this->terkirim => 2,
            $this->konfirmasi => 1,
            default => 0,
        };
    }

    public function calonTerpilih(): ?array
    {
        return collect($this->kandidat)->firstWhere('id', $this->dipilih);
    }

    public function bisaMemilih(): bool
    {
        return ($this->pemilihan['status'] ?? null) === 'berjalan';
    }
}; ?>

@php
    use Illuminate\Support\Carbon;

    $status = $pemilihan['status'] ?? null;
    $langkah = ['Pilih kandidat', 'Konfirmasi', 'Suara tercatat'];
@endphp

<div>
    @if (! $this->bisaMemilih())
        {{-- Pemilihan belum tersedia / sudah ditutup --}}
        @php
            $pesan = match ($status) {
                'draft' => [
                    'ikon' => 'clock',
                    'judul' => 'Pemilihan belum dibuka',
                    'isi' => 'Bilik suara akan dibuka pada '.Carbon::parse($pemilihan['mulai'])->translatedFormat('l, j F Y \p\u\k\u\l H:i').' WIB. Sambil menunggu, kenali dulu para pasangan calon.',
                ],
                'selesai' => [
                    'ikon' => 'lock-closed',
                    'judul' => 'Pemilihan sudah ditutup',
                    'isi' => 'Terima kasih atas partisipasinya. Hasil pemilihan akan diumumkan oleh panitia.',
                ],
                default => [
                    'ikon' => 'calendar',
                    'judul' => 'Belum ada pemilihan',
                    'isi' => 'Saat ini tidak ada pemilihan ketua OSIM yang dijadwalkan. Pantau beranda untuk pengumuman dari panitia.',
                ],
            };
        @endphp

        <x-osim.pita class="pt-6 sm:pt-10 pb-24">
            <div class="flex flex-col items-start gap-3" role="status">
                <span class="flex size-12 items-center justify-center rounded-xl bg-white/15 text-emas-300">
                    <flux:icon :name="$pesan['ikon']" class="size-7" />
                </span>
                <h1 class="font-display text-4xl font-black leading-none sm:text-5xl">{{ $pesan['judul'] }}</h1>
                @if ($pemilihan)
                    <p class="font-semibold text-emas-300">{{ $pemilihan['judul'] }}</p>
                @endif
                <p class="max-w-xl text-hijau-50">{{ $pesan['isi'] }}</p>
                <a href="{{ route('home') }}" wire:navigate class="mt-2 inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 font-bold text-hijau-800 hover:bg-hijau-50">
                    <flux:icon.arrow-left variant="micro" /> Kembali ke Beranda
                </a>
            </div>
        </x-osim.pita>

        @if ($status === 'draft')
            <x-osim.wadah class="-mt-14">
                <section aria-labelledby="judul-kandidat">
                    <x-osim.tab id="judul-kandidat" warna="putih" ikon="user-group">Kenali Pasangan Calon</x-osim.tab>
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach (collect($kandidat)->sortBy('nomor_urut') as $calon)
                            <x-kartu-kandidat :calon="$calon" wire:key="kandidat-{{ $calon['id'] }}">
                                <a href="{{ route('kandidat.profil', ['id' => $calon['id'], 'dari' => 'bilik-suara']) }}" wire:navigate class="inline-flex items-center gap-1.5 font-bold text-hijau-700 hover:underline">
                                    Profil &amp; visi misi <flux:icon.arrow-right variant="micro" />
                                </a>
                            </x-kartu-kandidat>
                        @endforeach
                    </div>
                </section>
            </x-osim.wadah>
        @endif
    @else
        {{-- Kepala bilik suara --}}
        <x-osim.pita class="pt-6 sm:pt-10 pb-20">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="font-display text-3xl font-black leading-tight sm:text-5xl">{{ $pemilihan['judul'] }}</h1>
                    <p class="mt-2 max-w-xl text-hijau-50">Pilih satu pasangan calon. Suara bersifat rahasia dan tidak bisa diubah setelah dikirim.</p>
                </div>
                <div class="flex shrink-0 items-center gap-3 rounded-xl bg-hijau-900/40 px-4 py-2.5 ring-1 ring-white/15">
                    <flux:icon.clock variant="mini" class="text-emas-300" />
                    <div>
                        <x-osim.label class="text-hijau-200">Ditutup pukul</x-osim.label>
                        <div class="font-display angka text-xl font-black">{{ Carbon::parse($pemilihan['selesai'])->format('H:i') }} WIB</div>
                    </div>
                </div>
            </div>

            {{-- Langkah memilih --}}
            <ol class="mt-6 grid grid-cols-3 gap-2">
                @foreach ($langkah as $i => $teks)
                    @php($aktif = $i === $this->langkahAktif())
                    @php($lewat = $i < $this->langkahAktif())
                    <li @class([
                        'flex flex-col items-center gap-1 rounded-xl px-2 py-2.5 text-center text-xs font-bold sm:flex-row sm:gap-2.5 sm:px-3 sm:text-left sm:text-sm',
                        'bg-emas-400 text-hijau-950' => $aktif,
                        'bg-white/15 text-white' => $lewat,
                        'bg-hijau-900/30 text-hijau-100' => ! $aktif && ! $lewat,
                    ]) @if ($aktif) aria-current="step" @endif>
                        <span @class(['font-display angka flex size-7 shrink-0 items-center justify-center rounded-full text-sm font-black', 'bg-hijau-950 text-emas-300' => $aktif, 'bg-white/20' => ! $aktif])>
                            @if ($lewat) <flux:icon.check variant="micro" /> @else {{ $i + 1 }} @endif
                        </span>
                        <span class="leading-tight">{{ $teks }}</span>
                    </li>
                @endforeach
            </ol>
        </x-osim.pita>

        <x-osim.wadah class="-mt-12 flex flex-col gap-6">
            @if ($panduan)
                {{-- Panduan cara memilih --}}
                <section aria-labelledby="judul-panduan" class="overflow-hidden rounded-[14px] bg-white shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis">
                    <div class="flex items-start justify-between gap-4 p-5 sm:p-6">
                        <div>
                            <h2 id="judul-panduan" class="font-display text-2xl font-black text-tinta sm:text-3xl">Cara memilih</h2>
                            <p class="mt-1 text-tinta-2">Baru pertama kali? Ikuti empat langkah singkat ini.</p>
                        </div>
                        <button type="button" wire:click="tutupPanduan" class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-bold text-tinta-2 hover:bg-hijau-50 hover:text-hijau-800">Lewati</button>
                    </div>

                    <ol class="grid gap-px bg-garis sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            ['user-group', 'Kenali kandidat', 'Lihat foto, nomor urut, dan buka profil untuk membaca visi misi.'],
                            ['cursor-arrow-rays', 'Pilih satu nomor', 'Tekan tombol “Pilih” pada satu pasangan calon. Tekan lagi untuk membatalkan.'],
                            ['shield-check', 'Konfirmasi', 'Periksa kembali pilihanmu di layar konfirmasi, lalu tekan “Kirim Suara”.'],
                            ['check-badge', 'Simpan bukti', 'Suara langsung tercatat dan terkunci. Kamu akan mendapat kode bukti.'],
                        ] as $i => [$ikon, $judul, $isi])
                            <li class="flex gap-4 bg-white p-5">
                                <span class="font-display angka text-5xl font-black leading-none text-hijau-200">{{ $i + 1 }}</span>
                                <div>
                                    <div class="flex items-center gap-1.5 font-bold text-tinta"><flux:icon :name="$ikon" variant="mini" class="text-hijau-600" /> {{ $judul }}</div>
                                    <p class="mt-1 text-sm text-tinta-2">{{ $isi }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    <div class="flex justify-end border-t border-dashed border-garis p-5">
                        <button type="button" wire:click="tutupPanduan" class="inline-flex items-center gap-2 rounded-xl bg-emas-400 px-5 py-3 font-bold text-hijau-950 shadow-[0_4px_10px_-3px_var(--color-emas-500)] hover:bg-emas-300 active:translate-y-0.5">
                            Mengerti, mulai memilih <flux:icon.arrow-right variant="micro" />
                        </button>
                    </div>
                </section>
            @else
                {{-- Hasil verifikasi kelayakan pemilih --}}
                @unless ($this->kelayakan['boleh'])
                    @php($sudah = $this->kelayakan['alasan'] === 'sudah_memilih')
                    <div role="status" @class([
                        'flex flex-col gap-3 rounded-[14px] p-5 shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] sm:flex-row sm:items-center sm:justify-between',
                        'bg-white ring-2 ring-hijau-500' => $sudah,
                        'bg-emas-200 ring-1 ring-emas-400' => ! $sudah,
                    ])>
                        <p class="flex items-start gap-2.5 font-bold text-hijau-950">
                            <flux:icon :name="$sudah ? 'check-badge' : 'lock-closed'" variant="mini" class="mt-0.5 shrink-0 {{ $sudah ? 'text-hijau-600' : '' }}" />
                            {{ $this->kelayakan['pesan'] }}
                        </p>
                        @switch($this->kelayakan['alasan'])
                            @case('belum_masuk')
                                <a href="{{ route('login') }}" wire:navigate class="shrink-0 rounded-xl bg-hijau-600 px-5 py-2.5 text-center font-bold text-white hover:bg-hijau-700">Masuk</a>
                                @break
                            @case('sudah_memilih')
                                <a href="{{ route('bilik-suara.bukti') }}" wire:navigate class="shrink-0 rounded-xl bg-hijau-600 px-5 py-2.5 text-center font-bold text-white hover:bg-hijau-700">Lihat bukti</a>
                                @break
                        @endswitch
                    </div>
                @endunless

                {{-- Daftar pasangan calon --}}
                <section aria-labelledby="judul-kandidat" @class(['pt-0' => true])>
                    <div class="flex items-end justify-between gap-3">
                        <x-osim.tab id="judul-kandidat" warna="putih" ikon="user-group" class="whitespace-nowrap">Pasangan Calon ({{ count($kandidat) }})</x-osim.tab>
                        <button type="button" wire:click="bukaPanduan" class="mb-1 inline-flex items-center gap-1 rounded-lg px-2 py-1 text-sm font-bold text-white hover:bg-white/10 lg:text-hijau-50">
                            <flux:icon.question-mark-circle variant="micro" /> <span class="sm:hidden">Panduan</span><span class="hidden sm:inline">Lihat panduan cara memilih</span>
                        </button>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3" role="radiogroup" aria-label="Pilih pasangan calon">
                        @foreach (collect($kandidat)->sortBy('nomor_urut') as $calon)
                            @php($terpilih = $dipilih === $calon['id'])
                            <x-kartu-kandidat :calon="$calon" :terpilih="$terpilih" wire:key="kandidat-{{ $calon['id'] }}">
                                <div class="flex flex-col gap-2">
                                    @if ($this->kelayakan['boleh'])
                                        <button type="button" wire:click="pilih({{ $calon['id'] }})" role="radio" aria-checked="{{ $terpilih ? 'true' : 'false' }}"
                                                @class([
                                                    'flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3 text-base font-black transition active:translate-y-0.5',
                                                    'bg-hijau-600 text-white shadow-[0_4px_10px_-3px_var(--color-hijau-900)]' => $terpilih,
                                                    'bg-white text-hijau-800 ring-2 ring-hijau-600 hover:bg-hijau-50' => ! $terpilih,
                                                ])>
                                            @if ($terpilih) <flux:icon.check-circle variant="mini" /> @endif
                                            {{ $terpilih ? 'Nomor '.$calon['nomor_urut'].' dipilih' : 'Pilih nomor '.$calon['nomor_urut'] }}
                                        </button>
                                    @endif
                                    <a href="{{ route('kandidat.profil', ['id' => $calon['id'], 'dari' => 'bilik-suara']) }}" wire:navigate class="inline-flex items-center justify-center gap-1 py-1 text-sm font-bold text-hijau-700 hover:underline">
                                        Profil &amp; visi misi <flux:icon.arrow-right variant="micro" />
                                    </a>
                                </div>
                            </x-kartu-kandidat>
                        @endforeach
                    </div>
                </section>

                {{-- Ringkasan pilihan aktif --}}
                @if ($this->kelayakan['boleh'])
                    <div class="sticky bottom-24 z-30 flex flex-col gap-3 rounded-[14px] bg-hijau-900 p-4 text-white shadow-[0_18px_40px_-12px_rgb(4_41_26/0.7)] sm:flex-row sm:items-center sm:justify-between lg:bottom-4" aria-live="polite">
                        @if ($calon = $this->calonTerpilih())
                            <div class="flex items-center gap-3">
                                <span class="font-display angka flex size-12 shrink-0 items-center justify-center rounded-xl bg-emas-400 text-3xl font-black text-hijau-950">{{ $calon['nomor_urut'] }}</span>
                                <div>
                                    <x-osim.label class="text-hijau-200">Pilihanmu</x-osim.label>
                                    <div class="font-bold leading-tight">Nomor {{ $calon['nomor_urut'] }} — {{ $calon['nama_ketua'] }} &amp; {{ $calon['nama_wakil'] }}</div>
                                </div>
                            </div>
                        @else
                            <div>
                                <p class="text-hijau-50">Belum ada pasangan calon yang dipilih. Tekan <strong class="text-white">Pilih</strong> pada salah satu kartu.</p>
                                @error('dipilih')
                                    <p class="mt-1 flex items-center gap-1.5 font-bold text-emas-300" role="alert">
                                        <flux:icon.exclamation-circle variant="micro" /> {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        @endif
                        <button type="button" wire:click="bukaKonfirmasi" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-emas-400 px-5 py-3 font-black text-hijau-950 shadow-[0_4px_10px_-3px_var(--color-emas-500)] hover:bg-emas-300 active:translate-y-0.5">
                            Lanjut ke konfirmasi <flux:icon.arrow-right variant="micro" />
                        </button>
                    </div>
                @endif

                <p class="flex items-center justify-center gap-1.5 text-sm font-semibold text-tinta-2">
                    <flux:icon.lock-closed variant="micro" /> Satu siswa hanya dapat memilih satu kali.
                </p>
            @endif
        </x-osim.wadah>

        {{-- Konfirmasi sebelum kirim suara --}}
        <flux:modal name="konfirmasi-suara" wire:model.self="konfirmasi" :dismissible="false" class="w-full max-w-md !p-0 overflow-hidden">
            @if ($calon = $this->calonTerpilih())
                <div class="osim">
                    <div class="bg-hijau-600 px-6 py-3 text-white">
                        <x-osim.label class="text-hijau-100">Surat Suara · Konfirmasi</x-osim.label>
                    </div>
                    <div class="p-6">
                        <h2 class="font-display text-2xl font-black text-tinta">Konfirmasi pilihan</h2>
                        <p class="mt-1 text-tinta-2">Apakah Anda yakin memilih <strong class="text-tinta">nomor {{ $calon['nomor_urut'] }}</strong>?</p>

                        <div class="mt-5 flex items-center gap-4">
                            <x-osim.pasfoto class="w-16 shrink-0" :foto="$calon['foto_url']" alt="" />
                            <span class="font-display angka flex h-24 w-20 shrink-0 items-center justify-center rounded-xl bg-hijau-50 text-7xl font-black text-hijau-700 ring-1 ring-garis">{{ $calon['nomor_urut'] }}</span>
                            <div class="min-w-0">
                                <div class="font-bold leading-tight text-tinta">{{ $calon['nama_ketua'] }}</div>
                                <div class="font-bold leading-tight text-tinta">&amp; {{ $calon['nama_wakil'] }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="osim-perforasi" style="--osim-takik: rgb(0 0 0 / 0.45)"></div>

                    <div class="space-y-4 p-6">
                        <p class="text-sm font-semibold text-tinta-2" wire:loading wire:target="kirimSuara" role="status">Mohon tunggu, jangan tutup atau muat ulang halaman.</p>
                        <p class="flex items-start gap-2 rounded-lg bg-emas-200 px-3 py-2 text-sm font-semibold text-hijau-950" wire:loading.remove wire:target="kirimSuara">
                            <flux:icon.exclamation-triangle variant="micro" class="mt-0.5 shrink-0" />
                            Suara yang sudah dikirim tidak bisa diubah atau dibatalkan.
                        </p>

                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <flux:modal.close>
                                <button type="button" class="w-full rounded-xl px-4 py-3 font-bold text-tinta-2 hover:bg-hijau-50 disabled:opacity-50 sm:w-auto" wire:loading.attr="disabled" wire:target="kirimSuara">Batal, pilih ulang</button>
                            </flux:modal.close>
                            <button type="button" wire:click="kirimSuara" wire:loading.attr="disabled" wire:target="kirimSuara"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emas-400 px-5 py-3 font-black text-hijau-950 shadow-[0_4px_10px_-3px_var(--color-emas-500)] hover:bg-emas-300 disabled:opacity-70 sm:w-auto">
                                <flux:icon.paper-airplane variant="mini" wire:loading.remove wire:target="kirimSuara" />
                                <flux:icon.arrow-path variant="mini" class="animate-spin" wire:loading wire:target="kirimSuara" />
                                <span wire:loading.remove wire:target="kirimSuara">Kirim Suara</span>
                                <span wire:loading wire:target="kirimSuara">Mengirim suara…</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </flux:modal>
    @endif
</div>
