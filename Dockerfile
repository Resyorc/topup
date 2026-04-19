FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    nodejs-current \
    npm \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    icu-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
        gd \
        bcmath \
        xml \
        intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# PHP upload config
RUN echo "upload_max_filesize = 5M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 5M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "upload_tmp_dir = /tmp" >> /usr/local/etc/php/conf.d/uploads.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PM2 globally
RUN npm install -g pm2

WORKDIR /app

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-scripts --ignore-platform-reqs --no-dev

# Install Node dependencies
COPY package.json package-lock.json ./
RUN npm install

# Copy all source
COPY . .

# Generate wayfinder types (skip if fails)
RUN CACHE_DRIVER=file SESSION_DRIVER=file php artisan wayfinder:generate --with-form || true

# Build assets (client + SSR)
RUN npm run build:ssr || npm run build

# Set permissions
RUN mkdir -p /var/log/nginx /run/nginx \
        /var/lib/nginx/tmp/client_body \
        /var/lib/nginx/tmp/fastcgi \
        /var/lib/nginx/tmp/proxy \
        /var/lib/nginx/tmp/uwsgi \
        /var/lib/nginx/tmp/scgi \
        storage/logs storage/framework/cache \
        storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache /var/lib/nginx \
    && chmod -R 775 storage bootstrap/cache /var/lib/nginx

# Copy configs
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/ecosystem.config.cjs /app/ecosystem.config.cjs

EXPOSE 80

CMD ["/bin/sh", "/app/docker/start.sh"]