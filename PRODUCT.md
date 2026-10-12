# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Siswa MTsN 2 Kota Malang (kelas 7–9, usia ±12–15 tahun)** — membaca kabar OSIM, mengenal kandidat, dan memberikan suara pemilihan ketua OSIM. Membuka dari HP sendiri (kabar, kampanye) maupun komputer lab sekolah (pemungutan suara bergiliran).
- **Pengurus OSIM & panitia pemilihan** — menerbitkan pengumuman, agenda, program kerja; menjalankan periode pemilihan dan memantau rekap (dashboard internal terpisah).

## Product Purpose

Satu aplikasi web untuk publikasi kegiatan OSIM sekaligus e-voting ketua OSIM: cepat (memilih sekali tekan), aman (satu siswa satu suara), dan transparan (kabar, profil kandidat, hasil terbuka setelah dipublikasikan). Berhasil bila siswa bisa membaca kabar terbaru dan mengirim suara dalam hitungan detik tanpa kertas.

## Positioning

Dibuat khusus untuk OSIM MTsN 2 Kota Malang — bukan portal sekolah generik: memadukan bilik suara yang menjaga kerahasiaan suara dengan kanal kabar OSIM yang dekat dengan keseharian siswa madrasah.

## Operating Context

- Organisasi siswa disebut **OSIM** (Organisasi Siswa Intra Madrasah), bukan OSIS.
- Pemungutan suara pada jam sekolah, bisa serentak di lab komputer.
- Selama pemilihan berjalan, perolehan per kandidat dirahasiakan; hasil dibuka setelah panitia mempublikasikan.

## Capabilities and Constraints

- Laravel 12 monolit: antarmuka siswa Blade + Livewire (Volt) + Flux + Tailwind 4; dashboard internal Filament (belum dibangun).
- Seluruh antarmuka berbahasa Indonesia.
- Fitur publik: Beranda (pengumuman, agenda, program kerja, cari), Bilik Suara, Kandidat (profil, materi, jadwal, tanya jawab), Hasil.

## Brand Commitments

- Nama: **OSIM MTsN 2 Kota Malang**; produk "OSIS Digital" dipakai sebagai nama aplikasi.
- Logo resmi madrasah: `public/images/brand/logo-mtsn2.png` (sumber: `RESMI-RE-LOGO.png`). Logo memuat kaligrafi *Iqra'*, buku & pena, sayap kuning, perisai hijau berbingkai merah-kuning.
- Warna resmi: **hijau madrasah**.
- Slogan madrasah yang tersedia: "Milenial Religius", "Berbudaya Lingkungan"; tagline Kemenag "Madrasah Maju, Bermutu, Mendunia".
- Logo OSIM: dikonfirmasi ada, **file belum diberikan** — jangan membuat tiruan logo OSIM.

## Evidence on Hand

- Logo madrasah, logo Kemenag, logo Adiwiyata, ikon slogan (folder LOGO milik pengguna).
- Belum ada foto asli kandidat/kegiatan; foto kandidat masih placeholder. Jangan mengarang testimoni, statistik, atau prestasi sekolah.

## Product Principles

1. Memilih harus terasa mudah dan aman bagi siswa kelas 7 yang baru pertama kali.
2. Kerahasiaan suara di atas segalanya — tidak ada tampilan yang membocorkan pilihan atau keunggulan sementara.
3. Terasa milik madrasah sendiri: identitas MTsN 2 Kota Malang dan nilai religius hadir, tidak generik.
4. Ramah HP dan kuota: ringan, terbaca di layar kecil.

## Accessibility & Inclusion

Pengguna remaja awal dengan literasi digital beragam; teks jelas, target sentuh besar, kontras cukup untuk layar HP murah dan proyektor/monitor lab.
