#!/usr/bin/env bash
set -e

BRANCH=${BRANCH:-main}
REMOTE=${REMOTE:-origin}
APP_PATH=$(pwd)
BACKUP_PATH="$APP_PATH/backups/$(date +%Y%m%d_%H%M%S)"

echo "=== Deploying $BRANCH ==="

# -------------------------
# Fix Git safe.directory
# -------------------------
if ! git config --global --get-all safe.directory | grep -q "$APP_PATH"; then
  git config --global --add safe.directory "$APP_PATH"
fi

# -------------------------
# Backup current build
# -------------------------
mkdir -p "$BACKUP_PATH"
echo "=== Creating backup ==="
cp -r public/build "$BACKUP_PATH/build"

# Optional: backup DB
DB_NAME=$(php artisan tinker <<< "echo env('DB_DATABASE');" | tail -n 1)
DB_USER=$(php artisan tinker <<< "echo env('DB_USERNAME');" | tail -n 1)
DB_PASS=$(php artisan tinker <<< "echo env('DB_PASSWORD');" | tail -n 1)
DB_HOST=$(php artisan tinker <<< "echo env('DB_HOST');" | tail -n 1)
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_PATH/db_backup.sql" || true

# -------------------------
# Pull latest code
# -------------------------
git fetch --all
git reset --hard "$REMOTE/$BRANCH"

# -------------------------
# Composer dependencies
# -------------------------
composer install --no-dev --prefer-dist --optimize-autoloader

# -------------------------
# Check Node version
# -------------------------
NODE_MIN_VERSION=16
NODE_MAJOR=$(node -v | sed 's/v\([0-9]*\).*/\1/')
if [ "$NODE_MAJOR" -lt "$NODE_MIN_VERSION" ]; then
  echo "Node version too old ($NODE_MAJOR). Installing Node 18..."
  curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
  apt install -y nodejs
fi

# -------------------------
# Frontend build (Vite)
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
# Set permissions for web server
# -------------------------
echo "=== Setting permissions ==="
chown -R www-data:www-data public/build
chmod -R 755 public/build

# -------------------------
# Laravel migrations
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
# Clear cache and optimize
# -------------------------
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# -------------------------
# Restart PHP-FPM & Nginx
# -------------------------
echo "=== Restarting services ==="
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx

# -------------------------
# Restart queue workers
# -------------------------
php artisan queue:restart || true

echo "=== Deploy Finished Successfully ==="
