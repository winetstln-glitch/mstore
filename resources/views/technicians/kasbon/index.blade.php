@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-warning">
            <div class="card-header py-3">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-coins me-2 text-warning"></i>{{ __('Rincian Kasbon Teknisi') }}</h5>
                    </div>
                    
                    <form action="{{ route('technicians.kasbon.index') }}" method="GET" class="w-100 border-top pt-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">{{ __('Filter Teknisi') }}</label>
                                <select name="user_id" class="form-select form-select-sm js-search-select">
                                    <option value="">{{ __('Semua Teknisi') }}</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ $user->role->name ?? __('User') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small fw-bold text-muted mb-1">{{ __('Status') }}</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">{{ __('Semua') }}</option>
                                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Belum Diproses') }}</option>
                                    <option value="processed" {{ request('status') === 'processed' ? 'selected' : '' }}>{{ __('Sudah Diproses') }}</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small fw-bold text-muted mb-1">{{ __('Tanggal Mulai') }}</label>
                                <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small fw-bold text-muted mb-1">{{ __('Tanggal Selesai') }}</label>
                                <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-md-auto d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm px-3">
                                    <i class="fa-solid fa-filter me-1"></i>{{ __('Filter') }}
                                </button>
                                <a href="{{ route('technicians.kasbon.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-responsive-mobile">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('No') }}</th>
                                <th>{{ __('Teknisi') }}</th>
                                <th>{{ __('Tanggal') }}</th>
                                <th>{{ __('Jumlah') }}</th>
                                <th>{{ __('Keterangan') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end pe-3">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($adjustments as $index => $adjustment)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-medium">{{ $adjustment->user->name }}</div>
                                    </td>
                                    <td class="small text-muted">
                                        {{ $adjustment->date->translatedFormat('d M Y') }}
                                    </td>
                                    <td class="fw-bold text-danger">
                                        Rp {{ number_format($adjustment->amount, 0, ',', '.') }}
                                    </td>
                                    <td>{{ $adjustment->description ?? '-' }}</td>
                                    <td>
                                        @if($adjustment->status === 'pending')
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ __('Belum Diproses') }}</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">{{ __('Sudah Diproses') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
                                            @if($adjustment->status !== 'processed')
                                                <form action="{{ route('salary-adjustments.destroy', $adjustment) }}" method="POST" class="d-inline" data-confirm="{{ __('Hapus kasbon ini?') }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fa-solid fa-inbox text-muted fa-3x mb-2"></i>
                                        <div class="text-muted">{{ __('Tidak ada data kasbon') }}</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $adjustments->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
