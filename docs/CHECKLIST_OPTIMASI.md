# Checklist Optimisasi Aplikasi MStore

Berikut adalah checklist langkah demi langkah untuk mengoptimalkan aplikasi MStore:

---

## [x] 1. Aktifkan OPCache (PHP)
Edit file `php.ini` (lokasi tergantung OS):
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
opcache.enable_cli=1
```

---

## [ ] 2. Konfigurasi Cache Driver (Redis/Memcached)
Ubah file `.env`:
```env
CACHE_STORE=redis
# Atau CACHE_STORE=memcached

# Redis Config
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=0

# Memcached Config (jika menggunakan Memcached)
MEMCACHED_HOST=127.0.0.1
MEMCACHED_PORT=11211
```

---

## [ ] 3. Aktifkan Config/Route/View Caching (Production Only!)
Jalankan perintah ini di server production:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## [ ] 4. Konfigurasi Queue
Ubah file `.env`:
```env
QUEUE_CONNECTION=database
# Atau QUEUE_CONNECTION=redis (lebih cepat!)
```

Jalankan queue worker (gunakan Supervisor di production):
```bash
php artisan queue:work --tries=3 --timeout=90
```

---

## [ ] 5. Optimisasi Database
- Gunakan MySQL/MariaDB alih-alih SQLite untuk data besar
- Pastikan semua index sudah ada (jalankan `php artisan migrate`)
- Optimisasi konfigurasi MySQL (lihat `GUIDE_OPTIMASI_PERFORMA.md`)

---

## [ ] 6. Compile Asset Frontend untuk Production
```bash
npm install
npm run build
```

---

## [ ] 7. Aktifkan Gzip/Brotli Compression di Web Server
Lihat `GUIDE_OPTIMASI_PERFORMA.md` untuk konfigurasi Nginx/Apache!

---

## [ ] 8. Jalankan Perintah Optimisasi Artisan
```bash
composer dump-autoload --optimize
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

Setelah semua checklist selesai, aplikasi MStore akan berjalan lebih cepat dan lebih ringan!
