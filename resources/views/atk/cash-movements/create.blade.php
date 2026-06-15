@extends('layouts.app')
@section('title', 'Sesuaikan Saldo Kas Utama')
@section('content')
<div class="container-fluid py-3">
    <h5 class="mb-3">Sesuaikan Saldo Kas Utama</h5>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('atk.cash-movements.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Saldo Kas Baru</label>
                    <input type="number" name="amount" class="form-control" placeholder="0" value="{{ old('amount') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi (Opsional)</label>
                    <input type="text" name="description" class="form-control" placeholder="Contoh: Penyesuaian saldo awal" value="{{ old('description') }}">
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('atk.cash-movements.index') }}" class="btn btn-light">Batal</a>
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection