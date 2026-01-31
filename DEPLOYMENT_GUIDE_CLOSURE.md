# Panduan Deployment Fitur Closure & Update Terbaru

Ikuti langkah-langkah berikut untuk mengupdate aplikasi di server produksi Anda.

## 1. Persiapan (Opsional tapi Disarankan)
Sebelum melakukan update, sangat disarankan untuk membackup database Anda untuk menghindari kehilangan data jika terjadi kesalahan.

```bash
# Contoh command backup (sesuaikan dengan setup server Anda)
mysqldump -u username -p database_name > backup_sebelum_update.sql
```

## 2. Ambil Update Terbaru dari GitHub
Masuk ke direktori project Anda di server dan jalankan perintah git pull.

```bash
cd /path/to/your/project
git pull origin main
```

## 3. Jalankan Migrasi Database
Fitur baru (Closure, Category, dll) memerlukan perubahan struktur database. Jalankan migrasi untuk membuat tabel baru.

```bash
php artisan migrate
```
*Jika diminta konfirmasi "Do you really wish to run this command?", ketik `yes`.*

## 4. Optimasi Cache
Bersihkan dan buat ulang cache konfigurasi agar perubahan route dan config terbaca dengan benar.

```bash
php artisan optimize:clear
php artisan optimize
```

## 5. Cek Permissions (Jika Perlu)
Pastikan folder storage dan bootstrap/cache memiliki permission yang benar (biasanya sudah benar, tapi cek jika ada error).

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## 6. Selesai
Fitur baru sudah siap digunakan!
- **Closure**: Menu baru di sidebar "Closure".
- **Map & Koordinat**: Form Closure sekarang memiliki peta dan tombol "Current Location".
- **ODC Connection**: Form ODC sekarang bisa memilih koneksi "Direct OLT" atau "Via Closure".
- **Category**: Manajemen kategori di menu Inventory/Settings.
- **Tampilan**: Perbaikan layout inventory di HP dan favicon baru.
