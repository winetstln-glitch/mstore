@extends('layouts.app')

@section('title', 'Transaksi Wash')

@section('content')
<div class="container-fluid wash-transactions-page py-2 py-md-3">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-4">
        <h1 class="h3 mb-0 text-body">Transaksi Wash</h1>
        <div class="d-flex flex-wrap gap-2 wash-transactions-toolbar">
            @if(Auth::user()->hasPermission('wash.manage'))
            <button type="button" class="btn btn-sm btn-outline-danger shadow-sm d-none" id="bulkDeleteBtn" onclick="confirmBulkDelete()">
                <i class="fas fa-trash fa-sm"></i>
                <span class="d-inline d-md-none ms-1">{{ __('Hapus') }} (<span id="selectedCount">0</span>)</span>
                <span class="d-none d-md-inline ms-1">{{ __('Hapus Terpilih') }} (<span id="selectedCountDesktop">0</span>)</span>
            </button>
            <form id="bulkDeleteForm" action="{{ route('wash.transactions.bulkDestroy') }}" method="POST" class="d-none">
                @csrf
                @method('DELETE')
            </form>
            @endif
            <a href="{{ route('wash.transactions.export.pdf', request()->all()) }}" class="btn btn-sm btn-danger shadow-sm" title="Buat PDF">
                <i class="fas fa-file-pdf fa-sm text-white-50"></i>
                <span class="d-none d-md-inline ms-1">Buat PDF</span>
            </a>
            <a href="{{ route('wash.transactions.export.excel', request()->all()) }}" class="btn btn-sm btn-success shadow-sm" title="Ekspor Excel">
                <i class="fas fa-file-excel fa-sm text-white-50"></i>
                <span class="d-none d-md-inline ms-1">Ekspor Excel</span>
            </a>
        </div>
    </div>

    <div class="card shadow mb-4 wash-panel">
        <div class="card-header py-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0 font-weight-bold text-primary">Daftar Transaksi</h6>
            </div>
            <form action="{{ route('wash.transactions.index') }}" method="GET" class="row g-3 align-items-center wash-filter-form">
                <div class="col-12 col-md-auto">
                    <label for="start_date" class="col-form-label">{{ __('Tanggal Mulai') }}</label>
                </div>
                <div class="col-12 col-md-auto">
                    <input type="date" id="start_date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-12 col-md-auto">
                    <label for="end_date" class="col-form-label">{{ __('Tanggal Selesai') }}</label>
                </div>
                <div class="col-12 col-md-auto">
                    <input type="date" id="end_date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-12 col-md-auto">
                    <label for="per_page" class="col-form-label">Per Halaman</label>
                </div>
                <div class="col-12 col-md-auto">
                    <select id="per_page" name="per_page" class="form-select">
                        <option value="10" {{ request('per_page', '10') === '10' ? 'selected' : '' }}>10</option>
                        <option value="20" {{ request('per_page') === '20' ? 'selected' : '' }}>20</option>
                        <option value="all" {{ request('per_page') === 'all' ? 'selected' : '' }}>Semua</option>
                    </select>
                </div>
                <div class="col-12 col-md-auto">
                    <label for="vehicle_plate" class="col-form-label">Plat Nomor</label>
                </div>
                <div class="col-12 col-md-auto">
                    <select id="vehicle_plate" name="vehicle_plate" class="form-select">
                        <option value="">Semua Plat</option>
                        @foreach(($knownVehiclePlates ?? []) as $plateOption)
                            <option value="{{ $plateOption }}" {{ request('vehicle_plate') === $plateOption ? 'selected' : '' }}>{{ $plateOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto d-flex flex-wrap gap-2 wash-filter-actions">
                    <button type="submit" class="btn btn-primary" title="{{ __('Saring') }}">
                        <i class="fas fa-filter"></i>
                        <span class="d-none d-md-inline ms-1">{{ __('Saring') }}</span>
                    </button>
                    <a href="{{ route('wash.transactions.index') }}" class="btn btn-secondary" title="{{ __('Atur Ulang') }}">
                        <i class="fas fa-rotate-left"></i>
                        <span class="d-none d-md-inline ms-1">{{ __('Atur Ulang') }}</span>
                    </a>
                </div>
            </form>
        </div>
        <div class="card-body">
            @if(Auth::user()->hasPermission('wash.manage'))
            <div class="d-flex d-md-none align-items-center gap-2 mb-2">
                <div class="form-check m-0">
                    <input class="form-check-input" type="checkbox" id="selectAllMobile">
                    <label class="form-check-label small" for="selectAllMobile">{{ __('Pilih semua') }}</label>
                </div>
            </div>
            @endif
            <div class="table-responsive table-responsive-mobile">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            @if(Auth::user()->hasPermission('wash.manage'))
                            <th style="width:40px;">
                                <div class="form-check m-0">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                </div>
                            </th>
                            @endif
                            <th>Tanggal</th>
                            <th>No. Antri</th>
                            <th>No. Transaksi</th>
                            <th>Pelanggan</th>
                            <th>No. WhatsApp</th>
                            <th>No. Plat</th>
                            <th>Total</th>
                            <th>Pembayaran</th>
                            <th>Kasir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                @if(Auth::user()->hasPermission('wash.manage'))
                                <td data-label="Pilih">
                                    <div class="form-check m-0">
                                        <input class="form-check-input transaction-checkbox" type="checkbox" value="{{ $transaction->id }}">
                                    </div>
                                </td>
                                @endif
                                <td data-label="Tanggal">{{ $transaction->created_at->format('d M Y H:i') }}</td>
                                <td data-label="No. Antri">
                                    <div class="fw-semibold">{{ $transaction->queue_display ?? ($transaction->queue_number ?? '-') }}</div>
                                    <div class="small text-muted">
                                        {{ $transaction->queue_priority_label ?? 'Bronze Queue' }}
                                        @if(!empty($transaction->queue_service_order_today))
                                            | Urutan #{{ $transaction->queue_service_order_today }}
                                        @endif
                                    </div>
                                    @if(($transaction->notes ?? null) === 'bonus_cuci_10x')
                                        <span class="badge bg-success ms-1">Bonus Gratis</span>
                                    @endif
                                </td>
                                <td data-label="No. Transaksi">{{ $transaction->transaction_number }}</td>
                                <td data-label="Pelanggan">{{ $transaction->customer_name ?? '-' }}</td>
                                <td data-label="No. WhatsApp">{{ $transaction->washCustomer->phone ?? '-' }}</td>
                                <td data-label="No. Plat">{{ $transaction->vehicle_plate ?? '-' }}</td>
                                <td data-label="Total">
                                    Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}
                                    @if(($transaction->notes ?? null) === 'bonus_cuci_10x' && ($transaction->discount_amount ?? 0) > 0)
                                        <br><small class="text-success">(Diskon bonus: Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }})</small>
                                    @endif
                                </td>
                                <td data-label="Pembayaran">{{ ucfirst($transaction->payment_method) }}</td>
                                <td data-label="Kasir">{{ $transaction->user->name ?? 'Tidak Diketahui' }}</td>
                                <td data-label="Aksi">
                                    <div class="d-flex flex-wrap gap-1 justify-content-end transaction-actions">
                                        <a href="{{ route('wash.transactions.show', $transaction->id) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Lihat') }}">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('wash.transactions.receipt', $transaction->id) }}" target="_blank" class="btn btn-sm btn-outline-warning" title="{{ __('Cetak') }}">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        @if(Auth::user()->hasPermission('wash.manage'))
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-info"
                                            title="{{ __('Ubah') }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editTransactionModal"
                                            data-update-url="{{ route('wash.transactions.update', $transaction) }}"
                                            data-transaction-number="{{ $transaction->transaction_number }}"
                                            data-customer-name="{{ $transaction->customer_name ?? '' }}"
                                            data-vehicle-plate="{{ $transaction->vehicle_plate ?? '' }}"
                                            data-vehicle-brand="{{ $transaction->vehicle_brand ?? '' }}"
                                            data-payment-method="{{ $transaction->payment_method ?? 'cash' }}"
                                            data-cash-amount="{{ (int) ($transaction->cash_amount ?? 0) }}"
                                            data-kasbon-type="{{ $transaction->kasbon_type ?? '' }}"
                                            data-kasbon-user-id="{{ $transaction->kasbon_user_id ?? '' }}"
                                            data-kasbon-name="{{ $transaction->kasbon_name ?? '' }}"
                                            data-items='@json($transaction->items->map(fn($item) => ["id" => $item->id, "service_name" => $item->service_name, "quantity" => (int) $item->quantity])->values())'
                                        >
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <form action="{{ route('wash.transactions.destroy', $transaction) }}" method="POST" class="d-inline" data-confirm="{{ __('Hapus transaksi ini?') }}" onsubmit="return confirm(this.dataset.confirm)">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Hapus') }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ Auth::user()->hasPermission('wash.manage') ? 11 : 10 }}" class="text-center">Tidak ada transaksi ditemukan.</td>
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
@if(Auth::user()->hasPermission('wash.manage'))
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editTransactionForm" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editTransactionTitle">Ubah Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Tutup') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="edit_customer_name">Nama Pelanggan</label>
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
                        <select class="form-select js-payment-method" id="edit_payment_method" name="payment_method" data-cash-target="#edit_cash_amount_group" data-kasbon-target="#edit_kasbon_group">
                            <option value="cash">💵 Tunai</option>
                            <option value="qris">📱 QRIS</option>
                            <option value="transfer">🏦 Transfer</option>
                            <option value="edc">💳 EDC</option>
                            <option value="kasbon">📜 Kasbon</option>
                        </select>
                    </div>
                    <div class="mb-3" id="edit_cash_amount_group">
                        <label class="form-label" for="edit_cash_amount">Nominal Tunai</label>
                        <input type="number" min="0" class="form-control" id="edit_cash_amount" name="cash_amount">
                    </div>
                    <div class="mb-3" id="edit_kasbon_group" style="display: none;">
                        <div class="card card-body bg-light">
                            <h6 class="fw-bold text-primary mb-3"><i class="fas fa-user-tie me-1"></i> Detail Kasbon</h6>
                            <div class="mb-3">
                                <label class="form-label">Tipe Pihak Kasbon</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="kasbon_type" id="edit_kasbon_employee" value="employee">
                                    <label class="form-check-label" for="edit_kasbon_employee">Karyawan (Daftar Akun)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="kasbon_type" id="edit_kasbon_outsider" value="outsider">
                                    <label class="form-check-label" for="edit_kasbon_outsider">Orang Luar / Nama Custom</label>
                                </div>
                            </div>
                            <div id="edit_kasbon_employee_section">
                                <label for="edit_kasbon_user_id" class="form-label">Pilih Karyawan</label>
                                <select class="form-select" id="edit_kasbon_user_id" name="kasbon_user_id">
                                    <option value="">-- Pilih Karyawan --</option>
                                    @foreach(\App\Models\User::all() as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="edit_kasbon_outsider_section" style="display: none;">
                                <label for="edit_kasbon_name" class="form-label">Nama Pihak Kasbon</label>
                                <input type="text" class="form-control" id="edit_kasbon_name" name="kasbon_name" placeholder="Masukkan nama...">
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label mb-2">Jumlah Layanan</label>
                        <div id="edit_items_container" class="d-grid gap-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Simpan Perubahan') }}</button>
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

        /* MOBILE TABLE - FORCE STYLES */
        .wash-transactions-page .table-responsive-mobile table {
            border: 0 !important;
        }

        .wash-transactions-page .table-responsive-mobile table thead {
            display: none !important;
        }

        .wash-transactions-page .table-responsive-mobile table tbody tr {
            display: block !important;
            margin-bottom: 1.25rem !important;
            border: 1px solid var(--bs-border-color) !important;
            border-radius: 0.9rem !important;
            overflow: hidden !important;
            background-color: var(--bs-body-bg) !important;
        }

        .wash-transactions-page .table-responsive-mobile table tbody td {
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 0.85rem 1rem !important;
            border-bottom: 1px solid var(--bs-border-color) !important;
            text-align: right !important;
            width: 100% !important;
        }

        .wash-transactions-page .table-responsive-mobile table tbody td:last-child {
            border-bottom: 0 !important;
        }

        .wash-transactions-page .table-responsive-mobile table tbody td::before {
            content: attr(data-label);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            color: var(--bs-primary);
            margin-right: 0.5rem;
            text-align: left;
            flex-shrink: 0;
            display: inline-block;
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

        .wash-transactions-page .table-responsive-mobile td[data-label="Aksi"] {
            display: block;
            text-align: left;
        }

        .wash-transactions-page .table-responsive-mobile td[data-label="Aksi"]::before {
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
    const itemsContainer = document.getElementById('edit_items_container');
    const selectAll = document.getElementById('selectAll');
    const selectAllMobile = document.getElementById('selectAllMobile');
    const transactionCheckboxes = document.querySelectorAll('.transaction-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const selectedCount = document.getElementById('selectedCount');
    const selectedCountDesktop = document.getElementById('selectedCountDesktop');
    const bulkDeleteForm = document.getElementById('bulkDeleteForm');
    const updatePaymentVisibility = () => {
        if (!paymentSelect) {
            return;
        }
        const method = paymentSelect.value;
        if (cashGroup) {
            cashGroup.style.display = method === 'cash' ? '' : 'none';
        }
        const kasbonGroup = document.getElementById('edit_kasbon_group');
        if (kasbonGroup) {
            kasbonGroup.style.display = method === 'kasbon' ? '' : 'none';
        }
    };
    const updateBulkDeleteBtn = () => {
        const selected = document.querySelectorAll('.transaction-checkbox:checked');
        if (selectedCount) {
            selectedCount.textContent = selected.length;
        }
        if (selectedCountDesktop) {
            selectedCountDesktop.textContent = selected.length;
        }
        if (bulkDeleteBtn) {
            bulkDeleteBtn.classList.toggle('d-none', selected.length === 0);
        }
        if (selectAll && transactionCheckboxes.length > 0) {
            selectAll.checked = selected.length === transactionCheckboxes.length;
        }
        if (selectAllMobile && transactionCheckboxes.length > 0) {
            selectAllMobile.checked = selected.length === transactionCheckboxes.length;
        }
    };
    const setAllChecked = (checked) => {
        transactionCheckboxes.forEach((checkbox) => {
            checkbox.checked = checked;
        });
        updateBulkDeleteBtn();
    };
    if (paymentSelect) {
        paymentSelect.addEventListener('change', updatePaymentVisibility);
    }

    const kasbonTypeRadios = document.querySelectorAll('#editTransactionModal input[name="kasbon_type"]');
    kasbonTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const employeeSection = document.getElementById('edit_kasbon_employee_section');
            const outsiderSection = document.getElementById('edit_kasbon_outsider_section');
            if (employeeSection && outsiderSection) {
                if (this.value === 'employee') {
                    employeeSection.style.display = '';
                    outsiderSection.style.display = 'none';
                } else {
                    employeeSection.style.display = 'none';
                    outsiderSection.style.display = '';
                }
            }
        });
    });
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            setAllChecked(this.checked);
        });
    }
    if (selectAllMobile) {
        selectAllMobile.addEventListener('change', function() {
            setAllChecked(this.checked);
        });
    }
    transactionCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', updateBulkDeleteBtn);
    });
    updateBulkDeleteBtn();
    window.confirmBulkDelete = function() {
        const selected = Array.from(document.querySelectorAll('.transaction-checkbox:checked')).map((checkbox) => checkbox.value);
        if (selected.length === 0 || !bulkDeleteForm) {
            return;
        }
        if (!confirm('{{ __("Apakah Anda yakin ingin menghapus transaksi terpilih?") }}')) {
            return;
        }
        bulkDeleteForm.querySelectorAll('input[name="ids[]"]').forEach((input) => input.remove());
        selected.forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            bulkDeleteForm.appendChild(input);
        });
        bulkDeleteForm.submit();
    };
    if (editModal) {
        editModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!trigger || !editForm) {
                return;
            }
            editForm.action = trigger.getAttribute('data-update-url') || '';
            editTitle.textContent = `Ubah Transaksi #${trigger.getAttribute('data-transaction-number') || ''}`;
            customerInput.value = trigger.getAttribute('data-customer-name') || '';
            plateInput.value = trigger.getAttribute('data-vehicle-plate') || '';
            brandInput.value = trigger.getAttribute('data-vehicle-brand') || '';
            paymentSelect.value = (trigger.getAttribute('data-payment-method') || 'cash').toLowerCase();
            cashInput.value = trigger.getAttribute('data-cash-amount') || '0';
            
            const kasbonType = trigger.getAttribute('data-kasbon-type') || '';
            const kasbonUserId = trigger.getAttribute('data-kasbon-user-id') || '';
            const kasbonName = trigger.getAttribute('data-kasbon-name') || '';
            
            const kasbonEmployeeRadio = document.getElementById('edit_kasbon_employee');
            const kasbonOutsiderRadio = document.getElementById('edit_kasbon_outsider');
            const editKasbonUserId = document.getElementById('edit_kasbon_user_id');
            const editKasbonName = document.getElementById('edit_kasbon_name');
            
            if (kasbonEmployeeRadio && kasbonOutsiderRadio) {
                if (kasbonType === 'employee') {
                    kasbonEmployeeRadio.checked = true;
                } else if (kasbonType === 'outsider') {
                    kasbonOutsiderRadio.checked = true;
                }
            }
            
            if (editKasbonUserId) {
                editKasbonUserId.value = kasbonUserId;
            }
            
            if (editKasbonName) {
                editKasbonName.value = kasbonName;
            }
            
            const employeeSection = document.getElementById('edit_kasbon_employee_section');
            const outsiderSection = document.getElementById('edit_kasbon_outsider_section');
            if (employeeSection && outsiderSection) {
                if (kasbonType === 'employee') {
                    employeeSection.style.display = '';
                    outsiderSection.style.display = 'none';
                } else if (kasbonType === 'outsider') {
                    employeeSection.style.display = 'none';
                    outsiderSection.style.display = '';
                }
            }
            
            const items = JSON.parse(trigger.getAttribute('data-items') || '[]');
            itemsContainer.innerHTML = '';
            items.forEach((item, index) => {
                const row = document.createElement('div');
                row.className = 'input-group';
                row.innerHTML = `
                    <span class="input-group-text">${item.service_name || 'Layanan'}</span>
                    <input type="hidden" name="items[${index}][id]" value="${item.id}">
                    <input type="number" min="1" class="form-control" name="items[${index}][quantity]" value="${item.quantity || 1}">
                `;
                itemsContainer.appendChild(row);
            });
            updatePaymentVisibility();
        });
    }
</script>
@endpush
@endsection
