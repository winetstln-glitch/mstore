@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="mb-0 h3">📖 Panduan Pengoperasian GT Wash</h1>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <div class="list-group sticky-top" style="top: 20px;">
                                <a href="#dashboard" class="list-group-item list-group-item-action active">
                                    📊 Dashboard Wash
                                </a>
                                <a href="#pos" class="list-group-item list-group-item-action">
                                    🏪 POS Wash (Transaksi)
                                </a>
                                <a href="#transactions" class="list-group-item list-group-item-action">
                                    📋 Transaksi Wash
                                </a>
                                <a href="#expenses" class="list-group-item list-group-item-action">
                                    💸 Pengeluaran Wash
                                </a>
                                <a href="#stock" class="list-group-item list-group-item-action">
                                    📦 Stok Wash
                                </a>
                                <a href="#services" class="list-group-item list-group-item-action">
                                    🛠️ Layanan Wash
                                </a>
                                <a href="#suppliers" class="list-group-item list-group-item-action">
                                    🚚 Supplier Wash
                                </a>
                                <a href="#shifts" class="list-group-item list-group-item-action">
                                    ⏰ Shift & Shift Session
                                </a>
                                <a href="#cash-register" class="list-group-item list-group-item-action">
                                    💰 Kasir & Mutasi Kas
                                </a>
                                <a href="#daily-closing" class="list-group-item list-group-item-action">
                                    📅 Penutupan Harian
                                </a>
                                <a href="#members" class="list-group-item list-group-item-action">
                                    👤 Member Wash
                                </a>
                                <a href="#loyalty" class="list-group-item list-group-item-action">
                                    🎁 Loyalty Program
                                </a>
                                <a href="#reports" class="list-group-item list-group-item-action">
                                    📈 Laporan Wash
                                </a>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <!-- Dashboard -->
                            <section id="dashboard" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">📊 Dashboard Wash</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Menampilkan ringkasan operasional wash hari ini.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>GT Wash &gt; Dashboard</strong></li>
                                    <li class="list-group-item">Lihat <strong>Total Penjualan Hari Ini</strong> dan <strong>Jumlah Transaksi</strong></li>
                                    <li class="list-group-item">Periksa <strong>Kehadiran Karyawan</strong> yang sudah absen hari ini</li>
                                    <li class="list-group-item">Lihat <strong>Grafik Tren Layanan</strong> 7 hari terakhir</li>
                                    <li class="list-group-item">Lihat <strong>Member Terbaru</strong> dan <strong>Top Customer Loyalty</strong></li>
                                </ol>
                            </section>

                            <!-- POS -->
                            <section id="pos" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">🏪 POS Wash (Transaksi Utama)</h2>
                                <div class="alert alert-success">
                                    <strong>Tujuan:</strong> Melakukan transaksi layanan wash dengan cepat.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>GT Wash &gt; POS Wash</strong></li>
                                    <li class="list-group-item">Pilih <strong>Merek Kendaraan</strong> dan masukkan <strong>Plat Nomor</strong></li>
                                    <li class="list-group-item">Masukkan <strong>Nomor HP Pelanggan</strong> (opsional, untuk loyalty/member)</li>
                                    <li class="list-group-item">Klik <strong>Cek</strong> untuk melihat riwayat dan voucher yang tersedia</li>
                                    <li class="list-group-item">Pilih <strong>Layanan</strong> yang diinginkan (klik pada kartu layanan)</li>
                                    <li class="list-group-item">Jika layanan memiliki aturan harga, pilih varian yang sesuai</li>
                                    <li class="list-group-item">Pilih <strong>Metode Pembayaran</strong>: Tunai, QRIS, Transfer, EDC, atau Kasbon</li>
                                    <li class="list-group-item">Untuk <strong>Tunai</strong>, masukkan nominal bayar untuk melihat kembalian</li>
                                    <li class="list-group-item">Untuk <strong>Kasbon</strong>, pilih karyawan atau masukkan nama pihak luar</li>
                                    <li class="list-group-item">Klik <strong>Proses Pembayaran</strong> untuk menyelesaikan transaksi</li>
                                </ol>
                                <div class="alert alert-warning mt-3">
                                    <strong>Tips:</strong> Centang "Kirim nota via WhatsApp" untuk mengirimkan nota ke pelanggan secara otomatis!
                                </div>
                            </section>

                            <!-- Transactions -->
                            <section id="transactions" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">📋 Transaksi Wash</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Melihat, mengedit, dan mencetak riwayat transaksi wash.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>GT Wash &gt; Transaksi</strong></li>
                                    <li class="list-group-item">Gunakan <strong>Filter Tanggal</strong> untuk melihat transaksi pada periode tertentu</li>
                                    <li class="list-group-item">Klik <strong>Detail</strong> untuk melihat rincian transaksi</li>
                                    <li class="list-group-item">Klik <strong>Cetak Nota</strong> untuk mencetak bukti transaksi</li>
                                    <li class="list-group-item">Untuk pengguna dengan izin <code>wash.manage</code>, bisa mengedit atau menghapus transaksi</li>
                                </ol>
                            </section>

                            <!-- Expenses -->
                            <section id="expenses" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">💸 Pengeluaran Wash</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Mencatat semua pengeluaran operasional wash.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>GT Wash &gt; Pengeluaran</strong></li>
                                    <li class="list-group-item">Klik <strong>Tambah Pengeluaran</strong></li>
                                    <li class="list-group-item">Isi <strong>Keterangan Pengeluaran</strong> (contoh: Beli sabun, Gaji karyawan, dll.)</li>
                                    <li class="list-group-item">Masukkan <strong>Jumlah Pengeluaran</strong></li>
                                    <li class="list-group-item">Pilih <strong>Tanggal Pengeluaran</strong></li>
                                    <li class="list-group-item">Klik <strong>Simpan</strong></li>
                                </ol>
                            </section>

                            <!-- Stock -->
                            <section id="stock" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">📦 Stok Wash</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Mengelola barang stok wash (sabun, shampoo, dll.) dan mencatat mutasi stok.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>GT Wash &gt; Stok Wash</strong></li>
                                    <li class="list-group-item">Untuk menambah item stok baru, klik <strong>Tambah Stok</strong></li>
                                    <li class="list-group-item">Isi nama barang, satuan, stok awal, dan harga</li>
                                    <li class="list-group-item">Untuk mencatat stok masuk, klik <strong>Stok Masuk</strong> pada item yang diinginkan</li>
                                    <li class="list-group-item">Untuk mencatat stok keluar, klik <strong>Stok Keluar</strong></li>
                                    <li class="list-group-item">Lihat riwayat mutasi stok pada tab <strong>Riwayat Stok</strong></li>
                                </ol>
                            </section>

                            <!-- Services -->
                            <section id="services" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">🛠️ Layanan Wash</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Mengelola daftar layanan wash yang tersedia.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>GT Wash &gt; Layanan Wash</strong></li>
                                    <li class="list-group-item">Klik <strong>Tambah Layanan</strong> untuk menambah layanan baru</li>
                                    <li class="list-group-item">Isi nama layanan, jenis kendaraan (Mobil/Motor/Kopi), kategori, dan harga</li>
                                    <li class="list-group-item">Untuk layanan dengan harga bervariasi (contoh: berdasarkan ukuran), tambahkan <strong>Aturan Harga</strong></li>
                                    <li class="list-group-item">Klik <strong>Simpan</strong></li>
                                </ol>
                            </section>

                            <!-- Suppliers -->
                            <section id="suppliers" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">🚚 Supplier Wash</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Menyimpan data supplier barang wash.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>GT Wash &gt; Supplier</strong></li>
                                    <li class="list-group-item">Klik <strong>Tambah Supplier</strong></li>
                                    <li class="list-group-item">Isi nama supplier, alamat, nomor telepon, dan email</li>
                                    <li class="list-group-item">Klik <strong>Simpan</strong></li>
                                </ol>
                            </section>

                            <!-- Shifts -->
                            <section id="shifts" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">⏰ Shift & Shift Session</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Mengelola jadwal shift karyawan dan mencatat sesi shift harian.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>GT Wash &gt; Shift</strong> untuk mengelola jadwal shift</li>
                                    <li class="list-group-item">Klik <strong>Tambah Shift</strong>, isi nama shift, jam mulai, dan jam selesai</li>
                                    <li class="list-group-item">Untuk mencatat sesi shift harian, buka <strong>GT Wash &gt; Shift Session</strong></li>
                                    <li class="list-group-item">Klik <strong>Buat Sesi Shift</strong>, pilih shift, tanggal, dan karyawan yang bertugas</li>
                                </ol>
                            </section>

                            <!-- Cash Register -->
                            <section id="cash-register" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">💰 Kasir & Mutasi Kas</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Mengelola kasir dan mencatat semua mutasi (masuk/keluar) uang tunai.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>GT Wash &gt; Kasir</strong> untuk melihat dan mengelola data kasir</li>
                                    <li class="list-group-item">Buka menu <strong>GT Wash &gt; Mutasi Kas</strong> untuk mencatat mutasi uang</li>
                                    <li class="list-group-item">Klik <strong>Tambah Mutasi Kas</strong></li>
                                    <li class="list-group-item">Pilih jenis mutasi: <strong>Masuk</strong> (pendapatan) atau <strong>Keluar</strong> (pengeluaran)</li>
                                    <li class="list-group-item">Isi keterangan, jumlah, dan pilih kasir yang terkait</li>
                                    <li class="list-group-item">Klik <strong>Simpan</strong></li>
                                </ol>
                            </section>

                            <!-- Daily Closing -->
                            <section id="daily-closing" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">📅 Penutupan Harian</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Menutup buku harian dan mencatat saldo akhir kas.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>GT Wash &gt; Penutupan Harian</strong></li>
                                    <li class="list-group-item">Klik <strong>Buat Penutupan Harian</strong></li>
                                    <li class="list-group-item">Pilih tanggal dan kasir yang akan ditutup</li>
                                    <li class="list-group-item">Masukkan <strong>Saldo Fisik</strong> (uang yang ada di kasir)</li>
                                    <li class="list-group-item">Sistem akan menampilkan <strong>Saldo Sistem</strong> dan <strong>Selisih</strong></li>
                                    <li class="list-group-item">Klik <strong>Simpan Penutupan</strong></li>
                                </ol>
                            </section>

                            <!-- Members -->
                            <section id="members" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">👤 Member Wash</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Mengelola data member wash dan memberikan benefit khusus.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>GT Wash &gt; Member</strong></li>
                                    <li class="list-group-item">Klik <strong>Daftarkan Member Baru</strong></li>
                                    <li class="list-group-item">Isi nama, nomor WhatsApp, alamat (opsional), dan data kendaraan</li>
                                    <li class="list-group-item">Klik <strong>Simpan</strong> — member akan mendapatkan nomor member otomatis</li>
                                    <li class="list-group-item">Klik <strong>Cetak Kartu Member</strong> untuk mencetak kartu member</li>
                                    <li class="list-group-item">Member otomatis mendapatkan diskon sesuai levelnya (Bronze/Silver/Gold/Platinum)</li>
                                </ol>
                            </section>

                            <!-- Loyalty -->
                            <section id="loyalty" class="mb-5 pb-3 border-bottom">
                                <h2 class="mb-3 text-primary">🎁 Loyalty Program</h2>
                                <div class="alert alert-success">
                                    <strong>Tujuan:</strong> Memberikan reward kepada pelanggan setia.
                                </div>
                                <h4>Cara Kerja:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Setiap transaksi wash yang dibayar (status: Lunas) akan dihitung sebagai 1 poin loyalty</li>
                                    <li class="list-group-item">Setelah mencapai target (default: 11 transaksi), pelanggan mendapatkan <strong>Voucher Gratis Cuci</strong></li>
                                    <li class="list-group-item">Voucher bisa digunakan pada transaksi berikutnya untuk mendapatkan 1 layanan wash gratis</li>
                                    <li class="list-group-item">Voucher memiliki masa berlaku (default: 60 hari)</li>
                                </ol>
                                <h4>Cara Menggunakan Voucher:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Di halaman POS, masukkan plat nomor pelanggan</li>
                                    <li class="list-group-item">Klik <strong>Cek</strong> — jika memiliki voucher, akan muncul di bagian voucher</li>
                                    <li class="list-group-item">Pilih voucher yang ingin digunakan dan centang <strong>Gunakan Voucher</strong></li>
                                    <li class="list-group-item">Total transaksi akan menjadi Rp 0</li>
                                </ol>
                            </section>

                            <!-- Reports -->
                            <section id="reports" class="mb-5 pb-3">
                                <h2 class="mb-3 text-primary">📈 Laporan Wash</h2>
                                <div class="alert alert-info">
                                    <strong>Tujuan:</strong> Melihat laporan keuangan dan operasional wash.
                                </div>
                                <h4>Cara Menggunakan:</h4>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item">Buka menu <strong>GT Wash &gt; Laporan Wash</strong></li>
                                    <li class="list-group-item">Pilih <strong>Periode Laporan</strong> (tanggal mulai dan tanggal selesai)</li>
                                    <li class="list-group-item">Lihat ringkasan: Total Penjualan, Total Pengeluaran, Laba/Rugi, Jumlah Transaksi</li>
                                    <li class="list-group-item">Lihat detail transaksi dan pengeluaran pada tabel di bawah</li>
                                    <li class="list-group-item">Klik <strong>Export PDF</strong> untuk mencetak laporan ke file PDF</li>
                                    <li class="list-group-item">Klik <strong>Export Excel</strong> untuk mengunduh laporan ke file Excel</li>
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