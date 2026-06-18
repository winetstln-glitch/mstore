# Panduan Penggunaan Modul ATK (Toko ATK)

## Daftar Isi
1. [Gambaran Umum](#gambaran-umum)
2. [Dashboard ATK](#dashboard-atk)
3. [Produk & Stok](#produk--stok)
4. [POS (Point of Sale)](#pos-point-of-sale)
5. [Riwayat Transaksi](#riwayat-transaksi)
6. [Pengeluaran](#pengeluaran)
7. [Manajemen Biaya (Fee Engine)](#manajemen-biaya-fee-engine)
8. [Akun Float](#akun-float)
9. [Dana Talangan](#dana-talangan)
10. [Mutasi Kas Utama](#mutasi-kas-utama)
11. [Pengaturan ATK](#pengaturan-atk)
12. [Laporan](#laporan)

---

## Gambaran Umum
Modul ATK adalah sistem manajemen toko ATK yang mencakup manajemen produk, transaksi POS, pengeluaran, perhitungan fee otomatis, dan laporan keuangan.

---

## Dashboard ATK
- **Akses**: Business Units → ATK → Dashboard
- **Fitur**:
  - Ringkasan transaksi hari ini
  - Total pendapatan
  - Grafik transaksi
  - Top produk terlaris
  - Widget fee terkini

---

## Produk & Stok
### Melihat Daftar Produk
- **Akses**: Business Units → ATK → Master Data → Manajemen ATK
- Menampilkan semua produk ATK beserta stok dan harga

### Menambah Produk Baru
1. Klik tombol "Tambah Produk"
2. Isi form:
   - Nama produk
   - Kategori (ATK, Jasa Potocopy, Jasa Transfer Bank)
   - Harga jual
   - Harga beli (cost)
   - Stok awal
3. Simpan

### Mengedit Produk
1. Klik ikon edit di baris produk yang ingin diubah
2. Ubah data yang diperlukan
3. Simpan

### Menghapus Produk
1. Klik ikon hapus di baris produk yang ingin dihapus
2. Konfirmasi penghapusan

---

## POS (Point of Sale)
- **Akses**: Business Units → ATK → POS ATK

### Melakukan Transaksi
1. Pilih produk dari daftar (klik produk untuk menambah ke keranjang)
2. Atur jumlah produk jika perlu
3. Untuk transaksi jasa (Transfer Bank, Tarik Tunai, Top Up, PPOB):
   - Pilih kategori jasa
   - Isi nominal transaksi
   - Fee akan dihitung otomatis berdasarkan fee profile yang aktif
   - Kamu bisa mengubah fee secara manual jika diizinkan
4. Pilih metode pembayaran (Tunai, Hutang, dll)
5. Klik "Proses Transaksi"
6. Nota akan ditampilkan dan bisa dicetak

---

## Riwayat Transaksi
- **Akses**: Business Units → ATK → Transaksi → Riwayat Transaksi
- Menampilkan semua transaksi ATK dengan filter tanggal dan jenis transaksi
- Kamu bisa melihat detail transaksi dan mencetak ulang nota

---

## Pengeluaran
### Melihat Daftar Pengeluaran
- **Akses**: Business Units → ATK → Keuangan → Pengeluaran

### Menambah Pengeluaran Baru
1. Klik "Tambah Pengeluaran"
2. Isi form:
   - Keterangan pengeluaran
   - Jumlah nominal
   - Tanggal
3. Simpan

---

## Manajemen Biaya (Fee Engine)
Modul Fee Engine Enterprise untuk mengelola biaya transaksi secara fleksibel.

- **Akses**: Business Units → ATK → Keuangan → Manajemen Biaya

### Tipe Fee yang Didukung
1. **Fixed Fee**: Biaya tetap per transaksi
2. **Percentage Fee**: Biaya berdasarkan persentase nominal
3. **Fixed + Percentage**: Kombinasi biaya tetap dan persentase
4. **Tier Fee**: Biaya bertingkat berdasarkan range nominal
5. **Cost Plus Markup**: Harga jual = harga cost + markup
6. **Custom Formula**: Formula kustom dengan variabel `amount`

### Membuat Fee Profile Baru
1. Klik "Tambah Fee Profile"
2. Isi form:
   - Nama profile
   - Tipe transaksi (Bank, Tarik Tunai, Top Up, PPOB, QRIS, Custom)
   - Mode fee (pilih salah satu dari 6 mode di atas)
   - Aktifkan profile (centang "Aktif")
   - Izinkan override manual (opsional)
3. Isi detail sesuai mode fee yang dipilih
4. Simpan

### Mengedit Fee Profile
1. Klik ikon edit di baris profile yang ingin diubah
2. Ubah data yang diperlukan
3. Simpan

### Menghapus Fee Profile
1. Klik ikon hapus di baris profile yang ingin dihapus
2. Konfirmasi penghapusan

---

## Akun Float
- **Akses**: Business Units → ATK → Keuangan → Akun Float
- Digunakan untuk mengelola akun float untuk transaksi tarik tunai
- Kamu bisa melihat saldo dan riwayat transaksi setiap akun

---

## Dana Talangan
- **Akses**: Business Units → ATK → Keuangan → Dana Talangan
- Digunakan untuk mengelola dana talangan (modal) untuk toko ATK

---

## Mutasi Kas Utama
- **Akses**: Business Units → ATK → Keuangan → Mutasi Kas Utama
- Menampilkan semua perubahan saldo kas utama (pemasukan dan pengeluaran)

---

## Pengaturan ATK
- **Akses**: Konfigurasi Sistem → Pengaturan → Pengaturan ATK
- Digunakan untuk mengatur fee dasar (backup jika fee profile tidak ada) dan pengaturan toko ATK lainnya

---

## Laporan
- **Akses**: Business Units → ATK → Laporan
- Tipe laporan yang tersedia:
  1. **Laporan Penjualan**: Ringkasan penjualan produk
  2. **Laporan Kas Harian**: Mutasi kas harian
  3. **Laporan Mutasi Kas**: Detail mutasi kas
  4. **Laporan Float**: Riwayat transaksi float
  5. **Laporan Dana Talangan**: Riwayat dana talangan

---

## Catatan Penting
1. Selalu cek stok sebelum melakukan transaksi
2. Pastikan fee profile sudah diatur dengan benar sebelum transaksi
3. Gunakan fitur audit trail di Fee Log untuk melihat riwayat perhitungan fee
