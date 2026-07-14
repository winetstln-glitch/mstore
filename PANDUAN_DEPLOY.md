# Panduan Deployment MStore

## Ringkasan Perubahan Terbaru

### 1. Perbaikan Hak Akses Dashboard HRD
- **Issue**: Teknisi bisa melihat dan mengakses Dashboard HRD
- **Solusi**:
  - Membuat permission baru `admin.dashboard.view`
  - Menambahkan permission ini hanya untuk role:
    - Admin
    - Direktur
    - HRD
    - HRD Manager
  - Memperbarui sidebar untuk hanya menampilkan "Dashboard HRD" jika memiliki permission tersebut
  - Memperbarui controller dan route untuk otorisasi yang tepat
  - Membuat migrasi baru untuk menambahkan permission ke database

### 2. Refaktorisasi Landing Page
- Memperbarui hero section dengan copy yang lebih meyakinkan
- Menambahkan badge pada service cards
- Memperbarui trust section dengan icon yang lebih profesional
- Menambahkan social proof section ("Mengapa Memilih Kami")
- Menambahkan final CTA sebelum footer
- Memperbaiki form placeholders
- Menambahkan gradient dan hover effects modern

### 3. Peningkatan Halaman Absensi
- Menambahkan tombol "Ajukan Cuti/Izin" yang menonjol
- Menambahkan modal form untuk pengajuan cuti/izin langsung dari halaman absensi

---

## Cara Update ke Server

Proyek MStore menggunakan **GitHub Actions** untuk deploy otomatis ketika push ke branch `main`.

### Opsi 1: Deploy Otomatis (Rekomendasi)

Setiap kali kamu push ke branch `main`, GitHub Actions akan otomatis:
1. Checkout kode terbaru
2. SSH ke server
3. Menjalankan script `deploy.sh`

Yang perlu kamu lakukan:
```bash
git add .
git commit -m "Deskripsi perubahan"
git push
```

### Opsi 2: Deploy Manual

Jika kamu ingin deploy manual ke server:

1. **Masuk ke server via SSH**
   ```bash
   ssh user@server-ip
   ```

2. **Masuk ke direktori proyek**
   ```bash
   cd /var/www/mstore  # atau path sesuai konfigurasi
   ```

3. **Ambil kode terbaru**
   ```bash
   git fetch --all -p
   git reset --hard origin/main
   ```

4. **Jalankan script deploy**
   ```bash
   bash deploy.sh
   ```

---

## Langkah-langkah Script `deploy.sh`

Script `deploy.sh` akan otomatis menjalankan:
1. Backup database
2. Install dependencies Composer
3. Jalankan migrasi database
4. Sync roles and permissions (php artisan roles:normalize)
5. Bersihkan dan cache konfigurasi
6. Build asset frontend
7. Restart queue worker
8. Set permissions file

---

## Setup GitHub Secrets (Untuk Deploy Otomatis)

Pastikan kamu sudah menambahkan secrets ini di repository GitHub:
- `SSH_HOST`: Alamat IP/domain server
- `SSH_USER`: Username SSH
- `SSH_KEY`: Private key SSH
- `SSH_PORT`: Port SSH (default: 22)
- `DEPLOY_PATH`: Path direktori proyek di server (default: /var/www/mstore)
- `HEALTHCHECK_URL`: (Opsional) URL untuk health check

---

## Catatan Penting

- Pastikan server kamu sudah memenuhi syarat Laravel:
  - PHP >= 8.1
  - Composer
  - Node.js & npm
  - Database (MySQL/MariaDB)
- Pastikan file `.env` di server sudah dikonfigurasi dengan benar
- Selalu backup database sebelum deploy
- Jika terjadi kesalahan, cek log di GitHub Actions atau server
