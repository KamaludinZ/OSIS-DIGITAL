<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
        <meta name="theme-color" content="#0f7a3d" />
        <title>{{ isset($title) ? $title.' · ' : '' }}OSIM MTsN 2 Kota Malang</title>
        <link rel="icon" href="/favicon.png" type="image/png" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="osim min-h-svh antialiased lg:grid lg:grid-cols-[1.1fr_1fr]">
        {{-- Sisi pita hijau madrasah --}}
        <section class="osim-guilloche flex flex-col bg-hijau-600 px-6 pb-24 pt-6 text-white sm:px-10 lg:min-h-svh lg:pb-10">
            <a href="{{ route('home') }}" wire:navigate class="flex w-fit items-center gap-2.5">
                <img src="/images/brand/logo-mtsn2.png" srcset="/images/brand/logo-mtsn2@2x.png 2x" alt="Logo MTsN 2 Kota Malang" class="size-11" />
                <span class="leading-none">
                    <span class="font-display block text-xl font-black">OSIM</span>
                    <span class="block text-xs font-medium text-hijau-100">MTsN 2 Kota Malang</span>
                </span>
            </a>

            <div class="mt-10 lg:mt-auto lg:mb-auto">
                <p class="font-display max-w-md text-4xl font-black leading-[0.98] sm:text-5xl">
                    Ruang kerja<br><span class="text-emas-300">pengurus OSIM.</span>
                </p>
                <p class="mt-4 max-w-md text-hijau-50">Kelola kabar, kegiatan, data pemilih, dan pemilihan ketua OSIM.</p>
            </div>

            <p class="hidden text-sm text-hijau-100 lg:block"><em>Milenial Religius, Berbudaya Lingkungan</em></p>
        </section>

        {{-- Sisi formulir --}}
        <main class="-mt-16 px-4 pb-10 sm:px-10 lg:mt-0 lg:flex lg:items-center lg:justify-center lg:py-10">
            <div class="mx-auto w-full max-w-md">
                {{ $slot }}
                <a href="{{ route('home') }}" wire:navigate
                   class="mt-4 flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 font-bold text-hijau-800 ring-1 ring-garis hover:bg-hijau-100">
                    <flux:icon.arrow-left variant="micro" /> Kembali ke Beranda
                </a>
            </div>
        </main>

        @fluxScripts
    </body>
</html>
