#!/bin/bash

# MStore Server Update Script
# Usage: chmod +x update_server.sh && ./update_server.sh

echo "=================================================="
echo "Starting MStore Update Process..."
echo "Date: $(date)"
echo "=================================================="

# 1. Pull latest code
echo "[1/6] Pulling latest changes from git..."
git pull origin main

# 2. Update Dependencies
echo "[2/6] Updating PHP dependencies..."
# Use --no-dev for production, remove it if this is a dev server
composer install --optimize-autoloader --no-dev

# 3. Database Updates
echo "[3/6] Running database migrations..."
php artisan migrate --force

# Run seeders to ensure new settings/permissions are added
echo "      Running seeders (safe update)..."
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=SettingSeeder --force
php artisan db:seed --class=PackageSeeder --force

# 4. Clear Caches
echo "[4/6] Clearing application cache..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. Build Assets (Optional, usually done locally but good to have)
if [ -f "package.json" ]; then
    echo "[5/6] Building frontend assets..."
    npm install
    npm run build
fi

# 6. Restart Services (Optional)
echo "[6/6] Reloading services..."
# Restart Queue if running
# php artisan queue:restart

# Reload PHP-FPM if possible (requires sudo/root usually)
# sudo systemctl reload php8.2-fpm

echo "=================================================="
echo "Update Completed Successfully!"
echo "=================================================="
