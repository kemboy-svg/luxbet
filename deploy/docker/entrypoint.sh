#!/bin/sh
# Caches configuration from the runtime environment, then starts the process
# (php-fpm, queue worker or scheduler). Migrations are NOT run here; the deploy
# script runs them once, after a backup.
set -eu

php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
php artisan event:cache --no-interaction

exec "$@"
