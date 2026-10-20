<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] class extends Component {
    #[\Livewire\Attributes\Locked]
    public array $akun = [];

    /** Hanya akun periode aktif yang tautannya cocok dengan domain platformnya; selain itu 404. */
    public function mount(int $id): void
    {
        $akun = \App\Models\SocialLink::find($id); // global scope: hanya akun periode aktif
        abort_unless($akun?->tautanSah(), 404);

        $this->akun = [
            'platform' => $akun->platform->value,
            'label' => $akun->platform->label(),
            'nama_akun' => $akun->nama_akun,
            'tautan' => $akun->tautan,
            'host' => parse_url($akun->tautan, PHP_URL_HOST),
        ];
    }

    public function rendering($view): void
    {
        $view->title('Membuka '.$this->akun['label']);
    }
}; ?>

{{-- Pengalihan aman: tampilkan tujuan sebelum meninggalkan situs OSIM. --}}
<div x-data="{ sisa: 5 }" x-init="const t = setInterval(() => { if (--sisa <= 0) { clearInterval(t); window.location.href = $refs.tujuan.href } }, 1000)">
    <x-osim.pita class="pt-6 pb-24 sm:pt-10">
        <h1 class="font-display text-4xl font-black leading-[1.02] sm:text-5xl">Membuka {{ $akun['label'] }}</h1>
    </x-osim.pita>

    <x-osim.wadah class="-mt-16 max-w-xl">
        <section class="overflow-hidden rounded-[14px] bg-white shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis">
            <div class="flex items-center gap-2 bg-hijau-100 px-6 py-2.5">
                <flux:icon.shield-check variant="micro" class="text-hijau-800" />
                <x-osim.label class="text-hijau-800">Akun resmi OSIM</x-osim.label>
            </div>
            <div class="flex flex-col gap-4 p-6">
                <div class="flex items-center gap-3">
                    <span class="flex size-12 items-center justify-center rounded-xl bg-hijau-50 text-hijau-700 ring-1 ring-garis">
                        <x-osim.ikon-sosmed :platform="$akun['platform']" class="size-6" />
                    </span>
                    <div>
                        <p class="text-lg font-black text-tinta">{{ $akun['nama_akun'] }}</p>
                        <p class="text-sm text-tinta-2">Kamu akan meninggalkan situs OSIM menuju <strong class="text-tinta">{{ $akun['host'] }}</strong>.</p>
                    </div>
                </div>
                <p class="text-sm text-tinta-2" aria-live="polite">Dialihkan otomatis dalam <span x-text="sisa">5</span> detik…</p>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <a x-ref="tujuan" href="{{ $akun['tautan'] }}" rel="noopener noreferrer"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-hijau-600 px-5 py-3 font-bold text-white hover:bg-hijau-700">
                        Lanjutkan ke {{ $akun['label'] }} <flux:icon.arrow-top-right-on-square variant="micro" />
                    </a>
                    <a href="{{ route('home') }}" wire:navigate x-on:click="sisa = 999"
                       class="inline-flex items-center justify-center rounded-xl px-5 py-3 font-bold text-hijau-800 ring-1 ring-garis hover:bg-hijau-50">Tetap di situs OSIM</a>
                </div>
            </div>
        </section>
    </x-osim.wadah>
</div>
