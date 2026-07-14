@extends('layouts.app')

@section('title', __('Detail Dana Talangan'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Detail Dana Talangan') }}</h1>
        <a href="{{ route('atk.owner-funds.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            <span class="d-none d-md-inline ms-2">{{ __('Kembali') }}</span>
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <table class="table">
                <tr>
                    <th>{{ __('Kode Transaksi') }}</th>
                    <td>{{ $fund->transaction_code }}</td>
                </tr>
                <tr>
                    <th>{{ __('Tanggal') }}</th>
                    <td>{{ optional($fund->transaction_date)->format('d M Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Tipe') }}</th>
                    <td>
                        <span class="badge {{ $fund->type === 'loan' ? 'bg-success' : 'bg-danger' }}">
                            {{ $fund->type === 'loan' ? __('Pinjaman') : __('Pengembalian') }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('Jumlah') }}</th>
                    <td>Rp {{ number_format($fund->amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <th>{{ __('Saldo Setelah Transaksi') }}</th>
                    <td>Rp {{ number_format($fund->balance, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <th>{{ __('Deskripsi') }}</th>
                    <td>{{ $fund->description ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Dibuat Oleh') }}</th>
                    <td>{{ $fund->creator->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Disetujui Oleh') }}</th>
                    <td>{{ $fund->approver->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Status') }}</th>
                    <td>
                        <span class="badge {{ $fund->status === 'approved' ? 'bg-success' : 'bg-secondary' }}">
                            {{ ucfirst($fund->status) }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
@endsection
