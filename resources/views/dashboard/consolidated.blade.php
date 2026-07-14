@extends('layouts.app')

@section('title', 'Dashboard Konsolidasi')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard Konsolidasi</h1>
        <form action="{{ route('dashboard.consolidated.generate') }}" method="POST" class="d-flex gap-2">
            @csrf
            <select name="type" class="form-control" required>
                <option value="daily">Generate Daily Summary</option>
                <option value="monthly">Generate Monthly Summary</option>
                <option value="yearly">Generate Yearly Summary</option>
            </select>
            <button type="submit" class="btn btn-primary">Generate</button>
        </form>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('dashboard.consolidated') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="business_unit_id" class="form-label">Business Unit</label>
                    <select name="business_unit_id" id="business_unit_id" class="form-control">
                        <option value="">All</option>
                        @foreach($businessUnits as $bu)
                            <option value="{{ $bu->id }}" {{ (request('business_unit_id') == $bu->id) ? 'selected' : '' }}>
                                {{ $bu->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="year" class="form-label">Year</label>
                    <select name="year" id="year" class="form-control">
                        @for($y = now()->year - 5; $y <= now()->year; $y++)
                            <option value="{{ $y }}" {{ (request('year') ?? now()->year) == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="month" class="form-label">Month</label>
                    <select name="month" id="month" class="form-control">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ (request('month') ?? now()->month) == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->formatLocalized('%B') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" name="date" id="date" class="form-control"
                           value="{{ request('date') ?? now()->toDateString() }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Pendapatan (Hari Ini)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                Rp {{ number_format($dailySummary?->total_income ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Laba (Hari Ini)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                Rp {{ number_format($dailySummary?->total_profit ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Transaksi (Hari Ini)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $dailySummary?->total_transactions ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Pendapatan (Bulanan)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                Rp {{ number_format($monthlySummary?->total_income ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Laba (Bulanan)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                Rp {{ number_format($monthlySummary?->total_profit ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Transaksi (Bulanan)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $monthlySummary?->total_transactions ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Trend Pendapatan & Pengeluaran Tahun {{ $selectedYear }}</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('trendChart').getContext('2d');
    const chartData = @json($chartData);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Pendapatan',
                    data: chartData.income,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'Pengeluaran',
                    data: chartData.expense,
                    borderColor: '#e74a3b',
                    backgroundColor: 'rgba(231, 74, 59, 0.1)',
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'Laba',
                    data: chartData.profit,
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    fill: true,
                    tension: 0.4,
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush