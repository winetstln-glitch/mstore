@extends('layouts.app')

@section('title', __('Tambah Transaksi Dana Talangan'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Tambah Transaksi Dana Talangan') }}</h1>
        <a href="{{ route('atk.owner-funds.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            <span class="d-none d-md-inline ms-2">{{ __('Kembali') }}</span>
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('atk.owner-funds.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Tanggal Transaksi') }}</label>
                        <input type="date" name="transaction_date" class="form-control @error('transaction_date') is-invalid @enderror" value="{{ old('transaction_date', now()->format('Y-m-d')) }}" required>
                        @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Tipe Transaksi') }}</label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="">{{ __('Pilih Tipe') }}</option>
                            <option value="loan" {{ old('type') === 'loan' ? 'selected' : '' }}>{{ __('Pinjaman') }}</option>
                            <option value="repayment" {{ old('type') === 'repayment' ? 'selected' : '' }}>{{ __('Pengembalian') }}</option>
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
