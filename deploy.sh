#!/usr/bin/env bash
set -e

BRANCH=${BRANCH:-main}
REMOTE=${REMOTE:-origin}

echo "=== Deploying $BRANCH ==="

# =========================
# Fix Git safe.directory
# =========================
if ! git config --global --get-all safe.directory | grep -q "$(pwd)"; then
  git config --global --add safe.directory "$(pwd)"
fi

# =========================
# Pull latest code
# =========================
git fetch --all
git reset --hard "$REMOTE/$BRANCH"

# =========================
# Backend dependencies
# =========================
composer install --no-dev --prefer-dist --optimize-autoloader

# =========================
# Frontend build (Vite)
# =========================
echo "=== Building frontend (Vite) ==="
npm install
npm run build

# =========================
# Database migrations
# =========================
php artisan migrate --force

# =========================
# Clear & cache configs/routes/views
# =========================
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# =========================
# Restart queue workers
# =========================
php artisan queue:restart || true

echo "=== Deploy Finished (Zero Downtime) ==="
