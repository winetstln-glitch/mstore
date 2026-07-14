@extends('layouts.app')

@section('content')
<div class="container-fluid">
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
                    🏪 POS ATK
                </a>
                <a href="#transactions" class="list-group-item list-group-item-action">
                    📋 Riwayat Transaksi
                </a>
                <a href="#fee" class="list-group-item list-group-item-action">
                    💲 Manajemen Biaya
                </a>
                <a href="#receipt" class="list-group-item list-group-item-action">
                    📄 Receipt Engine
                </a>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">📖 Panduan Modul ATK</h1>
                </div>
                <div class="card-body">

                    <section id="dashboard" class="mb-5 pb-3 border-bottom">
                        <h2 class="mb-3 text-primary">📊 Dashboard ATK</h2>
                        <p>
                            Dashboard menampilkan ringkasan operasional toko ATK hari ini, termasuk total penjualan,
                            transaksi, fee yang terkumpul, dan produk terlaris.
                        </p>
                        <h4>Cara mengakses:</h4>
                        <ol>
                            <li>Klik menu <strong>ATK</strong> di sidebar</li>
                            <li>Pilih <strong>Dashboard ATK</strong></li>
                        </ol>
                    </section>

                    <section id="products" class="mb-5 pb-3 border-bottom">
                        <h2 class="mb-3 text-primary">📦 Produk & Stok ATK</h2>
                        <p>
                            Kelola produk ATK, jasa, dan stok barang.
                        </p>
                        <h4>Menambah produk baru:</h4>
                        <ol>
                            <li>Buka <strong>ATK</strong> → <strong>Master Data</strong> → <strong>Manajemen ATK</strong></li>
                            <li>Klik tombol <strong>Tambah Produk</strong></li>
                            <li>Isi form dengan nama, kategori, harga, dan stok</li>
                            <li>Klik <strong>Simpan</strong></li>
                        </ol>
                    </section>

                    <section id="pos" class="mb-5 pb-3 border-bottom">
                        <h2 class="mb-3 text-primary">🏪 POS ATK</h2>
                        <p>
                            Melakukan transaksi penjualan dengan cepat dan otomatis menghitung fee.
                        </p>
                        <h4>Langkah transaksi:</h4>
                        <ol>
                            <li>Buka <strong>ATK</strong> → <strong>POS ATK</strong></li>
                            <li>Pilih produk dari daftar atau cari dengan kata kunci</li>
                            <li>Atur jumlah produk</li>
                            <li>Untuk jasa (transfer bank, tarik tunai, dll.): isi nominal → fee dihitung otomatis</li>
                            <li>Pilih metode pembayaran</li>
                            <li>Klik <strong>Proses Transaksi</strong></li>
                        </ol>
                    </section>

                    <section id="transactions" class="mb-5 pb-3 border-bottom">
                        <h2 class="mb-3 text-primary">📋 Riwayat Transaksi</h2>
                        <p>
                            Melihat, mencetak, dan mengedit riwayat transaksi ATK.
                        </p>
                    </section>

                    <section id="fee" class="mb-5 pb-3 border-bottom">
                        <h2 class="mb-3 text-primary">💲 Manajemen Biaya (Fee Engine)</h2>
                        <p>
                            Mengkonfigurasi biaya transaksi dengan berbagai mode: fixed, percentage, tiered, dll.
                        </p>
                        <h4>Membuat fee profile baru:</h4>
                        <ol>
                            <li>Buka <strong>ATK</strong> → <strong>Keuangan</strong> → <strong>Manajemen Biaya</strong></li>
                            <li>Klik <strong>Tambah Fee Profile</strong></li>
                            <li>Pilih tipe transaksi dan mode fee</li>
                            <li>Isi detail sesuai mode fee</li>
                            <li>Klik <strong>Simpan</strong></li>
                        </ol>
                    </section>

                    <section id="receipt" class="mb-5 pb-3">
                        <h2 class="mb-3 text-primary">📄 Receipt Engine</h2>
                        <p>
                            Receipt Engine mendukung berbagai fitur enterprise:
                        </p>
                        <ul>
                            <li>✅ Receipt thermal 58mm / 80mm</li>
                            <li>✅ Receipt PDF A4</li>
                            <li>✅ QR Code verifikasi</li>
                            <li>✅ Barcode transaksi</li>
                            <li>✅ Audit log cetak / bagikan</li>
                            <li>✅ Share via WhatsApp / Email</li>
                        </ul>
                    </section>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                links.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
});
</script>
@endpush
