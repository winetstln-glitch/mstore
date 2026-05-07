# Panduan Optimasi Performa MStore di Server

Untuk memastikan aplikasi berjalan sangat cepat tanpa loading lama, ada beberapa langkah teknis yang perlu dilakukan di server produksi (VPS/Dedicated Server).

## 1. Gunakan Fitur Caching Laravel (Paling Penting)
Jalankan perintah ini di terminal server setiap kali ada perubahan kode atau secara berkala:

```bash
# Mengoptimalkan file konfigurasi
php artisan config:cache

# Mengoptimalkan daftar route
php artisan route:cache

# Mengoptimalkan file tampilan (blade)
php artisan view:cache

# Gabungan optimasi (Laravel 10+)
php artisan optimize
```

## 2. Optimasi Database
- **Indexing:** Pastikan kolom yang sering dicari (seperti MAC, SN, Nama) sudah memiliki Index. (Saya sudah menambahkan beberapa index di update sebelumnya).
- **Vacuum (SQLite):** Jika menggunakan SQLite, jalankan perintah ini sesekali untuk merapikan file database:
  ```bash
  sqlite3 database/database.sqlite "VACUUM;"
  ```

## 3. Optimasi PHP (Server Level)
Pastikan modul **OPcache** aktif di PHP Anda. OPcache menyimpan script PHP yang sudah dikompilasi di memori (RAM), sehingga server tidak perlu membaca file dari disk setiap kali ada request.

Cek di terminal:
```bash
php -m | grep Zend\ OPcache
```
Jika belum ada, edit file `php.ini` dan aktifkan:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

## 4. Gunakan Antrian (Queues) untuk Tugas Berat
Tugas seperti mengirim pesan WhatsApp, Telegram, atau sinkronisasi OLT yang berat jangan dijalankan langsung saat user klik tombol. Gunakan Queue:
```bash
# Jalankan worker di background
php artisan queue:work --daemon
```

## 5. Kompresi Gambar & Aset
- Gunakan format **WebP** untuk logo atau foto bukti instalasi.
- Pastikan Nginx/Apache mengaktifkan **Gzip Compression** untuk memperkecil ukuran file CSS dan JS saat dikirim ke browser.

## 6. Bersihkan Log Secara Berkala
Log yang terlalu besar bisa memperlambat pembacaan disk:
```bash
# Hapus log lama
rm storage/logs/*.log
```

## 7. Update Server Secara Benar
Gunakan script `update_server.sh` yang sudah saya optimasi untuk menjalankan semua perintah caching di atas secara otomatis setiap kali Anda menarik kode terbaru dari GitHub.

---
**Tips:** Jika loading terasa lambat saat membuka menu tertentu, biasanya itu disebabkan oleh query database yang berat atau koneksi API pihak ketiga (seperti GenieACS atau Mikrotik) yang sedang timeout/lambat.
