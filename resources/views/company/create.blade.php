@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-10 px-3 px-lg-0">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis fs-6 fs-md-5">{{ __('Tambah Perusahaan') }}</h5>
                <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> <span class="d-none d-sm-inline">{{ __('Kembali') }}</span>
                </a>
            </div>

            <div class="card-body p-3 p-md-4">
                <form method="POST" action="{{ route('companies.store') }}">
                    @csrf

                    <h6 class="fw-bold text-body-secondary text-uppercase small mb-3">{{ __('Informasi Perusahaan') }}</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="name" class="form-label small text-muted fw-bold">{{ __('Nama Perusahaan') }}</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required class="form-control @error('name') is-invalid @enderror" placeholder="{{ __('Masukkan nama perusahaan') }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="code" class="form-label small text-muted fw-bold">{{ __('Kode Perusahaan') }}</label>
                            <input type="text" name="code" id="code" value="{{ old('code') }}" required class="form-control @error('code') is-invalid @enderror" placeholder="{{ __('Masukkan kode perusahaan') }}">
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="tax_id" class="form-label small text-muted fw-bold">{{ __('NPWP') }}</label>
                            <input type="text" name="tax_id" id="tax_id" value="{{ old('tax_id') }}" class="form-control @error('tax_id') is-invalid @enderror" placeholder="{{ __('Masukkan NPWP') }}">
                            @error('tax_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="currency" class="form-label small text-muted fw-bold">{{ __('Mata Uang') }}</label>
                            <select name="currency" id="currency" class="form-select @error('currency') is-invalid @enderror">
                                <option value="IDR" {{ old('currency', 'IDR') == 'IDR' ? 'selected' : '' }}>IDR</option>
                                <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="SGD" {{ old('currency') == 'SGD' ? 'selected' : '' }}>SGD</option>
                            </select>
                            @error('currency')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="country" class="form-label small text-muted fw-bold">{{ __('Negara') }}</label>
                            <select name="country" id="country" class="form-select @error('country') is-invalid @enderror">
                                <option value="ID" {{ old('country', 'ID') == 'ID' ? 'selected' : '' }}>Indonesia</option>
                                <option value="US" {{ old('country') == 'US' ? 'selected' : '' }}>United States</option>
                                <option value="SG" {{ old('country') == 'SG' ? 'selected' : '' }}>Singapore</option>
                            </select>
                            @error('country')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="address" class="form-label small text-muted fw-bold">{{ __('Alamat') }}</label>
                            <textarea name="address" id="address" rows="3" class="form-control @error('address') is-invalid @enderror" placeholder="{{ __('Masukkan alamat lengkap') }}">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex flex-column-reverse flex-md-row justify-content-end gap-2 border-top pt-4 mobile-sticky-footer">
                        <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary w-100 w-md-auto">{{ __('Batal') }}</a>
                        <button type="submit" class="btn btn-primary w-100 w-md-auto px-4">{{ __('Simpan Perusahaan') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
