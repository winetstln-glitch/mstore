# MStore - ISP Management System

Sistem manajemen komprehensif untuk operasional ISP, termasuk manajemen pelanggan, tiket (keluhan/permintaan), pelacakan instalasi, manajemen inventaris, dan keuangan dengan kontrol akses berbasis peran (Admin, NOC, Teknisi, Finance, dll).

![MStore Dashboard](public/img/logo.png)

## Fitur Utama

-   **Manajemen Pelanggan**: Data pelanggan, paket langganan, dan status layanan.
-   **Sistem Tiket**: Pelaporan gangguan, penugasan teknisi, dan pelacakan status.
-   **Manajemen Instalasi**: Jadwal pasang baru dan pemantauan progres.
-   **Manajemen Aset (OLT/ODP/ODC)**: Pemetaan dan manajemen perangkat jaringan.
-   **Keuangan**: Laporan pendapatan, komisi, dan pengeluaran.
-   **Manajemen Stok**: Inventaris perangkat (ONT, Kabel, dll).
-   **Peta Jaringan**: Visualisasi lokasi pelanggan dan infrastruktur (ODP/ODC).

## Persyaratan Sistem

-   **PHP**: Versi 8.1 atau lebih tinggi
-   **Database**: MySQL / MariaDB
-   **Web Server**: Apache / Nginx
-   **Composer**: Manajer dependensi PHP
-   **Node.js & NPM**: Untuk manajemen aset frontend

---

## Panduan Instalasi di Linux (Ubuntu/Debian)

Berikut adalah langkah-langkah instalasi untuk server berbasis Linux.

### 1. Persiapan Server
Pastikan sistem operasi diperbarui dan paket yang diperlukan terinstal.

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install apache2 mysql-server php php-mysql php-xml php-curl php-zip php-mbstring php-gd unzip git curl -y
```

### 2. Instalasi Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 3. Clone Repository
Masuk ke direktori web root (biasanya `/var/www/html`) dan clone repository ini.

```bash
cd /var/www/html
git clone https://github.com/winetstln-glitch/mstore.git
cd mstore
```

### 4. Instalasi Dependensi
```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### 5. Konfigurasi Environment
Salin file konfigurasi dan sesuaikan dengan database Anda.

```bash
cp .env.example .env
nano .env
```
Sesuaikan bagian berikut:
```env
DB_DATABASE=mstore
DB_USERNAME=user_database_anda
DB_PASSWORD=password_database_anda
APP_URL=http://ip-server-atau-domain-anda
```

### 6. Generate Key & Setup Database
```bash
php artisan key:generate
php artisan storage:link
php artisan migrate:fresh --seed
```

### 7. Pengaturan Izin Folder
Pastikan Apache memiliki akses ke folder storage.
```bash
sudo chown -R www-data:www-data /var/www/html/mstore
sudo chmod -R 775 /var/www/html/mstore/storage
sudo chmod -R 775 /var/www/html/mstore/bootstrap/cache
```

### 8. Konfigurasi Virtual Host (Opsional tapi Disarankan)
Buat file konfigurasi Apache baru: `sudo nano /etc/apache2/sites-available/mstore.conf`

```apache
<VirtualHost *:80>
    ServerName domain-anda.com
    DocumentRoot /var/www/html/mstore/public

    <Directory /var/www/html/mstore/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```
Aktifkan situs dan rewrite module:
```bash
sudo a2ensite mstore.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## Panduan Instalasi di Windows (XAMPP)

Berikut adalah langkah-langkah instalasi menggunakan XAMPP di Windows.

### 1. Persiapan
-   Download dan install [XAMPP](https://www.apachefriends.org/index.html) (pilih versi dengan PHP 8.1+).
-   Download dan install [Composer](https://getcomposer.org/download/).
-   Download dan install [Node.js](https://nodejs.org/).
-   Pastikan layanan Apache dan MySQL sudah berjalan di XAMPP Control Panel.

### 2. Download Source Code
-   Buka terminal (PowerShell atau Git Bash).
-   Masuk ke folder `htdocs` XAMPP (biasanya `C:\xampp\htdocs`).
-   Clone repository:
    ```bash
    git clone https://github.com/winetstln-glitch/mstore.git
    ```
-   Atau download ZIP dari GitHub dan ekstrak ke `C:\xampp\htdocs\mstore`.

### 3. Instalasi Dependensi
Buka terminal di dalam folder project (`C:\xampp\htdocs\mstore`) dan jalankan:

```bash
composer install
npm install
npm run build
```

### 4. Konfigurasi Database
-   Buka phpMyAdmin (`http://localhost/phpmyadmin`).
-   Buat database baru dengan nama `mstore`.

### 5. Konfigurasi Environment
-   Salin file `.env.example` menjadi `.env`.
-   Buka file `.env` dengan text editor (Notepad/VS Code).
-   Pastikan konfigurasi database sesuai:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=mstore
    DB_USERNAME=root
    DB_PASSWORD=
    ```

### 6. Setup Aplikasi
Kembali ke terminal di folder project, jalankan perintah berikut secara berurutan:

```bash
php artisan key:generate
php artisan storage:link
php artisan migrate:fresh --seed
```

### 7. Menjalankan Aplikasi
Jika Anda tidak membuat Virtual Host, Anda bisa mengakses aplikasi melalui browser di:
`http://localhost/mstore/public`

Atau gunakan built-in server Laravel:
```bash
php artisan serve
```
Lalu buka `http://localhost:8000` di browser.

---

## Panduan Update (Pembaruan Sistem)

Jika Anda sudah memiliki instalasi MStore yang berjalan dan ingin memperbaruinya ke versi terbaru dari GitHub, ikuti langkah-langkah berikut:

### 1. Backup (Sangat Disarankan)
Sebelum melakukan update, pastikan untuk mem-backup database dan file `.env` Anda.

-   **Backup Database**: Gunakan `mysqldump` atau fitur Export di phpMyAdmin.
-   **Backup File**: Salin file `.env` dan folder `storage/app/public` (jika ada file upload penting) ke lokasi aman.

### 2. Tarik Pembaruan Terbaru
Masuk ke direktori project Anda dan tarik perubahan terbaru dari GitHub.

```bash
cd /path/to/mstore
git pull origin main
```

### 3. Perbarui Dependensi
Jika ada perubahan pada library, Anda perlu memperbaruinya.

```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### 4. Jalankan Migrasi Database
Jika ada perubahan struktur database, jalankan migrasi.

```bash
php artisan migrate
```
> **Catatan**: Jangan gunakan `migrate:fresh` kecuali Anda ingin menghapus semua data!

### 5. Bersihkan Cache
Pastikan konfigurasi dan cache aplikasi diperbarui.

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. Restart Service (Opsional)
Jika Anda menggunakan queue worker atau supervisor, restart service tersebut.

```bash
php artisan queue:restart
# atau jika menggunakan supervisor
sudo supervisorctl restart all
```

---

## Panduan Konfigurasi HTTPS (SSL)

Untuk mengamankan koneksi server Anda dengan HTTPS menggunakan sertifikat SSL gratis dari Let's Encrypt, ikuti langkah-langkah berikut (khusus server Linux Ubuntu/Debian dengan Apache).

### 1. Instalasi Certbot
Pastikan Anda sudah login sebagai root atau user dengan akses sudo.

```bash
sudo apt update
sudo apt install certbot python3-certbot-apache -y
```

### 2. Dapatkan Sertifikat SSL
Jalankan perintah berikut untuk mendapatkan sertifikat dan mengonfigurasi Apache secara otomatis.

```bash
sudo certbot --apache
```

Ikuti instruksi di layar:
1.  Masukkan alamat email Anda (untuk notifikasi renewal).
2.  Setujui Terms of Service (ketik `Y`).
3.  Pilih apakah ingin membagikan email ke EFF (opsional).
4.  Pilih domain yang ingin dipasangi SSL (misal: `1` untuk domain-anda.com).

### 3. Verifikasi Auto-Renewal
Sertifikat Let's Encrypt berlaku selama 90 hari. Certbot biasanya menambahkan timer untuk renewal otomatis. Verifikasi dengan perintah:

```bash
sudo systemctl status certbot.timer
```

Anda juga bisa melakukan simulasi renewal untuk memastikan semuanya berjalan lancar:

```bash
sudo certbot renew --dry-run
```

### 4. Update Konfigurasi Aplikasi
Setelah HTTPS aktif, update file `.env` agar aplikasi menggunakan `https://`.

```bash
nano /var/www/html/mstore/.env
```
Ubah `APP_URL`:
```env
APP_URL=https://domain-anda.com
```

Bersihkan cache konfigurasi:
```bash
php artisan config:clear
php artisan config:cache
```

---

## Panduan Penggunaan

### Login Default
Setelah melakukan seeding database (`php artisan migrate:fresh --seed`), akun berikut tersedia untuk digunakan:

| Role | Email | Password |
| :--- | :--- | :--- |
| **Administrator** | `admin@mstore.local` | `password` |
| **NOC** | `noc@mstore.local` | `password` |
| **Teknisi** | `tech1@mstore.local` | `password` |
| **Finance** | `finance@mstore.local` | `password` |

### Langkah Awal
1.  **Login** sebagai Administrator.
2.  Masuk ke menu **Settings** untuk mengatur konfigurasi dasar aplikasi.
3.  Buat **Region** (Wilayah) operasional.
4.  Tambahkan data **ODC** dan **ODP** di menu Maps atau Network.
5.  Mulai tambahkan **Pelanggan** baru.

### Troubleshooting Umum

-   **Error 500 / Blank Page**:
    -   Cek permission folder `storage` dan `bootstrap/cache`.
    -   Pastikan file `.env` sudah ada dan terisi benar.
    -   Jalankan `php artisan config:clear` dan `php artisan cache:clear`.

-   **Gambar/Aset tidak muncul**:
    -   Jalankan `php artisan storage:link`.
    -   Pastikan `APP_URL` di `.env` sesuai dengan URL akses Anda.

-   **Database Error**:
    -   Pastikan service MySQL berjalan.
    -   Cek kredensial di `.env`.
