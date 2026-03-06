@extends('layouts.app')

@section('title', __('Transaction History'))

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Transaction History') }}</h1>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('atk.transactions.export.pdf', request()->all()) }}" class="btn btn-danger" title="{{ __('Export PDF') }}">
                <i class="fa-solid fa-file-pdf"></i>
                <span class="d-none d-md-inline ms-2">{{ __('Export PDF') }}</span>
            </a>
            <a href="{{ route('atk.transactions.export.excel', request()->all()) }}" class="btn btn-success" title="{{ __('Export Excel') }}">
                <i class="fa-solid fa-file-excel"></i>
                <span class="d-none d-md-inline ms-2">{{ __('Export Excel') }}</span>
            </a>
            <a href="{{ route('atk.pos') }}" class="btn btn-primary" title="{{ __('New Transaction') }}">
                <i class="fa-solid fa-cash-register"></i>
                <span class="d-none d-md-inline ms-2">{{ __('New Transaction') }}</span>
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <form action="{{ route('atk.transactions.index') }}" method="GET" class="row g-3 align-items-center">
                <div class="col-12 col-md-auto">
                    <label for="start_date" class="col-form-label">{{ __('Start Date') }}</label>
                </div>
                <div class="col-12 col-md-auto">
                    <input type="date" id="start_date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-12 col-md-auto">
                    <label for="end_date" class="col-form-label">{{ __('End Date') }}</label>
                </div>
                <div class="col-12 col-md-auto">
                    <input type="date" id="end_date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-12 col-md-auto">
                    <label for="category" class="col-form-label">{{ __('Category') }}</label>
                </div>
                <div class="col-12 col-md-auto">
                    @php($opts = isset($categories) ? $categories : ['ATK','JASA POTOCOPY','JASA TRANSFER BANK'])
                    <select id="category" name="category" class="form-select">
                        <option value="">{{ __('All') }}</option>
                        @foreach($opts as $opt)
                            <option value="{{ $opt }}" {{ request('category')===$opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary" title="{{ __('Filter') }}">
                        <i class="fa-solid fa-filter"></i>
                        <span class="d-none d-md-inline ms-1">{{ __('Filter') }}</span>
                    </button>
                    <a href="{{ route('atk.transactions.index') }}" class="btn btn-secondary" title="{{ __('Reset') }}">
                        <i class="fa-solid fa-rotate-left"></i>
                        <span class="d-none d-md-inline ms-1">{{ __('Reset') }}</span>
                    </a>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <span class="badge bg-success">
                    {{ __('Total Pendapatan') }}: Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}
                </span>
            </div>
            <div class="table-responsive table-responsive-mobile">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Transaction No') }}</th>
                            <th>{{ __('Cashier') }}</th>
                            <th>{{ __('Total') }}</th>
                            <th>{{ __('Method') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $transaction->transaction_number }}</td>
                            <td>{{ $transaction->user->name ?? '-' }}</td>
                            <td>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
                            <td><span class="badge bg-secondary">{{ strtoupper($transaction->payment_method) }}</span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1 justify-content-end">
                                    <a href="{{ route('atk.transactions.show', $transaction) }}" class="btn btn-sm btn-info" title="{{ __('View') }}">
                                        <i class="fa-solid fa-eye"></i>
                                        <span class="d-none d-md-inline ms-1">{{ __('View') }}</span>
                                    </a>
                                    <a href="{{ route('atk.transactions.receipt', $transaction) }}" target="_blank" class="btn btn-sm btn-warning" title="{{ __('Print') }}">
                                        <i class="fa-solid fa-print"></i>
                                        <span class="d-none d-md-inline ms-1">{{ __('Print') }}</span>
                                    </a>
                                    @if(Auth::user()->hasRole('admin'))
                                    <form action="{{ route('atk.transactions.destroy', $transaction) }}" method="POST" class="d-inline" data-confirm="{{ __('Delete this transaction?') }}" onsubmit="return confirm(this.dataset.confirm)">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="{{ __('Delete') }}">
                                            <i class="fa-solid fa-trash"></i>
                                            <span class="d-none d-md-inline ms-1">{{ __('Delete') }}</span>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
