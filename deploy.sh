#!/bin/bash
set -e

echo "=== Memulai deploy aplikasi MStore ==="

set_permissions() {
    echo "   Menyiapkan permission storage dan bootstrap/cache..."
    mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache

    chmod -R ug+rwX storage bootstrap/cache
    find storage bootstrap/cache -type d -exec chmod 775 {} \;
    find storage bootstrap/cache -type f -exec chmod 664 {} \;

    if id "www-data" >/dev/null 2>&1; then
        if command -v sudo >/dev/null 2>&1; then
            sudo chown -R www-data:www-data storage bootstrap/cache
        elif [ "$(id -u)" -eq 0 ]; then
            chown -R www-data:www-data storage bootstrap/cache
        else
            echo "   Peringatan: tidak bisa chown ke www-data karena sudo tidak tersedia."
            echo "   Pastikan user web server memiliki akses tulis ke storage dan bootstrap/cache."
        fi
    fi
}

# 0. Backup database & file (WAJIB!)
echo "0. Creating backup of database and files..."
if [ -f "artisan" ]; then
    php artisan db:backup
    echo "   Backup selesai!"
else
    echo "   Perintah artisan tidak ditemukan, backup dilewati!"
fi

# Pastikan permission benar sebelum artisan menulis cache/view.
echo "0b. Preparing writable directories..."
set_permissions

# 1. Install dependencies
echo "1. Installing dependencies..."
composer install --no-dev --optimize-autoloader

# 2. Run migrations
echo "2. Running database migrations..."
php artisan migrate --force

# 3. Sync roles and permissions
echo "3. Syncing roles and permissions..."
php artisan roles:normalize

# 4. Clear and cache config
echo "4. Clearing and caching configuration..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Build assets
echo "5. Building frontend assets..."
npm install
npm run build

# 6. Restart queue worker (optional)
echo "6. Restarting queue worker..."
php artisan queue:restart

# 7. Set permissions
echo "7. Setting file permissions..."
set_permissions

echo "=== Deploy selesai! ==="
