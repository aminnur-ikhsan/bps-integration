# Stage vendor: dipakai stage assets, karena Tailwind ikut memindai view milik Flux di vendor/.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Stage assets: kompilasi CSS/JS. Node hanya hidup di stage ini.
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY --from=vendor /app/vendor ./vendor
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# Stage runtime: PHP saja, tanpa Node dan tanpa node_modules.
FROM php:8.4-fpm-alpine AS runtime

RUN apk add --no-cache postgresql-dev icu-dev libzip-dev linux-headers \
    && docker-php-ext-install pdo_pgsql pgsql intl zip bcmath opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Container dijalankan sebagai user host (lihat docker-compose.yml) supaya file
# yang ditulis ke folder yang di-mount tetap bisa dibuka dari host.
RUN sed -i 's/^user = www-data/;user = www-data/; s/^group = www-data/;group = www-data/' \
    /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html
COPY --from=assets /app/public/build ./public/build
