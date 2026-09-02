FROM php:8.4-fpm-alpine AS runtime

RUN apk add --no-cache postgresql-dev icu-dev libzip-dev linux-headers \
    && docker-php-ext-install pdo_pgsql pgsql intl zip bcmath opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Container dijalankan sebagai user host (lihat docker-compose.yml) supaya file
# yang ditulis ke folder yang di-mount tetap bisa dibuka dari host.
RUN sed -i 's/^user = www-data/;user = www-data/; s/^group = www-data/;group = www-data/' \
    /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html
