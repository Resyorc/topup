#!/bin/sh

# Ensure writable runtime directories exist even if a volume mount resets permissions.
mkdir -p \
    /var/log/nginx \
    /run/nginx \
    /var/lib/nginx/tmp/client_body \
    /var/lib/nginx/tmp/fastcgi \
    /var/lib/nginx/tmp/proxy \
    /var/lib/nginx/tmp/uwsgi \
    /var/lib/nginx/tmp/scgi \
    /app/storage/logs \
    /app/storage/framework/cache \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/bootstrap/cache
touch /app/storage/logs/laravel.log
chown -R www-data:www-data /app/storage /app/bootstrap/cache /var/lib/nginx
chmod -R 775 /app/storage /app/bootstrap/cache /var/lib/nginx

# Start PHP-FPM dulu (background)
php-fpm -D
sleep 1

# Run migrations
php /app/artisan migrate --force || true

# Storage link
php /app/artisan storage:link || true

# Start background workers via PM2 (SSR if built, queue worker, and Reverb websocket server)
pm2 start /app/ecosystem.config.cjs
sleep 2

# Start Nginx (foreground)
nginx
