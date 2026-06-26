# PHP-FPM image for the Laravel application container.
FROM php:8.4-fpm-alpine

# System deps + PHP extensions required by Laravel + MySQL.
RUN apk add --no-cache \
        bash \
        git \
        unzip \
        libzip-dev \
        oniguruma-dev \
        icu-dev \
    && docker-php-ext-install pdo pdo_mysql bcmath mbstring zip intl

# Composer (copied from the official composer image).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy application source (in dev this is overridden by a bind mount).
COPY . /var/www

# Install PHP dependencies (skipped gracefully if vendor is bind-mounted).
RUN composer install --no-interaction --prefer-dist --optimize-autoloader || true

# Ensure Laravel can write to storage and cache.
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

EXPOSE 9000
CMD ["php-fpm"]
