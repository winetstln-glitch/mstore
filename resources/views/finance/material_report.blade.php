 @extends('layouts.app')

@section('title', __('Laporan Pendapatan Material'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Laporan Pendapatan Material') }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('finance.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to Finance') }}
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fa-solid fa-print me-1"></i> {{ __('Print Report') }}
            </button>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">{{ __('Filter Data') }}</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('finance.material_report') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">{{ __('Tanggal Awal') }}</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">{{ __('Tanggal Akhir') }}</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                </div>

                <div class="col-md-3">
                    <label for="coordinator_id" class="form-label">{{ __('Pengurus') }}</label>
                    <select class="form-select" id="coordinator_id" name="coordinator_id">
                        <option value="">{{ __('Semua Pengurus') }}</option>
                        @foreach($coordinators as $coord)
                            <option value="{{ $coord->id }}" {{ $coordinatorId == $coord->id ? 'selected' : '' }}>
                                {{ $coord->name }} ({{ $coord->region->name ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 text-end">
                    <a href="{{ route('finance.material_report') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                {{ __('Total Item Keluar') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalQuantity, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-boxes-stacked fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                {{ __('Total Pendapatan Material (Net)') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($netTotal, 0, ',', '.') }}</div>
                            <small class="text-muted">Gross: {{ number_format($totalValue, 0, ',', '.') }} | Komisi: -{{ number_format($commissionAmount, 0, ',', '.') }}</small>
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">{{ __('Rincian Transaksi Material') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>{{ __('Tanggal') }}</th>
                            <th>{{ __('Nama Barang') }}</th>
                            <th>{{ __('Pengurus') }}</th>
                            <th>{{ __('Wilayah') }}</th>
                            <th class="text-end">{{ __('Jumlah') }}</th>
                            <th class="text-end">{{ __('Harga Satuan') }}</th>
                            <th class="text-end">{{ __('Total Harga') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $t)
                            @php
                                $price = $t->item->price ?? 0;
                                $total = $t->quantity * $price;
                            @endphp
                            <tr>
                                <td>{{ $t->created_at->format('d/m/Y') }}</td>
                                <td>
                                    {{ $t->item->name ?? '-' }}
                                    <br>
                                    <small class="text-muted">{{ $t->item->brand ?? '' }} {{ $t->item->model ?? '' }}</small>
                                </td>
                                <td>{{ $t->coordinator->name ?? '-' }}</td>
                                <td>{{ $t->coordinator->region->name ?? '-' }}</td>
                                <td class="text-end">{{ number_format($t->quantity, 0, ',', '.') }} {{ $t->item->unit ?? '' }}</td>
                                <td class="text-end">{{ number_format($price, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold">{{ number_format($total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">{{ __('Tidak ada data transaksi material pada periode ini.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold bg-light">
                            <td colspan="4" class="text-end">{{ __('TOTAL GROSS') }}</td>
                            <td class="text-end">{{ number_format($totalQuantity, 0, ',', '.') }}</td>
                            <td></td>
                            <td class="text-end">{{ number_format($totalValue, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td colspan="6" class="text-end text-danger">{{ __('Komisi Pengurus (' . $commissionRate . '%)') }}</td>
                            <td class="text-end text-danger">-{{ number_format($commissionAmount, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="fw-bold table-primary">
                            <td colspan="6" class="text-end">{{ __('NET TOTAL') }}</td>
                            <td class="text-end">{{ number_format($netTotal, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
