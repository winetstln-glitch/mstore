# Panduan Troubleshooting Deploy ke Server

Berikut adalah solusi untuk masalah umum saat deploy ke server:

---

## 1. Masalah: Tidak bisa push ke GitHub (permission denied)
**Solusi:**
- Pastikan SSH key Anda sudah ditambahkan ke akun GitHub
- Atau gunakan HTTPS dengan personal access token
- Periksa: `git remote -v`

---

## 2. Masalah: GitHub Actions Deploy Gagal
**Solusi:**
Pastikan secrets berikut sudah ditambahkan di Settings > Secrets and Variables > Actions di repo GitHub:
- `SSH_HOST`: Alamat IP server
- `SSH_USER`: Username SSH server
- `SSH_KEY`: Private key SSH (isi dengan konten file ~/.ssh/id_rsa)
- `SSH_PORT`: Port SSH (default 22)
- `DEPLOY_PATH`: Path aplikasi di server (misal: /var/www/mstore)
- `HEALTHCHECK_URL` (opsional): URL untuk cek status aplikasi

---

## 3. Masalah: deploy.sh tidak bisa dijalankan (permission denied)
**Solusi:**
Berikan izin execute pada deploy.sh:
```bash
chmod +x deploy.sh
```

---

## 4. Masalah: Composer install gagal
**Solusi:**
- Pastikan PHP versi >= 8.2 terinstal
- Periksa memory limit PHP di php.ini: `memory_limit = 512M`
- Jalankan: `composer install --no-dev --optimize-autoloader`

---

## 5. Masalah: Migrasi database gagal
**Solusi:**
- Pastikan konfigurasi database di .env sudah benar
- Pastikan user database memiliki izin CREATE, ALTER, INSERT, dll.
- Coba jalankan manual: `php artisan migrate --force`

---

## 6. Masalah: Error 500 setelah deploy
**Solusi:**
- Periksa izin folder storage dan bootstrap/cache:
  ```bash
  chmod -R 775 storage bootstrap/cache
  chown -R www-data:www-data storage bootstrap/cache
  ```
- Periksa log di storage/logs/laravel.log
- Pastikan APP_DEBUG=false di .env untuk production

---

## 7. Masalah: Asset (CSS/JS) tidak muncul
**Solusi:**
- Jalankan: `npm run build`
- Pastikan link asset menggunakan `asset()` atau `mix()`
- Periksa symlink storage: `php artisan storage:link`

---

## 8. Masalah: Queue tidak berjalan
**Solusi:**
- Jalankan queue worker: `php artisan queue:work --tries=3`
- Untuk production, gunakan Supervisor untuk memantau worker
- Pastikan QUEUE_CONNECTION di .env bukan 'sync' (gunakan 'database' atau 'redis')

---

## 9. Masalah: Tidak bisa SSH ke server dari GitHub Actions
**Solusi:**
- Pastikan server mengizinkan koneksi dari IP GitHub Actions
- Atau tambahkan public key GitHub ke ~/.ssh/authorized_keys di server
- Periksa apakah firewall server memblokir port SSH

---

Jika masih ada masalah, periksa log GitHub Actions di tab Actions repo Anda!
