# GT Wash Deploy Checklist

Panduan ini fokus untuk deploy dan verifikasi modul GT Wash setelah fitur membership digital, loyalty program, reward voucher, booking priority, dan WhatsApp automation diaktifkan.

## Tujuan

Checklist ini memastikan hal berikut sesudah deploy:

- POS Wash tetap berjalan tanpa perubahan flow
- Membership digital aktif
- Loyalty 10x cuci -> gratis 1x aktif
- Level Bronze/Silver/Gold/Platinum tersedia
- Reward voucher dan redemption berjalan
- Dashboard, laporan, dan kartu member bisa diakses
- Integrasi WhatsApp dan Duitku minimal terkonfigurasi

## Pre-Deploy

Jalankan langkah umum deploy aman terlebih dahulu sesuai [GUIDE_DEPLOY_AMAN.md](file:///c:/Users/Lenovo/Documents/trae_projects/mstore/docs/GUIDE_DEPLOY_AMAN.md).

Checklist minimum sebelum `git pull` dan `migrate`:

- Pastikan backup database terakhir tersedia
- Pastikan queue worker dan scheduler aktif
- Pastikan kredensial WhatsApp dan Duitku sudah ada di `settings` atau `.env`
- Pastikan role wash yang dipakai kasir/manager sudah memiliki permission:
  - `wash.view`
  - `wash.pos`
  - `wash.manage`
  - `wash.report`
  - `wash.member.view`
  - `wash.member.manage`
  - `wash.loyalty.view`
  - `wash.loyalty.manage`
  - `wash.reward.view`
  - `wash.reward.manage`

## Deploy

Urutan perintah yang direkomendasikan:

```bash
php artisan down --message="GT Wash sedang diperbarui, silakan coba kembali dalam beberapa menit." --retry=60
php artisan db:backup
git fetch origin
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=RoleSeeder --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

Jika ada perubahan frontend:

```bash
npm install
npm run build
```

## Audit Otomatis Pasca-Deploy

Jalankan audit readiness khusus GT Wash:

```bash
php artisan wash:production-audit
```

Status yang mungkin muncul:

- `READY`: modul GT Wash siap dipakai
- `WARNING`: modul jalan, tetapi ada konfigurasi yang belum ideal
- `CRITICAL`: ada komponen inti GT Wash yang belum siap

Audit ini memeriksa:

- tabel dan kolom membership/loyalty
- seed level member
- route penting GT Wash
- permission utama GT Wash
- konfigurasi WhatsApp
- konfigurasi Duitku

## Smoke Test Manual

Lakukan smoke test singkat berikut setelah audit otomatis selesai.

### 1. Sidebar dan Akses

- Login sebagai user wash manager
- Pastikan menu berikut tampil:
  - `Dashboard`
  - `POS Wash`
  - `Transaksi`
  - `Pengeluaran`
  - `Stok Wash`
  - `Manajemen Layanan`
  - `Member`
  - `Loyalty Program`
  - `Reward Voucher`
  - `Membership Level`
  - `Riwayat Reward`
  - `Laporan Wash`

### 2. POS Wash

- Buka halaman `POS Wash`
- Input pelanggan baru dengan:
  - nama
  - WhatsApp
  - plat nomor
- Pastikan transaksi berhasil tersimpan
- Pastikan nomor antrean, priority queue, dan receipt muncul

### 3. Auto Member Creation

- Setelah transaksi pertama, buka modul `Member`
- Cari nomor WhatsApp atau plat yang baru dipakai
- Pastikan member otomatis terbentuk dengan:
  - `Member Number`
  - `Tanggal Bergabung`
  - `Level Bronze`
  - kendaraan terhubung

### 4. Kartu Member Digital

- Buka detail member
- Unduh PDF kartu member
- Scan atau buka URL verifikasi QR
- Pastikan halaman verifikasi publik terbuka tanpa login

### 5. Loyalty dan Reward

- Buat transaksi wash berbayar berulang pada plat yang sama
- Pastikan progress loyalty bertambah
- Saat mencapai target, pastikan voucher reward dibuat
- Gunakan voucher di POS dan pastikan:
  - hanya bisa dipakai sekali
  - total transaksi menjadi Rp0 bila valid

### 6. Membership Level dan Diskon

- Pastikan member yang mencapai ambang level naik otomatis
- Pastikan transaksi pemicu upgrade masih memakai diskon level lama
- Pastikan transaksi berikutnya memakai diskon level baru

### 7. Dashboard dan Laporan

- Pastikan widget membership tampil di dashboard wash
- Buka `Laporan Wash`
- Pastikan KPI berikut tampil:
  - `Member Aktif`
  - `Member Baru`
  - `Top Member`
  - `Level Distribution`
  - `Reward Redemption`
  - `Loyalty Progress`
- Uji export PDF dan Excel

### 8. WhatsApp

- Pastikan notifikasi setelah transaksi terkirim
- Pastikan notifikasi level-up terkirim
- Pastikan notifikasi reward voucher terkirim
- Uji bot dengan pesan:
  - `member wash`
  - `cek member`
  - `cek level`
  - `cek loyalty`
  - `cek voucher`

## Perintah Verifikasi Tambahan

```bash
php artisan route:list --name=wash
php artisan sidebar:audit
php artisan test tests/Feature/WashMembershipProgramTest.php tests/Feature/WashLoyaltyProgramTest.php
```

## Exit Criteria

GT Wash dianggap siap produksi bila:

- `php artisan wash:production-audit` menghasilkan `READY` atau `WARNING` non-kritis
- POS Wash bisa membuat transaksi normal
- member otomatis terbentuk
- loyalty bertambah
- reward voucher bisa dibuat dan diredeem
- dashboard dan laporan terbuka normal
- WhatsApp minimal terkonfigurasi jika notifikasi ingin dipakai

## Catatan Operasional

- Jika WhatsApp belum aktif, modul GT Wash tetap bisa berjalan, tetapi notifikasi membership/loyalty tidak akan terkirim
- Jika Duitku belum aktif, membership dan loyalty tetap berjalan, tetapi verifikasi readiness akan memberi status `WARNING`
- Untuk rollout aman, lakukan deploy di luar jam sibuk wash dan siapkan 1 transaksi uji setelah aplikasi kembali `up`
