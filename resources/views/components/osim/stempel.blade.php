{{-- Cap stempel bulat merah ala madrasah, teks melingkar: OSIM di atas, nama sekolah di bawah, tanggal (opsional) di lingkar dalam. --}}
@props(['atas' => 'OSIM', 'tengah' => 'SUDAH MEMILIH', 'bawah' => 'MTsN 2 KOTA MALANG', 'tanggal' => null])
@php($id = 'cap-'.\Illuminate\Support\Str::random(6))
<svg {{ $attributes->class('text-cap') }} viewBox="0 0 200 200" role="img" aria-label="Stempel: {{ $tengah }}">
    <defs>
        <path id="{{ $id }}-a" d="M 34 100 A 66 66 0 0 1 166 100" />
        <path id="{{ $id }}-b" d="M 26 100 A 74 74 0 0 0 174 100" />
    </defs>
    <g fill="none" stroke="currentColor">
        <circle cx="100" cy="100" r="94" stroke-width="5" />
        <circle cx="100" cy="100" r="84" stroke-width="1.5" />
        <circle cx="100" cy="100" r="56" stroke-width="1.5" />
    </g>
    <g fill="currentColor" font-family="Archivo Variable, Archivo, sans-serif" font-weight="800">
        <text font-size="{{ mb_strlen($atas) <= 8 ? 20 : 14 }}" letter-spacing="{{ mb_strlen($atas) <= 8 ? 6 : 1.6 }}"><textPath href="#{{ $id }}-a" startOffset="50%" text-anchor="middle">{{ $atas }}</textPath></text>
        @if ($bawah)
            <text font-size="12.5" letter-spacing="1.4"><textPath href="#{{ $id }}-b" startOffset="50%" text-anchor="middle" dominant-baseline="hanging">{{ $bawah }}</textPath></text>
        @endif
        <rect x="18" y="86" width="164" height="30" rx="3" />
        <text x="100" y="107.5" font-size="17" text-anchor="middle" fill="#fff" letter-spacing="1.5">{{ $tengah }}</text>
        <path d="M100 58l3 7 7 .5-5.5 4.5 2 7-6.5-4-6.5 4 2-7-5.5-4.5 7-.5z" />
        @if ($tanggal)
            <text x="100" y="136" font-size="11" text-anchor="middle" letter-spacing="0.6">{{ $tanggal }}</text>
        @else
            <path d="M100 128l3 7 7 .5-5.5 4.5 2 7-6.5-4-6.5 4 2-7-5.5-4.5 7-.5z" />
        @endif
    </g>
</svg>
