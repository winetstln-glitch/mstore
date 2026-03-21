#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-$(pwd)}"
cd "$APP_DIR"

echo "==> MStore post-install ready"

if [ ! -f ".env" ]; then
  cp .env.example .env
fi

export COMPOSER_ALLOW_SUPERUSER=1

echo "==> Composer install"
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

echo "==> NPM install/build"
if command -v npm >/dev/null 2>&1; then
  npm ci --no-audit --no-fund || npm install --no-audit --no-fund
  npm run build
fi

echo "==> Laravel optimize/migrate"
php artisan key:generate --force || true
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder --force || true
php artisan db:seed --class=RoleSeeder --force || true
php artisan db:seed --class=SettingSeeder --force || true
php artisan storage:link || true
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

APP_URL_VALUE="$(php -r "echo rtrim((string) env('APP_URL', ''), '/');")"
if [ -n "$APP_URL_VALUE" ]; then
  HEALTH_URL="$APP_URL_VALUE/api/hotspot/health"
  echo "==> Health probe: $HEALTH_URL"
  if command -v curl >/dev/null 2>&1; then
    curl -fsS "$HEALTH_URL" >/dev/null
  fi
fi

echo "==> READY"
