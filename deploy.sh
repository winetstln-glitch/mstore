#!/bin/bash

# MStore Deployment Script
# Usage: ./deploy.sh

echo "Starting deployment..."

# 1. Pull latest changes
echo "Pulling latest changes from git..."
git pull origin main

# 2. Install Dependencies
echo "Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev

echo "Installing Node dependencies..."
npm install
echo "Building assets..."
npm run build

# 3. Database Migration
echo "Running database migrations..."
php artisan migrate --force

# 3.1 Run Seeders (Safe updates)
echo "Running seeders..."
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=PackageSeeder --force
php artisan db:seed --class=SettingSeeder --force

# 4. Clear and Cache Config
echo "Clearing and caching configuration..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart Queue (if applicable)
# echo "Restarting queue..."
# php artisan queue:restart

# 6. Reload PHP-FPM (Auto-detect)
echo "Reloading PHP-FPM..."
if systemctl list-units --full -all | grep -q "php.*-fpm.service"; then
    # Reload all found php-fpm services
    systemctl reload $(systemctl list-units --full -all | grep -o "php.*-fpm.service" | head -n 1)
    echo "PHP-FPM reloaded."
else
    echo "PHP-FPM service not found. Skipping reload."
fi

echo "Deployment completed successfully!"
