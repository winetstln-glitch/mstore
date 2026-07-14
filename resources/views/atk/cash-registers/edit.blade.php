@extends('layouts.app')
@section('title', 'Edit Shift Kasir')
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="mb-0">Edit Shift Kasir</h5>
            <small class="text-muted">Perubahan saldo awal akan otomatis mengupdate saldo Kas Utama</small>
        </div>
        <a href="{{ route('atk.cash-registers.index') }}" class="btn btn-light">
            <i class="fa-solid fa-arrow-left me-2"></i>Kembali
        </a>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('atk.cash-registers.update', $register) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Nama Shift</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $register->name) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Saldo Awal</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="opening_balance" class="form-control" value="{{ old('opening_balance', $register->opening_balance) }}" required>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('atk.cash-registers.index') }}" class="btn btn-light">Batal</a>
                            <button class="btn btn-primary">Update Shift</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection