# Panduan Update Server MStore

Ikuti langkah-langkah berikut untuk memperbarui aplikasi MStore di server produksi.

## 1. Persiapan
Pastikan Anda memiliki akses SSH ke server dan berada di direktori proyek (biasanya `/var/www/mstore` atau `~/mstore`).

## 2. Update Otomatis (Rekomendasi)
Jika skrip `deploy.sh` sudah tersedia dan memiliki izin eksekusi, jalankan perintah berikut:

```bash
./deploy.sh
```

Skrip ini akan melakukan:
- Pull kode terbaru dari Git
- Install dependensi PHP & Node.js
- Jalankan migrasi database
- Update seeder (permission, role, setting)
- Link storage
- Bersihkan dan cache konfigurasi

## 3. Update Manual (Jika script gagal)
Jika Anda perlu melakukan update secara manual, jalankan perintah berikut secara berurutan:

### Ambil Kode Terbaru
```bash
git pull origin main
```

### Install Dependensi
```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### Update Database
**PENTING:** Langkah ini wajib dilakukan karena ada perubahan struktur tabel (penambahan kolom `type_group`, tabel `assets`, dll).
```bash
php artisan migrate --force
```

### Update Data Referensi (Seeder)
```bash
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=SettingSeeder --force
```

### Konfigurasi & Cache
```bash
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Restart Layanan (Opsional tapi Disarankan)
Jika menggunakan Supervisor untuk queue:
```bash
php artisan queue:restart
```

Jika perlu merestart PHP-FPM (sesuaikan versi php, misal 8.1, 8.2, dst):
```bash
sudo service php8.2-fpm reload
```

## 4. Verifikasi Pasca Update
1. **Cek Inventaris:** Buka menu Inventory, pastikan filter "Material & Devices" dan "Tools & Assets" berfungsi.
2. **Cek Modal Edit:** Coba edit item inventaris, pastikan field "Type Group" muncul dan terisi dengan benar.
3. **Cek Translasi:** Pastikan antarmuka menggunakan Bahasa Indonesia (sesuai setting `APP_LOCALE=id` di `.env`).

## Catatan Penting
- **Backup:** Selalu disarankan melakukan backup database sebelum menjalankan migrasi besar.
- **Environment:** Pastikan file `.env` di server memiliki `APP_ENV=production` dan `APP_DEBUG=false` untuk keamanan.
