@extends('layouts.app')
@section('title', isset($expense) ? 'Edit Pengeluaran Wash' : 'Tambah Pengeluaran Wash')
@section('content')
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
                <div class="mb-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', isset($expense) ? optional($expense->transaction_date)->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nominal</label>
                    <input type="number" name="amount" class="form-control" placeholder="0" value="{{ old('amount', $expense->amount ?? '') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <input type="text" name="description" class="form-control" placeholder="Contoh: Beli sabun, listrik, dll" value="{{ old('description', $expense->description ?? '') }}" required>
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

    .wash-expenses-create-page .form-control {
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
@endsection
