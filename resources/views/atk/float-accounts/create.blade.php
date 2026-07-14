@extends('layouts.app')

@section('title', __('Tambah Akun Float'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Tambah Akun Float Baru') }}</h1>
        <a href="{{ route('atk.float-accounts.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            <span class="d-none d-md-inline ms-2">{{ __('Kembali') }}</span>
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('atk.float-accounts.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Kode Akun') }}</label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Nama Akun') }}</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Tipe Akun') }}</label>
                        <select name="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
                            <option value="">{{ __('Pilih Tipe Akun') }}</option>
                            <option value="bank" {{ old('account_type') === 'bank' ? 'selected' : '' }}>{{ __('Bank') }}</option>
                            <option value="e-wallet" {{ old('account_type') === 'e-wallet' ? 'selected' : '' }}>{{ __('E-Wallet') }}</option>
                            <option value="ppob_deposit" {{ old('account_type') === 'ppob_deposit' ? 'selected' : '' }}>{{ __('PPOB Deposit') }}</option>
                        </select>
                        @error('account_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Saldo Awal') }}</label>
                        <input type="number" name="current_balance" class="form-control @error('current_balance') is-invalid @enderror" value="{{ old('current_balance', 0) }}" min="0" required>
                        @error('current_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>{{ __('Aktif') }}</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>{{ __('Nonaktif') }}</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">{{ __('Deskripsi') }}</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">{{ __('Simpan Akun') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
