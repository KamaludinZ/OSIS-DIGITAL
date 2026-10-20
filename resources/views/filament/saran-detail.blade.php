{{-- Riwayat satu saran: pesan pengirim lalu tanggapan pengurus. --}}
@php($tgl = fn ($t) => $t->translatedFormat('j F Y, H:i'))
<ol class="flex flex-col gap-3">
    <li class="rounded-xl bg-hijau-50 p-4 ring-1 ring-garis">
        <p class="text-xs font-bold uppercase tracking-[0.12em] text-tinta-2">
            {{ $saran->pengirim() }} · {{ $tgl($saran->created_at) }}
        </p>
        <p class="mt-1 whitespace-pre-line text-tinta">{{ $saran->isi }}</p>
    </li>
    @if ($saran->sudahDitanggapi())
        <li class="ml-6 rounded-xl bg-white p-4 ring-1 ring-hijau-600">
            <p class="text-xs font-bold uppercase tracking-[0.12em] text-hijau-700">Tanggapan {{ $saran->penanggap?->name ?? 'pengurus' }} · {{ $tgl($saran->ditanggapi_pada) }}</p>
            <p class="mt-1 whitespace-pre-line text-tinta">{{ $saran->tanggapan }}</p>
        </li>
    @else
        <li class="ml-6 rounded-xl border-2 border-dashed border-garis p-4 text-sm text-tinta-2">Belum ditanggapi.</li>
    @endif
</ol>
<p class="mt-3 text-xs text-tinta-2">Periode {{ $saran->periode->nama }}</p>
