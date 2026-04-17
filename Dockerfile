FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    nodejs \
    npm \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
        gd \
        bcmath \
        xml \
    && pecl install redis \
    && docker-php-ext-enable redis

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
RUN mkdir -p /var/log/nginx /run/nginx storage/logs storage/framework/cache \
        storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copy configs
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/ecosystem.config.cjs /app/ecosystem.config.cjs

EXPOSE 80

CMD ["/bin/sh", "/app/docker/start.sh"]
