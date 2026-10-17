<?php

use App\Services\LayananPublikasi;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] #[Title('Cari Kabar OSIM')] class extends Component {
    #[Url(as: 'q', except: '')]
    public string $kataKunci = '';

    public function with(LayananPublikasi $publikasi): array
    {
        $hasil = $publikasi->cari($this->kataKunci);

        return [
            'pengumuman' => collect($hasil['pengumuman']),
            'kegiatan' => collect($hasil['kegiatan']),
        ];
    }
}; ?>

<div>
    <x-osim.pita class="pt-6 sm:pt-10 pb-20">
        <h1 class="font-display text-4xl font-black leading-none sm:text-5xl">Cari Kabar OSIM</h1>
        <p class="mt-2 text-hijau-50">Temukan pengumuman atau kegiatan lama dengan kata kunci.</p>

        <form class="mt-5 max-w-2xl" role="search" wire:submit.prevent>
            <label for="kata-kunci" class="sr-only">Kata kunci pencarian</label>
            <div class="flex items-center gap-2 rounded-xl bg-white px-4 shadow-[0_4px_10px_-3px_rgb(0_0_0/0.12)]">
                <flux:icon.magnifying-glass class="size-5 text-hijau-600" />
                <input id="kata-kunci" type="search" wire:model.live.debounce.300ms="kataKunci" autofocus maxlength="100"
                       placeholder="Contoh: pemilihan, lomba, pentas seni"
                       class="h-14 w-full bg-transparent text-lg text-tinta placeholder:text-tinta-2/70 focus:outline-none" />
                <flux:icon.arrow-path class="size-5 animate-spin text-hijau-500" wire:loading wire:target="kataKunci" />
            </div>
        </form>
    </x-osim.pita>

    <x-osim.wadah class="-mt-10 flex max-w-3xl flex-col gap-6">
    @if (trim($kataKunci) !== '')
        <p role="status" class="w-fit rounded-full bg-hijau-900 px-4 py-1.5 text-sm font-bold text-white">
            {{ $pengumuman->count() + $kegiatan->count() }} hasil untuk “{{ $kataKunci }}”
        </p>

        @if ($pengumuman->isNotEmpty())
            <section aria-labelledby="hasil-pengumuman" class="flex flex-col gap-3">
                <div>
                    <x-osim.tab id="hasil-pengumuman" ikon="megaphone">Pengumuman</x-osim.tab>
                    <div class="overflow-hidden rounded-[14px] rounded-tl-none bg-white ring-1 ring-garis">
                        <x-daftar-pengumuman :pengumuman="$pengumuman" />
                    </div>
                </div>
            </section>
        @endif

        @if ($kegiatan->isNotEmpty())
            <section aria-labelledby="hasil-kegiatan" class="flex flex-col gap-3">
                <div>
                <x-osim.tab id="hasil-kegiatan" warna="hijau" ikon="calendar-days">Kegiatan</x-osim.tab>
                <ul class="divide-y divide-dashed divide-garis overflow-hidden rounded-[14px] rounded-tl-none bg-white ring-1 ring-garis">
                    @foreach ($kegiatan as $item)
                        <li wire:key="hasil-kegiatan-{{ $item['id'] }}" class="flex items-center justify-between gap-4 p-4">
                            <div class="min-w-0">
                                <a href="{{ route('kegiatan.detail', $item['id']) }}" class="font-semibold text-tinta hover:underline" wire:navigate>{{ $item['nama'] }}</a>
                                <p class="text-tinta-2 text-sm">{{ $item['tempat'] }} · {{ $item['divisi'] }}</p>
                            </div>
                            <time datetime="{{ $item['tanggal_mulai'] }}" class="shrink-0 text-sm text-tinta-2">
                                {{ \Illuminate\Support\Carbon::parse($item['tanggal_mulai'])->translatedFormat('j M Y') }}
                            </time>
                        </li>
                    @endforeach
                </ul>
                </div>
            </section>
        @endif
    @endif
    </x-osim.wadah>
</div>
