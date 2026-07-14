@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-10 px-3 px-lg-0">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis fs-6 fs-md-5">{{ __('Detail Perusahaan') }}</h5>
                <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> <span class="d-none d-sm-inline">{{ __('Kembali') }}</span>
                </a>
            </div>

            <div class="card-body p-3 p-md-4">
                <div class="mb-4">
                    <h4 class="fw-bold text-primary">{{ $company->name }}</h4>
                    <div class="text-muted small">
                        {{ $company->code }}
                        @if($company->is_active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle ms-2">{{ __('Aktif') }}</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-2">{{ __('Non-Aktif') }}</span>
                        @endif
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold">{{ __('NPWP') }}</label>
                            <div>{{ $company->tax_id ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold">{{ __('Mata Uang') }}</label>
                            <div>{{ $company->currency ?? 'IDR' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold">{{ __('Negara') }}</label>
                            <div>{{ $company->country ?? 'ID' }}</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold">{{ __('Alamat') }}</label>
                            <div>{{ $company->address ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4 pt-4 border-top">
                    @can('company.manage')
                    <a href="{{ route('companies.edit', $company) }}" class="btn btn-primary">
                        <i class="fa-solid fa-pen-to-square me-1"></i> {{ __('Ubah') }}
                    </a>
                    <form action="{{ route('companies.destroy', $company) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Yakin ingin menghapus perusahaan ini?') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fa-solid fa-trash me-1"></i> {{ __('Hapus') }}
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
