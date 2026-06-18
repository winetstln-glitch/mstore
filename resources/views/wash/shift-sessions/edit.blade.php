@extends('layouts.app')

@section('title', 'Tutup Shift')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Tutup Shift</h1>
        <a href="{{ route('wash.shift-sessions.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-info-circle"></i> Informasi Sesi Shift Saat Ini
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr><th>Shift</th><td>{{ $session->shift->name ?? '-' }}</td></tr>
                        <tr><th>Kasir</th><td>{{ $session->user->name }}</td></tr>
                        <tr><th>Waktu Buka</th><td>{{ $session->opened_at->format('d-m-Y H:i') }}</td></tr>
                        <tr><th>Uang Kas Awal</th><td>Rp {{ number_format($session->opening_cash, 0, ',', '.') }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr><th>Total Penjualan</th><td>Rp {{ number_format($session->total_sales, 0, ',', '.') }}</td></tr>
                        <tr><th>Total Pengeluaran</th><td>Rp {{ number_format($session->total_expenses, 0, ',', '.') }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" action="{{ route('wash.shift-sessions.update', $session) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="closing_cash" class="form-label">Uang Kas Akhir <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="closing_cash" id="closing_cash" class="form-control" value="0" step="100" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Catatan</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3">{{ $session->notes }}</textarea>
                </div>

                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-door-closed"></i> Tutup Shift
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
