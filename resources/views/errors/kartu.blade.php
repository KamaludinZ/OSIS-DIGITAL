{{-- Tata letak halaman galat & perawatan. Sengaja tanpa akses database/sesi. --}}
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
        <meta name="theme-color" content="#0f7a3d" />
        <meta name="robots" content="noindex" />
        <title>{{ $judul }} · OSIM MTsN 2 Kota Malang</title>
        <link rel="icon" href="/favicon.png" type="image/png" />
        @vite(['resources/css/app.css'])
    </head>
    <body class="osim flex min-h-svh flex-col antialiased">
        <section class="osim-guilloche bg-hijau-600 px-4 pb-28 pt-6 text-white sm:px-6">
            <div class="mx-auto max-w-3xl">
                <a href="/" class="flex w-fit items-center gap-2.5">
                    <img src="/images/brand/logo-mtsn2.png" alt="Logo MTsN 2 Kota Malang" class="size-10" />
                    <span class="leading-none">
                        <span class="font-display block text-lg font-black">OSIM</span>
                        <span class="block text-xs font-medium text-hijau-100">MTsN 2 Kota Malang</span>
                    </span>
                </a>
                <p class="font-display angka mt-10 text-[clamp(5rem,22vw,10rem)] font-black leading-[0.8] text-emas-300" aria-hidden="true">{{ $kode }}</p>
            </div>
        </section>

        <main class="-mt-20 flex-1 px-4 pb-12 sm:px-6">
            <div class="mx-auto max-w-3xl overflow-hidden rounded-[14px] bg-white shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis">
                <div class="flex items-center justify-between gap-3 bg-hijau-100 px-6 py-2.5">
                    <span class="font-sempit text-[11px] font-bold uppercase tracking-[0.14em] text-hijau-800">Kode {{ $kode }}</span>
                    <img src="/images/brand/logo-mtsn2.png" alt="" class="size-6" />
                </div>
                <div class="p-6 sm:p-8">
                    <h1 class="font-display text-3xl font-black leading-tight text-tinta sm:text-4xl">{{ $judul }}</h1>
                    <p class="mt-3 max-w-[60ch] text-lg text-tinta-2">{{ $pesan }}</p>
                </div>
                <div class="osim-perforasi"></div>
                <div class="flex flex-col gap-2 p-6 sm:flex-row sm:px-8">
                    @if (! empty($aksi))
                        {!! $aksi !!}
                    @else
                        <a href="/" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emas-400 px-5 py-3 font-black text-hijau-950 shadow-[0_4px_10px_-3px_var(--color-emas-500)] hover:bg-emas-300">Kembali ke Beranda</a>
                        <button type="button" onclick="history.back()" class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-3 font-bold text-hijau-800 ring-1 ring-garis hover:bg-hijau-50">Halaman sebelumnya</button>
                    @endif
                </div>
            </div>
            <p class="mx-auto mt-6 max-w-3xl text-center text-sm text-tinta-2">Masih bermasalah? Hubungi pengurus OSIM di ruang OSIM.</p>
        </main>
    </body>
</html>
