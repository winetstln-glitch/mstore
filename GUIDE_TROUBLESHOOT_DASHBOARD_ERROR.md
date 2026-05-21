# Panduan Troubleshooting Error Dashboard di Server

Error "Terjadi kesalahan pada server Silakan coba beberapa saat lagi." biasanya karena masalah konfigurasi di server. Berikut langkah-langkah troubleshooting:

---

## 1. Cek Log Laravel di Server
Langkah pertama, cek log Laravel untuk melihat detail errornya!
```bash
cd /path/to/mstore
tail -f storage/logs/laravel.log
```
Atau lihat log terbaru:
```bash
cat storage/logs/laravel.log | grep -i error
```

---

## 2. Cek Permission File dan Folder
Pastikan permission untuk folder `storage` dan `bootstrap/cache` sudah benar:
```bash
cd /path/to/mstore
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
# Atau chown -R nginx:nginx jika kamu pakai Nginx
```

---

## 3. Cek File .env di Server
Pastikan file `.env` di server sudah dikonfigurasi dengan benar:
```bash
cd /path/to/mstore
cat .env
```
Yang harus diperiksa:
- `APP_DEBUG`: Di production, sebaiknya set ke `false`
- `APP_KEY`: Pastikan sudah ada (jika tidak, jalankan `php artisan key:generate`)
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: Pastikan sesuai dengan database di server
- `CACHE_STORE`: Jika menggunakan Redis, pastikan Redis berjalan di server
- `QUEUE_CONNECTION`: Pastikan sesuai dengan konfigurasi di server

---

## 4. Clear Cache di Server
Jalankan perintah ini di server untuk membersihkan cache:
```bash
cd /path/to/mstore
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 5. Cek Autoload Composer
Jalankan `composer dump-autoload` di server:
```bash
cd /path/to/mstore
composer dump-autoload --optimize
```

---

## 6. Cek Apakah Nginx/Apache Berjalan
```bash
# Untuk Nginx
sudo systemctl status nginx
# Untuk Apache
sudo systemctl status apache2
```
Jika tidak berjalan, jalankan:
```bash
# Untuk Nginx
sudo systemctl start nginx
# Untuk Apache
sudo systemctl start apache2
```

---

## 7. Cek Apakah PHP-FPM Berjalan
```bash
# Sesuaikan versi PHP dengan yang kamu gunakan!
sudo systemctl status php8.2-fpm
# Atau
sudo systemctl status php8.1-fpm
```
Jika tidak berjalan, jalankan:
```bash
sudo systemctl start php8.2-fpm
```

---

## 8. Cek Koneksi Database
Jalankan perintah ini di server untuk cek koneksi database:
```bash
cd /path/to/mstore
php artisan tinker
# Di dalam tinker:
DB::connection()->getPdo();
# Jika tidak error, berarti koneksi database berhasil!
```

---

## 9. Cek Queue Worker (Jika Menggunakan Queue)
Jika kamu menggunakan queue, pastikan queue worker berjalan:
```bash
# Jika menggunakan Supervisor (rekomendasikan):
sudo supervisorctl status
# Jika worker tidak berjalan:
sudo supervisorctl restart all
```

---

## 10. Aktifkan Debug Sementara di Server (Untuk Melihat Detail Error)
**Catatan**: Lakukan ini hanya untuk troubleshooting, jangan lupa nonaktifkan setelah selesai!
1. Edit file `.env` di server:
   ```env
   APP_DEBUG=true
   ```
2. Refresh halaman dashboard di browser, kamu akan melihat detail errornya!
3. Setelah selesai troubleshooting, ubah kembali menjadi `APP_DEBUG=false`!

---

Setelah selesai troubleshooting, jangan lupa jalankan deploy ulang atau restart service jika diperlukan!
