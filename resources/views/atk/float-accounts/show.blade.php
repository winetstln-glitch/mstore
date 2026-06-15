@extends('layouts.app')

@section('title', __('Detail Akun Float'))

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Detail Akun Float') }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('atk.float-accounts.transactions.create', $account) }}" class="btn btn-success">
                <i class="fa-solid fa-plus"></i>
                <span class="d-none d-md-inline ms-2">{{ __('Tambah Transaksi') }}</span>
            </a>
            <a href="{{ route('atk.float-accounts.edit', $account) }}" class="btn btn-warning">
                <i class="fa-solid fa-pen"></i>
                <span class="d-none d-md-inline ms-2">{{ __('Edit Akun') }}</span>
            </a>
            <a href="{{ route('atk.float-accounts.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                <span class="d-none d-md-inline ms-2">{{ __('Kembali') }}</span>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Informasi Akun') }}</h6>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>{{ __('Kode Akun') }}</th>
                            <td>{{ $account->code }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Nama Akun') }}</th>
                            <td>{{ $account->name }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Tipe Akun') }}</th>
                            <td>{{ $account->account_type }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Saldo Saat Ini') }}</th>
                            <td><strong>Rp {{ number_format($account->current_balance, 0, ',', '.') }}</strong></td>
                        </tr>
                        <tr>
                            <th>{{ __('Status') }}</th>
                            <td>
                                <span class="badge {{ $account->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $account->status === 'active' ? __('Aktif') : __('Nonaktif') }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('Deskripsi') }}</th>
                            <td>{{ $account->description ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-8 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Riwayat Transaksi') }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>{{ __('Tanggal') }}</th>
                                    <th>{{ __('Tipe') }}</th>
                                    <th>{{ __('Jumlah') }}</th>
                                    <th>{{ __('Saldo Awal') }}</th>
                                    <th>{{ __('Saldo Akhir') }}</th>
                                    <th>{{ __('Deskripsi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->created_at->format('d M Y H:i') }}</td>
                                    <td>
                                        <span class="badge {{ in_array($transaction->transaction_type, ['deposit', 'topup', 'transfer']) ? 'bg-success' : 'bg-danger' }}">
                                            {{ $transaction->transaction_type }}
                                        </span>
                                    </td>
                                    <td>Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($transaction->balance_before, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($transaction->balance_after, 0, ',', '.') }}</td>
                                    <td>{{ $transaction->description ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">{{ __('Belum ada transaksi.') }}</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $transactions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
