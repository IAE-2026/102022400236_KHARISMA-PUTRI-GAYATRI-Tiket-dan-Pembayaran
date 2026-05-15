#!/bin/sh
set -e

cd /var/www

if [ ! -f .env ]; then
    cp .env.example .env
fi

php artisan key:generate --force --no-interaction 2>/dev/null || true
php artisan migrate --force --no-interaction
php artisan l5-swagger:generate --no-interaction 2>/dev/null || true

exec php-fpm
