# Release Notes 2026-04-03

## Finance
- Menambahkan filter bulan pada tabel `Rekonsiliasi Kas Pengurus (Cash Only)` agar hasil benar-benar mengikuti bulan yang dipilih.
- Memperbaiki filter periode di halaman detail koordinator agar sinkron dengan `start_date` dan `end_date`.
- Merapikan tampilan kolom aksi pada tabel transaksi finance (ukuran tombol, alignment, dan spacing lebih konsisten).

## Voucher / RADIUS / MikroTik
- Menambahkan fondasi sistem voucher hotspot terintegrasi FreeRADIUS:
  - tabel inti `radcheck`, `radreply`, `radacct`, `nas` (jika belum ada),
  - tabel `voucher_batches`, perluasan tabel `vouchers`, dan `queue_jobs`.
- Menambahkan endpoint voucher:
  - `POST /voucher/generate`
  - `GET /voucher/list`
  - `POST /voucher/disconnect`
  - export `csv`, `excel`, `pdf`.
- Menambahkan UI voucher:
  - manajemen profile paket voucher,
  - generate voucher bulk,
  - export data voucher.
- Menambahkan fallback retry queue saat disconnect ke MikroTik gagal.

## Employee
- Menambahkan modul `Data Karyawan` (CRUD) dengan validasi dan upload dokumen.
- Menambahkan relasi integrasi karyawan ke data existing (`users`, `wash_employees`).
- Menambahkan sinkronisasi data karyawan dari modul existing.
- Menambahkan export data karyawan (`csv`, `excel`, `pdf`) dengan dukungan logo.

## Wash Expense & Stock
- Menambahkan manajemen stok wash:
  - tabel `wash_stock_items`,
  - tabel `wash_stock_movements`.
- Menambahkan pencatatan pengeluaran pembelanjaan:
  - Sampo wash,
  - Snack,
  - Kopi,
  - Lainnya.
- Menambahkan fitur:
  - update stok otomatis saat pembelian,
  - modal pemakaian stok (stock out),
  - warning stok minimum,
  - filter kategori stok/pengeluaran,
  - riwayat pergerakan stok (IN/OUT).
- Menambahkan fallback aman saat tabel stok belum termigrasi.

## UI / Sidebar / Konsistensi
- Merapikan struktur sidebar menjadi grup yang lebih konsisten:
  - SDM & Kehadiran,
  - Aset & Tools,
  - Pusat Keuangan,
  - Pusat Toko ATK,
  - Pusat Car Wash,
  - Konfigurasi Sistem,
  - Pusat Pelanggan,
  - Pusat Jaringan.
- Menambahkan style global konsistensi tabel dan tombol aksi di CSS terpusat.
- Menyamakan tampilan kolom aksi di inventory dan finance.

## User ID Card
- Redesign halaman ID card ke tampilan "ID Card Pro".
- Menambahkan QR dan barcode yang jelas di profil digital.
- Menambahkan mode `Print Preview` khusus kartu.
- Memperbaiki print stylesheet agar:
  - sidebar/header tidak ikut tercetak,
  - data utama fokus: Nama, ID Card, Divisi/Jabatan, No HP,
  - warna print tetap muncul lebih konsisten (dengan `print-color-adjust`).

## Catatan Operasional
- Setelah deploy, pastikan migrasi dijalankan:

```bash
php artisan migrate --force
```

