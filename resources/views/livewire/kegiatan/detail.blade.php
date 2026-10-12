<?php

use App\Services\LayananPublikasi;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] class extends Component {
    #[\Livewire\Attributes\Locked]
    public array $kegiatan = [];

    public function mount(int $id, LayananPublikasi $publikasi): void
    {
        $this->kegiatan = $publikasi->cariKegiatan($id) ?? abort(404);
    }

    public function rendering($view): void
    {
        $view->title($this->kegiatan['nama']);
    }
}; ?>

@php
    $mulai = Carbon::parse($kegiatan['tanggal_mulai']);
    $selesai = Carbon::parse($kegiatan['tanggal_selesai']);
    $status = match (true) {
        $selesai->copy()->endOfDay()->isPast() => ['Sudah berlalu', 'bg-white/15 text-white'],
        $mulai->copy()->startOfDay()->isFuture() => ['Akan datang', 'bg-emas-400 text-hijau-950'],
        default => ['Berlangsung hari ini', 'bg-white text-hijau-800'],
    };
@endphp

{{-- Membuka langsung juga menandai pemberitahuannya sudah dibaca (per perangkat). --}}
<article x-data x-init="$store.notif.tandai('kegiatan-{{ $kegiatan['id'] }}')">
    <x-osim.pita class="pt-6 sm:pt-10 pb-24">
        <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-sm font-bold text-hijau-50 hover:bg-white/10">
            <flux:icon.arrow-left variant="micro" /> Kembali ke Beranda
        </a>
        <div class="mt-4 max-w-3xl">
            <span class="font-sempit inline-block rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider {{ $status[1] }}">{{ $status[0] }}</span>
            <h1 class="font-display mt-3 text-4xl font-black leading-[1.02] sm:text-5xl">{{ $kegiatan['nama'] }}</h1>
        </div>
    </x-osim.pita>

    <x-osim.wadah class="-mt-16 max-w-3xl">
        <div class="overflow-hidden rounded-[14px] bg-white shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis">
            <dl class="grid gap-5 p-6 sm:grid-cols-3">
                <div>
                    <dt class="flex items-center gap-1.5"><flux:icon.calendar variant="micro" class="text-hijau-600" /><x-osim.label>Tanggal</x-osim.label></dt>
                    <dd class="mt-1 font-bold text-tinta">
                        {{ $mulai->isSameDay($selesai) ? $mulai->translatedFormat('l, j F Y') : $mulai->translatedFormat('j F').' – '.$selesai->translatedFormat('j F Y') }}
                    </dd>
                </div>
                <div>
                    <dt class="flex items-center gap-1.5"><flux:icon.map-pin variant="micro" class="text-hijau-600" /><x-osim.label>Tempat</x-osim.label></dt>
                    <dd class="mt-1 font-bold text-tinta">{{ $kegiatan['tempat'] }}</dd>
                </div>
                <div>
                    <dt class="flex items-center gap-1.5"><flux:icon.user-group variant="micro" class="text-hijau-600" /><x-osim.label>Penanggung jawab</x-osim.label></dt>
                    <dd class="mt-1 font-bold text-tinta">{{ $kegiatan['divisi'] }}</dd>
                </div>
            </dl>
            <div class="osim-perforasi"></div>
            <p class="max-w-[70ch] p-6 text-lg leading-relaxed text-tinta">{{ $kegiatan['deskripsi'] }}</p>
        </div>
    </x-osim.wadah>
</article>
