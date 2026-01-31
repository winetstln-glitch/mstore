@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Laporan & Statistik</h1>

    <div class="row">
        <!-- ATK Report Card -->
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Laporan Toko (ATK)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Penjualan & Stok</div>
                            <p class="mt-2 text-muted small">Lihat laporan penjualan harian, bulanan, dan performa produk toko.</p>
                            <a href="{{ route('reports.atk') }}" class="btn btn-primary btn-sm mt-2">Buka Laporan</a>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-store fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wash Report Card -->
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Laporan Layanan (Steam)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Cucian & Komisi</div>
                            <p class="mt-2 text-muted small">Lihat laporan pendapatan cuci mobil/motor dan komisi karyawan.</p>
                            <a href="{{ route('reports.wash') }}" class="btn btn-success btn-sm mt-2">Buka Laporan</a>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-soap fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
