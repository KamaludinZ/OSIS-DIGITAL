<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] #[Title('Panduan Cara Memilih')] class extends Component {
    //
}; ?>

<div>
    <x-osim.pita class="pt-6 pb-24 sm:pt-10">
        <h1 class="font-display text-4xl font-black leading-[1.02] sm:text-5xl">Panduan Cara Memilih</h1>
        <p class="mt-3 max-w-xl text-hijau-50">Pemilihan ketua OSIM cukup dengan kode pilih 6 angka dari panitia. Tanpa akun, tanpa kata sandi, dan pilihanmu rahasia.</p>
    </x-osim.pita>

    <x-osim.wadah class="-mt-16 flex max-w-5xl flex-col gap-6">
        <section aria-labelledby="judul-langkah" class="overflow-hidden rounded-[14px] bg-white shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis">
            <h2 id="judul-langkah" class="font-display px-5 pt-5 text-2xl font-black text-tinta sm:px-6">Empat langkah memilih</h2>
            <p class="px-5 pb-4 text-tinta-2 sm:px-6">Siapkan kartu pemilihmu, lalu buka Bilik Suara dan masukkan kode pilih.</p>
            <x-langkah-memilih />
        </section>

        <section aria-labelledby="judul-tanya" class="rounded-[14px] bg-white p-5 ring-1 ring-garis sm:p-6">
            <h2 id="judul-tanya" class="font-display text-2xl font-black text-tinta">Pertanyaan umum</h2>
            <div class="mt-3 divide-y divide-dashed divide-garis">
                @foreach ([
                    ['Di mana kode pilihku?', 'Kode 6 angka tertera di kartu pemilih yang dibagikan panitia. Kode hanya berlaku untukmu dan sekali pakai.'],
                    ['Kartu pemilihku hilang atau kodenya salah.', 'Datangi meja panitia dengan identitasmu. Panitia bisa mengecek data dan membuatkan kode baru.'],
                    ['Bisakah aku mengubah pilihan?', 'Sebelum menekan “Kirim Suara” kamu bebas mengganti pilihan. Setelah dikirim, suara terkunci dan tidak bisa diubah.'],
                    ['Apakah orang lain tahu pilihanku?', 'Tidak. Kartu bukti hanya memuat kode bukti dan waktu, bukan nomor yang kamu pilih.'],
                    ['Untuk apa kode bukti?', 'Sebagai tanda kamu sudah menggunakan hak pilih. Simpan atau cetak bila diminta wali kelas.'],
                ] as [$tanya, $jawab])
                    <details class="group py-3">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 font-bold text-tinta">
                            {{ $tanya }}
                            <flux:icon.chevron-down variant="micro" class="shrink-0 transition group-open:rotate-180" />
                        </summary>
                        <p class="mt-2 text-tinta-2">{{ $jawab }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        <div class="flex flex-col gap-2 sm:flex-row">
            <a href="{{ route('bilik-suara') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-xl bg-emas-400 px-5 py-3 font-black text-hijau-950 shadow-[0_4px_10px_-3px_var(--color-emas-500)] hover:bg-emas-300">
                <flux:icon.check-badge variant="mini" /> Buka Bilik Suara
            </a>
            <a href="{{ route('kandidat.index') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 font-bold text-hijau-800 ring-1 ring-garis hover:bg-hijau-50">
                <flux:icon.user-group variant="mini" /> Kenali kandidat
            </a>
        </div>
    </x-osim.wadah>
</div>
