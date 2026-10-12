<?php

use App\Services\LayananPemilihan;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] #[Title('Bukti Suara')] class extends Component {
    #[\Livewire\Attributes\Locked]
    public array $bukti = [];

    public function mount(LayananPemilihan $layanan): void
    {
        $pemilih = app(\App\Services\SesiPemilih::class)->pemilih();
        $bukti = $pemilih ? $layanan->buktiSuara($pemilih) : null;

        if (! $bukti) {
            // Belum memilih: arahkan ke bilik suara.
            $this->redirectRoute('bilik-suara', navigate: true);

            return;
        }

        $this->bukti = $bukti;
    }

    /** Akhiri sesi pemilih agar komputer bisa dipakai siswa berikutnya. */
    public function selesai(): void
    {
        app(\App\Services\SesiPemilih::class)->keluar();
        $this->redirectRoute('home', navigate: true);
    }
}; ?>

<div>
    <x-osim.pita class="pt-6 sm:pt-10 pb-28">
        <h1 class="font-display text-4xl font-black leading-none sm:text-5xl">Suara Anda sudah tercatat</h1>
        <p class="mt-3 max-w-xl text-hijau-50">Terima kasih telah menggunakan hak pilih. Suara Anda sudah dikunci dan tidak bisa diubah lagi.</p>
    </x-osim.pita>

    <x-osim.wadah class="-mt-20 max-w-xl">
        {{-- Kartu bukti memilih --}}
        <section role="status" aria-label="Kartu bukti memilih" class="relative overflow-hidden rounded-[14px] bg-white shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis">
            <div class="flex items-center justify-between gap-3 bg-hijau-100 px-5 py-2.5">
                <x-osim.label class="text-hijau-800">Kartu Bukti Memilih</x-osim.label>
                <img src="/images/brand/logo-mtsn2.png" alt="" class="size-6" />
            </div>

            <div class="relative p-5 sm:p-6">
                <x-osim.stempel class="osim-cap-masuk pointer-events-none absolute -right-3 top-2 size-36 -rotate-12 opacity-90 mix-blend-multiply sm:right-2 sm:size-40" bawah="{{ \Illuminate\Support\Carbon::parse($bukti['waktu'])->translatedFormat('j M Y') }}" />

                <dl class="relative grid gap-4 pr-28 sm:pr-36">
                    <div>
                        <dt><x-osim.label>Pemilihan</x-osim.label></dt>
                        <dd class="mt-0.5 font-bold text-tinta">{{ $bukti['pemilihan'] }}</dd>
                    </div>
                    <div>
                        <dt><x-osim.label>Waktu tercatat</x-osim.label></dt>
                        <dd class="angka mt-0.5 font-bold text-tinta">{{ \Illuminate\Support\Carbon::parse($bukti['waktu'])->translatedFormat('j F Y, H:i') }} WIB</dd>
                    </div>
                </dl>
            </div>

            <div class="osim-perforasi"></div>

            <div class="p-5 sm:p-6">
                <x-osim.label>Kode bukti</x-osim.label>
                <p class="font-sempit angka mt-1 select-all text-3xl font-black tracking-[0.08em] text-hijau-800 sm:text-4xl">{{ $bukti['kode'] }}</p>
                <p class="mt-3 flex items-start gap-2 text-sm text-tinta-2">
                    <flux:icon.eye-slash variant="micro" class="mt-0.5 shrink-0" />
                    Pilihan Anda bersifat rahasia dan tidak ditampilkan di bukti ini.
                </p>
            </div>
        </section>

        <div class="mt-5 flex flex-col gap-2 sm:flex-row print:hidden">
            <button type="button" wire:click="selesai" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-hijau-600 px-5 py-3 font-bold text-white hover:bg-hijau-700">
                <flux:icon.arrow-right-start-on-rectangle variant="mini" /> Selesai &amp; keluar
            </button>
            <button type="button" x-on:click="window.print()" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 font-bold text-hijau-800 ring-1 ring-garis hover:bg-hijau-50">
                <flux:icon.printer variant="mini" /> Cetak bukti
            </button>
        </div>

    </x-osim.wadah>
</div>
