# Panduan Testing WhatsApp Bot dan E-Voucher QRIS

## Daftar Isi
1. [Setup Awal](#1-setup-awal)
2. [Testing WhatsApp Bot](#2-testing-whatsapp-bot)
3. [Testing Pembelian E-Voucher via QRIS](#3-testing-pembelian-e-voucher-via-qris)
4. [Troubleshooting Umum](#4-troubleshooting-umum)

---

## 1. Setup Awal

### 1.1 Konfigurasi .env
Pastikan file `.env` Anda sudah dikonfigurasi dengan benar:

```env
# Database
DB_CONNECTION=sqlite
# atau untuk MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=mstore
# DB_USERNAME=root
# DB_PASSWORD=

# WhatsApp Gateway (Contoh menggunakan Fonnte)
WHATSAPP_API_URL=https://api.fonnte.com
WHATSAPP_API_KEY=your_whatsapp_api_key_here

# Duitku (Payment Gateway)
DUITKU_MERCHANT_CODE=your_merchant_code
DUITKU_API_KEY=your_duitku_api_key
DUITKU_CALLBACK_URL=https://your-domain.com/voucher-payment/callback
DUITKU_RETURN_URL=https://your-domain.com/voucher-payment/return
```

### 1.2 Menjalankan Server
```bash
# Jalankan server Laravel
php artisan serve

# (Opsional) Jalankan queue worker jika ada
php artisan queue:work
```

---

## 2. Testing WhatsApp Bot

### 2.1 Setup WhatsApp Bot Builder
1. Buka browser dan akses `/whatsapp-builder`
2. Klik tombol **"Import Templates"** untuk memuat semua template otomatis
3. Atau buat menu baru secara manual dengan klik **"Tambah Menu"**

### 2.2 Testing Auto-Reply
1. Kirim pesan ke nomor WhatsApp yang terhubung dengan gateway
2. Coba keyword berikut:
   - `halo` / `hi`
   - `bantuan` / `menu`
   - `absen`
   - `pulang`
   - `jadwal`
   - `voucher`
   - `tiket`
   - `kontak`
   - `terima kasih` / `makasih`
   - `selamat pagi` / `selamat siang` / `selamat malam`

3. Periksa apakah bot memberikan balasan yang sesuai

### 2.3 Cek Log WhatsApp
1. Akses halaman WhatsApp logs di `/whatsapp/logs`
2. Atau cek tabel `whatsapp_logs` di database

---

## 3. Testing Pembelian E-Voucher via QRIS

### 3.1 Siapkan Voucher Template
1. Akses menu **Voucher** di aplikasi
2. Buat voucher template baru jika belum ada
3. Isi nama paket, harga, durasi, kuota, dll.

### 3.2 Membuat Pembayaran Test
#### Cara 1: Menggunakan Route Test
Akses `/test-duitku` di browser untuk membuat pembayaran test otomatis.

#### Cara 2: Manual
1. Buat route atau form khusus untuk test (jika ada)
2. Atau buat data `VoucherPayment` manual di database:
   ```sql
   INSERT INTO voucher_payments (reference_id, voucher_template_id, phone_number, customer_name, amount, status, created_at, updated_at)
   VALUES ('TEST-123', 1, '6281234567890', 'Test User', 10000, 'pending', datetime('now'), datetime('now'));
   ```

### 3.3 Simulasi Callback Duitku
Untuk mensimulasikan pembayaran berhasil tanpa harus bayar sungguhan:
1. Akses `/test-duitku` (sudah disediakan di routes)
2. Atau kirim POST request manual ke `/voucher-payment/callback` dengan data:
   ```json
   {
     "merchantCode": "your_merchant_code",
     "merchantOrderId": "TEST-123",
     "statusCode": "00",
     "statusMessage": "Success",
     "amount": "10000",
     "paymentCode": "QRIS",
     "reference": "D1234567890",
     "signature": "your_signature_here"
   }
   ```

### 3.4 Verifikasi Hasil
Setelah callback sukses:
1. Periksa status di tabel `voucher_payments` menjadi `paid`
2. Periksa apakah voucher baru dibuat di tabel `vouchers`
3. Periksa apakah pesan WhatsApp berisi voucher dikirim ke nomor yang dituju
4. Buka halaman `/voucher-payment/TEST-123` untuk melihat detail pembayaran

---

## 4. Troubleshooting Umum

### 4.1 WhatsApp Bot Tidak Balas
- Periksa konfigurasi `WHATSAPP_API_URL` dan `WHATSAPP_API_KEY` di `.env`
- Periksa status device di panel gateway (contoh: Fonnte)
- Periksa log di `storage/logs/laravel.log`
- Periksa tabel `whatsapp_logs` di database

### 4.2 Voucher Tidak Terkirim
- Periksa apakah status pembayaran sudah `paid`
- Periksa log di `storage/logs/laravel.log` untuk error saat generate voucher
- Pastikan nomor telepon di `voucher_payments` menggunakan format 62 (contoh: 6281234567890)

### 4.3 Callback Duitku Tidak Berfungsi
- Pastikan `DUITKU_CALLBACK_URL` dapat diakses dari internet (gunakan ngrok untuk testing lokal)
- Periksa apakah signature valid
- Periksa log di `storage/logs/laravel.log`

### 4.4 Error Route Parameter
Jika mengalami error "Missing required parameter", periksa:
- Route di `routes/web.php`
- Controller di `app/Http/Controllers/WhatsAppBotBuilderController.php`
- Pastikan parameter route sesuai dengan variable di controller

---

## Catatan Penting
- Selalu backup database sebelum testing
- Gunakan environment `local` untuk testing
- Jangan gunakan API key production untuk testing
- Gunakan ngrok atau sejenisnya untuk testing callback secara lokal

## Kontributor
Dibuat dengan ❤️ oleh Tim MStore
