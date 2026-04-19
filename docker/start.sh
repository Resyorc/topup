#!/bin/sh

# Start PHP-FPM dulu (background)
php-fpm -D
sleep 1

# Run migrations
php /app/artisan migrate --force || true

# Storage link
php /app/artisan storage:link || true

# Start SSR server via PM2
if [ -f /app/bootstrap/ssr/ssr.js ]; then
    pm2 start /app/ecosystem.config.cjs
    sleep 2
fi

# Start Nginx (foreground)
nginx