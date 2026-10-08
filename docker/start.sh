#!/bin/sh
set -eu

php artisan config:clear
php artisan view:clear
php artisan migrate --force

if [ ! -e public/storage ]; then
    php artisan storage:link
fi

if [ "${SEED_ADMIN_ON_DEPLOY:-false}" = "true" ]; then
    php artisan db:seed --class=AdminUserSeeder --force
fi

if [ "${SEED_USER_ON_DEPLOY:-false}" = "true" ]; then
    php artisan db:seed --class=UsersSeeder --force
fi

if [ "${INTEGRATION_DIAGNOSTICS:-false}" = "true" ]; then
    php artisan integrations:check || true
fi

exec apache2-foreground
