{{-- Slot foto bersudut tempel, seperti foto di kartu pelajar. Rasio diatur lewat class (mis. aspect-[3/4], aspect-[4/3]). --}}
@props(['foto' => null, 'alt' => '', 'gelap' => false])
<div {{ $attributes->class(['relative overflow-hidden rounded-md', 'bg-hijau-700 text-hijau-200' => $gelap, 'bg-hijau-50 text-hijau-600 ring-1 ring-garis' => ! $gelap]) }}>
    {{-- Bila berkas foto gagal dimuat, tampilkan keadaan "belum tersedia". --}}
    <div x-data="{ gagal: {{ $foto ? 'false' : 'true' }} }" class="size-full">
        @if ($foto)
            <img src="{{ $foto }}" alt="{{ $alt }}" class="size-full object-cover" loading="lazy" x-show="! gagal" x-on:error="gagal = true" />
        @endif
        <div class="flex size-full flex-col items-center justify-center gap-1 p-2 text-center" @if ($foto) x-show="gagal" x-cloak @endif>
            <svg viewBox="0 0 48 48" class="w-1/2 max-w-16" fill="currentColor" aria-hidden="true">
                <circle cx="24" cy="17" r="9" opacity=".55" />
                <path d="M6 46c1.5-10 8.6-16 18-16s16.5 6 18 16z" opacity=".55" />
            </svg>
            <span class="font-sempit text-[10px] font-bold uppercase leading-tight tracking-[0.12em]">Foto pasangan belum tersedia</span>
        </div>
    </div>
    {{-- Sudut tempel --}}
    @foreach (['left-0 top-0 rotate-0', 'right-0 top-0 rotate-90', 'right-0 bottom-0 rotate-180', 'left-0 bottom-0 -rotate-90'] as $posisi)
        <svg viewBox="0 0 12 12" class="pointer-events-none absolute size-3.5 {{ $posisi }} {{ $gelap ? 'text-hijau-950/60' : 'text-hijau-600/70' }}" aria-hidden="true"><path d="M0 0h12L0 12z" fill="currentColor" /></svg>
    @endforeach
</div>
