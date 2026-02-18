#!/usr/bin/env bash
set -e

BRANCH=${BRANCH:-main}
REMOTE=${REMOTE:-origin}
DEPLOY_PATH=$(pwd)
BACKUP_DIR="$DEPLOY_PATH/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

echo "=== Deploying $BRANCH ==="

# Load DB credentials from .env if present (fallback to DB_* env)
if [ -f ".env" ]; then
  DB_DATABASE=$(grep -E '^DB_DATABASE=' .env | head -n1 | cut -d '=' -f2- | sed 's/^"//; s/"$//')
  DB_USERNAME=$(grep -E '^DB_USERNAME=' .env | head -n1 | cut -d '=' -f2- | sed 's/^"//; s/"$//')
  DB_PASSWORD=$(grep -E '^DB_PASSWORD=' .env | head -n1 | cut -d '=' -f2- | sed 's/^"//; s/"$//')
fi
DB_NAME=${DB_NAME:-$DB_DATABASE}
DB_USER=${DB_USER:-$DB_USERNAME}
DB_PASS=${DB_PASS:-$DB_PASSWORD}

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

if [ -n "$DB_NAME" ] && [ -n "$DB_USER" ] && [ -n "$DB_PASS" ]; then
  mysqldump --no-tablespaces -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
    > "$BACKUP_DIR/db/db_$TIMESTAMP.sql" || true
else
  echo "⚠️  Skip DB backup: DB_NAME/DB_USER/DB_PASS tidak lengkap"
fi

echo "=== Pull latest code ==="
git fetch --all
git reset --hard "$REMOTE/$BRANCH"

echo "=== Composer install ==="
# Allow running composer as root in CI/automation (plugins allowed explicitly)
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

echo "=== Building frontend ==="
# Check Node.js version (recommend >= 20.19 or >= 22.12)
if command -v node >/dev/null 2>&1; then
  NODE_VER="$(node -v)"
  if ! echo "$NODE_VER" | grep -Eq '^v(20|22)\.'; then
    echo "⚠️  Node.js $NODE_VER terdeteksi. Disarankan >= v20.19 atau v22.12. Lanjut build..."
  fi
fi
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
