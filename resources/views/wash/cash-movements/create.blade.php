@extends('layouts.app')

@section('title', 'Tambah Mutasi Kas')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Tambah Mutasi Kas</h1>
        <a href="{{ route('wash.cash-movements.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" action="{{ route('wash.cash-movements.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="wash_cash_register_id" class="form-label">Kasir Utama <span class="text-danger">*</span></label>
                    <select name="wash_cash_register_id" id="wash_cash_register_id" class="form-select" required>
                        <option value="">Pilih Kasir Utama</option>
                        @foreach($registers as $register)
                        <option value="{{ $register->id }}">{{ $register->name }} (Saldo: Rp {{ number_format($register->current_balance, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="type" class="form-label">Tipe Mutasi <span class="text-danger">*</span></label>
                        <select name="type" id="type" class="form-select" required>
                            <option value="in">Kas Masuk</option>
                            <option value="out">Kas Keluar</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="amount" class="form-label">Jumlah <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="amount" id="amount" class="form-control" value="0" step="100" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="movement_date" class="form-label">Tanggal & Waktu <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="movement_date" id="movement_date" class="form-control" value="{{ date('Y-m-d\TH:i') }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Keterangan <span class="text-danger">*</span></label>
                    <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
