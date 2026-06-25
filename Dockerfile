# syntax=docker/dockerfile:1

FROM php:8.4-cli-alpine AS php-base

RUN apk add --no-cache libzip-dev icu-dev oniguruma-dev sqlite-dev libxml2-dev  \
    && docker-php-ext-install -j"$(nproc)" pcntl pdo_sqlite zip intl 



FROM composer:2 AS vendor

ARG PACKAGE_VERSION=dev
ARG COMPOSER_PACKAGE=eloquage/laravel-mcp-fetch

WORKDIR /app

COPY docker/mcp-host/composer.json ./
RUN --mount=type=cache,target=/tmp/composer-cache,sharing=locked \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer update --no-dev --no-scripts --no-interaction --prefer-dist --no-install

COPY docker/mcp-host/ ./

COPY . /opt/package

RUN --mount=type=cache,target=/tmp/composer-cache,sharing=locked \
    set -eux; \
    export COMPOSER_CACHE_DIR=/tmp/composer-cache; \
    VERSION="${PACKAGE_VERSION#v}"; \
    composer config --json repositories.self '{"type":"path","url":"/opt/package","options":{"symlink":false}}'; \
    if composer show "${COMPOSER_PACKAGE}" >/dev/null 2>&1; then \
      composer remove "${COMPOSER_PACKAGE}" --no-interaction 2>/dev/null || true; \
    fi; \
    if [ "${VERSION}" != "dev" ] && composer require "${COMPOSER_PACKAGE}:${VERSION}" --no-interaction --prefer-dist 2>/dev/null; then \
      echo "Installed ${COMPOSER_PACKAGE}:${VERSION} from Packagist"; \
    else \
      composer require "${COMPOSER_PACKAGE}:@dev" --no-interaction --prefer-dist; \
    fi; \
    composer install --no-dev --no-interaction --prefer-dist --no-scripts; \
    composer dump-autoload --optimize; \
    cp .env.example .env; \
    php artisan key:generate --force --no-interaction

FROM php-base AS runtime

ARG MCP_HANDLE=fetch

ENV MCP_HANDLE=${MCP_HANDLE} \
    RUN_MIGRATIONS=false \
    APP_ENV=production \
    LOG_CHANNEL=stderr \
    DB_DATABASE=/app/database/database.sqlite

RUN mkdir -p /app/database /app/storage/framework/cache /app/storage/framework/sessions \
    /app/storage/framework/views /app/storage/logs /app/bootstrap/cache

WORKDIR /app

COPY --from=vendor /app /app

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
