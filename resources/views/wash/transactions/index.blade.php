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
                <div class="col-12 col-md-auto">
                    <label for="per_page" class="col-form-label">Per Page</label>
                </div>
                <div class="col-12 col-md-auto">
                    <select id="per_page" name="per_page" class="form-select">
                        <option value="10" {{ request('per_page', '10') === '10' ? 'selected' : '' }}>10</option>
                        <option value="20" {{ request('per_page') === '20' ? 'selected' : '' }}>20</option>
                        <option value="all" {{ request('per_page') === 'all' ? 'selected' : '' }}>All</option>
                    </select>
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
                                        </a>
                                        <a href="{{ route('wash.transactions.receipt', $transaction->id) }}" target="_blank" class="btn btn-sm btn-warning" title="{{ __('Print') }}">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        @if(Auth::user()->hasRole('admin'))
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-primary"
                                            title="{{ __('Edit') }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editTransactionModal"
                                            data-update-url="{{ route('wash.transactions.update', $transaction) }}"
                                            data-transaction-number="{{ $transaction->transaction_number }}"
                                            data-customer-name="{{ $transaction->customer_name ?? '' }}"
                                            data-vehicle-plate="{{ $transaction->vehicle_plate ?? '' }}"
                                            data-vehicle-brand="{{ $transaction->vehicle_brand ?? '' }}"
                                            data-payment-method="{{ $transaction->payment_method ?? 'cash' }}"
                                            data-cash-amount="{{ (int) ($transaction->cash_amount ?? 0) }}"
                                        >
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <form action="{{ route('wash.transactions.destroy', $transaction) }}" method="POST" class="d-inline" data-confirm="{{ __('Delete this transaction?') }}" onsubmit="return confirm(this.dataset.confirm)">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="{{ __('Delete') }}">
                                                <i class="fas fa-trash"></i>
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
                {{ $transactions->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@if(Auth::user()->hasRole('admin'))
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editTransactionForm" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editTransactionTitle">Edit Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="edit_customer_name">Nama Customer</label>
                        <input type="text" class="form-control" id="edit_customer_name" name="customer_name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit_vehicle_plate">Plat Nomor</label>
                        <input type="text" class="form-control text-uppercase" id="edit_vehicle_plate" name="vehicle_plate">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit_vehicle_brand">Merek Kendaraan</label>
                        <input type="text" class="form-control" id="edit_vehicle_brand" name="vehicle_brand">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit_payment_method">Metode Pembayaran</label>
                        <select class="form-select js-payment-method" id="edit_payment_method" name="payment_method" data-cash-target="#edit_cash_amount_group">
                            <option value="cash">Cash</option>
                            <option value="qris">QRIS</option>
                        </select>
                    </div>
                    <div class="mb-0" id="edit_cash_amount_group">
                        <label class="form-label" for="edit_cash_amount">Nominal Cash</label>
                        <input type="number" min="0" class="form-control" id="edit_cash_amount" name="cash_amount">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
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

    .wash-transactions-page .transaction-actions .btn,
    .wash-transactions-page .transaction-actions button {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.55rem;
        padding: 0;
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

    [data-bs-theme="dark"] #editTransactionModal .modal-content {
        background-color: #0f172a;
        color: #e2e8f0;
        border-color: #334155;
    }

    [data-bs-theme="dark"] #editTransactionModal .modal-header,
    [data-bs-theme="dark"] #editTransactionModal .modal-footer {
        border-color: #334155;
    }

    [data-bs-theme="dark"] #editTransactionModal .form-control,
    [data-bs-theme="dark"] #editTransactionModal .form-select {
        background-color: #0b1228;
        color: #e2e8f0;
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

@push('scripts')
<script>
    const editModal = document.getElementById('editTransactionModal');
    const editForm = document.getElementById('editTransactionForm');
    const editTitle = document.getElementById('editTransactionTitle');
    const customerInput = document.getElementById('edit_customer_name');
    const plateInput = document.getElementById('edit_vehicle_plate');
    const brandInput = document.getElementById('edit_vehicle_brand');
    const paymentSelect = document.getElementById('edit_payment_method');
    const cashGroup = document.getElementById('edit_cash_amount_group');
    const cashInput = document.getElementById('edit_cash_amount');
    const updateCashVisibility = () => {
        if (!paymentSelect || !cashGroup) {
            return;
        }
        cashGroup.style.display = paymentSelect.value === 'cash' ? '' : 'none';
    };
    if (paymentSelect) {
        paymentSelect.addEventListener('change', updateCashVisibility);
    }
    if (editModal) {
        editModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!trigger || !editForm) {
                return;
            }
            editForm.action = trigger.getAttribute('data-update-url') || '';
            editTitle.textContent = `Edit Transaksi #${trigger.getAttribute('data-transaction-number') || ''}`;
            customerInput.value = trigger.getAttribute('data-customer-name') || '';
            plateInput.value = trigger.getAttribute('data-vehicle-plate') || '';
            brandInput.value = trigger.getAttribute('data-vehicle-brand') || '';
            paymentSelect.value = (trigger.getAttribute('data-payment-method') || 'cash').toLowerCase();
            cashInput.value = trigger.getAttribute('data-cash-amount') || '0';
            updateCashVisibility();
        });
    }
</script>
@endpush
@endsection
