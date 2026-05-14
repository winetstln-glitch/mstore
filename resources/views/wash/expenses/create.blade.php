@extends('layouts.app')
@section('title', isset($expense) ? 'Edit Pengeluaran Wash' : 'Tambah Pengeluaran Wash')
@section('content')
@php
    $stockCategoryLabels = [
        'shampoo' => 'Sampo Wash',
        'snack' => 'Snack',
        'kopi' => 'Caffe',
        'caffe' => 'Caffe',
        'lainnya' => 'Lainnya',
    ];
@endphp
<div class="container-fluid py-3 wash-expenses-create-page">
    <div class="d-flex justify-content-between align-items-center mb-3 create-header">
        <h5 class="mb-0">{{ isset($expense) ? 'Edit Pengeluaran Wash' : 'Tambah Pengeluaran Wash' }}</h5>
        <a href="{{ route('wash.expenses.index') }}" class="btn btn-outline-secondary btn-sm d-none d-md-inline-flex">Kembali</a>
    </div>
    <div class="card create-panel">
        <div class="card-body">
            <form method="POST" action="{{ isset($expense) ? route('wash.expenses.update', $expense->id) : route('wash.expenses.store') }}" id="createExpenseForm">
                @csrf
                @if(isset($expense))
                    @method('PUT')
                @endif
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', isset($expense) ? optional($expense->transaction_date)->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kategori Pembelanjaan</label>
                        @php
                            $selectedGroup = old(
                                'expense_group',
                                str_contains(strtolower($expense->category ?? ''), 'sampo')
                                    ? 'shampoo'
                                    : (str_contains(strtolower($expense->category ?? ''), 'snack')
                                        ? 'snack'
                                        : ((str_contains(strtolower($expense->category ?? ''), 'kopi') || str_contains(strtolower($expense->category ?? ''), 'caffe')) ? 'caffe' : 'lainnya'))
                            );
                        @endphp
                        <select name="expense_group" class="form-select" required>
                            <option value="shampoo" {{ $selectedGroup==='shampoo' ? 'selected' : '' }}>Sampo Wash</option>
                            <option value="snack" {{ $selectedGroup==='snack' ? 'selected' : '' }}>Snack</option>
                            <option value="caffe" {{ in_array($selectedGroup, ['caffe', 'kopi'], true) ? 'selected' : '' }}>Caffe</option>
                            <option value="lainnya" {{ $selectedGroup==='lainnya' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pilih Stok Yang Ada</label>
                        <select name="stock_item_id" class="form-select">
                            <option value="">-- Buat item baru --</option>
                            @foreach(($stockItems ?? []) as $item)
                                <option value="{{ $item->id }}" {{ (string) old('stock_item_id', $stockMovement->wash_stock_item_id ?? '') === (string) $item->id ? 'selected' : '' }}>
                                    {{ $stockCategoryLabels[strtolower((string) $item->category)] ?? ucfirst((string) $item->category) }} - {{ $item->name }} (Stok: {{ (float)$item->current_stock }} {{ $item->unit }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nama Item Pembelian</label>
                        <input type="text" name="item_name" class="form-control" placeholder="Contoh: Sampo Snow Wash 5L" value="{{ old('item_name', $stockMovement->stockItem->name ?? '') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Satuan</label>
                        <input type="text" name="unit" class="form-control" placeholder="pcs/liter/pack" value="{{ old('unit', $stockMovement->stockItem->unit ?? 'pcs') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Jumlah</label>
                        <input type="number" step="0.01" min="0.01" name="quantity" id="quantityInput" class="form-control" value="{{ old('quantity', $stockMovement->quantity ?? '1') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Harga Satuan</label>
                        <input type="number" step="0.01" min="0" name="unit_price" id="unitPriceInput" class="form-control" value="{{ old('unit_price', $stockMovement->unit_price ?? '') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Nominal</label>
                        <input type="number" name="amount" id="amountInput" class="form-control" placeholder="otomatis" value="{{ old('amount', $expense->amount ?? '') }}" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Deskripsi</label>
                        <input type="text" name="description" class="form-control" placeholder="Contoh: Belanja stok operasional wash" value="{{ old('description', $expense->description ?? '') }}" required>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('wash.expenses.index') }}" class="btn btn-light">Batal</a>
                    <button class="btn btn-primary">{{ isset($expense) ? 'Update' : 'Simpan' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="position-fixed bottom-0 start-0 end-0 bg-body border-top shadow d-md-none" style="z-index: 1030;">
    <div class="container py-2">
        <div class="d-flex gap-2">
            <a href="{{ route('wash.expenses.index') }}" class="btn btn-outline-secondary w-50">Batal</a>
            <button type="submit" class="btn btn-primary w-50" form="createExpenseForm">{{ isset($expense) ? 'Update' : 'Simpan' }}</button>
        </div>
    </div>
</div>
@push('styles')
<style>
    .wash-expenses-create-page .create-panel {
        border: 1px solid rgba(59, 130, 246, 0.15);
        border-radius: 1rem;
    }

    .wash-expenses-create-page .form-control,
    .wash-expenses-create-page .form-select {
        min-height: 44px;
    }

    @media (max-width: 767.98px) {
        .wash-expenses-create-page {
            padding-left: 0.35rem;
            padding-right: 0.35rem;
            padding-bottom: 5rem !important;
        }

        .wash-expenses-create-page .create-header {
            margin-bottom: 0.8rem;
        }

        .wash-expenses-create-page .card-body {
            padding: 0.9rem;
        }

        .wash-expenses-create-page .d-flex.gap-2 {
            display: none !important;
        }
    }

    [data-bs-theme="dark"] .wash-expenses-create-page .create-panel {
        border-color: rgba(96, 165, 250, 0.28);
    }
</style>
@endpush
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const qty = document.getElementById('quantityInput');
        const unitPrice = document.getElementById('unitPriceInput');
        const amount = document.getElementById('amountInput');
        if (!qty || !unitPrice || !amount) return;

        function recalcAmount() {
            const q = parseFloat(qty.value || '0');
            const p = parseFloat(unitPrice.value || '0');
            const total = (q * p);
            amount.value = Number.isFinite(total) ? total.toFixed(2) : '';
        }

        qty.addEventListener('input', recalcAmount);
        unitPrice.addEventListener('input', recalcAmount);
        if (!amount.value) {
            recalcAmount();
        }
    });
</script>
@endpush
@endsection
