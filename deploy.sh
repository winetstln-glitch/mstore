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
# Check Node version
# =========================
NODE_MIN_VERSION=16

NODE_MAJOR=$(node -v | sed 's/v\([0-9]*\).*/\1/')

if [ "$NODE_MAJOR" -lt "$NODE_MIN_VERSION" ]; then
  echo "Node version too old ($NODE_MAJOR). Installing Node 18..."
  # Require root privileges for apt
  sudo curl -fsSL https://deb.nodesource.com/setup_18.x | sudo bash -
  sudo apt install -y nodejs
fi

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
