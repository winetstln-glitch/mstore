# Panduan Deploy Aman ke Server

Berikut adalah panduan langkah demi langkah untuk deploy ke server dengan aman, termasuk backup dan rollback jika terjadi kesalahan.

---

## Prasyarat
- Akses SSH ke server
- Semua perubahan sudah di-push ke GitHub
- Git, Composer, dan PHP sudah terinstall di server

---

## Langkah 1: Masuk ke Server via SSH
```bash
ssh user@server-ip -p 22
# Atau jika port SSH bukan 22:
ssh user@server-ip -p PORT_SSH
```

## Langkah 2: Pindah ke Direktori Aplikasi
```bash
cd /path/to/mstore
# Contoh: cd /var/www/mstore
```

## Langkah 3: Aktifkan Maintenance Mode (Opsional tapi Direkomendasikan)
Ini akan menampilkan halaman maintenance untuk pengunjung sementara:
```bash
php artisan down
```
Atau dengan pesan kustom:
```bash
php artisan down --message="Aplikasi sedang diperbarui, silakan coba kembali dalam beberapa menit." --retry=60
```

## Langkah 4: Backup Database & File (WAJIB!)
Jalankan perintah backup yang sudah kita buat sebelumnya:
```bash
php artisan db:backup
```
Backup akan disimpan di `storage/app/backups/` dengan nama `backup-YYYY-MM-DD-HH-MM-SS.zip`.

Jika ingin backup manual:
```bash
# Backup database
mysqldump -u DB_USERNAME -p DB_DATABASE > backup-$(date +%Y-%m-%d-%H-%M-%S).sql
# Backup file storage
zip -r backup-storage-$(date +%Y-%m-%d-%H-%M-%S).zip storage
```

## Langkah 5: Pull Perubahan dari GitHub
```bash
git fetch origin
git pull origin main
# Atau jika kamu menggunakan branch lain: git pull origin nama-branch
```

## Langkah 6: Install Dependencies Composer
```bash
composer install --no-dev --optimize-autoloader
```
Opsi `--no-dev` tidak menginstall paket development, `--optimize-autoloader` untuk performa lebih cepat.

## Langkah 7: Jalankan Migrasi Database
Pastikan kamu sudah backup database terlebih dahulu!
```bash
php artisan migrate --force
```
Opsi `--force` untuk menjalankan migrasi di environment production.

## Langkah 8: Clear & Cache Semua Konfigurasi
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Langkah 9: Compile Asset Frontend (Jika Ada Perubahan)
```bash
npm install
npm run build
```

## Langkah 10: Set Permission File/Folder
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data .
# Atau jika user kamu bukan www-data: chown -R $(whoami):www-data .
```

## Langkah 11: Restart Queue Worker
```bash
php artisan queue:restart
```
Jika kamu menggunakan supervisor, restart juga service supervisor:
```bash
sudo supervisorctl restart all
# Atau nama service spesifik: sudo supervisorctl restart mstore-worker
```

## Langkah 12: Nonaktifkan Maintenance Mode
```bash
php artisan up
```

---

## Cara Rollback Jika Terjadi Kesalahan
Jika setelah deploy terjadi error, kamu bisa rollback dengan langkah berikut:

### 1. Nonaktifkan Maintenance Mode (jika masih aktif)
```bash
php artisan up
```

### 2. Restore Database dari Backup
```bash
# Dapatkan nama file backup terbaru
ls -la storage/app/backups/
# Unzip file backup
unzip storage/app/backups/backup-YYYY-MM-DD-HH-MM-SS.zip -d backup-temp
# Restore database
mysql -u DB_USERNAME -p DB_DATABASE < backup-temp/database.sql
# Restore storage (jika perlu)
cp -r backup-temp/storage/* storage/
# Hapus temporary
rm -rf backup-temp
```

### 3. Kembalikan Kode ke Commit Sebelumnya
```bash
# Lihat log commit untuk mendapatkan hash commit yang ingin dirollback
git log --oneline
# Kembalikan ke commit tertentu
git reset --hard <hash-commit-sebelumnya>
# Atau kembali ke commit sebelum terakhir: git reset --hard HEAD~1
# Push force ke repo (jika perlu)
git push origin main --force
```

### 4. Jalankan Ulang Langkah 6-11 di atas (Install Dependencies, Clear Cache, dll.)

---

## Tips Deploy Aman Tambahan
1. **Gunakan Branch Staging**: Deploy ke server staging terlebih dahulu sebelum production
2. **Backup Sebelum Setiap Deploy**: Selalu backup database dan file sebelum deploy
3. **Gunakan Tag Release**: Beri tag setiap release di GitHub untuk memudahkan rollback
   ```bash
   git tag -a v1.0.0 -m "Release v1.0.0"
   git push origin v1.0.0
   ```
4. **Monitor Log Setelah Deploy**: Cek log Laravel untuk memastikan tidak ada error
   ```bash
   tail -f storage/logs/laravel.log
   ```
5. **Gunakan GitHub Actions**: Untuk deploy otomatis dengan approval manual
