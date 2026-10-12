@props(['sudahMemilih' => app(\App\Services\LayananPemilihan::class)->sudahMemilih(auth()->user())])

{{-- Tombol utama menuju bilik suara; terkunci setelah siswa memilih. --}}
@if ($sudahMemilih)
    <a href="{{ route('bilik-suara.bukti') }}" wire:navigate
       {{ $attributes->class('inline-flex items-center justify-center gap-2 rounded-xl border-2 border-dashed border-hijau-300 bg-hijau-100 px-5 py-3 font-bold text-hijau-800') }}>
        <flux:icon.check-badge variant="mini" />
        Anda sudah memilih
    </a>
@else
    <a href="{{ route('bilik-suara') }}" wire:navigate
       {{ $attributes->class('group inline-flex items-center justify-center gap-2 rounded-xl bg-emas-400 px-5 py-3 font-bold text-hijau-950 shadow-[0_4px_10px_-3px_var(--color-emas-500),0_8px_18px_-6px_rgb(4_41_26/0.45)] transition hover:-translate-y-0.5 hover:bg-emas-300 active:translate-y-0.5 active:shadow-[0_2px_10px_-3px_var(--color-emas-500)]') }}>
        <flux:icon.check-badge variant="mini" />
        Mulai Memilih
        <flux:icon.arrow-right variant="micro" class="transition group-hover:translate-x-0.5" />
    </a>
@endif
