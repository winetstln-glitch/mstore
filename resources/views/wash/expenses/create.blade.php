@extends('layouts.app')
@section('title', 'Tambah Pengeluaran Wash')
@section('content')
<div class="container-fluid py-3">
    <h5 class="mb-3">Tambah Pengeluaran Wash</h5>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('wash.expenses.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="transaction_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nominal</label>
                    <input type="number" name="amount" class="form-control" placeholder="0" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <input type="text" name="description" class="form-control" placeholder="Contoh: Beli sabun, listrik, dll" required>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('wash.expenses.index') }}" class="btn btn-light">Batal</a>
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
