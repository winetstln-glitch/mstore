# Panduan Optimisasi Performa Aplikasi MStore

Berikut adalah panduan untuk mengoptimalkan performa aplikasi MStore agar berjalan lebih cepat dan ringan di server:

---

## 1. Optimisasi Konfigurasi Aplikasi

### A. Aktifkan OPCache (PHP)
OPCache menyimpan bytecode PHP di memori, mengurangi waktu parsing skrip.

Edit file `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

### B. Gunakan Cache Driver yang Cepat
Ubah `CACHE_STORE` di `.env` menjadi `redis` atau `memcached` (jika tersedia):
```env
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### C. Aktifkan Route & Config Caching
Jalankan perintah ini untuk meng-cache konfigurasi dan routes (untuk produksi):
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 2. Optimisasi Database

### A. Gunakan MySQL/MariaDB (lebih cepat dari SQLite untuk data besar)
Ubah konfigurasi database di `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mstore
DB_USERNAME=root
DB_PASSWORD=
```

### B. Optimisasi MySQL/MariaDB
Edit file `my.cnf` atau `my.ini`:
```ini
[mysqld]
query_cache_size=64M
query_cache_type=1
innodb_buffer_pool_size=256M
innodb_log_file_size=64M
max_connections=100
```

### C. Pastikan Semua Index Sudah Ada
Aplikasi sudah memiliki migrasi yang menambahkan index pada tabel-tabel penting (lihat `database/migrations/2026_05_19_225129_add_indexes_to_olt_related_tables.php`).

Untuk memastikan index sudah diterapkan:
```bash
php artisan migrate
```

---

## 3. Optimisasi Query

### A. Gunakan Eager Loading untuk Menghindari N+1 Query
Contoh di `DashboardController`:
```php
// Buruk (N+1 query)
$customers = Customer::all();
foreach ($customers as $customer) {
    echo $customer->odp->name;
}

// Baik (Eager Loading)
$customers = Customer::with('odp')->get();
foreach ($customers as $customer) {
    echo $customer->odp->name;
}
```

### B. Gunakan Pagination untuk Data Banyak
Contoh di `OLTController`:
```php
$olts = OLT::withCount('onts')->orderBy('name')->paginate(20);
```

### C. Gunakan `select()` untuk Memilih Kolom yang Dibutuhkan
```php
// Buruk (ambil semua kolom)
$users = User::all();

// Baik (ambil kolom yang dibutuhkan saja)
$users = User::select('id', 'name', 'email')->get();
```

---

## 4. Optimisasi Asset & Frontend

### A. Compile Asset untuk Produksi
```bash
npm run build
```

### B. Aktifkan Gzip/Brotli Compression di Web Server
Untuk Nginx, tambahkan di konfigurasi:
```nginx
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
```

Untuk Apache, tambahkan di `.htaccess`:
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

---

## 5. Queue untuk Proses Lambat

### A. Jalankan Queue Worker
Proses seperti polling OLT, notifikasi, dll., sebaiknya dijalankan via queue.

Jalankan worker:
```bash
php artisan queue:work --tries=3
```

Untuk production, gunakan Supervisor untuk memantau queue worker.

---

## 6. Monitoring Performa

### A. Gunakan Laravel Telescope (untuk development)
Instal Telescope untuk memantau query dan performa:
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### B. Log Slow Query
Untuk MySQL, aktifkan slow query log di `my.cnf`:
```ini
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2
```

---

## 7. Perintah Artisan untuk Optimisasi

Jalankan perintah ini secara berkala (atau di deploy script):
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache konfigurasi (produksi only)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimisasi autoloader
composer dump-autoload --optimize
```

---

Dengan mengikuti panduan di atas, aplikasi MStore akan berjalan lebih cepat dan lebih efisien di server!
