@extends('layouts.app')

@section('title', __('Edit Akun Float'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Edit Akun Float') }}</h1>
        <a href="{{ route('atk.float-accounts.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            <span class="d-none d-md-inline ms-2">{{ __('Kembali') }}</span>
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('atk.float-accounts.update', $account) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Kode Akun') }}</label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $account->code) }}" required>
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Nama Akun') }}</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $account->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Tipe Akun') }}</label>
                        <select name="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
                            <option value="">{{ __('Pilih Tipe Akun') }}</option>
                            <option value="bank" {{ old('account_type', $account->account_type) === 'bank' ? 'selected' : '' }}>{{ __('Bank') }}</option>
                            <option value="e-wallet" {{ old('account_type', $account->account_type) === 'e-wallet' ? 'selected' : '' }}>{{ __('E-Wallet') }}</option>
                            <option value="ppob_deposit" {{ old('account_type', $account->account_type) === 'ppob_deposit' ? 'selected' : '' }}>{{ __('PPOB Deposit') }}</option>
                        </select>
                        @error('account_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" {{ old('status', $account->status) === 'active' ? 'selected' : '' }}>{{ __('Aktif') }}</option>
                            <option value="inactive" {{ old('status', $account->status) === 'inactive' ? 'selected' : '' }}>{{ __('Nonaktif') }}</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">{{ __('Deskripsi') }}</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $account->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">{{ __('Update Akun') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
