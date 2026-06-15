@extends('layouts.app')

@section('title', __('Tambah Transaksi Float'))

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Tambah Transaksi Float') }}</h1>
        <div>
            <a href="{{ route('atk.float-accounts.show', $account) }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                <span class="d-none d-md-inline ms-2">{{ __('Kembali ke Akun') }}</span>
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="mb-4">
                <h5>{{ __('Akun:') }} {{ $account->name }} ({{ $account->code }})</h5>
                <p><strong>{{ __('Saldo Saat Ini:') }}</strong> Rp {{ number_format($account->current_balance, 0, ',', '.') }}</p>
            </div>
            <form action="{{ route('atk.float-accounts.transactions.store', $account) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Tipe Transaksi') }}</label>
                        <select name="transaction_type" class="form-select @error('transaction_type') is-invalid @enderror" required>
                            <option value="">{{ __('Pilih Tipe Transaksi') }}</option>
                            <option value="deposit" {{ old('transaction_type') === 'deposit' ? 'selected' : '' }}>{{ __('Deposit') }}</option>
                            <option value="withdrawal" {{ old('transaction_type') === 'withdrawal' ? 'selected' : '' }}>{{ __('Withdrawal') }}</option>
                            <option value="transfer" {{ old('transaction_type') === 'transfer' ? 'selected' : '' }}>{{ __('Transfer') }}</option>
                            <option value="topup" {{ old('transaction_type') === 'topup' ? 'selected' : '' }}>{{ __('Topup') }}</option>
                            <option value="ppob" {{ old('transaction_type') === 'ppob' ? 'selected' : '' }}>{{ __('PPOB') }}</option>
                            <option value="adjustment" {{ old('transaction_type') === 'adjustment' ? 'selected' : '' }}>{{ __('Adjustment') }}</option>
                        </select>
                        @error('transaction_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Jumlah') }}</label>
                        <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" min="0" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">{{ __('Deskripsi') }}</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">{{ __('Simpan Transaksi') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
