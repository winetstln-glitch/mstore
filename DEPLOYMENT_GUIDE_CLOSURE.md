# Panduan Deployment Fitur Closure & Peta
(Terakhir Diperbarui: 2026-02-01)

Ikuti langkah-langkah berikut untuk mengupdate aplikasi di server produksi Anda.

## 1. Persiapan (Backup)
Sangat disarankan untuk membackup database sebelum update.

```bash
# Contoh (sesuaikan user/db):
mysqldump -u username -p database_name > backup_mstore_$(date +%F).sql
```

## 2. Ambil Update Code
Masuk ke direktori project dan pull perubahan terbaru.

```bash
cd /path/to/mstore
git pull origin main
```

## 3. Jalankan Migrasi Database
Terdapat perubahan struktur database untuk fitur Closure dan relasi ODP/ODC.

```bash
php artisan migrate
```
*Pastikan tidak ada error saat migrasi. Migrasi akan membuat tabel `closures` dan menambahkan kolom relasi.*

## 4. Bersihkan Cache
Agar perubahan konfigurasi dan view terbaca sempurna.

```bash
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
```

## 5. Fitur Baru yang Tersedia
Setelah update, fitur berikut akan aktif:

1.  **Manajemen Closure**:
    - Menu baru "Closure" untuk mendata closure (Splice Closure).
    - Field lengkap: Kapasitas, Port PON, Warna, Area, No Kabel, dll.
    - Relasi Parent: Closure bisa menginduk ke OLT atau ODC.
    - Relasi Child: ODC dan ODP sekarang bisa menginduk ke Closure.

2.  **Peta & Lokasi**:
    - **Pilihan Layer Peta**: Form Tambah/Edit (Closure & ODP) sekarang memiliki pilihan layer:
        - **Street (OSM)**: Tampilan jalan standar.
        - **Satellite (Google)**: Tampilan satelit.
        - **Dark Mode**: Tampilan gelap (cocok untuk mode malam).
    - **Marker Drag & Drop**: Titik lokasi bisa digeser untuk presisi.
    - **Current Location**: Tombol untuk mengambil lokasi GPS saat ini.

3.  **Perbaikan Bug**:
    - Fix error saat menyimpan ODP dengan parent Closure.
    - Fix tampilan peta di mode gelap.

## Troubleshooting
Jika peta tidak muncul atau error:
- Pastikan koneksi internet server/client stabil (untuk memuat peta Leaflet/Google).
- Clear cache browser Anda (Ctrl+Shift+R).
