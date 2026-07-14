@extends('layouts.app')

@section('title', 'Buka Shift')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Buka Shift Baru</h1>
        <a href="{{ route('wash.shift-sessions.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" action="{{ route('wash.shift-sessions.store') }}">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="wash_shift_id" class="form-label">Shift</label>
                        <select name="wash_shift_id" id="wash_shift_id" class="form-select">
                            <option value="">Pilih Shift (Opsional)</option>
                            @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->name }} ({{ $shift->start_time }} - {{ $shift->end_time }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="wash_cash_register_id" class="form-label">Kasir Utama</label>
                        <select name="wash_cash_register_id" id="wash_cash_register_id" class="form-select">
                            <option value="">Pilih Kasir (Opsional)</option>
                            @foreach($cashRegisters as $register)
                            <option value="{{ $register->id }}">{{ $register->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="opening_cash" class="form-label">Uang Kas Awal <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="opening_cash" id="opening_cash" class="form-control" value="0" step="100" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Catatan</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-door-open"></i> Buka Shift
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
