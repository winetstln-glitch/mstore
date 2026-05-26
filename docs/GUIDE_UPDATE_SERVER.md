# Panduan Update Aplikasi ke Server

Berikut adalah langkah-langkah untuk mengupdate aplikasi MStore ke server:

---

## 1. Push Perubahan ke GitHub

Pastikan semua perubahan sudah di-commit dan di-push ke repository GitHub:
```bash
git status
git add -A
git commit -m "Update aplikasi"
git push origin main
```

---

## 2. Login ke Server

SSH ke server:
```bash
ssh user@server-ip
```

---

## 3. Pindah ke Direktori Aplikasi

```bash
cd /path/to/mstore
```

---

## 4. Pull Perubahan dari GitHub

```bash
git pull origin main
```

---

## 5. Install Dependencies (jika ada perubahan di composer.json)

```bash
composer install --no-dev --optimize-autoloader
```

---

## 6. Jalankan Migrasi Database

```bash
php artisan migrate --force
```

---

## 7. Clear & Cache Konfigurasi

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Untuk production, cache konfigurasi:
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 8. Compile Asset Frontend (jika ada perubahan)

```bash
npm install
npm run build
```

---

## 9. Restart Queue Worker (jika menggunakan queue)

Jika menggunakan Supervisor:
```bash
sudo supervisorctl restart mstore-worker
```

Atau jika menjalankan secara manual:
```bash
php artisan queue:restart
```

---

## 10. Set Permission (jika diperlukan)

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

Selesai! Aplikasi sudah terupdate di server!
