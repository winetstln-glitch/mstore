@extends('layouts.app')

@section('title', 'Wash Transactions')

@section('content')
<div class="container-fluid wash-transactions-page py-2 py-md-3">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-4">
        <h1 class="h3 mb-0 text-body">Wash Transactions</h1>
        <div class="d-flex flex-wrap gap-2 wash-transactions-toolbar">
            <a href="{{ route('wash.transactions.export.pdf', request()->all()) }}" class="btn btn-sm btn-danger shadow-sm" title="Generate PDF">
                <i class="fas fa-file-pdf fa-sm text-white-50"></i>
                <span class="d-none d-md-inline ms-1">Generate PDF</span>
            </a>
            <a href="{{ route('wash.transactions.export.excel', request()->all()) }}" class="btn btn-sm btn-success shadow-sm" title="Export Excel">
                <i class="fas fa-file-excel fa-sm text-white-50"></i>
                <span class="d-none d-md-inline ms-1">Export Excel</span>
            </a>
        </div>
    </div>

    <div class="card shadow mb-4 wash-panel">
        <div class="card-header py-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0 font-weight-bold text-primary">Transaction List</h6>
            </div>
            <form action="{{ route('wash.transactions.index') }}" method="GET" class="row g-3 align-items-center wash-filter-form">
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
                <div class="col-12 col-md-auto d-flex flex-wrap gap-2 wash-filter-actions">
                    <button type="submit" class="btn btn-primary" title="{{ __('Filter') }}">
                        <i class="fas fa-filter"></i>
                        <span class="d-none d-md-inline ms-1">{{ __('Filter') }}</span>
                    </button>
                    <a href="{{ route('wash.transactions.index') }}" class="btn btn-secondary" title="{{ __('Reset') }}">
                        <i class="fas fa-rotate-left"></i>
                        <span class="d-none d-md-inline ms-1">{{ __('Reset') }}</span>
                    </a>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive table-responsive-mobile">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Transaction #</th>
                            <th>Customer</th>
                            <th>Plate No</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Cashier</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->created_at->format('d M Y H:i') }}</td>
                                <td>{{ $transaction->transaction_number }}</td>
                                <td>{{ $transaction->customer_name ?? '-' }}</td>
                                <td>{{ $transaction->vehicle_plate ?? '-' }}</td>
                                <td>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
                                <td>{{ ucfirst($transaction->payment_method) }}</td>
                                <td>{{ $transaction->user->name ?? 'Unknown' }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1 justify-content-end transaction-actions">
                                        <a href="{{ route('wash.transactions.show', $transaction->id) }}" class="btn btn-sm btn-info" title="{{ __('View') }}">
                                            <i class="fas fa-eye"></i>
                                            <span class="d-none d-md-inline ms-1">{{ __('View') }}</span>
                                        </a>
                                        <a href="{{ route('wash.transactions.receipt', $transaction->id) }}" target="_blank" class="btn btn-sm btn-warning" title="{{ __('Print') }}">
                                            <i class="fas fa-print"></i>
                                            <span class="d-none d-md-inline ms-1">{{ __('Print') }}</span>
                                        </a>
                                        @if(Auth::user()->hasRole('admin'))
                                        <form action="{{ route('wash.transactions.destroy', $transaction) }}" method="POST" class="d-inline" data-confirm="{{ __('Delete this transaction?') }}" onsubmit="return confirm(this.dataset.confirm)">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="{{ __('Delete') }}">
                                                <i class="fas fa-trash"></i>
                                                <span class="d-none d-md-inline ms-1">{{ __('Delete') }}</span>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No transactions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</div>
@push('styles')
<style>
    .wash-transactions-page .wash-panel {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid rgba(59, 130, 246, 0.15);
        border-radius: 1.2rem;
        overflow: hidden;
    }

    .wash-transactions-page .wash-panel .card-header {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.12) 0%, rgba(59, 130, 246, 0.03) 100%);
        border-bottom: 1px solid rgba(59, 130, 246, 0.18);
    }

    .wash-transactions-page .table thead th {
        background: rgba(148, 163, 184, 0.12);
    }

    [data-bs-theme="dark"] .wash-transactions-page .wash-panel {
        background: linear-gradient(180deg, #0f172a 0%, #0b1228 100%);
        border-color: rgba(96, 165, 250, 0.28);
    }

    [data-bs-theme="dark"] .wash-transactions-page .wash-panel .card-header {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.22) 0%, rgba(15, 23, 42, 0.3) 100%);
        border-bottom-color: rgba(96, 165, 250, 0.28);
    }

    [data-bs-theme="dark"] .wash-transactions-page .table thead th {
        background: rgba(51, 65, 85, 0.5);
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] .wash-transactions-page .table tbody td {
        border-color: #334155;
    }

    @media (max-width: 767.98px) {
        .wash-transactions-page {
            padding-left: 0.35rem;
            padding-right: 0.35rem;
        }

        .wash-transactions-page h1.h3 {
            font-size: 1.15rem;
            line-height: 1.3;
        }

        .wash-transactions-page .wash-transactions-toolbar > .btn {
            flex: 1 1 calc(50% - 0.5rem);
            min-height: 42px;
            border-radius: 0.8rem;
        }

        .wash-transactions-page .wash-panel {
            border-radius: 1rem;
        }

        .wash-transactions-page .wash-filter-form .col-12.col-md-auto {
            margin-bottom: 0.1rem;
        }

        .wash-transactions-page .wash-filter-form .col-form-label {
            margin-bottom: 0;
            font-size: 0.85rem;
            color: var(--bs-secondary-color);
        }

        .wash-transactions-page .wash-filter-form .form-control {
            min-height: 42px;
        }

        .wash-transactions-page .wash-filter-actions {
            width: 100%;
        }

        .wash-transactions-page .wash-filter-actions .btn {
            flex: 1 1 0;
            min-height: 42px;
            border-radius: 0.75rem;
        }

        .wash-transactions-page .table-responsive {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.9rem;
            padding: 0.25rem;
        }

        .wash-transactions-page .table-responsive-mobile td {
            align-items: flex-start;
            gap: 0.55rem;
            padding-top: 0.6rem;
            padding-bottom: 0.6rem;
        }

        .wash-transactions-page .table-responsive-mobile td:before {
            font-size: 0.68rem;
            letter-spacing: 0.25px;
        }

        .wash-transactions-page .table-responsive-mobile td[data-label="Actions"] {
            display: block;
            text-align: left;
        }

        .wash-transactions-page .table-responsive-mobile td[data-label="Actions"]::before {
            display: block;
            margin-bottom: 0.45rem;
        }

        .wash-transactions-page .table-responsive-mobile .transaction-actions {
            justify-content: flex-start !important;
            gap: 0.4rem;
        }

        .wash-transactions-page .table-responsive-mobile .transaction-actions .btn,
        .wash-transactions-page .table-responsive-mobile .transaction-actions button {
            min-height: 34px;
            min-width: 34px;
            border-radius: 0.65rem;
            padding: 0.32rem 0.48rem;
        }

        .wash-transactions-page #dataTable {
            min-width: 100%;
        }
    }
</style>
@endpush
@endsection
