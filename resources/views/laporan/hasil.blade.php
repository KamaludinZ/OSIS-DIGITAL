<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Berita Acara Hasil · {{ $rekap['judul'] }}</title>
        <link rel="icon" href="/favicon.png" type="image/png" />
        @vite(['resources/css/app.css'])
        <style>
            /* Berita acara: A4 tegak, warna kop tetap tercetak, baris tabel & tanda tangan tidak terpotong halaman. */
            @page { size: A4 portrait; margin: 15mm 15mm 18mm; }
            @media print {
                html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; font-size: 11pt; }
                tr, .ttd { break-inside: avoid; }
                a[href]::after { content: none; }
            }
        </style>
    </head>
    <body class="osim min-h-screen py-8 print:py-0" style="background:#fff">
        <div class="mx-auto max-w-3xl px-6">
            <div class="mb-6 flex justify-end gap-2 print:hidden">
                <a href="{{ route('hasil') }}" class="rounded-xl px-4 py-2 font-bold text-hijau-800 ring-1 ring-garis hover:bg-hijau-50">Kembali</a>
                <button type="button" onclick="window.print()" class="rounded-xl bg-emas-400 px-4 py-2 font-black text-hijau-950 hover:bg-emas-300">Cetak / simpan PDF</button>
            </div>

            <header class="flex items-center gap-4 border-b-4 border-double border-hijau-800 pb-4">
                <img src="/images/brand/logo-mtsn2@2x.png" alt="Logo MTsN 2 Kota Malang" class="size-20" />
                <div>
                    <p class="font-sempit text-sm font-bold uppercase tracking-[0.14em] text-hijau-800">Organisasi Siswa Intra Madrasah</p>
                    <p class="font-display text-2xl font-black text-tinta">MTsN 2 Kota Malang</p>
                </div>
            </header>

            <h1 class="font-display mt-6 text-center text-2xl font-black uppercase text-tinta">Berita Acara Hasil Pemilihan</h1>
            <p class="text-center font-bold text-tinta">{{ $rekap['judul'] }}</p>

            <dl class="mt-6 grid grid-cols-[12rem_1fr] gap-y-1.5 text-tinta">
                <dt class="text-tinta-2">Periode pemungutan</dt><dd>: {{ $pemilihan->mulai->translatedFormat('j F Y, H:i') }} – {{ $pemilihan->selesai->translatedFormat('j F Y, H:i') }} WIB</dd>
                <dt class="text-tinta-2">Status</dt><dd>: {{ $rekap['status'] === 'selesai' ? 'Ditutup' : ($rekap['status'] === 'berjalan' ? 'Berlangsung' : 'Belum dibuka') }}{{ $rekap['hasil_publik'] ? ', hasil dipublikasikan' : ', hasil belum dipublikasikan' }}</dd>
                <dt class="text-tinta-2">Total pemilih</dt><dd class="angka">: {{ number_format($rekap['total_pemilih'], 0, ',', '.') }}</dd>
                <dt class="text-tinta-2">Suara masuk</dt><dd class="angka">: {{ number_format($rekap['suara_masuk'], 0, ',', '.') }} ({{ str_replace('.', ',', $partisipasi) }}%)</dd>
            </dl>

            <table class="mt-6 w-full border-collapse text-tinta">
                <thead>
                    <tr class="bg-hijau-100 text-left">
                        <th class="border border-garis px-3 py-2">No.</th>
                        <th class="border border-garis px-3 py-2">{{ ucfirst(\App\Support\Istilah::calon()) }}</th>
                        <th class="border border-garis px-3 py-2 text-right">Suara</th>
                        <th class="border border-garis px-3 py-2 text-right">%</th>
                    </tr>
                </thead>
                <tbody class="angka">
                    @foreach ($baris as $c)
                        <tr @class(['font-bold' => $pemenang['status'] === 'menang' && $pemenang['kandidat']['id'] === $c['id']])>
                            <td class="border border-garis px-3 py-2">{{ $c['nomor_urut'] }}</td>
                            <td class="border border-garis px-3 py-2">{{ $c['nama_tampil'] }}</td>
                            <td class="border border-garis px-3 py-2 text-right">{{ number_format($c['suara'], 0, ',', '.') }}</td>
                            <td class="border border-garis px-3 py-2 text-right">{{ str_replace('.', ',', $c['persen']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="mt-6 text-tinta">
                @if ($pemenang['status'] === 'menang')
                    Berdasarkan perolehan suara di atas, {{ $pemenang['kandidat']['berpasangan'] ? 'pasangan calon' : 'calon ketua' }} nomor urut <strong>{{ $pemenang['kandidat']['nomor_urut'] }}</strong>,
                    <strong>{{ $pemenang['kandidat']['nama_tampil'] }}</strong>, memperoleh suara terbanyak.
                @elseif ($pemenang['status'] === 'seri')
                    Perolehan suara teratas seri. Pemenang ditetapkan oleh panitia sesuai tata tertib pemilihan.
                @else
                    Belum ada suara yang masuk.
                @endif
            </p>

            <div class="ttd mt-12 grid grid-cols-2 gap-8 text-center text-tinta">
                <div>
                    <p>Ketua Panitia Pemilihan</p>
                    <p class="mt-20 border-t border-tinta pt-1">(.................................)</p>
                </div>
                <div>
                    <p>Malang, {{ $dibuat->translatedFormat('j F Y') }}<br>Pembina OSIM</p>
                    <p class="mt-14 border-t border-tinta pt-1">(.................................)</p>
                </div>
            </div>

            <p class="mt-10 text-xs text-tinta-2">Dibuat otomatis oleh OSIS Digital pada {{ $dibuat->translatedFormat('j F Y, H:i') }} WIB.</p>
        </div>
    </body>
</html>
