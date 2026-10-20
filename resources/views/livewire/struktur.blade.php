<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] #[Title('Struktur Organisasi')] class extends Component {
    #[\Livewire\Attributes\Locked]
    public string $periode = '';

    #[\Livewire\Attributes\Locked]
    public bool $adaPeriodeAktif = false;

    /** Bagan periode aktif: url gambar, rasio, tanggal diperbarui. */
    #[\Livewire\Attributes\Locked]
    public ?array $struktur = null;

    /** Arsip periode lama yang punya bagan: slug => data (hanya-baca). */
    #[\Livewire\Attributes\Locked]
    public array $arsip = [];

    /** Slug periode arsip yang sedang dilihat (mis. 2025-2026); kosong = periode aktif. */
    #[\Livewire\Attributes\Url(as: 'periode')]
    public string $lihat = '';

    public function mount(\App\Services\LayananStruktur $layanan): void
    {
        $data = $layanan->untukPublik();
        $this->adaPeriodeAktif = $data['ada_periode_aktif'];
        $this->periode = $data['periode'] ?? \App\Models\Member::periodeAktif();
        $this->struktur = $data['struktur'];
        $this->arsip = $layanan->arsipPublik();

        if (! isset($this->arsip[$this->lihat])) {
            $this->lihat = '';
        }
    }

    public function updatedLihat(): void
    {
        if (! isset($this->arsip[$this->lihat])) {
            $this->lihat = '';
        }
    }

    /** Data bagan yang sedang ditampilkan (aktif atau arsip). */
    public function tampil(): array
    {
        return $this->lihat
            ? ['periode' => $this->arsip[$this->lihat]['periode'], 'struktur' => $this->arsip[$this->lihat], 'arsip' => true]
            : ['periode' => $this->periode, 'struktur' => $this->struktur, 'arsip' => false];
    }
}; ?>

@php
    $t = $this->tampil();
    $bagan = $t['struktur'];
@endphp
<div>
    <x-osim.pita class="pt-6 pb-24 sm:pt-10">
        <h1 class="font-display text-4xl font-black leading-[1.02] sm:text-5xl">Struktur Organisasi</h1>
        <p class="mt-3 max-w-xl text-hijau-50">Susunan pengurus OSIM periode <span class="font-bold text-emas-300">{{ $t['periode'] }}</span>.</p>
    </x-osim.pita>

    <x-osim.wadah class="-mt-16 flex max-w-4xl flex-col gap-5">
        @if ($t['arsip'])
            <p class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-emas-200 px-4 py-2.5 text-sm font-bold text-hijau-950 ring-1 ring-emas-400" role="status">
                <span class="flex items-center gap-1.5"><flux:icon.archive-box variant="micro" /> Arsip kepengurusan {{ $t['periode'] }}</span>
                <button type="button" wire:click="$set('lihat', '')" class="underline">Lihat periode {{ $periode }}</button>
            </p>
        @endif

        <figure class="overflow-hidden rounded-[14px] bg-white shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis">
            <div class="flex items-center justify-between gap-3 bg-hijau-100 px-6 py-2.5">
                <x-osim.label class="text-hijau-800">Bagan Kepengurusan</x-osim.label>
                <span class="text-xs font-bold text-tinta-2">Periode {{ $t['periode'] }}</span>
            </div>

            @if ($bagan)
                <x-bagan-struktur :gambar="$bagan['gambar']" :rasio="$bagan['rasio']" :alt="'Bagan struktur organisasi OSIM periode '.$t['periode']" wire:key="bagan-{{ $lihat ?: 'aktif' }}" />
                <figcaption class="flex flex-wrap items-center justify-between gap-2 px-6 py-3 text-sm text-tinta-2">
                    <span>Ketuk bagan untuk membukanya ukuran penuh.</span>
                    <span>Diperbarui {{ \Illuminate\Support\Carbon::parse($bagan['diperbarui'])->translatedFormat('j F Y') }}</span>
                </figcaption>
            @else
                <div class="flex aspect-[4/3] flex-col items-center justify-center gap-3 bg-hijau-50 p-8 text-center">
                    <flux:icon.rectangle-group class="size-12 text-hijau-600" />
                    @if ($adaPeriodeAktif)
                        <p class="font-bold text-tinta">Bagan struktur belum diunggah</p>
                        <p class="max-w-sm text-sm text-tinta-2">Pengurus OSIM sedang menyiapkan bagan kepengurusan periode ini. Cek lagi nanti, ya.</p>
                    @else
                        <p class="font-bold text-tinta">Kepengurusan baru sedang disiapkan</p>
                        <p class="max-w-sm text-sm text-tinta-2">Belum ada periode kepengurusan OSIM yang aktif. Struktur akan tampil setelah pengurus baru dilantik.</p>
                    @endif
                </div>
            @endif
        </figure>

        {{-- Arsip kepengurusan sebelumnya (hanya dilihat) --}}
        @if (count($arsip))
            <section aria-labelledby="judul-arsip">
                <h2 id="judul-arsip" class="font-bold text-tinta">Arsip kepengurusan</h2>
                <ul class="mt-2 flex flex-wrap gap-2">
                    <li>
                        <button type="button" wire:click="$set('lihat', '')" @class(['rounded-full px-3.5 py-1.5 text-sm font-bold ring-1', 'bg-hijau-600 text-white ring-hijau-600' => ! $lihat, 'bg-white text-hijau-800 ring-garis hover:bg-hijau-50' => $lihat])>
                            {{ $periode }} (sekarang)
                        </button>
                    </li>
                    @foreach ($arsip as $slug => $a)
                        <li>
                            <button type="button" wire:click="$set('lihat', '{{ $slug }}')" @class(['inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-bold ring-1', 'bg-hijau-600 text-white ring-hijau-600' => $lihat === $slug, 'bg-white text-hijau-800 ring-garis hover:bg-hijau-50' => $lihat !== $slug])>
                                <flux:icon.archive-box variant="micro" /> {{ $a['periode'] }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </x-osim.wadah>
</div>
