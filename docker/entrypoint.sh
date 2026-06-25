#!/bin/sh
set -e

cd /app

if [ "$#" -gt 0 ]; then
    exec "$@"
fi

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ -z "${APP_KEY:-}" ] && ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force --no-interaction
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    touch "${DB_DATABASE:-/app/database/database.sqlite}" 2>/dev/null || true
    php artisan migrate --force --no-interaction
fi

exec php artisan mcp:start "${MCP_HANDLE}"
