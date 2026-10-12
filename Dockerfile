# syntax=docker/dockerfile:1.7
# ---------------------------------------------------------------
# OSIS Digital — image produksi (Laravel 12 + Filament + Livewire)
# Satu image, tiga peran (APP_ROLE): web | worker | scheduler
# ---------------------------------------------------------------

ARG PHP_VERSION=8.5
ARG NODE_VERSION=22

# 1) Dependensi PHP (tanpa paket dev). Ekstensi diperiksa di image akhir.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

# 2) Aset frontend (Vite + Tailwind). Butuh vendor/ untuk CSS Filament & Flux.
FROM node:${NODE_VERSION}-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY --from=vendor /app/vendor ./vendor
COPY resources ./resources
COPY app ./app
COPY vite.config.js ./
RUN npm run build

# 3) Runtime: PHP-FPM + Nginx (diatur supervisord)
FROM php:${PHP_VERSION}-fpm-alpine AS runtime

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql intl zip pcntl bcmath \
    && (php -m | grep -qi '^Zend OPcache$' || install-php-extensions opcache) \
    && apk add --no-cache nginx supervisor tzdata curl su-exec \
    && cp /usr/share/zoneinfo/Asia/Jakarta /etc/localtime && echo "Asia/Jakarta" > /etc/timezone \
    && rm -rf /var/cache/apk/*

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-osis.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-osis.conf
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

RUN mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && php artisan package:discover --ansi

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    APP_ROLE=web

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=90s --retries=3 \
    CMD if [ "$APP_ROLE" = "web" ]; then curl -fsS http://127.0.0.1/up > /dev/null; else pgrep -f "artisan" > /dev/null; fi || exit 1

ENTRYPOINT ["entrypoint"]
