#!/bin/sh
set -e

mkdir -p \
    storage/app/tenants \
    storage/settings \
    storage/productos \
    storage/dispositivos \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

cp -an /opt/app-storage-seed/. storage/ 2>/dev/null || true

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

php artisan migrate --force

exec apache2-foreground
