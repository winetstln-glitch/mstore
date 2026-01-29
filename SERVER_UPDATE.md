# Server Update Guide

Follow these steps to update the server with the latest changes.

## 1. Pull Latest Changes
Navigate to your project directory and pull the latest changes from the repository:
```bash
cd /path/to/your/project
git pull origin main
```

## 2. Install/Update Dependencies
Install any new PHP dependencies:
```bash
composer install --optimize-autoloader --no-dev
```

## 3. Run Database Migrations
Run pending migrations (including the fix for asset status):
```bash
php artisan migrate --force
```

## 4. Clear and Cache Configuration
Clear old caches and rebuild them for performance:
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 5. Restart Queue Worker (Optional)
If you are using a queue worker, restart it to apply code changes:
```bash
php artisan queue:restart
```

## Troubleshooting
- **500 Server Error**: Check `storage/logs/laravel.log` for details.
- **Permission Issues**: Ensure `storage` and `bootstrap/cache` directories are writable (`chmod -R 775 ...`).
