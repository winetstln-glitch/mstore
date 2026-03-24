#!/usr/bin/env bash
set -e

BRANCH=${BRANCH:-main}
REMOTE=${REMOTE:-origin}
DEPLOY_PATH=$(pwd)
BACKUP_DIR="$DEPLOY_PATH/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DEPLOY_USER=${DEPLOY_USER:-deploy}
DEPLOY_GROUP=${DEPLOY_GROUP:-$DEPLOY_USER}

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
# Check/Install Node.js (recommend >= 20.19 or >= 22.12)
if command -v node >/dev/null 2>&1; then
  NODE_VER="$(node -v)"
  if ! echo "$NODE_VER" | grep -Eq '^v(20|22)\.'; then
    echo "⚠️  Node.js $NODE_VER terdeteksi. Disarankan >= v20.19 atau v22.12."
    if [ "$(id -u)" = "0" ] && command -v apt-get >/dev/null 2>&1; then
      echo "➡️  Menginstall Node.js 20.x via NodeSource..."
      curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
      apt-get install -y nodejs
      echo "✅ Node.js terpasang: $(node -v)"
    else
      echo "ℹ️  Tidak menjalankan sebagai root atau apt-get tidak tersedia. Melanjutkan build dengan versi saat ini."
    fi
  fi
else
  if [ "$(id -u)" = "0" ] && command -v apt-get >/dev/null 2>&1; then
    echo "➡️  Node.js tidak ditemukan. Menginstall Node.js 20.x..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs
    echo "✅ Node.js terpasang: $(node -v)"
  else
    echo "❌ Node.js tidak ditemukan dan tidak bisa dipasang otomatis. Install Node >=20.19 lalu jalankan ulang."
    exit 1
  fi
fi
# Ensure proper ownership for node modules to avoid EACCES during build
if [ -d "node_modules" ]; then
  echo "↪️  Memperbaiki kepemilikan node_modules untuk $DEPLOY_USER:$DEPLOY_GROUP"
  chown -R "$DEPLOY_USER:$DEPLOY_GROUP" node_modules || true
fi
chown -f "$DEPLOY_USER:$DEPLOY_GROUP" package.json package-lock.json 2>/dev/null || true

# Run npm ci with retry and fallback
echo "➡️  npm ci (non-interactive)"
set +e
npm ci --no-audit --no-fund
NPM_STATUS=$?
if [ $NPM_STATUS -ne 0 ]; then
  echo "⚠️  npm ci gagal (kode $NPM_STATUS). Membersihkan dan mencoba ulang..."
  rm -rf node_modules
  npm ci --no-audit --no-fund
  NPM_STATUS=$?
  if [ $NPM_STATUS -ne 0 ]; then
    echo "⚠️  npm ci masih gagal. Mencoba npm install..."
    npm install --no-audit --no-fund
    NPM_STATUS=$?
    if [ $NPM_STATUS -ne 0 ]; then
      echo "❌ Gagal menjalankan npm install. Periksa izin/Node.js."
      exit $NPM_STATUS
    fi
  fi
fi
set -e
echo "➡️  npm run build"
npm run build
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder --force || true
php artisan db:seed --class=RoleSeeder --force || true
php artisan db:seed --class=SettingSeeder --force || true
php artisan accounting:sync-finance-ledger || true

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure code owned by deploy user (avoids EACCES during future CI runs)
chown -R "$DEPLOY_USER:$DEPLOY_GROUP" . || true

# Fix permissions for storage and cache (avoid 500 due to write issues)
chown -R www-data:www-data storage bootstrap/cache || true
find storage -type d -exec chmod 775 {} \; || true
find storage -type f -exec chmod 664 {} \; || true
chmod -R 775 bootstrap/cache || true

# Ensure web server can serve built assets
chown -R www-data:www-data public/build || true

echo "=== Restart services ==="
php artisan queue:restart || true
if command -v sudo >/dev/null 2>&1; then
  sudo systemctl restart php8.2-fpm || true
  sudo systemctl reload nginx || true
else
  systemctl restart php8.2-fpm || true
  systemctl reload nginx || true
fi

APP_URL_VALUE=$(grep -E '^APP_URL=' .env | head -n1 | cut -d '=' -f2- | sed 's/^"//; s/"$//' | sed 's#/$##')
if [ -n "$APP_URL_VALUE" ] && command -v curl >/dev/null 2>&1; then
  HEALTH_URL="$APP_URL_VALUE/api/hotspot/health"
  echo "=== Health probe: $HEALTH_URL ==="
  curl -fsS "$HEALTH_URL" >/dev/null && echo "✅ Hotspot API health OK" || echo "⚠️  Health probe gagal"
fi

echo "✅ Deploy Finished"
