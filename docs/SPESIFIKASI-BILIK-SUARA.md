# Spesifikasi Bilik Suara

Spesifikasi tiga sub-fitur bilik suara: **Pilih & Konfirmasi**, **Bukti Sudah Memilih**, dan
**Panduan Cara Memilih**. Semua perilaku di bawah sudah diimplementasikan dan dijaga uji otomatis.

Alamat `/bilik-suara`, `/bilik-suara/bukti`, dan `/panduan-memilih` hanya terbuka saat **mode
pemilihan aktif** (lihat `WajibModePemilihan`). Di luar masa pemilihan, kunjungan dialihkan ke
`/terkunci`, dan aksi Livewire yang sedang terbuka ditolak (423).

## 1. Pilih & Konfirmasi

**Tempat:** `resources/views/livewire/bilik-suara.blade.php`, komponen `<x-kartu-kandidat>` dan modal
`konfirmasi-suara`. Layanan: `App\Services\LayananPemilihan`.

### Alur

1. Pemilih memasukkan **kode pilih 6 angka** (`SesiPemilih::masukDenganKode`). Tebakan salah dibatasi
   berlapis (per sesi & per jaringan); panitia diberi tahu bila tebakan melonjak.
2. Sistem memeriksa **kelayakan** (`LayananPemilihan::kelayakan`), berurutan:

   | Alasan | Kondisi | Pesan ke pemilih |
   |---|---|---|
   | `pemilihan_tertutup` | Tidak ada pemilihan atau di luar jam buka | Bilik suara sedang tidak dibuka. Cek jadwal di Beranda. |
   | `belum_kode` | Belum memasukkan kode | Masukkan kode pilih 6 digit dari panitia. |
   | `bukan_pemilih` | Verifikasi `menunggu` / `ditolak` | Arahan ke meja panitia. |
   | `sudah_memilih` | Sudah tercatat memilih | Suara sudah tercatat dan tidak bisa diubah. |

3. Pemilih menekan **Pilih nomor N** pada satu kartu (`pilih`). Hanya satu pilihan aktif; menekan kartu
   yang sama membatalkan. ID kandidat di luar pemilihan aktif diabaikan.
4. **Lanjut ke konfirmasi** (`bukaKonfirmasi`) menolak bila belum ada pilihan. Modal menampilkan nomor,
   foto, nama calon, dan peringatan bahwa suara tidak bisa diubah. **Batal, pilih ulang** menutup modal
   tanpa mengirim.
5. **Kirim Suara** (`kirimSuara`) memeriksa ulang kelayakan dan bahwa pemilihan belum berganti, lalu
   memanggil `LayananPemilihan::kirimSuara`.

### Penyimpanan (anonim)

Satu transaksi menulis **dua catatan yang tidak saling terhubung**:

| Tabel | Isi | Tidak memuat |
|---|---|---|
| `votes` (kehadiran) | `election_id`, `voter_id`, `kode_bukti`, `created_at` | pilihan kandidat |
| `ballots` (surat suara) | `id` UUID acak, `election_id`, `candidate_id` | identitas pemilih, waktu |

Rekap `vote_tallies` bertambah dalam transaksi yang sama. Penjaga:

- **Suara ganda** ditolak berlapis: kelayakan di server, lalu indeks unik `votes(election_id, voter_id)`.
- **Tabrakan kode bukti** saat bersamaan diterbitkan ulang (paling banyak 3 kali), bukan dianggap
  sudah memilih.
- **Tidak bisa diubah:** trigger basis data menolak `UPDATE` pada `votes` dan `ballots`; model juga menolak.
- **Gangguan sistem** tak terduga: suara tidak tercatat, pilihan tetap di layar, pemilih diminta mencoba lagi.

## 2. Bukti Sudah Memilih

**Tempat:** `resources/views/livewire/bilik-suara/bukti.blade.php`. Data: `LayananPemilihan::buktiSuara`.

- **Data:** `kode` (format `OSIM-XXXX-XXXX`, huruf/angka tanpa 0/O/1/I/L), `pemilihan` (judul),
  `waktu` tercatat. **Tidak pernah** memuat kandidat yang dipilih.
- **Kartu bukti:** stempel "SUDAH MEMILIH", waktu, kode bukti yang bisa diseleksi/disalin, catatan
  kerahasiaan pilihan.
- **Aksi:** *Selesai & keluar* (mengakhiri sesi pemilih agar komputer bisa dipakai berikutnya),
  *Cetak bukti* (gaya cetak tanpa menu), *Kembali* ke Beranda.
- **Keadaan kosong:** tanpa sesi atau belum memilih, halaman menampilkan "Belum ada bukti suara" dengan
  tombol ke bilik suara dan Beranda (tidak dialihkan diam-diam).
- Penanda "sudah memilih" juga tersedia di model (`Voter::sudahMemilih`), tombol Beranda, dan kolom/filter
  Data Pemilih di Ruang OSIM.

## 3. Panduan Cara Memilih

**Tempat:** komponen `<x-langkah-memilih>`, dipakai di bilik suara dan di halaman `/panduan-memilih`.

- **Empat langkah:** Kenali kandidat → Pilih satu nomor → Konfirmasi → Simpan bukti.
- **Di bilik suara:** tampil pertama kali sebelum daftar kandidat; bisa dilewati (diingat di sesi) dan
  dibuka lagi lewat tombol "Lihat panduan cara memilih".
- **Halaman panduan:** langkah memilih, pertanyaan umum (kode pilih, kartu hilang, mengubah pilihan,
  kerahasiaan, kegunaan kode bukti), dan tautan ke Bilik Suara serta daftar kandidat.
- Tata letak responsif: 1 kolom (ponsel), 2 kolom (tablet), 4 kolom (desktop).

## Uji terkait

`BilikSuaraTest`, `BilikSuaraLanjutanTest`, `SuaraAnonimTest`, `AgregasiSuaraTest`, `KartuKandidatTest`,
`ModePemilihanTest`, `SakelarModePemilihanTest`.
