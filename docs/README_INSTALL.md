# Panduan Instalasi MStore

Panduan ringkas dan jelas untuk menginstal MStore di lingkungan Linux (Ubuntu/Debian) dan Windows (XAMPP). Tidak ada script `install.sh`; gunakan langkah manual di bawah ini atau deploy cepat dengan `deploy.sh`.

## Prasyarat

- Sistem Operasi: Ubuntu 20.04/22.04/24.04 (disarankan) atau Windows 10/11
- PHP 8.1+ dan Composer
- MySQL/MariaDB
- Node.js dan NPM
- Web server: Nginx/Apache

## Instalasi di Linux (Ubuntu/Debian)

### 1. Persiapan Server
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install nginx mysql-server php php-fpm php-mysql php-xml php-curl php-zip php-mbstring php-gd unzip git curl -y
```

### 2. Clone Repository
```bash
cd /var/www
git clone https://github.com/winetstln-glitch/mstore.git
cd mstore
```

### 3. Instalasi Dependensi
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### 4. Konfigurasi Environment
```bash
cp .env.example .env
nano .env
```
Sesuaikan:
```env
APP_URL=https://domain-atau-ip-anda
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mstore
DB_USERNAME=nama_user
DB_PASSWORD=password
SESSION_DOMAIN=domain-anda
SANCTUM_STATEFUL_DOMAINS=domain-anda
```

### 5. Generate Key, Storage Link, Migrasi
```bash
php artisan key:generate
php artisan storage:link
php artisan migrate --force
```

### 6. Izin Folder
```bash
sudo chown -R www-data:www-data /var/www/mstore
sudo chmod -R 775 /var/www/mstore/storage
sudo chmod -R 775 /var/www/mstore/bootstrap/cache
```

### 7. Nginx Server Block (contoh)
```nginx
server {
  listen 80;
  server_name domain-anda;
  return 301 https://domain-anda$request_uri;
}

server {
  listen 443 ssl http2;
  server_name domain-anda;

  root /var/www/mstore/public;
  index index.php;

  ssl_certificate /etc/letsencrypt/live/domain-anda/fullchain.pem;
  ssl_certificate_key /etc/letsencrypt/live/domain-anda/privkey.pem;

  location / { try_files $uri $uri/ /index.php?$query_string; }
  location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
  }
  client_max_body_size 64M;
}
```
```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Instalasi di Windows (XAMPP)

### 1. Persiapan
- Install XAMPP (PHP 8.1+), Composer, dan Node.js
- Aktifkan Apache dan MySQL di XAMPP Control Panel

### 2. Clone Repository
```bash
cd C:\xampp\htdocs
git clone https://github.com/winetstln-glitch/mstore.git
cd mstore
```

### 3. Instalasi Dependensi
```bash
composer install
npm install
npm run build
```

### 4. Database dan .env
- Buat database `mstore` di phpMyAdmin
- Salin `.env.example` menjadi `.env` dan sesuaikan kredensial
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mstore
DB_USERNAME=root
DB_PASSWORD=
APP_URL=http://localhost
```

### 5. Setup Aplikasi
```bash
php artisan key:generate
php artisan storage:link
php artisan migrate
```
Akses: `http://localhost/mstore/public` atau jalankan `php artisan serve`.

## Deploy Cepat (Server)

Repositori menyertakan `deploy.sh` untuk memperbarui server dengan aman:
```bash
cd /var/www/mstore
git pull origin main
chmod +x deploy.sh
./deploy.sh
```
Script ini melakukan fetch/reset, composer install, migrate --force, dan rebuild cache.

## Login Default (Seeder)

Jika menggunakan seeder:
- Administrator: `admin@mstore.local` / `password`
- NOC: `noc@mstore.local` / `password`
- Teknisi: `tech1@mstore.local` / `password`
- Finance: `finance@mstore.local` / `password`

## Troubleshooting

- 500 Error: cek `storage/logs/laravel.log`, pastikan permission `storage` dan `bootstrap/cache`.
- Gambar tidak muncul: jalankan `php artisan storage:link`, cek `APP_URL`.
- Database error: pastikan service MySQL aktif, kredensial `.env` benar.
