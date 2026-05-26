# Panduan Aktivasi Pembayaran QRIS (Wash & ATK)

## Ringkasan

Modul **Wash** dan **ATK** di aplikasi ini sudah mendukung metode pembayaran `qris` pada POS.

Status implementasi saat ini:

- POS bisa memilih metode bayar `QRIS`.
- Transaksi tersimpan dengan `payment_method = qris`.
- Laporan transaksi akan menghitung QRIS sebagai metode pembayaran terpisah.
- Sistem belum melakukan verifikasi otomatis dari gateway QRIS (callback/webhook). Alur yang dipakai adalah **konfirmasi manual kasir** setelah pelanggan scan.

## Lokasi Fitur di Sistem

- POS Wash: `/wash/pos`
- POS ATK: `/atk/pos`
- Pengaturan umum: `/settings`
- Pengaturan ATK: `/settings/atk`
- Pengaturan Wash: `/settings/wash`

## Prasyarat Sebelum Aktif

1. Merchant QRIS sudah aktif (statis/dinamis) dari penyedia Anda (bank/aggregator).
2. Anda sudah punya media QRIS yang bisa discan pelanggan:
   - paling cepat: **QRIS statis** (print/laminated), atau
   - string QRIS pada pengaturan (`pos_qris_text`) jika dipakai untuk kebutuhan internal POS.
3. Kasir paham alur verifikasi: cek notifikasi masuk di aplikasi merchant sebelum klik simpan transaksi.

## Langkah Aktivasi (Operasional)

### 1) Aktifkan SOP pembayaran QRIS di kasir

1. Buka POS Wash atau POS ATK.
2. Saat checkout pilih metode pembayaran **QRIS**.
3. Tampilkan QRIS statis ke pelanggan (print/display).
4. Setelah pelanggan scan dan kasir menerima notifikasi sukses di aplikasi merchant, lanjutkan simpan transaksi.

### 2) Simpan data QRIS di pengaturan aplikasi

1. Buka menu **Settings**.
2. Isi field **Data String QRIS** (`pos_qris_text`) dengan data yang Anda pakai.
3. Klik **Simpan Pengaturan**.

Catatan:

- Pada implementasi saat ini, field ini bersifat data konfigurasi.
- Alur pembayaran tetap mengandalkan konfirmasi manual kasir.

### 3) Pastikan laporan QRIS terbaca

1. Buat 1 transaksi uji di Wash dengan metode `QRIS`.
2. Buat 1 transaksi uji di ATK dengan metode `QRIS`.
3. Cek:
   - Riwayat transaksi menampilkan `QRIS`.
   - Laporan harian/bulanan memecah nominal berdasarkan `payment_method`.

## Checklist Uji Cepat

- Wash POS bisa pilih `QRIS`.
- ATK POS bisa pilih `QRIS`.
- Nilai `cash_amount` kosong saat metode `QRIS`.
- Struk menampilkan metode bayar `QRIS`.
- Laporan menampilkan total metode bayar `QRIS`.

## Rekomendasi Akurasi Kas

Agar data kas lebih akurat saat pakai QRIS:

- Rekonsiliasi harian antara laporan aplikasi dan mutasi merchant QRIS.
- Pisahkan rekening penerimaan QRIS dari kas tunai.
- Tetapkan cutoff verifikasi (misalnya H+0 pukul 23:59).

## Jika Ingin Full Otomatis (Opsional Lanjutan)

Bila Anda ingin pembayaran QRIS otomatis tervalidasi tanpa konfirmasi manual, perlu integrasi payment gateway:

1. Buat endpoint pembuatan invoice/QR dinamis.
2. Simpan status transaksi `pending`.
3. Terima webhook callback sukses dari gateway.
4. Ubah status transaksi ke `paid` otomatis.
5. Audit log callback untuk keamanan dan pelacakan.

Mode ini belum aktif di implementasi saat ini, namun bisa ditambahkan pada tahap berikutnya.
