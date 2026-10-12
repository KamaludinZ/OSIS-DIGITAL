# Laporan Audit Keamanan & Fungsional — OSIS Digital

Tanggal: 12 Oktober 2026 · Cakupan: aplikasi publik (Beranda, Bilik Suara, Kandidat, Hasil,
Pemberitahuan), Ruang OSIM (Filament), autentikasi, antrean/penjadwal, dan konfigurasi produksi.

## Metode

1. **Uji otomatis di dua basis data.** 177 uji fitur dijalankan di SQLite dan **PostgreSQL 18**
   (skema terpisah `pengujian`, koneksi `pgsql_uji`).
2. **Uji ujung-ke-ujung di peramban.** Chrome headless dijalankan terhadap server lokal dengan PostgreSQL:
   - memilih dengan kode, bukti suara, keluar, menolak kode bekas;
   - verifikasi NISN + tanggal lahir lalu bertanya;
   - akun kandidat masuk, menanggapi, dan dialihkan dari `/admin`.
3. **Telaah kode.** Diperiksa: otorisasi tiap menu, properti Livewire yang bisa diubah peramban,
   batas percobaan, XSS (`{!! !!}`), SQL mentah, unggahan berkas, pengalihan, header HTTP, dan proxy.
4. **Audit dependensi.** `composer audit` dan `npm audit`.

## Temuan & perbaikan

| # | Tingkat | Temuan | Perbaikan |
|---|---|---|---|
| 1 | **Kritis** | Setelah kode pilih dimasukkan, aksi berikutnya di bilik suara gagal **419 (sesi kedaluwarsa)**. `session()->regenerate()` mengganti token CSRF, sedangkan halaman Livewire masih memakai token lama. Hanya terlihat di peramban sungguhan. | Pakai `session()->migrate(true)`: ID sesi tetap baru (anti *session fixation*), token CSRF tetap. Berlaku juga untuk verifikasi penanya. Ada uji regresi. |
| 2 | **Kritis** | Seluruh Ruang OSIM error 500 di PostgreSQL setelah lonceng pemberitahuan aktif. Kolom `notifications.data` bertipe `text`, padahal Filament memakai operator JSON. | Migrasi mengubah kolom menjadi `json`. Semua uji kini juga dijalankan di PostgreSQL. |
| 3 | **Tinggi** | Batas tebakan kode pilih **direset saat berhasil masuk**. Pemilik satu kode sah bisa menyelipkannya untuk menebak kode orang lain tanpa batas. | Batas berlapis tanpa reset: 5 salah/sesi/menit, 20/IP/menit, 60/IP/jam. Panitia diberi tahu bila >40 salah per jam dari satu IP. Batas per sesi membuat satu komputer lab yang salah ketik tidak mengunci seluruh lab. |
| 4 | **Tinggi** | Zona waktu aplikasi `UTC`, padahal semua jam ditampilkan "WIB". Jam buka/tutup bilik suara yang diisi panitia bergeser 7 jam. | `APP_TIMEZONE` bawaan `Asia/Jakarta`. |
| 5 | Sedang | Sesi bilik suara yang ditinggal di komputer lab bisa dipakai orang berikutnya untuk memilih atas nama pemilik kode. | Sesi pemilih kedaluwarsa otomatis setelah 15 menit. |
| 6 | Sedang | Properti Livewire milik server bisa diubah dari peramban (id pemilihan di bilik suara, data kandidat di profil, dll.). | `#[Locked]` pada semua properti milik server. Kiriman suara juga dicek ulang ke pemilihan aktif. |
| 7 | Sedang | Di balik reverse proxy, semua pengunjung tampak ber-IP sama. Batas per IP jadi salah sasaran. Mempercayai semua proxy (`*`) membuka pemalsuan `X-Forwarded-For`. | Hanya jaringan privat (Traefik/Docker) yang dipercaya. Ada uji pemalsuan header. |
| 8 | Sedang | Unggahan gambar di Ruang OSIM (`image()`) bisa menerima **SVG** yang dapat berisi skrip. | Dibatasi JPG/PNG/WebP. Nginx menyajikan `/storage` dengan CSP *sandbox* dan tidak pernah menjalankan PHP di sana. |
| 9 | Rendah | Belum ada policy untuk model pemilihan (periode, kandidat, materi, jadwal, tanya jawab). Yang ada baru pembatasan per halaman. | `KelolaPemilihanPolicy` (panitia/admin), lapisan kedua di server. |
| 10 | Rendah | Belum ada header keamanan HTTP. | `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS (saat HTTPS). |
| 11 | Rendah | 13 kerentanan paket npm (alat build) dan `axios` yang tidak terpakai. | `axios` dihapus, `npm audit fix` → **0 kerentanan**. `composer audit` bersih. |

## Yang diperiksa dan dinyatakan aman

- **Kerahasiaan suara:**
  - bukti suara tidak memuat pilihan;
  - perolehan per kandidat tidak masuk state Livewire sebelum dipublikasikan;
  - laporan hasil (cetak/CSV) dijaga gate `lihat-perolehan`.
- **Satu pemilih satu suara:**
  - kelayakan dicek di server;
  - kandidat harus milik pemilihan aktif;
  - indeks unik `(election_id, voter_id)` menjadi penjaga terakhir untuk permintaan bersamaan.
- **SQL:** semua kueri mentah statis atau memakai parameter.
- **XSS:** satu-satunya `{!! !!}` berisi HTML statis halaman galat. Isi pengguna selalu di-escape. Daftar pemberitahuan memakai `x-text`.
- **Tautan media sosial:** wajib `https` dan domain platform yang cocok persis (`/sosmed/{id}/buka`).
- **Akun:**
  - login hanya untuk staf dan kandidat;
  - akun nonaktif langsung diputus;
  - reset kata sandi memutus sesi di perangkat lain;
  - ganti kata sandi dibatasi 5 percobaan per menit dan harus berbeda dari yang lama.
- **Otorisasi menu:**
  - pengurus: publikasi dan organisasi;
  - panitia: pemilihan, data pemilih, dan partisipasi;
  - admin: semuanya, termasuk akun;
  - kandidat: hanya tanya jawab miliknya.

## Risiko yang tersisa (keputusan desain)

- **Kode pilih 6 angka** tetap bisa ditebak secara teori bila penyerang memakai banyak IP.
  Mitigasi: batas berlapis, peringatan ke panitia, dan kode bisa dibuat ulang massal.
  Bagikan kartu langsung ke pemiliknya.
- **Verifikasi penanya** (NISN + tanggal lahir) memakai data yang mungkin diketahui teman sekelas.
  Dampaknya terbatas: hanya bisa bertanya atas nama orang lain, dan panitia bisa menyembunyikan pertanyaan.
- **Pemberitahuan sistem peramban** hanya muncul selama situs terbuka (belum ada *web push*).

## Cara mengulang audit

```bash
php artisan test                                  # SQLite (cepat)
DB_CONNECTION=pgsql_uji php artisan test          # PostgreSQL, skema "pengujian" (data dev aman)
composer audit && npm audit
```
