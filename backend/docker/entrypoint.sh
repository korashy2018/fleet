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

write_app_key() {
    local key="$1"
    local env_file=".env"

    if [ ! -f "$env_file" ]; then
        echo "APP_KEY=${key}" >> "$env_file"
        return
    fi

    if grep -q '^APP_KEY=' "$env_file"; then
        sed -i "s|^APP_KEY=.*|APP_KEY=${key}|" "$env_file"
    else
        echo "APP_KEY=${key}" >> "$env_file"
    fi
}

# Compose injects APP_KEY from env_file even when it is empty. Laravel's
# `key:generate --force` then refuses to rewrite .env ("already present in
# the environment") and still exits 0 — so generate, write the mount, export.
if [ -z "${APP_KEY:-}" ]; then
    APP_KEY="$(php artisan key:generate --show --no-interaction --no-ansi | tr -d '\r\n')"
    if [[ ! "$APP_KEY" =~ ^base64: ]]; then
        echo "Failed to generate APP_KEY" >&2
        exit 1
    fi
    write_app_key "$APP_KEY"
    export APP_KEY
fi

php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction

exec php-fpm -F
