# PHP-FPM image for local development and CI-like runs.
# Pin to a reviewed minor version; bump deliberately.
FROM php:8.3.15-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libicu-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl zip pcntl opcache bcmath \
    && pecl install redis-6.1.0 \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-luxbet.ini

WORKDIR /var/www/html

# Run as the unprivileged www-data user, never root.
USER www-data

EXPOSE 9000
CMD ["php-fpm"]
