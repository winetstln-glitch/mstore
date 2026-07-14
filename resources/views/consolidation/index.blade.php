@extends('layouts.app')

@section('title', __('Konsolidasi Laporan'))

@section('content')
<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h4 class="fw-bold text-primary mb-1">{{ __('Konsolidasi Laporan Keuangan') }}</h4>
            <p class="text-muted small mb-0">{{ __('Lihat laporan konsolidasi dari semua perusahaan aktif.') }}</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('consolidation.index') }}" class="row g-2 g-md-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label small text-muted fw-bold">{{ __('Tanggal Mulai') }}</label>
                        <input type="date" name="start_date" value="{{ $startDate }}" class="form-control">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small text-muted fw-bold">{{ __('Tanggal Akhir') }}</label>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="form-control">
                    </div>
                    <div class="col-12 col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa-solid fa-filter me-1"></i> {{ __('Tampilkan Laporan') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="fw-bold text-body-secondary text-uppercase small mb-3">{{ __('Ringkasan Konsolidasi') }}</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">{{ __('Perusahaan') }}</th>
                                <th>{{ __('Jumlah Transaksi') }}</th>
                                <th>{{ __('Total Debit') }}</th>
                                <th>{{ __('Total Kredit') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($companies as $company)
                                @php
                                    $transactions = $consolidatedData[$company->id] ?? ['transactions' => 0, 'debit' => 0, 'credit' => 0];
                                @endphp
                                <tr>
                                    <td class="ps-3">{{ $company->name }}</td>
                                    <td>{{ $transactions['transactions'] }}</td>
                                    <td>{{ number_format($transactions['debit'], 0, ',', '.') }}</td>
                                    <td>{{ number_format($transactions['credit'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-body-secondary">
                                        <div class="mb-2"><i class="fa-solid fa-chart-pie fa-2x opacity-25"></i></div>
                                        {{ __('Belum ada data.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
