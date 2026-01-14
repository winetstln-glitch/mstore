# Panduan Instalasi MStore

Panduan ini menjelaskan cara menginstal aplikasi MStore pada server Linux (Ubuntu/Debian) menggunakan script instalasi otomatis.

## Prasyarat

- **Sistem Operasi**: Ubuntu 20.04, 22.04, atau 24.04 LTS (Direkomendasikan)
- **Pengguna**: Akses Root (atau pengguna dengan hak akses sudo)
- **Internet**: Diperlukan untuk mengunduh paket dan dependensi

## Langkah Instalasi

### 1. Unggah File ke Server
Unggah seluruh folder proyek MStore ke server Anda (misalnya menggunakan FileZilla, SFTP, atau Git clone).

### 2. Berikan Izin Eksekusi pada Script
Buka terminal server Anda dan masuk ke folder proyek yang baru saja diunggah. Jalankan perintah berikut agar script dapat dijalankan:

```bash
chmod +x install.sh
```

### 3. Jalankan Installer
Jalankan script instalasi dengan hak akses root (sudo):

```bash
sudo ./install.sh
```

### 4. Ikuti Instruksi di Layar
Script akan berjalan secara interaktif dan meminta beberapa informasi:
- **Konfirmasi Instalasi**: Tekan `y` untuk melanjutkan.
- **Nama Domain**: Masukkan domain atau IP address server (contoh: `mstore.example.com` atau `192.168.1.100`).
- **Nama Database**: Masukkan nama database yang diinginkan (default: `mstore`).
- **User Database**: Masukkan username database (default: `mstore`).
- **Password Database**: Buat password untuk user database tersebut.

## Apa yang Dilakukan Script Ini?

Script `install.sh` mengotomatiskan tugas-tugas teknis berikut:
1.  **Update Sistem**: Memperbarui daftar paket Linux.
2.  **Instalasi Dependensi**: Menginstal PHP 8.2, Nginx, MariaDB (MySQL), Composer, dan Node.js.
3.  **Setup Database**: Membuat database dan user MySQL secara otomatis sesuai input Anda.
4.  **Setup Aplikasi**:
    - Menginstal library PHP via Composer.
    - Membuat file konfigurasi `.env`.
    - Mengenerate Application Key.
    - Menjalankan migrasi database (membuat tabel).
    - Mengompilasi aset frontend (CSS/JS) menggunakan Vite.
5.  **Konfigurasi Web Server**: Mengatur Virtual Host Nginx agar aplikasi bisa diakses via web.
6.  **Izin Akses (Permissions)**: Mengatur hak akses folder agar aman dan bisa dibaca oleh web server (`www-data`).

## Pasca Instalasi

Setelah instalasi selesai, Anda dapat mengakses aplikasi melalui browser di alamat:
`http://nama-domain-anda.com` (atau IP address server).

### Login Default
Jika menggunakan data dummy (seeder) bawaan:
- **Email**: `admin@example.com`
- **Password**: `password`

### Pemecahan Masalah (Troubleshooting)

- **500 Server Error**:
  - Cek log error di `/var/www/mstore/storage/logs/laravel.log`.
  - Pastikan folder `storage` dan `bootstrap/cache` memiliki izin tulis (`chmod -R 775`).
- **Permission Denied**:
  - Jalankan perintah: `chown -R www-data:www-data /var/www/mstore`.
- **Database Error**:
  - Pastikan kredensial di file `.env` sesuai dengan yang Anda masukkan saat instalasi.
