{{-- Cap stempel bulat merah ala madrasah, teks melingkar. --}}
@props(['atas' => 'OSIM · MTsN 2 MALANG', 'tengah' => 'SUDAH MEMILIH', 'bawah' => null])
@php($id = 'cap-'.\Illuminate\Support\Str::random(6))
<svg {{ $attributes->class('text-cap') }} viewBox="0 0 200 200" role="img" aria-label="Stempel: {{ $tengah }}">
    <defs>
        <path id="{{ $id }}-a" d="M 30 100 A 70 70 0 0 1 170 100" />
        <path id="{{ $id }}-b" d="M 26 100 A 74 74 0 0 0 174 100" />
    </defs>
    <g fill="none" stroke="currentColor">
        <circle cx="100" cy="100" r="94" stroke-width="5" />
        <circle cx="100" cy="100" r="84" stroke-width="1.5" />
        <circle cx="100" cy="100" r="56" stroke-width="1.5" />
    </g>
    <g fill="currentColor" font-family="Archivo Variable, Archivo, sans-serif" font-weight="800">
        <text font-size="14" letter-spacing="1.6"><textPath href="#{{ $id }}-a" startOffset="50%" text-anchor="middle">{{ $atas }}</textPath></text>
        @if ($bawah)
            <text font-size="13" letter-spacing="2"><textPath href="#{{ $id }}-b" startOffset="50%" text-anchor="middle" dominant-baseline="hanging">{{ $bawah }}</textPath></text>
        @endif
        <rect x="18" y="86" width="164" height="30" rx="3" />
        <text x="100" y="107.5" font-size="17" text-anchor="middle" fill="#fff" letter-spacing="1.5">{{ $tengah }}</text>
        <path d="M100 58l3 7 7 .5-5.5 4.5 2 7-6.5-4-6.5 4 2-7-5.5-4.5 7-.5z" />
        <path d="M100 128l3 7 7 .5-5.5 4.5 2 7-6.5-4-6.5 4 2-7-5.5-4.5 7-.5z" />
    </g>
</svg>
