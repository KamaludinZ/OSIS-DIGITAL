{{-- Tautan akun resmi OSIM periode aktif (ikon + nama akun), dibuka di tab baru. --}}
@props(['akun' => [], 'gelap' => false])
@if (count($akun))
    <ul {{ $attributes->class('flex flex-wrap gap-2') }} aria-label="Akun media sosial resmi OSIM">
        @foreach ($akun as $a)
            <li>
                <a href="{{ $a['url'] }}" target="_blank" rel="noopener"
                   @class(['inline-flex items-center gap-2 rounded-full px-3.5 py-2 text-sm font-bold transition',
                       'bg-white text-hijau-800 ring-1 ring-garis hover:bg-hijau-50' => ! $gelap,
                       'bg-white/10 text-white ring-1 ring-white/20 hover:bg-white/20' => $gelap])>
                    <x-osim.ikon-sosmed :platform="$a['platform']" class="size-4" />
                    <span>{{ $a['nama_akun'] }}</span>
                    <span class="sr-only">di {{ $a['label'] }} (tab baru)</span>
                </a>
            </li>
        @endforeach
    </ul>
@endif
