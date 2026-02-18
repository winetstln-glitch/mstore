#!/usr/bin/env bash
set -e

BRANCH=${BRANCH:-main}
REMOTE=${REMOTE:-origin}
DEPLOY_PATH=$(pwd)
BACKUP_DIR="$DEPLOY_PATH/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

echo "=== Deploying $BRANCH ==="

# Validate Node
if ! command -v npm &> /dev/null; then
  echo "❌ npm not found. Install Node.js first."
  exit 1
fi

# Git safe directory
git config --global --add safe.directory "$DEPLOY_PATH" || true

mkdir -p "$BACKUP_DIR/build"
mkdir -p "$BACKUP_DIR/db"

echo "=== Backup build & DB ($TIMESTAMP) ==="
[ -d public/build ] && cp -r public/build "$BACKUP_DIR/build/build_$TIMESTAMP"

mysqldump --no-tablespaces -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
  > "$BACKUP_DIR/db/db_$TIMESTAMP.sql" || true

echo "=== Pull latest code ==="
git fetch --all
git reset --hard "$REMOTE/$BRANCH"

echo "=== Composer install ==="
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

echo "=== Building frontend ==="
npm ci
npm run build

echo "=== Running migrations ==="
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder --force || true
php artisan db:seed --class=RoleSeeder --force || true
php artisan db:seed --class=SettingSeeder --force || true

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== Restart services ==="
php artisan queue:restart || true
systemctl restart php8.2-fpm || true
systemctl reload nginx || true

echo "✅ Deploy Finished"
