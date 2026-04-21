# Migrasi SQLite ke MySQL di Ubuntu/Proxmox

Dokumen ini dipakai untuk memindahkan aplikasi `mstore` dari `sqlite` ke `mysql` atau `mariadb` dengan risiko minimum dan tanpa mengganggu layanan lain.

## Prinsip Aman

1. Siapkan database MySQL baru, jangan menimpa service lain.
2. Import data SQLite ke database baru terlebih dahulu.
3. Uji aplikasi memakai `.env` baru di jam sepi atau pada clone service.
4. Setelah lolos uji, baru ganti `DB_CONNECTION` di server produksi.

## 1. Siapkan Database Baru

```sql
CREATE DATABASE mstore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mstore_user'@'%' IDENTIFIED BY 'ganti-password-kuat';
GRANT ALL PRIVILEGES ON mstore.* TO 'mstore_user'@'%';
FLUSH PRIVILEGES;
```

Batasi host sesuai kebutuhan bila server aplikasi dan database berada di mesin yang sama:

```sql
CREATE USER 'mstore_user'@'127.0.0.1' IDENTIFIED BY 'ganti-password-kuat';
GRANT ALL PRIVILEGES ON mstore.* TO 'mstore_user'@'127.0.0.1';
FLUSH PRIVILEGES;
```

## 2. Backup SQLite Lama

```bash
cp /path/app/database/database.sqlite /path/backup/database-$(date +%F-%H%M).sqlite
```

## 3. Export Data SQLite

Contoh cepat:

```bash
sqlite3 /path/app/database/database.sqlite .dump > /path/backup/mstore-sqlite-dump.sql
```

Untuk migrasi data yang lebih aman antar engine, gunakan skrip import bertahap atau tool ETL. Untuk aplikasi aktif, lebih aman:

1. `php artisan migrate` ke MySQL baru
2. copy data master penting
3. copy data transaksi
4. validasi jumlah record

## 4. Ubah Konfigurasi Aplikasi

Contoh `.env` server produksi:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mstore
DB_USERNAME=mstore_user
DB_PASSWORD=ganti-password-kuat

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=database
LOG_LEVEL=warning
```

## 5. Jalankan Migrasi dan Cache

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 6. Validasi Setelah Switch

Periksa hal berikut:

1. login berhasil
2. dashboard terbuka cepat
3. simpan customer, ticket, inventory, finance berhasil
4. export PDF/Excel normal
5. queue/job tidak error

## 7. Cutover Minim Gangguan

Urutan aman:

1. backup SQLite
2. siapkan MySQL dan import data
3. aktifkan maintenance singkat
4. ubah `.env`
5. jalankan `migrate --force`
6. jalankan cache Laravel
7. tes login + simpan data
8. matikan maintenance

## 8. Rollback Cepat

Jika ada error:

1. kembalikan `.env` ke SQLite
2. jalankan `php artisan optimize:clear`
3. restart PHP-FPM / web server
4. aplikasi kembali memakai database lama

## Catatan

- Untuk performa lebih tinggi, tahap berikutnya pindahkan `SESSION_DRIVER` dan `CACHE_STORE` ke `redis`.
- Jangan langsung hapus file SQLite lama sampai MySQL stabil beberapa hari.
