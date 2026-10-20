{{-- Isi dialog konfirmasi: dampak menyalakan/mematikan mode pemilihan. --}}
@php
    $ke = $menyalakan ? 'aktif' : 'nonaktif';
    $dampak = $menyalakan
        ? [
            ['heroicon-m-eye', 'Menu publik berganti ke Bilik Suara, Kandidat, dan Hasil.'],
            ['heroicon-m-lock-open', 'Alamat /bilik-suara, /kandidat, dan /hasil bisa dibuka lagi.'],
            ['heroicon-m-eye-slash', 'Struktur Organisasi dan Kotak Saran disembunyikan dari menu.'],
        ]
        : [
            ['heroicon-m-eye', 'Menu publik berganti ke Struktur Organisasi dan Kotak Saran.'],
            ['heroicon-m-lock-closed', 'Alamat /bilik-suara, /kandidat, dan /hasil dikunci; pengunjung melihat pemberitahuan halaman terkunci.'],
            ['heroicon-m-exclamation-triangle', 'Pemilih yang sedang di bilik suara tidak bisa melanjutkan.'],
        ];
@endphp
<div class="flex flex-col gap-3 text-left">
    <p class="text-sm text-tinta-2">Setelah mode menjadi <strong class="text-tinta">{{ $ke }}</strong>:</p>
    <ul class="flex flex-col gap-2">
        @foreach ($dampak as [$ikon, $teks])
            <li class="flex items-start gap-2.5 rounded-lg bg-hijau-50 px-3 py-2 text-sm text-tinta ring-1 ring-garis">
                <x-filament::icon :icon="$ikon" class="mt-0.5 size-4 shrink-0 text-hijau-700" />
                <span>{{ $teks }}</span>
            </li>
        @endforeach
    </ul>
    @include('filament.pratinjau-menu-publik', ['aktif' => $menyalakan, 'menu' => app(\App\Services\LayananModePemilihan::class)->menu($menyalakan), 'sorot' => false])
    <p class="text-xs text-tinta-2">Data pemilihan, suara, dan saran tidak berubah. Mode bisa diubah lagi kapan saja.</p>
</div>
