<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] #[Title('Struktur Organisasi')] class extends Component {
    #[\Livewire\Attributes\Locked]
    public string $periode = '';

    /** Data tiruan: satu bagan untuk periode aktif (diganti tabel organization_structures di backend). */
    #[\Livewire\Attributes\Locked]
    public ?array $struktur = null;

    public function mount(): void
    {
        $this->periode = \App\Models\OsisPeriod::aktif()?->nama ?? \App\Models\Member::periodeAktif();
        $this->struktur = \App\Support\DataTiruanStruktur::aktif();
    }
}; ?>

<div>
    <x-osim.pita class="pt-6 pb-24 sm:pt-10">
        <h1 class="font-display text-4xl font-black leading-[1.02] sm:text-5xl">Struktur Organisasi</h1>
        <p class="mt-3 max-w-xl text-hijau-50">Susunan pengurus OSIM periode <span class="font-bold text-emas-300">{{ $periode }}</span>.</p>
    </x-osim.pita>

    <x-osim.wadah class="-mt-16 max-w-4xl">
        <figure class="overflow-hidden rounded-[14px] bg-white shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis">
            <div class="flex items-center justify-between gap-3 bg-hijau-100 px-6 py-2.5">
                <x-osim.label class="text-hijau-800">Bagan Kepengurusan</x-osim.label>
                <span class="text-xs font-bold text-tinta-2">Periode {{ $periode }}</span>
            </div>

            @if ($struktur)
                <x-bagan-struktur :gambar="$struktur['gambar']" :rasio="$struktur['rasio']" :alt="'Bagan struktur organisasi OSIM periode '.$periode" />
                <figcaption class="flex flex-wrap items-center justify-between gap-2 px-6 py-3 text-sm text-tinta-2">
                    <span>Ketuk bagan untuk membukanya ukuran penuh.</span>
                    <span>Diperbarui {{ \Illuminate\Support\Carbon::parse($struktur['diperbarui'])->translatedFormat('j F Y') }}</span>
                </figcaption>
            @else
                <div class="flex aspect-[4/3] flex-col items-center justify-center gap-3 bg-hijau-50 p-8 text-center">
                    <flux:icon.rectangle-group class="size-12 text-hijau-600" />
                    <p class="font-bold text-tinta">Bagan struktur belum diunggah</p>
                    <p class="max-w-sm text-sm text-tinta-2">Pengurus OSIM sedang menyiapkan bagan kepengurusan periode ini. Cek lagi nanti, ya.</p>
                </div>
            @endif
        </figure>
    </x-osim.wadah>
</div>
