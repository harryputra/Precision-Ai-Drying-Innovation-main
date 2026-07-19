# syntax=docker/dockerfile:1
# ======================================================================
# SolarDryerAI — image produksi (multi-stage)
#   vendor : composer install (tanpa dev)
#   assets : vite build (tailwind + alpine + echo)
#   runtime: php:8.3-apache + mod_proxy_wstunnel (WS Reverb same-origin)
# Satu image dipakai 4 service: web (apache), queue, scheduler, reverb.
# ======================================================================

# ── Stage 1: vendor ───────────────────────────────────────────────────
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev --no-scripts --no-autoloader \
    --prefer-dist --no-interaction --no-progress \
    --ignore-platform-reqs

# ── Stage 2: assets ───────────────────────────────────────────────────
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --ignore-scripts
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ── Stage 3: runtime ──────────────────────────────────────────────────
FROM php:8.3-apache AS runtime

RUN apt-get update && apt-get install -y --no-install-recommends \
        curl unzip sqlite3 \
        libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" zip gd pcntl bcmath opcache \
    && a2enmod rewrite proxy proxy_http proxy_wstunnel headers \
    && sed -ri 's/^ServerTokens .*/ServerTokens Prod/; s/^ServerSignature .*/ServerSignature Off/' \
        /etc/apache2/conf-available/security.conf \
    && rm -rf /var/lib/apt/lists/*

# PHP produksi: tanpa expose_php, opcache agresif (kode immutable di container)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
        echo 'expose_php=Off'; \
        echo 'memory_limit=256M'; \
        echo 'upload_max_filesize=20M'; \
        echo 'post_max_size=25M'; \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.validate_timestamps=0'; \
    } > "$PHP_INI_DIR/conf.d/zz-app.ini"

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY --from=vendor /usr/bin/composer /usr/local/bin/composer

# Normalisasi CRLF (repo Windows) + autoload optimal + package discovery
RUN sed -i 's/\r$//' docker/entrypoint.sh artisan \
    && chmod +x docker/entrypoint.sh artisan \
    && composer dump-autoload --optimize --classmap-authoritative --no-interaction \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
        storage/logs storage/app/public bootstrap/cache /data \
    && chown -R www-data:www-data storage bootstrap/cache /data

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
CMD ["apache2-foreground"]
