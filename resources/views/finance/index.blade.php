@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Finance Dashboard') }}</h1>
        <div>
            @if(Auth::user()->hasRole('admin'))
            <a href="{{ route('finance.profit_loss') }}" class="btn btn-info me-2">
                <i class="fa-solid fa-file-invoice-dollar me-1"></i> {{ __('Profit & Loss Report') }}
            </a>
            @endif
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <i class="fa-solid fa-plus me-1"></i> {{ __('Add Transaction') }}
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Earnings (Monthly) Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                {{ __('Total Income') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalIncome, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings (Monthly) Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                {{ __('Total Expenses') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalExpense, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-file-invoice-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings (Monthly) Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                {{ __('Company Balance') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($balance, 0, ',', '.') }}</div>
                            <small class="text-muted" title="{{ __('Gross Share') }} - {{ __('General Expenses') }}">
                                {{ number_format($totalCompanyGrossShare, 0, ',', '.') }} - {{ number_format($totalGeneralExpenses, 0, ',', '.') }}
                            </small>
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Investor Funds Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                {{ __('Investor Funds') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalInvestorFunds ?? 0, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-briefcase fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fund Status Cards -->
    <div class="row mb-4">
        <!-- ISP Fund Card -->
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                {{ __('ISP Share Fund') }} (25%)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalIspShare, 0, ',', '.') }}</div>
                            <small class="text-muted">{{ __('Total Accumulated Allocation') }}</small>
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-server fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tool Fund Card -->
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                {{ __('Tool Fund') }} (15%)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalToolFund, 0, ',', '.') }}</div>
                            <small class="text-muted">{{ __('Total Accumulated Allocation') }}</small>
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-toolbox fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">{{ __('Coordinator Balance Sheet') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>{{ __('Coordinator') }}</th>
                            <th>{{ __('Total Revenue') }}</th>
                            <th>{{ __('Commission') }} (15%)</th>
                            <th>{{ __('ISP Share') }} (25%)</th>
                            <th>{{ __('Tool Fund') }} (15%)</th>
                            <th>{{ __('Expenses') }}</th>
                            <th>{{ __('Investor Share') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($coordinatorSummaries as $summary)
                        <tr>
                            <td>{{ $summary->name }}</td>
                            <td class="text-end">{{ number_format($summary->gross_revenue, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">-{{ number_format($summary->commission, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">-{{ number_format($summary->isp_share, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">-{{ number_format($summary->tools_cost, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">-{{ number_format($summary->expenses, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold {{ $summary->net_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($summary->net_balance, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <h6 class="m-0 font-weight-bold text-primary me-3">{{ __('Transactions') }}</h6>
                @if(Auth::user()->hasRole('admin'))
                <button type="button" id="bulkDeleteBtn" class="btn btn-sm btn-danger d-none" onclick="submitBulkDelete()">
                    <i class="fa-solid fa-trash me-1"></i> {{ __('Delete Selected') }}
                </button>
                @endif
            </div>
            <form action="{{ route('finance.index') }}" method="GET" class="d-flex align-items-center">
                <input type="month" name="month" class="form-control form-control-sm me-2" value="{{ request('month') }}">
                
                @if(Auth::user()->hasRole('admin'))
                <select name="coordinator_id" class="form-select form-select-sm me-2" style="max-width: 150px;">
                    <option value="">{{ __('All Coordinators') }}</option>
                    @foreach($coordinators as $coordinator)
                        <option value="{{ $coordinator->id }}" {{ request('coordinator_id') == $coordinator->id ? 'selected' : '' }}>
                            {{ $coordinator->name }}
                        </option>
                    @endforeach
                </select>
                @endif

                <select name="type" class="form-select form-select-sm me-2">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>{{ __('Income') }}</option>
                    <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>{{ __('Expense') }}</option>
                </select>
                <button type="submit" class="btn btn-sm btn-secondary">{{ __('Filter') }}</button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            @if(Auth::user()->hasRole('admin'))
                            <th class="text-center" width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            @endif
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Coordinator') }}</th>
                            <th class="text-end">{{ __('Amount') }}</th>
                            <th>{{ __('Ref') }}</th>
                            <th class="text-center">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr>
                            @if(Auth::user()->hasRole('admin'))
                            <td class="text-center align-middle">
                                <input type="checkbox" name="ids[]" value="{{ $transaction->id }}" class="form-check-input select-row">
                            </td>
                            @endif
                            <td class="align-middle">{{ $transaction->transaction_date->format('d M Y') }}</td>
                            <td class="align-middle">
                                <span class="badge bg-{{ $transaction->type == 'income' ? 'success' : 'danger' }}">
                                    {{ $transaction->type == 'income' ? __('Income') : __('Expense') }}
                                </span>
                            </td>
                            <td class="align-middle">
                                <span class="badge bg-secondary text-white">{{ ucfirst(__($transaction->category)) }}</span>
                            </td>
                            <td class="align-middle">{{ $transaction->description }}</td>
                            <td class="align-middle">
                                @if($transaction->coordinator)
                                    <span class="badge bg-info text-dark">{{ $transaction->coordinator->name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="align-middle text-end fw-bold {{ $transaction->type == 'income' ? 'text-success' : 'text-danger' }}">
                                {{ $transaction->type == 'income' ? '+' : '-' }} {{ number_format($transaction->amount, 0, ',', '.') }}
                            </td>
                            <td class="align-middle small">{{ $transaction->reference_number }}</td>
                            <td class="align-middle text-center">
                                @if(Auth::user()->hasRole('admin'))
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                    data-bs-toggle="modal"  
                                    data-bs-target="#editTransactionModal"
                                    data-id="{{ $transaction->id }}"
                                    data-type="{{ $transaction->type }}"
                                    data-category="{{ $transaction->category }}"
                                    data-amount="{{ $transaction->amount }}"
                                    data-date="{{ $transaction->transaction_date->format('Y-m-d') }}"
                                    data-coordinator="{{ $transaction->coordinator_id }}"
                                    data-investor="{{ $transaction->investor_id }}"
                                    data-description="{{ $transaction->description }}"
                                    data-ref="{{ $transaction->reference_number }}"
                                    data-action="{{ route('finance.update', $transaction->id) }}">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form action="{{ route('finance.destroy', $transaction->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this transaction?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ Auth::user()->hasRole('admin') ? 9 : 8 }}" class="text-center">{{ __('No transactions found.') }}</td>
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

<form id="bulkDeleteForm" action="{{ route('finance.bulkDestroy') }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add Transaction') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('finance.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type') }}</label>
                        <select name="type" class="form-select" required>
                            <option value="income">{{ __('Income') }}</option>
                            <option value="expense">{{ __('Expense') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Category') }}</label>
                        <select name="category" class="form-select" required>
                            <option value="">{{ __('Select Category') }}</option>
                            <optgroup label="{{ __('Income') }}">
                                <option value="Member Income">{{ __('Member Income') }}</option>
                                <option value="Voucher Income">{{ __('Voucher Income') }}</option>
                            </optgroup>
                            <optgroup label="{{ __('Expense') }}">
                                <option value="Salary">{{ __('Salary') }}</option>
                                <option value="Operational">{{ __('Operational') }}</option>
                                <option value="Maintenance">{{ __('Maintenance') }}</option>
                                <option value="Pembayaran ISP">{{ __('Pembayaran ISP') }}</option>
                                <option value="Pembelian Alat">{{ __('Pembelian Alat') }}</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Amount') }}</label>
                        <input type="number" name="amount" class="form-control" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Date') }}</label>
                        <input type="date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Coordinator (Optional)') }}</label>
                        <select name="coordinator_id" class="form-select">
                            <option value="">{{ __('Select Coordinator') }}</option>
                            @foreach($coordinators as $coordinator)
                                <option value="{{ $coordinator->id }}">{{ $coordinator->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Investor (Optional)') }}</label>
                        <select name="investor_id" class="form-select">
                            <option value="">{{ __('Select Investor') }}</option>
                            @foreach($investors as $investor)
                                <option value="{{ $investor->id }}">{{ $investor->name }} ({{ $investor->coordinator->name }})</option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ __('Select only if this transaction is related to an investor (e.g., Capital, Profit Share)') }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Reference Number') }}</label>
                        <input type="text" name="reference_number" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Transaction') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Edit Transaction') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTransactionForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type') }}</label>
                        <select name="type" class="form-select" required>
                            <option value="income">{{ __('Income') }}</option>
                            <option value="expense">{{ __('Expense') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Category') }}</label>
                        <select name="category" class="form-select" required>
                            <option value="">{{ __('Select Category') }}</option>
                            <optgroup label="{{ __('Income') }}">
                                <option value="Member Income">{{ __('Member Income') }}</option>
                                <option value="Voucher Income">{{ __('Voucher Income') }}</option>
                            </optgroup>
                            <optgroup label="{{ __('Expense') }}">
                                <option value="Salary">{{ __('Salary') }}</option>
                                <option value="Operational">{{ __('Operational') }}</option>
                                <option value="Maintenance">{{ __('Maintenance') }}</option>
                                <option value="Pembayaran ISP">{{ __('Pembayaran ISP') }}</option>
                                <option value="Pembelian Alat">{{ __('Pembelian Alat') }}</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Amount') }}</label>
                        <input type="number" name="amount" class="form-control" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Date') }}</label>
                        <input type="date" name="transaction_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Coordinator (Optional)') }}</label>
                        <select name="coordinator_id" class="form-select">
                            <option value="">{{ __('Select Coordinator') }}</option>
                            @foreach($coordinators as $coordinator)
                                <option value="{{ $coordinator->id }}">{{ $coordinator->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Reference Number') }}</label>
                        <input type="text" name="reference_number" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Update Transaction') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Edit Modal Logic
        var editTransactionModal = document.getElementById('editTransactionModal');
        if (editTransactionModal) {
            editTransactionModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-id');
                var type = button.getAttribute('data-type');
                var category = button.getAttribute('data-category');
                var amount = button.getAttribute('data-amount');
                var date = button.getAttribute('data-date');
                var coordinator = button.getAttribute('data-coordinator');
                var investor = button.getAttribute('data-investor');
                var description = button.getAttribute('data-description');
                var ref = button.getAttribute('data-ref');
                var action = button.getAttribute('data-action');

                var form = document.getElementById('editTransactionForm');
                form.action = action;

                form.querySelector('[name="type"]').value = type;
                form.querySelector('[name="category"]').value = category;
                form.querySelector('[name="amount"]').value = amount;
                form.querySelector('[name="transaction_date"]').value = date;
                form.querySelector('[name="coordinator_id"]').value = coordinator || '';
                form.querySelector('[name="investor_id"]').value = investor || '';
                form.querySelector('[name="description"]').value = description;
                form.querySelector('[name="reference_number"]').value = ref;
            });
        }

        // Bulk Delete Logic
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.select-row');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const bulkDeleteForm = document.getElementById('bulkDeleteForm');

        function toggleButton() {
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            if (bulkDeleteBtn) {
                if (anyChecked) {
                    bulkDeleteBtn.classList.remove('d-none');
                } else {
                    bulkDeleteBtn.classList.add('d-none');
                }
            }
        }

        if(selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => {
                    cb.checked = selectAll.checked;
                });
                toggleButton();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', toggleButton);
        });

        window.submitBulkDelete = function() {
            if (confirm('{{ __('Are you sure you want to delete the selected transactions?') }}')) {
                // Clear previous inputs (except token and method)
                const inputs = bulkDeleteForm.querySelectorAll('input[name="ids[]"]');
                inputs.forEach(input => input.remove());
                
                // Add checked inputs to form
                checkboxes.forEach(cb => {
                    if (cb.checked) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = cb.value;
                        bulkDeleteForm.appendChild(input);
                    }
                });
                
                bulkDeleteForm.submit();
            }
        }
    });
</script>

@endsection
