#!/usr/bin/env bash
set -e
# Load NVM & Node
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"         # Load NVM
[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"
export PATH="$NVM_DIR/versions/node/v18.20.8/bin:$PATH"

# =========================
# CONFIG
# =========================
BRANCH=${BRANCH:-main}
REMOTE=${REMOTE:-origin}
DEPLOY_PATH=$(pwd)
BACKUP_DIR="$DEPLOY_PATH/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# =========================
# Load NVM & Node
# =========================
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"         # Load NVM
[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"
export PATH="$NVM_DIR/versions/node/v18.20.8/bin:$PATH"

echo "=== Deploying $BRANCH ==="

# =========================
# Fix Git safe.directory
# =========================
if ! git config --global --get-all safe.directory | grep -q "$DEPLOY_PATH"; then
  git config --global --add safe.directory "$DEPLOY_PATH"
fi

# =========================
# Backup old build & DB
# =========================
mkdir -p "$BACKUP_DIR/build"
mkdir -p "$BACKUP_DIR/db"

echo "=== Creating backup old build & DB ($TIMESTAMP) ==="
cp -r public/build "$BACKUP_DIR/build/build_$TIMESTAMP"

# Backup MySQL DB (update DB credentials)
mysqldump --no-tablespaces -u mstore -p'Mstore@2026!App' mstore > backup.sql
 "$BACKUP_DIR/db/db_$TIMESTAMP.sql" || true

# =========================
# Pull latest code
# =========================
echo "=== Pull latest code ==="
git fetch --all
git reset --hard "$REMOTE/$BRANCH"

# =========================
# Composer install
# =========================
echo "=== Composer install ==="
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# =========================
# Frontend build (Vite)
# =========================
echo "=== Building frontend (Vite) ==="
set +e
npm install
npm run build
BUILD_STATUS=$?
set -e

if [ $BUILD_STATUS -ne 0 ]; then
  echo "🚨 Frontend build failed! Restoring previous build..."
  rm -rf public/build/*
  cp -r "$BACKUP_DIR/build/build_$TIMESTAMP"/* public/build/
  exit 1
fi

# =========================
# Laravel migrate & optimize
# =========================
echo "=== Running migrations & optimize caches ==="
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder --force || true
php artisan db:seed --class=RoleSeeder --force || true
php artisan db:seed --class=SettingSeeder --force || true

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# =========================
# Restart queue & services
# =========================
echo "=== Restarting queues & PHP-FPM ==="
php artisan queue:restart || true
systemctl restart php8.2-fpm || true
systemctl reload nginx || true

echo "=== Deploy Finished (Zero Downtime) ==="
