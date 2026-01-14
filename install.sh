#!/bin/bash

# Script Installer Otomatis MStore untuk Ubuntu/Debian
# Mendukung Ubuntu 20.04, 22.04, 24.04 LTS
# Jalankan sebagai root atau dengan sudo

# Warna
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Banner
echo -e "${GREEN}=================================================${NC}"
echo -e "${GREEN}    Installer Otomatis MStore untuk Server Linux ${NC}"
echo -e "${GREEN}=================================================${NC}"

# Cek apakah dijalankan sebagai root
if [ "$EUID" -ne 0 ]; then 
  echo -e "${RED}Harap jalankan sebagai root (sudo bash install.sh)${NC}"
  exit 1
fi

# Variabel
APP_DIR="/var/www/mstore"
CURRENT_DIR=$(pwd)
PHP_VERSION="8.2"

echo -e "${YELLOW}Script ini akan menginstal PHP $PHP_VERSION, Nginx, MariaDB, Composer, Node.js dan mengatur MStore.${NC}"
read -p "Apakah Anda ingin melanjutkan? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 1
fi

# 1. Update Sistem
echo -e "${GREEN}[1/8] Memperbarui Sistem...${NC}"
apt-get update && apt-get upgrade -y
apt-get install -y curl git unzip zip software-properties-common ca-certificates lsb-release gnupg

# 2. Install PHP
echo -e "${GREEN}[2/8] Menginstal PHP $PHP_VERSION...${NC}"
add-apt-repository ppa:ondrej/php -y
apt-get update
apt-get install -y php$PHP_VERSION php$PHP_VERSION-fpm php$PHP_VERSION-mysql php$PHP_VERSION-curl \
    php$PHP_VERSION-gd php$PHP_VERSION-mbstring php$PHP_VERSION-xml php$PHP_VERSION-zip \
    php$PHP_VERSION-bcmath php$PHP_VERSION-intl php$PHP_VERSION-cli

# 3. Install Nginx
echo -e "${GREEN}[3/8] Menginstal Nginx...${NC}"
apt-get install -y nginx

# 4. Install MariaDB
echo -e "${GREEN}[4/8] Menginstal MariaDB...${NC}"
apt-get install -y mariadb-server

# 5. Install Composer
echo -e "${GREEN}[5/8] Menginstal Composer...${NC}"
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
else
    echo "Composer sudah terinstal."
fi

# 6. Install Node.js (LTS)
echo -e "${GREEN}[6/8] Menginstal Node.js...${NC}"
if ! command -v node &> /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs
else
    echo "Node.js sudah terinstal."
fi

# 7. Setup Aplikasi
echo -e "${GREEN}[7/8] Mengatur Aplikasi...${NC}"

# Buat Direktori jika belum ada (atau salin dari direktori saat ini jika kita berada di repo)
if [ "$CURRENT_DIR" != "$APP_DIR" ]; then
    echo "Menyalin file ke $APP_DIR..."
    mkdir -p $APP_DIR
    cp -r . $APP_DIR
fi

# Perbaiki izin untuk setup
chown -R www-data:www-data $APP_DIR
cd $APP_DIR

# Konfigurasi Interaktif
read -p "Masukkan Nama Domain (contoh: mstore.example.com): " DOMAIN_NAME
read -p "Masukkan Nama Database (default: mstore): " DB_NAME
DB_NAME=${DB_NAME:-mstore}
read -p "Masukkan User Database (default: mstore): " DB_USER
DB_USER=${DB_USER:-mstore}
read -s -p "Masukkan Password Database: " DB_PASS
echo
read -s -p "Konfirmasi Password Database: " DB_PASS_CONFIRM
echo

if [ "$DB_PASS" != "$DB_PASS_CONFIRM" ]; then
    echo -e "${RED}Password tidak cocok! Keluar.${NC}"
    exit 1
fi

# Setup Database
echo "Mengkonfigurasi Database..."
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME};"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Setup .env
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Update .env menggunakan sed
sed -i "s/APP_URL=.*/APP_URL=http:\/\/${DOMAIN_NAME}/" .env
sed -i "s/DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/" .env
sed -i "s/APP_ENV=local/APP_ENV=production/" .env
sed -i "s/APP_DEBUG=true/APP_DEBUG=false/" .env

# Install Dependensi
echo "Menginstal Dependensi..."
# Jalankan sebagai user untuk menghindari masalah root composer, tapi kita root jadi allow-plugins
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader

# Generate Key & Migrate
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build Frontend
echo "Membangun Frontend..."
npm install
npm run build

# 8. Setup Konfigurasi Nginx
echo -e "${GREEN}[8/8] Mengkonfigurasi Nginx...${NC}"

NGINX_CONF="/etc/nginx/sites-available/$DOMAIN_NAME"

cat > $NGINX_CONF <<EOF
server {
    listen 80;
    server_name $DOMAIN_NAME;
    root $APP_DIR/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php$PHP_VERSION-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Aktifkan Situs
ln -sf $NGINX_CONF /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl restart nginx

# Izin Akhir
chown -R www-data:www-data $APP_DIR
chmod -R 775 $APP_DIR/storage $APP_DIR/bootstrap/cache

echo -e "${GREEN}=================================================${NC}"
echo -e "${GREEN}   Instalasi Selesai Berhasil!                   ${NC}"
echo -e "${GREEN}   URL: http://$DOMAIN_NAME                      ${NC}"
echo -e "${GREEN}=================================================${NC}"
