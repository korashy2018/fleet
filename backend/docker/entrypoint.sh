#!/usr/bin/env bash
set -euo pipefail

host="${DB_HOST:-postgres}"
port="${DB_PORT:-5432}"
user="${DB_USERNAME:-fleet}"
database="${DB_DATABASE:-fleet}"

until pg_isready -h "$host" -p "$port" -U "$user" -d "$database" >/dev/null 2>&1; do
    echo "Waiting for Postgres at ${host}:${port}..."
    sleep 1
done

if [ -z "${APP_KEY:-}" ]; then
    export APP_KEY
    APP_KEY="$(php artisan key:generate --show --no-interaction)"
fi

exec php-fpm -F
