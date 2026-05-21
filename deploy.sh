#!/bin/bash
set -e

echo "=== Memulai deploy aplikasi MStore ==="

# 0. Backup database & file (WAJIB!)
echo "0. Creating backup of database and files..."
if [ -x "artisan" ]; then
    php artisan db:backup
    echo "   Backup selesai!"
else
    echo "   Perintah artisan tidak ditemukan, backup dilewati!"
fi

# 1. Install dependencies
echo "1. Installing dependencies..."
composer install --no-dev --optimize-autoloader

# 2. Run migrations
echo "2. Running database migrations..."
php artisan migrate --force

# 3. Clear and cache config
echo "3. Clearing and caching configuration..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Build assets
echo "4. Building frontend assets..."
npm install
npm run build

# 5. Restart queue worker (optional)
echo "5. Restarting queue worker..."
php artisan queue:restart

# 6. Set permissions
echo "6. Setting file permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "=== Deploy selesai! ==="
