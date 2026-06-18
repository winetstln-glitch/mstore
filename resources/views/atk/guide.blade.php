@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="mb-0 h3">📖 Panduan Pengoperasian Toko ATK</h1>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <div class="list-group sticky-top" style="top: 20px;">
                                <a href="#dashboard" class="list-group-item list-group-item-action active">
                                    📊 Dashboard ATK
                                </a>
                                <a href="#products" class="list-group-item list-group-item-action">
                                    📦 Produk & Stok ATK
                                </a>
                                <a href="#pos" class="list-group-item list-group-item-action">
                                    🏪 POS ATK (Transaksi)
                                </a>
                                <a href="#transactions" class="list-group-item list-group-item-action">
                                    📋 Riwayat Transaksi
                                </a>
                                <a href="#expenses" class="list-group-item list-group-item-action">
                                    💸 Pengeluaran ATK
                                </a>
                                <a href="#fee" class="list-group-item list-group-item-action">
                                    💲 Manajemen Biaya (Fee Engine)
                                </a>
                                <a href="#float" class="list-group-item list-group-item-action">
                                    💰 Akun Float
                                </a>
                                <a href="#owner-funds" class="list-group-item list-group-item-action">
                                    📊 Dana Talangan
                                </a>
                                <a href="#cash-movements" class="list-group-item list-group-item-action">
                                    💵 Mutasi Kas Utama
                                </a>
                                <a href="#settings" class="list-group-item list-group-item-action">
                                    ⚙️ Pengaturan ATK
                                </a>
                                <a href="#reports" class="list-group-item list-group-item-action">
                                    📈 Laporan ATK
                                </a>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <!-- Dashboard -->
                            <section id="dashboard" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">📊 Dashboard ATK</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Menampilkan ringkasan operasional toko ATK hari ini.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>ATK &gt; Dashboard</strong></li>
                                    <li class="list-group-item">Lihat <strong>Total Penjualan Hari Ini</strong> dan <strong>Jumlah Transaksi</strong></li>
                                    <li class="list-group-item">Periksa <strong>Total Fee Hari Ini</strong> dan <strong>Total Fee Bulan Ini</strong></li>
                                    <li class="list-group-item">Lihat <strong>Top 5 Produk Terlaris</strong> dan <strong>Breakdown Fee</strong></li>
                                </ol>
                            </section>

                            <!-- Products -->
                            <section id="products" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">📦 Produk & Stok ATK</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Mengelola produk ATK, layanan, dan stok barang.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>ATK &gt; Master Data &gt; Manajemen ATK</strong></li>
                                    <li class="list-group-item">Untuk menambah produk baru, klik <strong>Tambah Produk</strong></li>
                                    <li class="list-group-item">Isi nama produk, kategori (ATK, Jasa Potocopy, Jasa Transfer Bank), harga jual, harga beli (cost), dan stok awal</li>
                                    <li class="list-group-item">Klik <strong>Simpan</strong></li>
                                    <li class="list-group-item">Untuk mengedit produk, klik ikon edit di baris produk yang ingin diubah</li>
                                    <li class="list-group-item">Untuk menghapus produk, klik ikon hapus dan konfirmasi</li>
                                </ol>
                            </section>

                            <!-- POS -->
                            <section id="pos" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">🏪 POS ATK (Transaksi Utama)</h2>
                                <div class="alert alert-success">
                                    <strong>Tujuan:</strong> Melakukan transaksi penjualan produk ATK dan jasa dengan cepat.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>ATK &gt; POS ATK</strong></li>
                                    <li class="list-group-item">Pilih produk dari daftar (klik produk untuk menambah ke keranjang)</li>
                                    <li class="list-group-item">Atur jumlah produk jika perlu</li>
                                    <li class="list-group-item">Untuk transaksi jasa (Transfer Bank, Tarik Tunai, Top Up, PPOB):
                                        <ul class="mt-2">
                                            <li>Pilih kategori jasa</li>
                                            <li>Isi nominal transaksi</li>
                                            <li>Fee akan dihitung otomatis berdasarkan fee profile yang aktif</li>
                                            <li>Kamu bisa mengubah fee secara manual jika diizinkan</li>
                                        </ul>
                                    </li>
                                    <li class="list-group-item">Pilih <strong>Metode Pembayaran</strong>: Tunai, Hutang, dll.</li>
                                    <li class="list-group-item">Untuk <strong>Tunai</strong>, masukkan nominal bayar untuk melihat kembalian</li>
                                    <li class="list-group-item">Klik <strong>Proses Transaksi</strong> untuk menyelesaikan</li>
                                </ol>
                            </section>

                            <!-- Transactions -->
                            <section id="transactions" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">📋 Riwayat Transaksi</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Melihat, mengedit, dan mencetak riwayat transaksi ATK.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>ATK &gt; Transaksi &gt; Riwayat Transaksi</strong></li>
                                    <li class="list-group-item">Gunakan <strong>Filter Tanggal</strong> untuk melihat transaksi pada periode tertentu</li>
                                    <li class="list-group-item">Klik <strong>Detail</strong> untuk melihat rincian transaksi</li>
                                    <li class="list-group-item">Klik <strong>Cetak Nota</strong> untuk mencetak bukti transaksi</li>
                                    <li class="list-group-item">Untuk pengguna dengan izin <code>atk.manage</code>, bisa mengedit atau menghapus transaksi</li>
                                </ol>
                            </section>

                            <!-- Expenses -->
                            <section id="expenses" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">💸 Pengeluaran ATK</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Mencatat semua pengeluaran operasional toko ATK.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>ATK &gt; Keuangan &gt; Pengeluaran</strong></li>
                                    <li class="list-group-item">Klik <strong>Tambah Pengeluaran</strong></li>
                                    <li class="list-group-item">Isi <strong>Keterangan Pengeluaran</strong> (contoh: Beli kertas, Gaji karyawan, dll.)</li>
                                    <li class="list-group-item">Masukkan <strong>Jumlah Pengeluaran</strong></li>
                                    <li class="list-group-item">Pilih <strong>Tanggal Pengeluaran</strong></li>
                                    <li class="list-group-item">Klik <strong>Simpan</strong></li>
                                </ol>
                            </section>

                            <!-- Fee Management -->
                            <section id="fee" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">💲 Manajemen Biaya (Fee Engine Enterprise)</h2>
                                <div class="alert alert-success">
                                    <strong>Tujuan:</strong> Mengelola dan mengkonfigurasi biaya transaksi secara fleksibel tanpa perubahan kode.
                                </div>

                                <h4>Tipe Fee yang Didukung:</h4>
                                <ol class="list-group list-group-numbered mb-3">
                                    <li class="list-group-item"><strong>Fixed Fee:</strong> Biaya tetap per transaksi (contoh: Rp 2.000)</li>
                                    <li class="list-group-item"><strong>Percentage Fee:</strong> Biaya berdasarkan persentase nominal (contoh: 0.5%)</li>
                                    <li class="list-group-item"><strong>Fixed + Percentage:</strong> Kombinasi biaya tetap dan persentase</li>
                                    <li class="list-group-item"><strong>Tier Fee:</strong> Biaya bertingkat berdasarkan range nominal (contoh: 0-1jt = 5rb, 1jt-3jt = 10rb)</li>
                                    <li class="list-group-item"><strong>Cost Plus Markup:</strong> Harga jual = harga cost + markup</li>
                                    <li class="list-group-item"><strong>Custom Formula:</strong> Formula kustom dengan variabel amount (contoh: (amount * 0.5 / 100) + 3000)</li>
                                </ol>

                                <h4>Cara Membuat Fee Profile Baru:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>ATK &gt; Keuangan &gt; Manajemen Biaya</strong></li>
                                    <li class="list-group-item">Klik <strong>Tambah Fee Profile</strong></li>
                                    <li class="list-group-item">Isi:
                                        <ul>
                                            <li><strong>Nama Profile:</strong> Nama yang jelas (contoh: Fee Transfer Bank)</li>
                                            <li><strong>Tipe Transaksi:</strong> Pilih jenis transaksi (Bank, Tarik Tunai, Top Up, PPOB, QRIS, Custom)</li>
                                            <li><strong>Mode Fee:</strong> Pilih salah satu dari 6 mode di atas</li>
                                            <li><strong>Aktif:</strong> Centang untuk mengaktifkan profile</li>
                                            <li><strong>Izinkan Override Manual:</strong> Centang untuk mengizinkan kasir mengubah fee secara manual</li>
                                        </ul>
                                    </li>
                                    <li class="list-group-item">Isi detail sesuai mode fee yang dipilih (min amount, max amount, fee value, dll.)</li>
                                    <li class="list-group-item">Klik <strong>Simpan</strong></li>
                                </ol>

                                <h4>Cara Mengedit/Hapus Fee Profile:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Di halaman daftar fee profile, klik ikon <strong>Edit</strong> untuk mengubah</li>
                                    <li class="list-group-item">Klik ikon <strong>Hapus</strong> untuk menghapus (konfirmasi terlebih dahulu)</li>
                                    <li class="list-group-item">Klik <strong>Lihat</strong> untuk melihat detail fee profile beserta tier-nya</li>
                                </ol>
                            </section>

                            <!-- Float Accounts -->
                            <section id="float" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">💰 Akun Float</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Mengelola akun float untuk transaksi tarik tunai dan transfer bank.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>ATK &gt; Keuangan &gt; Akun Float</strong></li>
                                    <li class="list-group-item">Klik <strong>Tambah Akun Float</strong></li>
                                    <li class="list-group-item">Isi nama akun, nomor rekening, dan saldo awal</li>
                                    <li class="list-group-item">Klik <strong>Simpan</strong></li>
                                    <li class="list-group-item">Untuk melihat riwayat transaksi float, klik <strong>Lihat Transaksi</strong> pada akun yang diinginkan</li>
                                </ol>
                            </section>

                            <!-- Owner Funds -->
                            <section id="owner-funds" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">📊 Dana Talangan</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Mengelola dana talangan (modal) untuk operasional toko ATK.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>ATK &gt; Keuangan &gt; Dana Talangan</strong></li>
                                    <li class="list-group-item">Klik <strong>Tambah Dana</strong> untuk mencatat penambahan modal</li>
                                    <li class="list-group-item">Isi jumlah dana, keterangan, dan tanggal</li>
                                    <li class="list-group-item">Klik <strong>Simpan</strong></li>
                                    <li class="list-group-item">Lihat riwayat dana talangan di halaman utama</li>
                                </ol>
                            </section>

                            <!-- Cash Movements -->
                            <section id="cash-movements" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">💵 Mutasi Kas Utama</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Mencatat semua perubahan saldo kas utama (masuk/keluar uang tunai).
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>ATK &gt; Keuangan &gt; Mutasi Kas Utama</strong></li>
                                    <li class="list-group-item">Klik <strong>Tambah Mutasi</strong></li>
                                    <li class="list-group-item">Pilih jenis mutasi: <strong>Masuk</strong> (pendapatan) atau <strong>Keluar</strong> (pengeluaran)</li>
                                    <li class="list-group-item">Isi keterangan, jumlah, dan tanggal</li>
                                    <li class="list-group-item">Klik <strong>Simpan</strong></li>
                                </ol>
                            </section>

                            <!-- Settings -->
                            <section id="settings" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">⚙️ Pengaturan ATK</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Mengkonfigurasi pengaturan dasar toko ATK (sebagai fallback jika fee profile tidak ada).
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>Konfigurasi Sistem &gt; Pengaturan &gt; Pengaturan ATK</strong></li>
                                    <li class="list-group-item">Isi fee dasar untuk setiap jenis transaksi (bank, cash out, top up, PPOB) dalam persen dan fixed</li>
                                    <li class="list-group-item">Klik <strong>Simpan Pengaturan</strong></li>
                                </ol>
                            </section>

                            <!-- Reports -->
                            <section id="reports" class="mb-5 pb-3">
                                <h2 class="mb-3 text-primary">📈 Laporan ATK</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Melihat laporan keuangan dan operasional toko ATK.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>ATK &gt; Laporan</strong></li>
                                    <li class="list-group-item">Pilih jenis laporan: <strong>Laporan Penjualan</strong>, <strong>Laporan Kas Harian</strong>, dll.</li>
                                    <li class="list-group-item">Pilih <strong>Periode Laporan</strong> (tanggal mulai dan tanggal selesai)</li>
                                    <li class="list-group-item">Lihat ringkasan laporan pada tabel yang ditampilkan</li>
                                    <li class="list-group-item">Klik <strong>Export PDF</strong> atau <strong>Export Excel</strong> untuk mengunduh laporan</li>
                                </ol>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll untuk anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                // Update active state pada list group
                document.querySelectorAll('.list-group-item').forEach(item => item.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
});
</script>
@endpush
