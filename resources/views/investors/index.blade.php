@extends('layouts.app')

@section('title', __('Investors'))

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>{{ __('Investors') }}</h2>
        <div class="toolbar-scroll">
            <form method="GET" action="{{ route('investors.index') }}" class="d-flex align-items-center">
                <input type="month" name="month" class="form-control me-2" value="{{ request('month') }}" onchange="this.form.submit()">
            </form>
            <a href="{{ route('investors.export.excel', ['month' => request('month')]) }}" class="btn btn-success" data-bs-toggle="tooltip" title="{{ __('Export Excel') }}">
                <i class="fa-solid fa-file-excel"></i> <span class="d-none d-sm-inline ms-1">{{ __('Export Excel') }}</span>
            </a>
            <a href="{{ route('investors.export.pdf', ['month' => request('month')]) }}" class="btn btn-danger" data-bs-toggle="tooltip" title="{{ __('Export PDF') }}">
                <i class="fa-solid fa-file-pdf"></i> <span class="d-none d-sm-inline ms-1">{{ __('Export PDF') }}</span>
            </a>
            <a href="{{ route('investors.create') }}" class="btn btn-primary" data-bs-toggle="tooltip" title="{{ __('Add Investor') }}">
                <i class="fa-solid fa-user-plus"></i> <span class="d-none d-sm-inline ms-1">{{ __('Add Investor') }}</span>
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
                            <th>{{ __('Total Investment') }}</th>
                            <th>{{ __('Balance') }}</th>
                            <th class="text-nowrap">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($investors as $investor)
                            <tr>
                                <td>{{ $investor->name }}</td>
                                <td>{{ $investor->coordinator->name }}</td>
                                <td>Rp {{ number_format($investor->income_transactions_sum_amount ?? 0, 0, ',', '.') }}</td>
                                <td class="{{ ($investor->income_transactions_sum_amount - $investor->expense_transactions_sum_amount) >= 0 ? 'text-success' : 'text-danger' }}">
                                    Rp {{ number_format(($investor->income_transactions_sum_amount ?? 0) - ($investor->expense_transactions_sum_amount ?? 0), 0, ',', '.') }}
                                </td>
                                <td>
                                    <a href="{{ route('investors.show', $investor) }}" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="{{ __('Details') }}">
                                        <i class="fa-solid fa-eye"></i> <span class="d-none d-sm-inline ms-1">{{ __('Details') }}</span>
                                    </a>
                                    <a href="{{ route('investors.edit', $investor) }}" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                        <i class="fa-solid fa-pen"></i> <span class="d-none d-sm-inline ms-1">{{ __('Edit') }}</span>
                                    </a>
                                    <form action="{{ route('investors.destroy', $investor) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('Are you sure?') }}')" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                            <i class="fa-solid fa-trash"></i> <span class="d-none d-sm-inline ms-1">{{ __('Delete') }}</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">{{ __('No investors found.') }}</td>
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
