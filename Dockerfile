# syntax=docker/dockerfile:1

# =============================================================================
# Stage 1 — Composer (PHP deps, no dev)
# =============================================================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

# App source needed for classmap / PSR-4 dump (no artisan scripts — no .env in build)
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes
COPY artisan ./artisan
RUN composer dump-autoload --optimize --no-dev --no-interaction

# =============================================================================
# Stage 2 — Vite frontend assets (welcome page @vite; cached separately)
# =============================================================================
FROM node:20-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json* ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

# =============================================================================
# Stage 3 — Production runtime: nginx + php-fpm + supervisord
# =============================================================================
FROM php:8.2-fpm-bookworm

LABEL org.opencontainers.image.title="Market Eye Backend" \
      org.opencontainers.image.description="Laravel 12 API + Blade admin for Coolify"

# Extensions actually required by this app:
# - pdo_mysql  → DB_CONNECTION=mysql
# - mbstring   → Laravel
# - openssl    → Laravel crypto + FCM service-account JWT (already in image)
# - curl       → Guzzle / Socialite / FCM HTTP (libcurl + ext)
# - zip        → Composer/archive utilities
# - bcmath     → Laravel decimal / money-safe math
# - pcntl      → queue:work / schedule:work signal handling
# - opcache    → production PHP
# - intl       → not required (optional SpoofChecker only)
# - gd/imagick → not used (no image packages)
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        curl \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        bcmath \
        pcntl \
        zip \
        opcache \
    && rm -rf /var/lib/apt/lists/*

# Production PHP settings
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-docker.conf

COPY docker/nginx/default.conf /etc/nginx/sites-available/default
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default \
    && rm -f /etc/nginx/conf.d/default.conf \
    && sed -i 's/user www-data;/user www-data;/' /etc/nginx/nginx.conf \
    && sed -i 's/worker_processes auto;/worker_processes auto;/' /etc/nginx/nginx.conf

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/wait-for-db.php /usr/local/bin/wait-for-db.php
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

# App code (no vendor/node_modules — come from build stages)
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

# Ensure writable Laravel dirs exist; firebase credentials mounted at runtime
RUN mkdir -p \
        storage/app/public \
        storage/app/firebase \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache \
    && rm -f .env .env.backup .env.production \
    && rm -rf node_modules tests .git \
    && php -r "file_exists('vendor/autoload.php') || exit(1);"

# Do not bake secrets; Coolify injects env. APP_KEY must be set at runtime.
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    QUEUE_CONNECTION=database \
    CACHE_STORE=database \
    SESSION_DRIVER=database \
    BROADCAST_CONNECTION=log \
    FILESYSTEM_DISK=local

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
