{{-- Tab judul mading: label kecil yang menempel di atas modul. --}}
@props(['warna' => 'emas', 'ikon' => null, 'level' => 2])
<h{{ $level }} {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-t-lg px-3.5 pb-1.5 pt-2 text-sm font-bold tracking-wide',
    'bg-emas-400 text-hijau-950' => $warna === 'emas',
    'bg-hijau-600 text-white' => $warna === 'hijau',
    'bg-white text-hijau-800' => $warna === 'putih',
]) }}>
    @if ($ikon)
        <flux:icon :name="$ikon" variant="micro" />
    @endif
    {{ $slot }}
</h{{ $level }}>
