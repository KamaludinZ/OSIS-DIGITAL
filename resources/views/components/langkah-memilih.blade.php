{{-- Empat langkah memilih; dipakai di bilik suara dan halaman Panduan Cara Memilih. --}}
<ol {{ $attributes->class('grid gap-px bg-garis sm:grid-cols-2 lg:grid-cols-4') }}>
    @foreach ([
        ['user-group', 'Kenali kandidat', 'Lihat foto, nomor urut, dan buka profil untuk membaca visi misi.'],
        ['cursor-arrow-rays', 'Pilih satu nomor', 'Tekan tombol “Pilih” pada satu '.\App\Support\Istilah::calon().'. Tekan lagi untuk membatalkan.'],
        ['shield-check', 'Konfirmasi', 'Periksa kembali pilihanmu di layar konfirmasi, lalu tekan “Kirim Suara”.'],
        ['check-badge', 'Simpan bukti', 'Suara langsung tercatat dan terkunci. Kamu akan mendapat kode bukti.'],
    ] as $i => [$ikon, $judul, $isi])
        <li class="flex gap-4 bg-white p-5">
            <span class="font-display angka text-5xl font-black leading-none text-hijau-200">{{ $i + 1 }}</span>
            <div>
                <div class="flex items-center gap-1.5 font-bold text-tinta"><flux:icon :name="$ikon" variant="mini" class="text-hijau-600" /> {{ $judul }}</div>
                <p class="mt-1 text-sm text-tinta-2">{{ $isi }}</p>
            </div>
        </li>
    @endforeach
</ol>
