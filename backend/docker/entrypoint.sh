#!/usr/bin/env bash
set -euo pipefail

if [ ! -f .env ]; then
    cp .env.example .env
fi

# php artisan serve spawns workers from $_ENV after Dotenv loads .env.
# Host-oriented example values (REDIS_HOST=127.0.0.1) would then beat
# Compose. Overlay the container environment into .env first.
overlay_env() {
    local key="$1"
    local value="${2-}"

    if [ -z "$value" ]; then
        return
    fi

    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

overlay_env APP_NAME "${APP_NAME-}"
overlay_env APP_URL "${APP_URL-}"
overlay_env DB_CONNECTION "${DB_CONNECTION-}"
overlay_env DB_HOST "${DB_HOST-}"
overlay_env DB_PORT "${DB_PORT-}"
overlay_env DB_DATABASE "${DB_DATABASE-}"
overlay_env DB_USERNAME "${DB_USERNAME-}"
overlay_env DB_PASSWORD "${DB_PASSWORD-}"
overlay_env REDIS_HOST "${REDIS_HOST-}"
overlay_env CACHE_STORE "${CACHE_STORE-}"
overlay_env SESSION_DRIVER "${SESSION_DRIVER-}"
overlay_env QUEUE_CONNECTION "${QUEUE_CONNECTION-}"

host="${DB_HOST:-postgres}"
port="${DB_PORT:-5432}"
user="${DB_USERNAME:-fleet}"
database="${DB_DATABASE:-fleet}"

until pg_isready -h "$host" -p "$port" -U "$user" -d "$database" >/dev/null 2>&1; do
    echo "Waiting for Postgres at ${host}:${port}..."
    sleep 1
done

if [ -z "${APP_KEY:-}" ]; then
    php artisan key:generate --force --no-interaction
fi

exec php artisan serve --host=0.0.0.0 --port=8000
