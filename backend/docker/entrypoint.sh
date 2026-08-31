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
    php artisan key:generate --force --no-interaction
fi

exec php artisan serve --host=0.0.0.0 --port=8000
