---
version: 1
slug: "resources-views-livewire-beranda-blade-php"
primary_target: "resources/views/livewire/beranda.blade.php"
related_targets: ["resources/views/components/layouts/siswa.blade.php","resources/views/livewire/bilik-suara.blade.php","resources/views/livewire/kandidat/index.blade.php","resources/views/livewire/hasil.blade.php"]
---

# Surface brief — Halaman publik siswa OSIM

Scope: semua halaman publik siswa (Beranda, Bilik Suara + bukti, Kandidat + profil, Hasil, Cari, detail pengumuman/kegiatan) dan layout `components/layouts/siswa`. Mode: Operate (Beranda condong Read).
Audiens: siswa MTsN 2 Kota Malang kelas 7–9, HP & komputer lab. Tugas: baca kabar, kenali kandidat, memilih sekali, lihat hasil.

## Direction contract

THESIS: Setiap siswa memegang kartunya sendiri — aplikasi dibangun dari kartu pelajar/kartu peserta madrasah: nomor besar, stempel resmi, sobekan perforasi. Menolak portal sekolah standar (banner foto + kartu putih abu + sidebar).

OWN-WORLD: Bidang hijau madrasah pekat (#0f7a3d / tinta #063d22) memiliki seluruh kepala halaman; kuning emas sayap logo (#f2c200) untuk aksi utama dan tab mading; merah bingkai logo (#d7261e) hanya untuk stempel/cap. Kartu putih bersudut 14px dengan garis perforasi titik dan takik setengah lingkaran; label kecil huruf kapital ber-spasi seperti isian kartu ("NO. URUT", "NAMA"). Nomor urut raksasa angka tabular. Satu grid 8px.

STORY: Siswa melihat madrasahnya sendiri (logo, salam), langsung tahu pemilihan sedang buka, menekan Mulai Memilih, memilih satu kartu kandidat, menerima kartu bukti bercap "SUDAH MEMILIH".

FIRST VIEWPORT: Pita hijau penuh: logo MTsN 2 + "OSIM MTsN 2 Kota Malang", salam "Assalamu'alaikum, Pemilih!" display besar, lalu kartu pemilihan berperforasi menjorok ke bawah pita: judul pemilihan, jam tutup, tombol kuning Mulai Memilih. Di bawahnya mading: modul bertab kecil (Pengumuman, Agenda) rapat.

FORM: Kartu Peserta Madrasah — kandidat #4 dari daftar berurut; seed key 8a731c68. Raises: papan agenda/rekap yang mengurutkan baris di tempat (gate board); modul mading bertab (high-density); grid 8px tetap (Crouwel).

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
