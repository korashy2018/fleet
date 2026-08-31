FROM php:8.4-fpm-bookworm AS php

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
        libicu-dev \
        libfcgi-bin \
        postgresql-client \
        $PHPIZE_DEPS \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql zip intl pcntl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM php AS vendor

COPY composer.json composer.lock ./
RUN composer install \
        --no-scripts \
        --prefer-dist \
        --no-interaction \
        --no-autoloader

FROM php AS app

COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/php-fpm/zz-fleet.conf /usr/local/etc/php-fpm.d/zz-fleet.conf

RUN composer dump-autoload --optimize --no-scripts \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data /var/www/html

EXPOSE 9000

HEALTHCHECK --interval=10s --timeout=5s --start-period=30s --retries=8 \
    CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET \
        cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

ENTRYPOINT ["entrypoint.sh"]
