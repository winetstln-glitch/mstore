#!/usr/bin/env bash
set -e

# -------------------------
# Config
# -------------------------
BRANCH=${BRANCH:-main}
REMOTE=${REMOTE:-origin}
APP_PATH=$(pwd)
BACKUP_ROOT="$APP_PATH/backups"

# -------------------------
# Backup Rotation (keep 7)
# -------------------------
mkdir -p "$BACKUP_ROOT"
BACKUP_COUNT=$(ls -1 "$BACKUP_ROOT" | wc -l)
if [ "$BACKUP_COUNT" -ge 7 ]; then
  OLDEST=$(ls -1 "$BACKUP_ROOT" | sort | head -n 1)
  rm -rf "$BACKUP_ROOT/$OLDEST"
fi

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_PATH="$BACKUP_ROOT/$TIMESTAMP"
mkdir -p "$BACKUP_PATH"

echo "=== Backup old build & DB ($TIMESTAMP) ==="
cp -r public/build "$BACKUP_PATH/build"

# Optional: backup DB if mysqldump works
DB_NAME=$(php artisan tinker <<< "echo env('DB_DATABASE');" | tail -n 1)
DB_USER=$(php artisan tinker <<< "echo env('DB_USERNAME');" | tail -n 1)
DB_PASS=$(php artisan tinker <<< "echo env('DB_PASSWORD');" | tail -n 1)
DB_HOST=$(php artisan tinker <<< "echo env('DB_HOST');" | tail -n 1)
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_PATH/db_backup.sql" || true

# -------------------------
# Git Pull
# -------------------------
echo "=== Pull latest code ==="
if ! git config --global --get-all safe.directory | grep -q "$APP_PATH"; then
  git config --global --add safe.directory "$APP_PATH"
fi

git fetch --all
git reset --hard "$REMOTE/$BRANCH"

# -------------------------
# Composer
# -------------------------
echo "=== Composer install ==="
composer install --no-dev --prefer-dist --optimize-autoloader

# -------------------------
# Frontend Build
# -------------------------
echo "=== Building frontend (Vite) ==="
set +e
npm install
npm run build
BUILD_STATUS=$?
set -e

if [ $BUILD_STATUS -ne 0 ]; then
  echo "🚨 Frontend build failed! Restoring previous build..."
  rm -rf public/build
  cp -r "$BACKUP_PATH/build" public/build
  exit 1
fi

# -------------------------
# Permissions
# -------------------------
echo "=== Setting permissions ==="
chown -R www-data:www-data public/build
chmod -R 755 public/build

# -------------------------
# Laravel Migrations
# -------------------------
echo "=== Running migrations ==="
set +e
php artisan migrate --force
MIGRATE_STATUS=$?
set -e
if [ $MIGRATE_STATUS -ne 0 ]; then
  echo "🚨 Migration failed! Restoring DB backup..."
  mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$BACKUP_PATH/db_backup.sql" || true
  exit 1
fi

# -------------------------
# Cache + Optimize
# -------------------------
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# -------------------------
# Restart services
# -------------------------
echo "=== Restart PHP-FPM & Nginx ==="
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx

# -------------------------
# Restart queue workers
# -------------------------
php artisan queue:restart || true

echo "=== Deploy Finished Successfully ==="
echo "Backup saved at: $BACKUP_PATH"
