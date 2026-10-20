<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Kartu Pemilih{{ $kelompok ? ' · '.($kelompok === 'gtk' ? 'GTK' : 'Kelas '.$kelompok) : '' }}</title>
        <link rel="icon" href="/favicon.png" type="image/png" />
        @vite(['resources/css/app.css'])
        <style>
            @page { size: A4; margin: 10mm; }
            .kartu { break-inside: avoid; }
        </style>
    </head>
    <body class="osim py-8 print:py-0" style="background:#fff">
        <div class="mx-auto max-w-5xl px-6 print:max-w-none print:px-0">
            @if ($contoh ?? false)
                <p class="mb-4 rounded-lg bg-emas-200 px-3 py-2 text-sm font-bold text-hijau-950">Pratinjau tata letak dengan data contoh — bukan kode asli. 3 kolom per baris pada kertas A4, gunting di garis putus-putus.</p>
            @else
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 print:hidden">
                <div>
                    <h1 class="font-display text-2xl font-black text-tinta">Kartu Pemilih</h1>
                    <p class="text-tinta-2">{{ $pemilih->count() }} kartu{{ $kelompok ? ' · '.($kelompok === 'gtk' ? 'GTK' : 'Kelas '.$kelompok) : '' }}. Gunting di garis putus-putus. Kode bersifat rahasia — bagikan langsung ke pemiliknya.</p>
                </div>
                <div class="flex gap-2">
                    <a href="/admin/data-pemilih" class="rounded-xl px-4 py-2 font-bold text-hijau-800 ring-1 ring-garis hover:bg-hijau-50">Kembali</a>
                    <button type="button" onclick="window.print()" class="rounded-xl bg-emas-400 px-4 py-2 font-black text-hijau-950 hover:bg-emas-300">Cetak</button>
                </div>
            </div>
            @endif

            @if ($pemilih->isEmpty())
                <p class="rounded-xl bg-emas-200 p-4 font-bold text-hijau-950">Belum ada pemilih terverifikasi yang punya kode pilih. Buat kode dulu di Data Pemilih.</p>
            @endif

            <div class="grid grid-cols-3 gap-0 border-l border-t border-dashed border-tinta-2/50">
                @foreach ($pemilih as $p)
                    <article class="kartu border-b border-r border-dashed border-tinta-2/50 p-3">
                        <div class="overflow-hidden rounded-lg ring-1 ring-garis">
                            <div class="flex items-center gap-2 bg-hijau-600 px-3 py-1.5 text-white">
                                <img src="/images/brand/logo-mtsn2.png" alt="" class="size-5" />
                                <span class="font-sempit text-[10px] font-bold uppercase tracking-[0.14em]">Kartu Pemilih · OSIM MTsN 2</span>
                            </div>
                            <div class="px-3 pb-3 pt-2">
                                <p class="truncate text-sm font-bold text-tinta">{{ $p->nama }}</p>
                                <p class="text-xs text-tinta-2">{{ $p->keterangan() }}</p>
                                <p class="font-sempit text-[9px] font-bold uppercase tracking-[0.14em] text-tinta-2 mt-2">Kode pilih</p>
                                <p class="font-sempit angka text-3xl font-black tracking-[0.25em] text-hijau-800">{{ $p->kode_pilih }}</p>
                                <p class="mt-1 text-[10px] leading-tight text-tinta-2">Buka <strong>{{ preg_replace('#^https?://#', '', rtrim(config('app.url'), '/')) }}/bilik-suara</strong>, masukkan kode ini. Berlaku satu kali{{ $pemilihan ? ' untuk '.$pemilihan->judul : '' }}.</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </body>
</html>
