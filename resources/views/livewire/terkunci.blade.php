<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] #[Title('Halaman ditutup sementara')] class extends Component {
    /** Halaman yang dicoba dibuka: bilik-suara | kandidat | hasil. */
    #[Url(as: 'halaman')]
    public string $halaman = '';

    /** Rute tujuan per halaman yang bisa terkunci. */
    private const RUTE = ['bilik-suara' => 'bilik-suara', 'kandidat' => 'kandidat.index', 'hasil' => 'hasil'];

    public function mount(): void
    {
        // Hanya nilai yang dikenal yang dipakai; selebihnya pesan umum.
        if (! array_key_exists($this->halaman, self::RUTE)) {
            $this->halaman = '';
        }

        // Mode pemilihan sudah aktif lagi: langsung ke halaman yang dituju.
        if (app(\App\Services\LayananModePemilihan::class)->aktif()) {
            $this->redirectRoute($this->halaman ? self::RUTE[$this->halaman] : 'home', navigate: true);
        }
    }

    public function namaHalaman(): string
    {
        return match ($this->halaman) {
            'bilik-suara' => 'Bilik Suara',
            'kandidat' => 'Kandidat',
            'hasil' => 'Hasil Pemilihan',
            default => 'Halaman pemilihan',
        };
    }
}; ?>

<div>
    <x-osim.pita class="pt-6 pb-24 sm:pt-10">
        <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-sm font-bold text-hijau-50 hover:bg-white/10">
            <flux:icon.arrow-left variant="micro" /> Kembali ke Beranda
        </a>
        <h1 class="font-display mt-4 text-4xl font-black leading-[1.02] sm:text-5xl">{{ $this->namaHalaman() }} sedang ditutup</h1>
    </x-osim.pita>

    <x-osim.wadah class="-mt-16 max-w-3xl">
        <section class="overflow-hidden rounded-[14px] bg-white shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis">
            <div class="flex items-center gap-2 bg-emas-300/60 px-6 py-2.5">
                <flux:icon.lock-closed variant="micro" class="text-hijau-900" />
                <x-osim.label class="text-hijau-900">Di luar masa pemilihan</x-osim.label>
            </div>
            <div class="flex flex-col gap-5 p-6 sm:p-8">
                <p class="text-lg text-tinta">
                    Bilik Suara, Kandidat, dan Hasil hanya dibuka selama masa pemilihan ketua OSIM.
                    Halaman ini akan terbuka lagi saat panitia memulai pemilihan berikutnya.
                </p>
                <p class="text-tinta-2">Sambil menunggu, kamu bisa mengenal pengurus OSIM atau menyampaikan saran.</p>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <a href="{{ route('struktur') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-xl bg-hijau-600 px-5 py-3 font-bold text-white hover:bg-hijau-700">
                        <flux:icon.rectangle-group variant="micro" /> Lihat Struktur Organisasi
                    </a>
                    <a href="{{ route('saran') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-xl bg-emas-400 px-5 py-3 font-black text-hijau-950 hover:bg-emas-300">
                        <flux:icon.inbox-arrow-down variant="micro" /> Kirim saran
                    </a>
                </div>
            </div>
        </section>
    </x-osim.wadah>
</div>
