@extends('layouts.app')
@section('title', isset($expense) ? 'Edit Pengeluaran ATK' : 'Tambah Pengeluaran ATK')
@section('content')
<div class="container-fluid py-3">
    <h5 class="mb-3">{{ isset($expense) ? 'Edit Pengeluaran ATK' : 'Tambah Pengeluaran ATK' }}</h5>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ isset($expense) ? route('atk.expenses.update', $expense->id) : route('atk.expenses.store') }}">
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
                    <input type="text" name="description" class="form-control" placeholder="Contoh: Beli tinta, kertas, dll" value="{{ old('description', $expense->description ?? '') }}" required>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('atk.expenses.index') }}" class="btn btn-light">Batal</a>
                    <button class="btn btn-primary">{{ isset($expense) ? 'Update' : 'Simpan' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
