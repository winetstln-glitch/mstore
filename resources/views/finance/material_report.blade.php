@extends('layouts.app')

@section('title', __('Laporan Pendapatan Material'))

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('Laporan Pendapatan Material') }}</h1>
            <p class="mb-0 text-muted small">Laporan penjualan barang & potongan komisi pengurus</p>
        </div>
        <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
            <a href="{{ route('finance.index') }}" class="btn btn-secondary btn-lg w-100 w-md-auto">
                <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}
            </a>
            <!-- Tombol Download Excel/PDF disarankan ditambahkan di sini jika ada -->
            <button onclick="window.print()" class="btn btn-primary btn-lg w-100 w-md-auto">
                <i class="fa-solid fa-print me-1"></i> {{ __('Cetak') }}
            </button>
        </div>
    </div>

    <!-- FILTER SECTION -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fa-solid fa-filter me-2"></i>{{ __('Filter Laporan') }}</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('finance.material_report') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">Tanggal Awal</label>
                    <input type="date" class="form-control form-control-lg" name="start_date" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">Tanggal Akhir</label>
                    <input type="date" class="form-control form-control-lg" name="end_date" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">Pengurus</label>
                    <select class="form-select form-select-lg" name="coordinator_id">
                        <option value="">{{ __('Semua Pengurus') }}</option>
                        @foreach($coordinators as $coord)
                            <option value="{{ $coord->id }}" {{ request('coordinator_id') == $coord->id ? 'selected' : '' }}>
                                {{ $coord->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Tampilkan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-left-info shadow h-100 py-2" style="border-left: 0.25rem solid #36b9cc !important;">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                        {{ __('Total Item Terjual') }}</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalQuantity, 0, ',', '.') }} Unit</div>
                    <small class="text-muted">Transaksi Sukses</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                        {{ __('Pendapatan Kotor (Gross)') }}</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($totalValue, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                        {{ __('Pendapatan Bersih (Net)') }}</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($netTotal, 0, ',', '.') }}</div>
                    <div class="small text-danger">- Potongan Komisi ({{ number_format($commissionAmount, 0, ',', '.') }})</div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLE SECTION -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">{{ __('Rincian Transaksi Material') }}</h6>
            <span class="badge bg-secondary-subtle text-body border">Periode: {{ request('start_date') ?? 'Awal' }} s/d {{ request('end_date') ?? 'Akhir' }}</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-responsive-mobile" width="100%" cellspacing="0">
                    <thead class="">
                        <tr>
                            <th width="100">{{ __('Tanggal') }}</th>
                            <th>{{ __('Item / Barang') }}</th>
                            <th width="150">{{ __('Pengurus') }}</th>
                            <th width="100" class="text-center">{{ __('Qty') }}</th>
                            <th width="150" class="text-end">{{ __('Total Harga') }}</th>
                            <th width="150" class="text-end">{{ __('Net (Setelah Komisi)') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $t)
                        @php
                            $price = $t->item->price ?? 0;
                            $gross = $t->quantity * $price;
                            // Hitung komisi baris ini
                            $commission = $gross * ($commissionRate / 100);
                            $net = $gross - $commission;
                        @endphp
                        <tr>
                            <td>{{ $t->created_at->format('d/m/y') }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $t->item->name ?? '-' }}</div>
                                <small class="text-muted">{{ $t->item->brand ?? '' }} {{ $t->item->model ?? '' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info border border-info">
                                    {{ $t->coordinator->name ?? '-' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ number_format($t->quantity, 0, ',', '.') }} {{ $t->item->unit ?? '' }}</span>
                            </td>
                            <td class="text-end fw-bold text-success">{{ number_format($gross, 0, ',', '.') }}</td>
                            <!-- Kolom Net -->
                            <td class="text-end fw-bold text-primary ">
                                {{ number_format($net, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-box-open fa-2x mb-2 d-block"></i>
                                Tidak ada transaksi material pada periode ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="fw-bold">
                        <tr class="table-light">
                            <td colspan="3" class="text-end">GRAND TOTAL</td>
                            <td class="text-center">{{ number_format($totalQuantity, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($totalValue, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($netTotal, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="alert alert-light border mt-3 mb-0 small">
                <i class="fa-solid fa-circle-info me-1"></i>
                <strong>Catatan:</strong> Pendapatan Bersih (Net) adalah Total Harga dikurangi Komisi Pengurus sebesar <strong>{{ $commissionRate }}%</strong>.
            </div>
        </div>
    </div>
</div>
@endsection
