#!/bin/sh
# Menjalankan OSIS Digital sesuai peran kontainer (APP_ROLE): web | worker | scheduler.
set -e
cd /var/www/html

log() { echo "[osis] $*"; }
artisan() { log "php artisan $*"; su-exec www-data php artisan "$@"; }
log "peran: ${APP_ROLE:-web}, PHP $(php -r 'echo PHP_VERSION;')"

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY belum diisi. Buat dengan: php artisan key:generate --show lalu simpan di variabel lingkungan Coolify." >&2
    exit 1
fi

# APP_KEY harus "base64:" + 32 byte acak; format lain membuat semua halaman (dan /up) error 500.
# Bersihkan kutip/spasi/baris baru yang sering ikut tersalin dari panel.
APP_KEY=$(printf '%s' "$APP_KEY" | tr -d " \r\n\t\"'")
# Kunci 32 byte yang tersalin tanpa awalan "base64:" dilengkapi otomatis.
case "$APP_KEY" in
  base64:*) ;;
  *) [ "$(php -r 'echo strlen((string) base64_decode(getenv("APP_KEY"), true));')" = "32" ] && APP_KEY="base64:$APP_KEY" && echo "[osis] APP_KEY tanpa awalan base64: dilengkapi otomatis" ;;
esac
export APP_KEY
if ! php -r '$k = getenv("APP_KEY"); exit(str_starts_with($k, "base64:") && strlen((string) base64_decode(substr($k, 7), true)) === 32 ? 0 : 1);'; then
    php -r '$k = getenv("APP_KEY"); $b = str_starts_with($k, "base64:"); $d = base64_decode($b ? substr($k, 7) : $k, true);
        fwrite(STDERR, sprintf("[osis] APP_KEY tidak valid (panjang %d karakter, awalan base64: %s, hasil decode %s byte; harus 32).\n", strlen($k), $b ? "ada" : "TIDAK ADA", $d === false ? "gagal" : strlen($d)));'
    echo "[osis] Isi APP_KEY di Coolify dengan hasil 'php artisan key:generate --show' secara utuh, contoh bentuk: base64:AbC...xyz= (51 karakter)." >&2
    exit 1
fi

# Volume storage bisa masih kosong saat pertama kali dipasang.
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Laravel memakai DB_URL di atas DB_HOST/DB_DATABASE/... Stack ini dikonfigurasi lewat variabel DB_*,
# jadi DB_URL (sering tersalin dari "Postgres URL" resource lain di Coolify) diabaikan.
if [ -n "${DB_URL:-}" ] || [ -n "${DATABASE_URL:-}" ]; then
    echo "[osis] PERINGATAN: DB_URL/DATABASE_URL diabaikan; koneksi memakai DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD. Hapus DB_URL di Coolify." >&2
    unset DB_URL DATABASE_URL
fi

# DB_HOST salah isi di panel (mis. "db" atau UUID resource lain) tetapi service "postgres" bawaan ada:
# pakai yang bawaan dan beri tahu di log, daripada kontainer gagal terus.
DB_HOST=${DB_HOST:-postgres}
host_dikenal() { H="$1" php -r '$h = getenv("H"); exit(filter_var($h, FILTER_VALIDATE_IP) || gethostbyname($h) !== $h ? 0 : 1);'; }
if ! host_dikenal "$DB_HOST"; then
    if [ "$DB_HOST" != "postgres" ] && host_dikenal postgres; then
        echo "[osis] PERINGATAN: host basis data '$DB_HOST' tidak dikenal; memakai service 'postgres' bawaan. Hapus variabel DB_HOST di Coolify (atau isi 'postgres')." >&2
        DB_HOST=postgres
    else
        echo "[osis] host basis data '$DB_HOST' tidak dikenal di jaringan stack ini. Hapus DB_HOST di Coolify agar memakai 'postgres' bawaan, atau lihat panduan bagian 3a." >&2
    fi
fi
export DB_HOST

# Tunggu PostgreSQL siap menerima koneksi (maks. ~90 detik).
i=0
until php -r 'try { new PDO("pgsql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 5432).";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); } catch (Throwable $e) { exit(1); }'; do
    i=$((i + 1))
    [ "$i" -ge 45 ] && { echo "Basis data tidak bisa dihubungi. Periksa DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD." >&2; exit 1; }
    log "menunggu basis data ${DB_HOST}:${DB_PORT:-5432}... ($i)"; sleep 2
done
log "basis data terhubung (${DB_USERNAME}@${DB_HOST}:${DB_PORT:-5432}/${DB_DATABASE})"

# Worker & scheduler menunggu migrasi dari service app selesai (tabel jobs ada).
tunggu_migrasi() {
    j=0
    until php -r 'try { $d = new PDO("pgsql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 5432).";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit($d->query("select to_regclass(\x27public.jobs\x27) is not null")->fetchColumn() ? 0 : 1); } catch (Throwable $e) { exit(1); }'; do
        j=$((j + 1)); [ "$j" -ge 150 ] && { log "migrasi belum selesai setelah 5 menit"; exit 1; }
        log "menunggu migrasi dari service app... ($j)"; sleep 2
    done
}

case "${APP_ROLE:-web}" in
  web)
    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        artisan migrate --force --no-interaction
    fi
    artisan storage:link --force > /dev/null 2>&1 || true
    artisan optimize
    artisan filament:optimize > /dev/null 2>&1 || true
    log "menyalakan nginx + php-fpm"
    exec /usr/bin/supervisord -c /etc/supervisord.conf
    ;;
  worker)
    tunggu_migrasi
    artisan optimize
    # Berhenti tiap jam agar memori segar; Docker menyalakannya lagi (restart: unless-stopped).
    exec su-exec www-data php artisan queue:work --tries=3 --backoff=10 --max-time=3600 --sleep=3
    ;;
  scheduler)
    tunggu_migrasi
    artisan optimize
    exec su-exec www-data php artisan schedule:work
    ;;
  *)
    exec "$@"
    ;;
esac
