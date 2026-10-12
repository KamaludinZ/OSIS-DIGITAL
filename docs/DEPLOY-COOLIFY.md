# Panduan Deploy OSIS Digital ke Coolify

Panduan ini memasang OSIS Digital (OSIM MTsN 2 Kota Malang) di server sendiri memakai
**Coolify v4** dengan **Dockerfile + Docker Compose** yang sudah ada di repositori.

---

## 1. Gambaran susunan

| Service    | Isi                                   | Tugas                                                                  |
|------------|---------------------------------------|------------------------------------------------------------------------|
| `app`      | Nginx + PHP-FPM (image `Dockerfile`)  | Melayani situs & Ruang OSIM, menjalankan migrasi saat deploy           |
| `worker`   | Image yang sama, `APP_ROLE=worker`    | Mengirim pemberitahuan akun (antrean basis data)                       |
| `scheduler`| Image yang sama, `APP_ROLE=scheduler` | Pengumuman terjadwal (tiap menit) & pengingat pemilihan (tiap 5 menit) |
| `postgres` | PostgreSQL 18                         | Basis data                                                             |

Volume tetap:
- `osis-storage` → foto kandidat, poster, thumbnail media sosial (unggahan).
- `osis-pgdata` → data PostgreSQL.

HTTPS, sertifikat, dan domain diurus **Traefik bawaan Coolify**. Kontainer tidak membuka port ke internet.

---

## 2. Yang perlu disiapkan

1. **Server (VPS)** dengan Coolify v4 sudah terpasang.
   Saran minimal untuk ±1.000 pemilih: 2 vCPU, 4 GB RAM, 30 GB disk.
2. **Domain/subdomain**, mis. `osim.mtsn2kotamalang.sch.id`, dengan **A record** mengarah ke IP server.
3. **Repositori GitHub** proyek ini (`KamaludinZ/OSIS-DIGITAL`).
   Untuk repo privat, hubungkan lewat **Sources → GitHub App** di Coolify.
4. Dua nilai rahasia yang dibuat sekali:

   ```bash
   # APP_KEY (jalankan di folder proyek di komputer)
   php artisan key:generate --show

   # Kata sandi basis data yang kuat
   openssl rand -base64 32
   ```

   Simpan di pengelola kata sandi. **Jangan** dimasukkan ke repositori.

---

## 3. Langkah di Coolify

1. **Projects → (pilih/buat project) → + New → Resource**.
2. Pilih **Private Repository (with GitHub App)** atau **Public Repository**.
   - Repository: `KamaludinZ/OSIS-DIGITAL`
   - Branch: `main`
3. **Build Pack: Docker Compose**.
   - Base Directory: `/`
   - Docker Compose Location: `/docker-compose.yml`
4. Klik **Continue**. Coolify membaca keempat service.
5. Buka tab **Environment Variables** dan isi (contoh lengkap ada di `docs/coolify.env.example`):

   | Variabel       | Wajib | Contoh / keterangan                                        |
   |----------------|:-----:|------------------------------------------------------------|
   | `APP_KEY`      | ✅    | `base64:…` dari `php artisan key:generate --show`          |
   | `APP_URL`      | ✅    | `https://osim.mtsn2kotamalang.sch.id`                      |
   | `DB_PASSWORD`  | ✅    | hasil `openssl rand -base64 32`                            |
   | `DB_DATABASE`  |       | bawaan `osis_digital`                                      |
   | `DB_USERNAME`  |       | bawaan `osis_digital`                                      |
   | `LOG_LEVEL`    |       | bawaan `warning`                                           |
   | `RUN_MIGRATIONS` |     | bawaan `true` (migrasi otomatis tiap deploy)               |

   Tandai `APP_KEY` dan `DB_PASSWORD` sebagai **secret/locked** bila tersedia.
6. Di bagian **Services → app → Domains**, isi `https://osim.mtsn2kotamalang.sch.id`.
   Bila Coolify meminta port, gunakan `https://osim.mtsn2kotamalang.sch.id:80`
   (port internal kontainer; publik tetap 443).
   Service `worker`, `scheduler`, dan `postgres` **tidak** diberi domain.
7. Klik **Deploy**. Build pertama ±5–10 menit (Composer, npm, ekstensi PHP).
8. Tunggu sampai keempat service **Running (healthy)**.
   `app` baru sehat setelah migrasi selesai dan `/up` merespons.

> **Penting:** `APP_KEY` jangan diganti setelah aplikasi dipakai. Sesi dan data
> terenkripsi akan rusak, dan semua orang harus masuk ulang.

---

## 4. Setelah deploy pertama

### 4.1 Buat akun admin pertama

Tidak ada pendaftaran mandiri, dan data contoh **tidak** dipasang di produksi.
Buka **Coolify → service `app` → Terminal**, lalu jalankan:

```bash
php artisan osim:buat-admin admin@mtsn2kotamalang.sch.id --nama="Admin Madrasah"
```

Masukkan kata sandi minimal 12 karakter, atau kosongkan agar dibuatkan otomatis
(ditampilkan sekali). Masuk di `https://<domain>/login`, lalu ganti kata sandi di **Pengaturan akun**.

### 4.2 Urutan menyiapkan pemilihan

1. **Ruang OSIM → Pengaturan → Akun Pengguna**: buat akun pengurus dan panitia.
2. **Pemilihan → Periode Pemilihan**: buat periode.
   Pilih format **Ketua saja** atau **Ketua + wakil**, isi jam buka/tutup (WIB), status *Draf*.
3. **Pemilihan → Kandidat**: tambahkan kandidat, foto, visi-misi.
   Isi juga tab *Materi kampanye* dan *Jadwal kampanye*.
4. **Akun Pengguna**: buat akun berperan **Kandidat** untuk tiap kandidat, agar mereka bisa menanggapi tanya jawab.
5. **Pemilihan → Data Pemilih → Impor data**: unggah data siswa (NISN) dan GTK (NIK) memakai templat.
6. **Buat kode pilih**, lalu **Cetak kartu pemilih** dan bagikan.
7. Pada hari pemilihan, ubah status periode menjadi **Berjalan**.
   Bilik suara terbuka otomatis di antara jam buka dan tutup.
8. Setelah ditutup, panitia mempublikasikan hasil dari halaman **Hasil**.

---

## 5. Antrean & penjadwal

- **worker** mengirim pemberitahuan akun (pengumuman baru, pertanyaan untuk kandidat,
  pengingat pemilihan, peringatan tebakan kode).
  Bila service ini mati, pemberitahuan tertahan di tabel `jobs` dan terkirim setelah worker hidup lagi.
- **scheduler** menjalankan:
  - `osim:kirim-pengumuman-terjadwal` setiap menit;
  - `osim:kirim-pengingat-pemilihan` setiap 5 menit (H-1, saat dibuka, 3 jam sebelum ditutup).
- Cek dari Terminal service `app`:

  ```bash
  php artisan schedule:list
  php artisan queue:failed      # pekerjaan yang gagal
  php artisan queue:retry all   # coba ulang yang gagal
  ```

---

## 6. Cadangan (backup) — wajib sebelum hari pemilihan

### 6.1 Basis data

Di **Coolify → service `postgres` → Scheduled Tasks**, tambahkan tugas harian (mis. `0 1 * * *`):

```bash
sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc > /var/lib/postgresql/cadangan-$(date +%F).dump && find /var/lib/postgresql -name "cadangan-*.dump" -mtime +14 -delete'
```

Unduh salinan secara berkala ke tempat lain (Google Drive sekolah, dll.).
Pulihkan dari Terminal `postgres`:

```bash
pg_restore -U "$POSTGRES_USER" -d "$POSTGRES_DB" --clean --if-exists /var/lib/postgresql/cadangan-2026-10-20.dump
```

### 6.2 Berkas unggahan

Volume `osis-storage` berisi foto kandidat, poster, dan thumbnail media sosial.
Coolify menambahkan awalan ID resource pada nama volume. Cari nama lengkapnya di server:

```bash
docker volume ls | grep osis-storage
```

Lalu cadangkan (ganti `<nama-volume>`):

```bash
docker run --rm -v <nama-volume>:/data -v $PWD:/backup alpine tar czf /backup/storage-$(date +%F).tgz -C /data .
```

### 6.3 Data tetap aman saat redeploy

Semua data yang harus bertahan disimpan di volume, bukan di dalam image:

| Data | Lokasi | Volume |
|---|---|---|
| Foto kandidat, poster, thumbnail, berkas unggahan sementara | `storage/app/…` | `osis-storage` |
| Basis data (termasuk sesi, cache, antrean) | `/var/lib/postgresql` | `osis-pgdata` |

Redeploy membangun ulang image tetapi **tidak** menyentuh volume. Data hanya hilang bila:
- resource dihapus di Coolify **dengan** pilihan hapus volume dicentang;
- nama volume di `docker-compose.yml` diubah, sehingga Coolify membuat volume baru yang kosong
  (**jangan ganti** `osis-storage` / `osis-pgdata`);
- `docker compose down -v` dijalankan manual.

**Cek setelah deploy pertama:**
1. Unggah satu foto kandidat.
2. Klik **Redeploy**.
3. Pastikan fotonya masih tampil.

Volume juga terlihat di Coolify → resource → **Persistent Storage**.

---

## 7. Memperbarui aplikasi

1. Push perubahan ke branch `main`.
2. Di Coolify klik **Redeploy**, atau aktifkan **Auto Deploy** (webhook GitHub).
3. Migrasi berjalan otomatis (`RUN_MIGRATIONS=true`).
4. Hindari deploy **saat pemungutan suara berlangsung**. Bila terpaksa, deploy setelah jam tutup.

Rollback: **Deployments → pilih deploy sebelumnya → Redeploy**.
Bila migrasi baru sudah berjalan, pulihkan juga basis data dari cadangan terakhir.

---

## 8. Daftar periksa keamanan produksi

- [ ] `APP_DEBUG=false` dan `APP_ENV=production` (sudah diatur di compose).
- [ ] `APP_URL` memakai `https://`; cookie sesi aman (`SESSION_SECURE_COOKIE=true`) dan terenkripsi.
- [ ] `APP_KEY` dan `DB_PASSWORD` hanya ada di Coolify, tidak di Git.
- [ ] PostgreSQL **tidak** diberi domain/port publik.
- [ ] Admin pertama sudah mengganti kata sandi sementara.
- [ ] Akun contoh (`*@osis.test`) **tidak ada** di produksi. Seeder contoh otomatis menolak berjalan di produksi.
- [ ] Cadangan basis data harian aktif dan pernah dicoba dipulihkan.
- [ ] Kartu pemilih dibagikan langsung ke pemiliknya. Kode yang bocor bisa dibuat ulang dari Data Pemilih.
- [ ] Bila memakai Cloudflare/proxy tambahan, sesuaikan `TRUSTED_PROXIES`.
  Batas tebakan kode pilih bergantung pada IP asli pengunjung.

Perlindungan yang sudah ada di aplikasi:
- **Kode pilih:** dibatasi 5 salah per sesi per menit, 20 per IP per menit, dan 60 per IP per jam. Panitia diberi tahu saat ada lonjakan.
- **Bilik suara:** sesi berakhir sendiri setelah 15 menit.
- **Hak akses:** dijaga di server untuk setiap menu, ditambah header keamanan.
- **Unggahan:** hanya menerima JPG/PNG/WebP dan tidak pernah dieksekusi oleh Nginx.

---

## 9. Mengatasi masalah

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| Deploy gagal di tahap `composer`/`npm` | Jaringan server / memori kurang | Redeploy; pastikan RAM ≥ 2 GB saat build |
| Log `APP_KEY belum diisi` | Variabel kosong | Isi `APP_KEY`, redeploy |
| Log `Menunggu basis data...` terus | Kata sandi DB berubah setelah volume dibuat | Samakan `DB_PASSWORD` dengan saat pertama deploy, atau reset volume `osis-pgdata` (data hilang!) |
| Halaman 419 "Sesi kedaluwarsa" | Domain diakses lewat `http://` atau `APP_URL` beda dengan domain | Pakai `https://` dan samakan `APP_URL` |
| Foto/poster tidak tampil | Tautan storage belum dibuat | Terminal `app`: `php artisan storage:link --force` |
| Pemberitahuan tidak muncul | Service `worker` mati | Cek log `worker`, lalu `php artisan queue:retry all` |
| Pengumuman terjadwal tidak tayang | Service `scheduler` mati | Cek log `scheduler` |
| Jam tidak sesuai | Zona waktu | Aplikasi memakai `Asia/Jakarta` (WIB); cek `APP_TIMEZONE` bila diubah |
| Error 500 | Lihat detail di log | Coolify → service `app` → Logs |

---

## 10. Menguji image produksi di komputer sendiri (opsional)

Butuh Docker yang bisa dipakai user Anda (`sudo usermod -aG docker $USER`, lalu login ulang).

```bash
# Berkas lingkungan khusus uji (jangan di-commit)
cat > .env.docker <<'ENV'
APP_KEY=base64:GANTI_DENGAN_HASIL_key:generate
APP_URL=http://localhost:8080
DB_PASSWORD=rahasia-lokal-saja
ENV

docker compose -f docker-compose.yml -f docker-compose.local.yml --env-file .env.docker up --build
# buka http://localhost:8080, lalu buat admin:
docker compose exec app php artisan osim:buat-admin admin@contoh.id
```

Bersihkan: `docker compose -f docker-compose.yml -f docker-compose.local.yml down -v`.
