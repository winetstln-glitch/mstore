# Panduan Troubleshoot Error 403 Forbidden

Berikut adalah langkah demi langkah untuk mengatasi error 403 Forbidden di server!

---

## Langkah 1: Periksa dan Perbaiki Permission File/Folder
Jalankan perintah ini di server kamu:
```bash
cd /var/www/mstore
# Set izin file dan folder
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
# Set izin khusus untuk storage dan bootstrap/cache
chmod -R 775 storage bootstrap/cache
# Set owner ke www-data (atau user web server kamu)
chown -R www-data:www-data .
```

---

## Langkah 2: Periksa Apakah Public Folder Sudah Benar
Pastikan root di konfigurasi web server kamu menunjuk ke `/var/www/mstore/public`, **bukan** `/var/www/mstore`!

### Contoh Konfigurasi Nginx yang Benar
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name domainkamu.com www.domainkamu.com;
    root /var/www/mstore/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Contoh Konfigurasi Apache .htaccess yang Benar
File `.htaccess` di folder `public` sudah ada dan benar, tapi pastikan `mod_rewrite` aktif di Apache:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## Langkah 3: Clear Semua Cache Laravel
```bash
cd /var/www/mstore
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Langkah 4: Periksa File .env
Pastikan konfigurasi di `.env` kamu benar:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domainkamu.com
```

Setelah mengedit `.env`, jangan lupa clear cache lagi!

---

## Langkah 5: Periksa Log Web Server dan Laravel
Lihat log untuk detail errornya:
```bash
# Log Laravel
cd /var/www/mstore
tail -f storage/logs/laravel.log

# Log Nginx
sudo tail -f /var/log/nginx/error.log

# Log Apache
sudo tail -f /var/log/apache2/error.log
```

---

## Langkah 6: Restart Semua Service
```bash
# Restart Nginx
sudo systemctl restart nginx

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm

# Atau jika kamu menggunakan Apache
# sudo systemctl restart apache2
```

---

## Catatan Penting:
- **Jangan gunakan 777 di production**: Gunakan 755 untuk folder dan 644 untuk file, kecuali storage dan bootstrap/cache yang butuh 775
- **Pastikan index.php ada di public**: File `public/index.php` harus ada dan tidak dihapus
- **Pastikan .htaccess ada di public**: File `public/.htaccess` harus ada dan tidak diubah
