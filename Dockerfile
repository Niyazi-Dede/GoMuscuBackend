FROM php:8.4-cli-alpine

# System dependencies + PHP extensions
RUN apk add --no-cache \
        libpq-dev \
        libsodium-dev \
        icu-dev \
        git \
        unzip \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        sodium \
        intl \
        opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP dependencies (layer cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy application source
COPY . .

# Finalize composer (post-install scripts)
RUN composer dump-autoload --optimize --no-dev

# Build cache for production
ENV APP_ENV=prod
ENV APP_DEBUG=0
RUN php bin/console cache:clear --no-debug
RUN php bin/console cache:warmup --no-debug

# Ensure var/ is writable
RUN chmod -R 777 var/

EXPOSE 8080

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
