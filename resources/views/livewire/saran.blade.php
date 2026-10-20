<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] #[Title('Kotak Saran')] class extends Component {
    //
}; ?>

<div>
    <x-osim.pita class="pt-6 pb-24 sm:pt-10">
        <h1 class="font-display text-4xl font-black leading-[1.02] sm:text-5xl">Kotak Saran</h1>
        <p class="mt-3 max-w-xl text-hijau-50">Punya ide atau keluhan untuk OSIM? Sampaikan di sini, boleh tanpa nama.</p>
    </x-osim.pita>

    <x-osim.wadah class="-mt-16 max-w-3xl">
        <section class="overflow-hidden rounded-[14px] bg-white shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis">
            <div class="flex items-center justify-between gap-3 bg-hijau-100 px-6 py-2.5">
                <x-osim.label class="text-hijau-800">Aspirasi Siswa</x-osim.label>
            </div>
            <div class="flex flex-col items-center gap-3 p-8 text-center">
                <flux:icon.inbox-arrow-down class="size-12 text-hijau-600" />
                <p class="font-bold text-tinta">Formulir saran segera dibuka</p>
                <p class="max-w-sm text-sm text-tinta-2">Kamu nanti bisa mengirim saran secara anonim atau dengan nama dan kelas, lalu pengurus OSIM akan menanggapinya.</p>
            </div>
        </section>
    </x-osim.wadah>
</div>
