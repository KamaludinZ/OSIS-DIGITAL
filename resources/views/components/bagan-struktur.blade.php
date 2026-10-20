{{--
    Gambar bagan struktur organisasi yang responsif.
    - 4:3 (lebar): memenuhi lebar kolom di semua layar.
    - 3:4 (tinggi): penuh di ponsel; di layar lebar dibatasi tinggi layar dan diletakkan di tengah
      agar seluruh bagan terlihat tanpa menggulir.
    Kotak berproporsi tetap mencegah tata letak bergeser saat gambar dimuat.
--}}
@props(['gambar', 'rasio' => '4:3', 'alt' => 'Bagan struktur organisasi OSIM', 'tautan' => true])
@php($tinggi = $rasio === '3:4')
<div {{ $attributes->class(['osim-bagan relative flex justify-center bg-hijau-50']) }} data-rasio="{{ $rasio }}" x-data="{ gagal: false }">
    {{-- Gambar gagal dimuat (berkas hilang/jaringan): pesan pengganti, bukan ikon gambar rusak --}}
    <div x-cloak x-show="gagal" role="alert" @class(['flex w-full flex-col items-center justify-center gap-2 p-8 text-center', 'aspect-[4/3]' => $rasio !== '3:4', 'aspect-[3/4] sm:aspect-[4/3]' => $rasio === '3:4'])>
        <flux:icon.exclamation-triangle class="size-10 text-emas-500" />
        <p class="font-bold text-tinta">Gambar bagan gagal dimuat</p>
        <p class="max-w-sm text-sm text-tinta-2">Coba muat ulang halaman. Bila masih gagal, beri tahu pengurus OSIM.</p>
    </div>
    <div x-show="! gagal" @class([
        'relative w-full',
        'aspect-[4/3]' => ! $tinggi,
        'aspect-[3/4] sm:w-auto sm:h-[min(80vh,56rem)]' => $tinggi,
    ])>
        @if ($tautan)
            <a href="{{ $gambar }}" target="_blank" rel="noopener" class="group block size-full" aria-label="Buka bagan ukuran penuh">
        @endif
        <img src="{{ $gambar }}" alt="{{ $alt }}" loading="lazy" decoding="async" class="size-full object-contain" x-on:error="gagal = true" />
        @if ($tautan)
                <span class="absolute bottom-3 right-3 inline-flex items-center gap-1.5 rounded-full bg-hijau-950/80 px-3 py-1.5 text-xs font-bold text-white opacity-90 group-hover:opacity-100">
                    <flux:icon.arrows-pointing-out variant="micro" /> Perbesar
                </span>
            </a>
        @endif
    </div>
</div>
