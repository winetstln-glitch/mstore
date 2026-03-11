@extends('layouts.app')

@section('title', 'Investor')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-stretch align-items-lg-center mb-4 gap-2 flex-wrap">
        <h2 class="mb-0">Investor</h2>
        <form method="GET" action="{{ route('investors.index') }}" class="d-flex align-items-center w-100 w-lg-auto">
            <input type="month" name="month" class="form-control w-100" value="{{ request('month') }}" onchange="this.form.submit()">
        </form>
        <div class="toolbar-scroll d-flex align-items-center gap-2 w-100 w-lg-auto justify-content-start justify-content-lg-end">
            
            <a href="{{ route('investors.export.excel', ['month' => request('month')]) }}" class="btn btn-success" data-bs-toggle="tooltip" title="{{ __('Export Excel') }}">
                <i class="fa-solid fa-file-excel"></i> <span class="d-none d-sm-inline ms-1">{{ __('Export Excel') }}</span>
            </a>
            <a href="{{ route('investors.export.pdf', ['month' => request('month')]) }}" class="btn btn-danger" data-bs-toggle="tooltip" title="{{ __('Export PDF') }}">
                <i class="fa-solid fa-file-pdf"></i> <span class="d-none d-sm-inline ms-1">{{ __('Export PDF') }}</span>
            </a>
            <a href="{{ route('investors.create') }}" class="btn btn-primary" data-bs-toggle="tooltip" title="Tambah Investor">
                <i class="fa-solid fa-user-plus"></i> <span class="d-none d-sm-inline ms-1">Tambah Investor</span>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Coordinator') }}</th>
                            <th>{{ __('Account') }}</th>
                            <th>Total Investasi</th>
                            <th>{{ __('Balance') }}</th>
                            <th class="text-nowrap">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($investors as $investor)
                            <tr>
                                <td>{{ $investor->name }}</td>
                                <td>{{ $investor->coordinator->name }}</td>
                                <td>
                                    @if($investor->user)
                                        {{ $investor->user->name }} @if($investor->user->username) ({{ $investor->user->username }}) @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>Rp {{ number_format($investor->income_transactions_sum_amount ?? 0, 0, ',', '.') }}</td>
                                <td class="{{ ($investor->income_transactions_sum_amount - $investor->expense_transactions_sum_amount) >= 0 ? 'text-success' : 'text-danger' }}">
                                    Rp {{ number_format(($investor->income_transactions_sum_amount ?? 0) - ($investor->expense_transactions_sum_amount ?? 0), 0, ',', '.') }}
                                </td>
                                <td>
                                    <a href="{{ route('investors.show', $investor) }}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Detail">
                                        <i class="fa-solid fa-eye"></i> <span class="d-none d-sm-inline ms-1">Detail</span>
                                    </a>
                                    <a href="{{ route('investors.edit', $investor) }}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                        <i class="fa-solid fa-pen"></i> <span class="d-none d-sm-inline ms-1">{{ __('Edit') }}</span>
                                    </a>
                                    <form action="{{ route('investors.destroy', $investor) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('Are you sure?') }}')" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                            <i class="fa-solid fa-trash"></i> <span class="d-none d-sm-inline ms-1">{{ __('Delete') }}</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada investor ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $investors->links() }}
        </div>
    </div>
</div>
@endsection
