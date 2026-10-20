<?php

use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] #[Title('Kotak Saran')] class extends Component {
    public string $isi = '';

    /** false = anonim (bawaan); true = sertakan nama & kelas agar bisa dihubungi. */
    public bool $bernama = false;

    public string $nama = '';

    public string $kelas = '';

    #[\Livewire\Attributes\Locked]
    public bool $terkirim = false;

    #[\Livewire\Attributes\Locked]
    public string $periode = '';

    #[\Livewire\Attributes\Locked]
    public bool $dibuka = false;

    public function mount(): void
    {
        $aktif = \App\Models\OsisPeriod::aktif();
        $this->dibuka = $aktif !== null;
        $this->periode = $aktif?->nama ?? \App\Models\Member::periodeAktif();
    }

    public function kirim(\App\Services\LayananSaran $layanan): void
    {
        $this->terkirim = false;
        $this->validate(\App\Services\LayananSaran::aturan(), \App\Services\LayananSaran::pesan());

        // Cegah banjir kiriman dari satu perangkat/jaringan (tanpa menyimpan IP bersama saran).
        $kunci = 'kirim-saran:'.request()->ip();
        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            $this->addError('isi', 'Terlalu banyak saran dalam waktu singkat. Coba lagi '.ceil(RateLimiter::availableIn($kunci) / 60).' menit lagi.');

            return;
        }
        RateLimiter::hit($kunci, 600);

        try {
            $layanan->kirim(['isi' => $this->isi, 'bernama' => $this->bernama, 'nama' => $this->nama, 'kelas' => $this->kelas]);
        } catch (\App\Exceptions\PeriodeDitolak $e) {
            $this->addError('isi', $e->getMessage());

            return;
        }

        $this->reset('isi', 'nama', 'kelas', 'bernama');
        $this->terkirim = true;
    }
}; ?>

<div>
    <x-osim.pita class="pt-6 pb-24 sm:pt-10">
        <h1 class="font-display text-4xl font-black leading-[1.02] sm:text-5xl">Kotak Saran</h1>
        <p class="mt-3 max-w-xl text-hijau-50">Punya ide atau keluhan untuk OSIM periode <span class="font-bold text-emas-300">{{ $periode }}</span>? Sampaikan di sini, boleh tanpa nama.</p>
    </x-osim.pita>

    <x-osim.wadah class="-mt-16 grid max-w-5xl items-start gap-6 lg:grid-cols-[1fr_18rem]">
        <section aria-labelledby="judul-kirim" class="overflow-hidden rounded-[14px] bg-white shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis">
            <div class="flex items-center justify-between gap-3 bg-hijau-100 px-6 py-2.5">
                <x-osim.label class="text-hijau-800">Aspirasi Siswa</x-osim.label>
            </div>

            <form wire:submit="kirim" class="flex flex-col gap-4 p-6">
                <h2 id="judul-kirim" class="font-display text-2xl font-extrabold text-tinta">Tulis saranmu</h2>
                @unless ($dibuka)
                    <p class="rounded-lg bg-emas-300/40 px-3 py-2 text-sm font-semibold text-tinta ring-1 ring-garis">Kotak saran sedang ditutup karena kepengurusan baru belum aktif.</p>
                @endunless
                <div>
                    <label for="isi-saran" class="font-bold text-tinta">Isi saran</label>
                    <textarea id="isi-saran" wire:model="isi" rows="5" maxlength="1000"
                              placeholder="Contoh: Tolong tambah tempat sampah terpilah di dekat kantin."
                              class="mt-1 w-full rounded-xl border-2 border-garis bg-hijau-50/50 px-4 py-3 text-tinta placeholder:text-tinta-2/70 focus:border-hijau-600 focus:bg-white focus:outline-none"></textarea>
                    <p class="mt-1 text-right text-xs text-tinta-2" x-data x-text="($wire.isi?.length ?? 0) + '/1000'">0/1000</p>
                    @error('isi')
                        <p class="flex items-center gap-1.5 text-sm font-bold text-cap" role="alert"><flux:icon.exclamation-circle variant="micro" /> {{ $message }}</p>
                    @enderror
                </div>

                <fieldset class="flex flex-col gap-3">
                    <legend class="font-bold text-tinta">Kirim sebagai</legend>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ([0 => ['eye-slash', 'Anonim'], 1 => ['user', 'Dengan nama']] as $nilai => [$ikon, $label])
                            <label @class(['flex cursor-pointer items-center justify-center gap-1.5 rounded-xl px-3 py-2.5 text-sm font-bold ring-1', 'bg-hijau-600 text-white ring-hijau-600' => (int) $bernama === $nilai, 'bg-white text-hijau-800 ring-garis hover:bg-hijau-50' => (int) $bernama !== $nilai])>
                                <input type="radio" wire:model.live="bernama" value="{{ $nilai }}" class="sr-only" />
                                <flux:icon :name="$ikon" variant="micro" /> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @if ($bernama)
                        <div class="grid gap-3 sm:grid-cols-[1fr_8rem]">
                            <div>
                                <label for="nama-pengirim" class="text-sm font-bold text-tinta">Nama</label>
                                <input id="nama-pengirim" wire:model="nama" type="text" maxlength="100" autocomplete="name"
                                       class="mt-1 h-11 w-full rounded-xl border-2 border-garis bg-hijau-50/50 px-3 text-tinta focus:border-hijau-600 focus:bg-white focus:outline-none" />
                                @error('nama') <p class="mt-1 text-sm font-bold text-cap" role="alert">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="kelas-pengirim" class="text-sm font-bold text-tinta">Kelas</label>
                                <input id="kelas-pengirim" wire:model="kelas" type="text" maxlength="20" placeholder="VIII-B"
                                       class="mt-1 h-11 w-full rounded-xl border-2 border-garis bg-hijau-50/50 px-3 uppercase text-tinta focus:border-hijau-600 focus:bg-white focus:outline-none" />
                                @error('kelas') <p class="mt-1 text-sm font-bold text-cap" role="alert">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <p class="text-sm text-tinta-2">Nama & kelasmu hanya terlihat oleh pengurus OSIM, agar bisa menghubungimu kembali.</p>
                    @else
                        <p class="text-sm text-tinta-2">Pengurus hanya melihat isi saran. Nama, kelas, dan perangkatmu tidak dicatat.</p>
                    @endif
                </fieldset>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button type="submit" wire:loading.attr="disabled" wire:target="kirim"
                            class="inline-flex items-center gap-2 rounded-xl bg-hijau-600 px-5 py-2.5 font-bold text-white shadow-[0_4px_10px_-3px_var(--color-hijau-900)] hover:bg-hijau-700 disabled:opacity-60">
                        <flux:icon.paper-airplane variant="micro" /> Kirim saran
                    </button>
                </div>

                @if ($terkirim)
                    <p class="flex items-center gap-1.5 rounded-lg bg-hijau-50 px-3 py-2 text-sm font-bold text-hijau-700 ring-1 ring-garis" role="status">
                        <flux:icon.check-circle variant="micro" /> Saran terkirim. Terima kasih! Pengurus OSIM akan menanggapinya.
                    </p>
                @endif
            </form>
        </section>

        <aside aria-labelledby="judul-cara" class="rounded-[14px] bg-white p-5 ring-1 ring-garis">
            <h2 id="judul-cara" class="font-bold text-tinta">Bagaimana saran diproses?</h2>
            <ol class="mt-3 flex flex-col gap-3 text-sm text-tinta-2">
                @foreach ([
                    ['pencil-square', 'Tulis saran, anonim atau dengan nama & kelas.'],
                    ['inbox-arrow-down', 'Saran masuk ke Ruang OSIM periode '.$periode.'.'],
                    ['chat-bubble-left-right', 'Pengurus yang sedang menjabat membaca dan menanggapinya.'],
                ] as $i => [$ikon, $teks])
                    <li class="flex items-start gap-3">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-hijau-100 text-xs font-black text-hijau-800">{{ $i + 1 }}</span>
                        <span class="pt-1">{{ $teks }}</span>
                    </li>
                @endforeach
            </ol>
        </aside>
    </x-osim.wadah>
</div>
