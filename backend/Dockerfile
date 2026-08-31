FROM php:8.4-cli-bookworm AS php

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
        libicu-dev \
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

RUN cp .env.example .env \
    && composer dump-autoload --optimize --no-scripts \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && useradd --uid 1000 --create-home --shell /bin/bash fleet \
    && mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R fleet:fleet /var/www/html

USER fleet

EXPOSE 8000

HEALTHCHECK --interval=10s --timeout=5s --start-period=45s --retries=8 \
    CMD php -r "exit((@file_get_contents('http://127.0.0.1:8000/up') === false) ? 1 : 0);"

ENTRYPOINT ["entrypoint.sh"]
