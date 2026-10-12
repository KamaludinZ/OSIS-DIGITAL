<?php

use App\Services\LayananPublikasi;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] class extends Component {
    public array $pengumuman = [];

    public function mount(int $id, LayananPublikasi $publikasi): void
    {
        $this->pengumuman = $publikasi->cariPengumuman($id) ?? abort(404);
    }

    public function rendering($view): void
    {
        $view->title($this->pengumuman['judul']);
    }
}; ?>

{{-- Membuka langsung juga menandai pemberitahuannya sudah dibaca (per perangkat). --}}
<article x-data x-init="$store.notif.tandai('pengumuman-{{ $pengumuman['id'] }}')">
    <x-osim.pita class="pt-6 sm:pt-10 pb-24">
        <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-sm font-bold text-hijau-50 hover:bg-white/10">
            <flux:icon.arrow-left variant="micro" /> Kembali ke Beranda
        </a>
        <div class="mt-4 max-w-3xl">
            <h1 class="font-display text-4xl font-black leading-[1.02] sm:text-5xl">{{ $pengumuman['judul'] }}</h1>
            <p class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                <span class="font-sempit rounded-full bg-emas-400 px-3 py-1 text-xs font-bold uppercase tracking-wider text-hijau-950">{{ $pengumuman['kategori'] }}</span>
                <time datetime="{{ $pengumuman['tanggal'] }}" class="font-semibold text-hijau-100">{{ \Illuminate\Support\Carbon::parse($pengumuman['tanggal'])->translatedFormat('l, j F Y') }}</time>
            </p>
        </div>
    </x-osim.pita>

    <x-osim.wadah class="-mt-16 max-w-3xl">
        <div class="overflow-hidden rounded-[14px] bg-white shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis">
            <div class="flex items-center justify-between gap-3 bg-hijau-100 px-6 py-2.5">
                <x-osim.label class="text-hijau-800">Pengumuman Resmi OSIM</x-osim.label>
                <span class="text-sm font-bold text-hijau-800">Oleh {{ $pengumuman['penulis'] }}</span>
            </div>
            <div class="max-w-[70ch] space-y-4 p-6 text-lg leading-relaxed text-tinta sm:p-8">
                @foreach ($pengumuman['isi'] as $paragraf)
                    <p>{{ $paragraf }}</p>
                @endforeach
            </div>
        </div>
    </x-osim.wadah>
</article>
