#!/usr/bin/env bash
set -e

BRANCH=${BRANCH:-main}
REMOTE=${REMOTE:-origin}
APP_PATH=$(pwd)
BACKUP_PATH="$APP_PATH/backups/$(date +%Y%m%d_%H%M%S)"

echo "=== Deploying $BRANCH ==="

# =========================
# Fix Git safe.directory
# =========================
if ! git config --global --get-all safe.directory | grep -q "$APP_PATH"; then
  git config --global --add safe.directory "$APP_PATH"
fi

# =========================
# Backup current state
# =========================
echo "=== Creating backup ==="
mkdir -p "$BACKUP_PATH"

# Backup public/build assets
cp -r public/build "$BACKUP_PATH/build"

# Backup database (optional: replace with your DB credentials)
DB_NAME=$(php artisan tinker <<< "echo env('DB_DATABASE');" | tail -n 1)
DB_USER=$(php artisan tinker <<< "echo env('DB_USERNAME');" | tail -n 1)
DB_PASS=$(php artisan tinker <<< "echo env('DB_PASSWORD');" | tail -n 1)
DB_HOST=$(php artisan tinker <<< "echo env('DB_HOST');" | tail -n 1)

mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_PATH/db_backup.sql" || true
echo "Backup saved in $BACKUP_PATH"

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
  sudo curl -fsSL https://deb.nodesource.com/setup_18.x | sudo bash -
  sudo apt install -y nodejs
fi

# =========================
# Frontend build (Vite) with rollback
# =========================
echo "=== Building frontend (Vite) ==="
set +e
npm install
npm run build
BUILD_STATUS=$?
set -e
if [ $BUILD_STATUS -ne 0 ]; then
  echo "🚨 Build failed! Rolling back frontend..."
  rm -rf public/build
  cp -r "$BACKUP_PATH/build" public/build
  exit 1
fi

# =========================
# Database migrations with rollback
# =========================
echo "=== Running migrations ==="
set +e
php artisan migrate --force
MIGRATE_STATUS=$?
set -e
if [ $MIGRATE_STATUS -ne 0 ]; then
  echo "🚨 Migration failed! Rolling back..."
  # Optional: restore DB backup if needed
  mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$BACKUP_PATH/db_backup.sql" || true
  exit 1
fi

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

echo "=== Deploy Finished Successfully ==="
