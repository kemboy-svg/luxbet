# syntax=docker/dockerfile:1.7
#
# Production-style images for staging and production (DigitalOcean Droplet).
# Build from the repository root:
#
#   docker build -f deploy/docker/Dockerfile --target app -t luxbet-app .
#   docker build -f deploy/docker/Dockerfile --target web -t luxbet-web .
#
# Base images track a minor version (patch updates arrive on rebuild);
# Dependabot proposes the rest. Containers run as non-root users.

# --- Base: PHP-FPM with the extensions LuxBet needs --------------------------
FROM php:8.4-fpm-bookworm AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libicu-dev libzip-dev unzip \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl zip pcntl opcache bcmath \
    && pecl install redis-6.2.0 \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY deploy/docker/php-production.ini /usr/local/etc/php/conf.d/zz-luxbet.ini
WORKDIR /var/www/html

# --- Build: Composer (no dev) and the Vite frontend ---------------------------
FROM base AS build

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

COPY package.json package-lock.json .npmrc ./
RUN npm ci --no-audit --no-fund

COPY . .
# Wayfinder (run by the Vite build) boots the app; it needs a key but no
# database. This throwaway key is not kept in the final image.
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && cp .env.example .env \
    && php artisan key:generate --force \
    && npm run build \
    && rm -rf node_modules .env deploy/.env* tests .preview \
    && php artisan package:discover --ansi

# --- App: PHP-FPM, queue worker and scheduler all use this image --------------
FROM base AS app

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html
COPY --chmod=0755 deploy/docker/entrypoint.sh /usr/local/bin/luxbet-entrypoint

USER www-data
EXPOSE 9000
ENTRYPOINT ["luxbet-entrypoint"]
CMD ["php-fpm"]

# --- Web: Caddy serves static assets, proxies PHP and handles HTTPS ----------
FROM caddy:2.10-alpine AS web

COPY deploy/docker/Caddyfile /etc/caddy/Caddyfile
COPY --from=build /var/www/html/public /var/www/html/public
