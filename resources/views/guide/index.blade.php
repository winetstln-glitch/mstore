@extends('layouts.app')

@section('title', __('Panduan Penggunaan'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2 text-gray-800"><i class="fa-solid fa-book-open me-2"></i>{{ __('Panduan Penggunaan Aplikasi') }}</h1>
            <p class="mb-4">{{ __('Dokumentasi lengkap fitur dan cara penggunaan sistem manajemen MStore.') }}</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="list-group shadow-sm" id="list-tab" role="tablist">
                <a class="list-group-item list-group-item-action active fw-bold" id="list-dashboard-list" data-bs-toggle="list" href="#list-dashboard" role="tab"><i class="fa-solid fa-gauge me-2"></i>Dashboard</a>
                <a class="list-group-item list-group-item-action fw-bold" id="list-finance-list" data-bs-toggle="list" href="#list-finance" role="tab"><i class="fa-solid fa-wallet me-2"></i>Keuangan (Finance)</a>
                <a class="list-group-item list-group-item-action fw-bold" id="list-inventory-list" data-bs-toggle="list" href="#list-inventory" role="tab"><i class="fa-solid fa-boxes-stacked me-2"></i>Inventaris (Gudang)</a>
                <a class="list-group-item list-group-item-action fw-bold" id="list-hotspot-list" data-bs-toggle="list" href="#list-hotspot" role="tab"><i class="fa-solid fa-wifi me-2"></i>Hotspot & PPPoE</a>
                <a class="list-group-item list-group-item-action fw-bold" id="list-pos-list" data-bs-toggle="list" href="#list-pos" role="tab"><i class="fa-solid fa-cash-register me-2"></i>POS (ATK & Wash)</a>
                <a class="list-group-item list-group-item-action fw-bold" id="list-genie-list" data-bs-toggle="list" href="#list-genie" role="tab"><i class="fa-solid fa-server me-2"></i>GenieACS (TR-069)</a>
            </div>
        </div>
        <div class="col-md-9">
            <div class="tab-content" id="nav-tabContent">
                
                <!-- Dashboard -->
                <div class="tab-pane fade show active" id="list-dashboard" role="tabpanel">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3 bg-primary text-white">
                            <h6 class="m-0 fw-bold">{{ __('Dashboard Overview') }}</h6>
                        </div>
                        <div class="card-body">
                            <p>Halaman Dashboard memberikan ringkasan status sistem secara real-time.</p>
                            <ul>
                                <li><strong>Statistik Pelanggan:</strong> Jumlah total pelanggan dan penambahan bulan ini.</li>
                                <li><strong>Tiket & Instalasi:</strong> Status tiket gangguan dan jadwal instalasi baru.</li>
                                <li><strong>GenieACS Monitor:</strong> Status perangkat ON/OFF secara real-time (update setiap menit).</li>
                                <li><strong>Keuangan Cepat:</strong> Grafik pendapatan dan pengeluaran tahun berjalan.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Finance -->
                <div class="tab-pane fade" id="list-finance" role="tabpanel">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3 bg-success text-white">
                            <h6 class="m-0 fw-bold">{{ __('Modul Keuangan') }}</h6>
                        </div>
                        <div class="card-body">
                            <p>Fitur untuk mencatat dan memonitor arus kas.</p>
                            <h6 class="fw-bold">Fitur Utama:</h6>
                            <ul>
                                <li><strong>Transaksi:</strong> Catat Pemasukan (Income) dan Pengeluaran (Expense).</li>
                                <li><strong>Laporan Laba Rugi:</strong> Analisis detail pendapatan vs biaya operasional. Termasuk perhitungan otomatis:
                                    <ul>
                                        <li>Pendapatan Toko (ATK) & Jasa (Wash).</li>
                                        <li>Keuntungan Inventaris (Material Keluar).</li>
                                        <li>Komisi Koordinator & ISP.</li>
                                        <li>Dana Kas Pengurus (Investor Cash Fund) 5%.</li>
                                    </ul>
                                </li>
                                <li><strong>Investor Share:</strong> Pembagian hasil investasi otomatis.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Inventory -->
                <div class="tab-pane fade" id="list-inventory" role="tabpanel">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3 bg-warning text-dark">
                            <h6 class="m-0 fw-bold">{{ __('Manajemen Inventaris') }}</h6>
                        </div>
                        <div class="card-body">
                            <p>Kelola stok barang, alat, dan material.</p>
                            <ul>
                                <li><strong>Barang Masuk/Keluar:</strong> Catat mutasi stok. Barang keluar otomatis dihitung sebagai "Revenue" jika disetting demikian.</li>
                                <li><strong>Satuan & Kategori:</strong> Pengelompokan barang (Kabel, Modem, Alat Tulis, dll).</li>
                                <li><strong>Opname:</strong> Penyesuaian stok fisik dan sistem.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Hotspot -->
                <div class="tab-pane fade" id="list-hotspot" role="tabpanel">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3 bg-info text-white">
                            <h6 class="m-0 fw-bold">{{ __('Hotspot & PPPoE') }}</h6>
                        </div>
                        <div class="card-body">
                            <p>Integrasi Mikrotik untuk manajemen voucher dan pelanggan rumahan.</p>
                            <ul>
                                <li><strong>Generate Voucher:</strong> Buat voucher massal dengan template print custom.</li>
                                <li><strong>Biaya Print:</strong> Tambahan biaya jasa print voucher yang masuk ke kas.</li>
                                <li><strong>PPPoE Profil:</strong> Sinkronisasi profil paket internet dari Mikrotik.</li>
                                <li><strong>Monitoring Aktif:</strong> Lihat user yang sedang online real-time.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- POS -->
                <div class="tab-pane fade" id="list-pos" role="tabpanel">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3 bg-secondary text-white">
                            <h6 class="m-0 fw-bold">{{ __('Point of Sales (ATK & Wash)') }}</h6>
                        </div>
                        <div class="card-body">
                            <p>Sistem Kasir untuk unit usaha sampingan.</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="fw-bold">ATK (Alat Tulis Kantor)</h6>
                                    <ul>
                                        <li>Penjualan barang langsung.</li>
                                        <li>Cetak struk thermal (58mm).</li>
                                        <li>Laporan harian/bulanan.</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold">WASH (Cuci Kendaraan)</h6>
                                    <ul>
                                        <li>Transaksi jasa cuci (Motor/Mobil).</li>
                                        <li>Pilihan layanan & harga.</li>
                                        <li>Rekap pendapatan terpisah.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GenieACS -->
                <div class="tab-pane fade" id="list-genie" role="tabpanel">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3 bg-dark text-white">
                            <h6 class="m-0 fw-bold">{{ __('GenieACS (TR-069)') }}</h6>
                        </div>
                        <div class="card-body">
                            <p>Manajemen perangkat modem/ONU jarak jauh.</p>
                            <ul>
                                <li><strong>Monitoring:</strong> Status Online/Offline perangkat.</li>
                                <li><strong>Notifikasi Telegram:</strong> Alert otomatis saat perangkat mati/hidup.</li>
                                <li><strong>Update Admin:</strong> Ubah password Superadmin/User Admin dari satu pintu.</li>
                                <li><strong>Reboot/Reset:</strong> Kontrol perangkat jarak jauh.</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
