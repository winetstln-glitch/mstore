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
- **Indexing:** Pastikan kolom yang sering dicari (seperti MAC, SN, Nama, Status, Created At) sudah memiliki Index. (Saya sudah menambahkan index performa pada update 7 Mei 2026).
- **Hindari whereDate:** Gunakan range query (`where('created_at', '>=', $start)`) daripada `whereDate` agar database bisa menggunakan index secara optimal.
- **Vacuum (SQLite):** Jika menggunakan SQLite, jalankan perintah ini sesekali untuk merapikan file database:
  ```bash
  sqlite3 database/database.sqlite "VACUUM;"
  ```

## 3. Optimasi PHP & Cache
- **OPcache:** Pastikan modul **OPcache** aktif di PHP Anda.
- **View Composer:** Data sidebar dan notifikasi sekarang dikelola melalui View Composer untuk efisiensi.
- **Cache Settings:** Pengaturan aplikasi (Settings) sudah menggunakan cache otomatis.

## 4. Gunakan Antrian (Queues) untuk Tugas Berat
Tugas seperti mengirim pesan WhatsApp, Telegram, sinkronisasi OLT, atau monitoring jaringan yang berat jangan dijalankan langsung saat user klik tombol. Gunakan Queue:
```bash
# Jalankan worker di background
php artisan queue:work --daemon
```
Pastikan `QUEUE_CONNECTION` di `.env` disetel ke `database` (bukan `sync`).

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
