#!/bin/sh
# Menjalankan OSIS Digital sesuai peran kontainer (APP_ROLE): web | worker | scheduler.
set -e
cd /var/www/html

artisan() { su-exec www-data php artisan "$@"; }

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY belum diisi. Buat dengan: php artisan key:generate --show lalu simpan di variabel lingkungan Coolify." >&2
    exit 1
fi

# Volume storage bisa masih kosong saat pertama kali dipasang.
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Tunggu PostgreSQL siap menerima koneksi (maks. ~90 detik).
i=0
until php -r 'try { new PDO("pgsql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 5432).";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); } catch (Throwable $e) { exit(1); }'; do
    i=$((i + 1))
    [ "$i" -ge 45 ] && { echo "Basis data tidak bisa dihubungi. Periksa DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD." >&2; exit 1; }
    echo "Menunggu basis data... ($i)"; sleep 2
done

case "${APP_ROLE:-web}" in
  web)
    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        artisan migrate --force --no-interaction
    fi
    artisan storage:link --force > /dev/null 2>&1 || true
    artisan optimize
    artisan filament:optimize > /dev/null 2>&1 || true
    exec /usr/bin/supervisord -c /etc/supervisord.conf
    ;;
  worker)
    artisan optimize
    # Berhenti tiap jam agar memori segar; Docker menyalakannya lagi (restart: unless-stopped).
    exec su-exec www-data php artisan queue:work --tries=3 --backoff=10 --max-time=3600 --sleep=3
    ;;
  scheduler)
    artisan optimize
    exec su-exec www-data php artisan schedule:work
    ;;
  *)
    exec "$@"
    ;;
esac
