#!/bin/sh

# Run migrations
php /app/artisan migrate --force

# Start SSR server via PM2 (if SSR bundle exists)
if [ -f /app/bootstrap/ssr/ssr.js ]; then
    pm2 start /app/ecosystem.config.cjs --no-daemon &
fi

# Start PHP-FPM
php-fpm -D

# Start Nginx
nginx