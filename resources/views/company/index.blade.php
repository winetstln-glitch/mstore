@extends('layouts.app')

@section('title', __('Manajemen Perusahaan'))

@section('content')
<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h4 class="fw-bold text-primary mb-1">{{ __('Manajemen Perusahaan') }}</h4>
            <p class="text-muted small mb-0">{{ __('Kelola data perusahaan dan cabang.') }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-md-end align-items-center mobile-btns">
            @can('company.manage')
            <a href="{{ route('companies.create') }}" class="btn btn-primary flex-grow-0">
                <i class="fa-solid fa-plus me-1"></i> <span class="d-none d-sm-inline">{{ __('Tambah Perusahaan') }}</span>
            </a>
            @endcan
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-responsive-mobile">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">{{ __('Nama') }}</th>
                                <th>{{ __('Kode') }}</th>
                                <th>{{ __('NPWP') }}</th>
                                <th>{{ __('Mata Uang') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end pe-3">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($companies as $company)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold">{{ $company->name }}</div>
                                        <div class="small text-muted">{{ Str::limit($company->address, 50) }}</div>
                                    </td>
                                    <td>{{ $company->code }}</td>
                                    <td>{{ $company->tax_id ?? '-' }}</td>
                                    <td>{{ $company->currency ?? 'IDR' }}</td>
                                    <td>
                                        @if($company->is_active)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Aktif') }}</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ __('Non-Aktif') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end gap-1">
                                            @can('company.view')
                                            <a href="{{ route('companies.show', $company) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Lihat') }}">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            @endcan
                                            @can('company.manage')
                                            <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Ubah') }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <form action="{{ route('companies.destroy', $company) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Yakin ingin menghapus perusahaan ini?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Hapus') }}">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-body-secondary">
                                        <div class="mb-2"><i class="fa-solid fa-building fa-2x opacity-25"></i></div>
                                        {{ __('Belum ada perusahaan.') }}
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
