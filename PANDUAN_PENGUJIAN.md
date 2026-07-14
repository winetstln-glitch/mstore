# Panduan Pengujian Fitur

---

## 1. Reset Database & Persiapan Awal

Jalankan perintah berikut di terminal (pastikan kamu berada di direktori proyek):

```bash
php artisan migrate:fresh --seed
```

Perintah ini akan:
- Menghapus semua tabel di database
- Membuat ulang semua tabel
- Menjalankan semua seeder (termasuk data uji ATK)

Data uji yang otomatis dibuat:
- **Kategori ATK**: ATK Umum, Jasa Fotokopi, Jasa Transfer Bank
- **Supplier**: Supplier ATK Sejahtera, Supplier Jasa
- **Produk ATK**: Pulpen, Buku Tulis, Kertas HVS, Fotokopi, Transfer Bank
- **Akun Float**: DANA (Rp 1.000.000), OVO (Rp 1.000.000), PPOB (Rp 5.000.000), Transfer (Rp 10.000.000)
- **Fee Profiles**: Fee Top Up, PPOB, Cash Out, Transfer

---

## 2. Login Awal

Gunakan kredensial berikut untuk login:

- Email: `admin@mstore.local`
- Password: `password`

---

## 3. Pengujian Fitur ATK POS

### 3.1 Top Up E-Wallet
1. Buka menu **ATK POS**
2. Pilih tab **Top Up**
3. Pilih **Akun Float** (pastikan saldo float mencukupi)
4. Pilih Provider (Dana/OVO/GoPay/ShopeePay)
5. Masukkan nominal (contoh: Rp 10.000)
6. Masukkan biaya admin (contoh: Rp 500)
7. Klik "Tambah ke Keranjang"
8. Pilih **Metode Pembayaran** (Cash)
9. Klik "Proses Pembayaran"
10. Verifikasi:
    - Saldo Kas Utama bertambah Rp 10.500
    - Saldo Float berkurang Rp 10.000
    - Ada catatan di `AtkFloatTransaction` dengan tipe `topup`

### 3.2 PPOB
1. Di menu **ATK POS**, pilih tab **PPOB**
2. Pilih **Akun Float**
3. Pilih Layanan (Pulsa/Data/Listrik/PLN/BPJS)
4. Masukkan Nomor Pelanggan (contoh: `08123456789`)
5. Masukkan nominal (contoh: Rp 25.000)
6. Masukkan biaya admin (contoh: Rp 1.500)
7. Tambah ke keranjang dan proses
8. Verifikasi:
    - Kas bertambah Rp 26.500
    - Saldo Float berkurang Rp 25.000
    - Catatan `AtkFloatTransaction` dengan tipe `ppob`

### 3.3 Cash Out
1. Di menu **ATK POS**, pilih tab **Cash Out**
2. Pilih **Akun Float**
3. Masukkan nominal (contoh: Rp 50.000)
4. Masukkan biaya admin (contoh: Rp 2.500)
5. Proses transaksi
6. Verifikasi:
    - Kas berkurang Rp 50.000
    - Saldo Float bertambah Rp 52.500
    - Catatan `AtkFloatTransaction` dengan tipe `deposit`

### 3.4 Jasa Transfer Bank
1. Cari produk dengan kategori **JASA TRANSFER BANK**
2. Masukkan nominal transfer (contoh: Rp 100.000)
3. Masukkan biaya admin (contoh: Rp 5.000)
4. Tambah ke keranjang dan proses
5. Verifikasi:
    - Kas bertambah Rp 105.000
    - Saldo `AgentDeposit` bertambah Rp 100.000

---

## 4. Pengujian Fitur Loyalty Car Wash

### 4.1 Setup
1. Pastikan di `Setting` ada key `wash_loyalty_target` dengan nilai `11`
2. Buat 11 transaksi cuci berbayar untuk 1 pelanggan
3. Verifikasi transaksi ke-11 mendapatkan voucher gratis!

---

## 5. Pengujian Pembatalan Transaksi

### 5.1 Pembatalan ATK Transaction
1. Buat transaksi (Top Up/PPOB/Cash Out)
2. Catat nomor transaksi
3. Buka detail transaksi dan klik "Batal"
4. Verifikasi:
    - Saldo Kas kembali ke nilai sebelum transaksi
    - Saldo Float kembali ke nilai sebelum transaksi
    - Catatan `AtkFloatTransaction` baru dengan tipe `withdrawal` (untuk Cash Out) atau `deposit` (untuk Top Up/PPOB)

---

## 6. Pengujian Keamanan

### 6.1 Race Condition Float
1. Siapkan 2 tab browser
2. Login dengan user yang sama
3. Buat 2 transaksi Top Up di kedua tab secara bersamaan
4. Pastikan saldo float berkurang dengan benar (tidak kurang dari seharusnya)

### 6.2 Validasi Nominal
1. Coba buat transaksi Top Up dengan nominal Rp 500 (harus gagal)
2. Coba buat transaksi Top Up dengan nominal Rp 150.000.000 (harus gagal)

---

## 7. Pengujian Akuntansi
1. Pastikan setiap transaksi menghasilkan catatan di `Journal` dan `JournalEntry` (jika tabel tersebut ada)
2. Verifikasi akun debit/kredit sesuai dengan jenis transaksi

---

## 8. Catatan Penting
- Semua perubahan saldo harus selalu dibungkus di `DB::transaction()`
- Selalu gunakan `lockForUpdate()` saat memodifikasi saldo float
- Pastikan semua transaksi memiliki audit trail
