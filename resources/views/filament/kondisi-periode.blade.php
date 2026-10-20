{{-- Kondisi periode berjalan di dasbor Ruang OSIM. --}}
@php
    $k = app(\App\Services\LayananRingkasan::class)->kondisiPeriode();
    $kartu = [
        ['Mode pemilihan', $k['mode_pemilihan'] ? 'Aktif' : 'Nonaktif', $k['mode_pemilihan'] ? 'Menu publik: Bilik Suara, Kandidat, Hasil' : 'Menu publik: Struktur & Kotak Saran'],
        ['Bagan struktur', $k['ada_bagan'] ? 'Sudah diunggah' : 'Belum ada', $k['ada_bagan'] ? 'Tampil di halaman Struktur Organisasi' : 'Unggah di menu Struktur Organisasi'],
        ['Kotak saran', $k['saran_belum'].' belum ditanggapi', $k['saran_total'].' saran periode ini'],
        ['Akun media sosial', $k['akun_sosmed'].' akun', 'Tampil di Beranda & kaki halaman'],
    ];
    if ($k['pemilihan']) {
        $kartu[] = ['Partisipasi pemilihan', \App\Support\Angka::persen($k['suara_masuk'] ?? 0, $k['total_pemilih'] ?? 0), number_format($k['suara_masuk'] ?? 0, 0, ',', '.').' dari '.number_format($k['total_pemilih'] ?? 0, 0, ',', '.').' pemilih'];
    }
@endphp
<section aria-label="Kondisi periode berjalan" class="osim-kondisi-periode flex flex-col gap-3">
    @if (! $k['periode'])
        <p class="rounded-xl bg-emas-300/40 px-4 py-3 text-sm font-bold text-tinta ring-1 ring-garis">Belum ada periode aktif. Aktifkan periode di menu Periode OSIM agar data baru punya tempat.</p>
    @else
        <ul @class(['grid gap-3 sm:grid-cols-2', 'xl:grid-cols-4' => count($kartu) === 4, 'xl:grid-cols-5' => count($kartu) === 5])>
            @foreach ($kartu as [$judul, $nilai, $ket])
                <li class="rounded-xl bg-white px-4 py-3 ring-1 ring-garis">
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-tinta-2">{{ $judul }}</p>
                    <p class="text-lg font-black text-tinta">{{ $nilai }}</p>
                    <p class="text-xs text-tinta-2">{{ $ket }}</p>
                </li>
            @endforeach
        </ul>
    @endif
</section>
