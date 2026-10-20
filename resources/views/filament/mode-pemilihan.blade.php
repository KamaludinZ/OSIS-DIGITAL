{{-- Kartu status sakelar mode pemilihan di halaman Periode Pemilihan. --}}
@php
    $status = \App\Support\DataTiruanModePemilihan::status();
    $aktif = $status['aktif'];
@endphp
<section @class([
    'osim-mode-pemilihan flex flex-col gap-4 rounded-[14px] p-5 sm:flex-row sm:items-center sm:justify-between',
    'bg-hijau-950 text-white' => $aktif,
    'bg-white ring-1 ring-garis' => ! $aktif,
])>
    <div class="flex items-start gap-4">
        <span @class([
            'flex size-12 shrink-0 items-center justify-center rounded-xl',
            'bg-emas-300 text-hijau-950' => $aktif,
            'bg-hijau-50 text-tinta-2 ring-1 ring-garis' => ! $aktif,
        ])>
            <x-filament::icon :icon="$aktif ? 'heroicon-o-check-badge' : 'heroicon-o-pause-circle'" class="size-7" />
        </span>
        <div>
            <p @class(['text-[11px] font-bold uppercase tracking-[0.14em]', 'text-hijau-200' => $aktif, 'text-tinta-2' => ! $aktif])>Mode pemilihan</p>
            <p @class(['text-2xl font-black', 'text-emas-300' => $aktif, 'text-tinta' => ! $aktif]) style="font-family: var(--font-display); font-variation-settings: 'wdth' 75">
                {{ $aktif ? 'Aktif' : 'Nonaktif' }}
            </p>
            <p @class(['max-w-xl text-sm', 'text-hijau-100' => $aktif, 'text-tinta-2' => ! $aktif])>
                {{ $aktif
                    ? 'Menu publik menampilkan Bilik Suara, Kandidat, dan Hasil.'
                    : 'Menu publik menampilkan Struktur Organisasi dan Kotak Saran. Bilik Suara, Kandidat, dan Hasil dikunci.' }}
            </p>
        </div>
    </div>
    <p @class(['text-xs sm:text-right', 'text-hijau-200' => $aktif, 'text-tinta-2' => ! $aktif])>
        Terakhir diubah {{ \Illuminate\Support\Carbon::parse($status['diubah_pada'])->translatedFormat('j M Y, H:i') }}<br>
        oleh {{ $status['diubah_oleh'] }}
    </p>
</section>
