@extends('layouts.app')
@section('title', 'Kasir Shift - ATK')
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="mb-0">Riwayat Kasir Shift</h5>
            <small class="text-muted">Buka dan tutup shift kasir untuk pencatatan saldo harian</small>
        </div>
        @if(!$activeRegister)
            <a href="{{ route('atk.cash-registers.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-door-open me-2"></i>Buka Shift Baru
            </a>
        @endif
    </div>

    @if($activeRegister)
        <div class="card mb-3 border-left-success shadow">
            <div class="card-body">
                <h6 class="fw-bold text-success mb-2">
                    <i class="fa-solid fa-circle-check me-2"></i>Shift Aktif: {{ $activeRegister->name }}
                </h6>
                <div class="d-flex gap-4 text-muted small">
                    <span><i class="fa-solid fa-user-tie me-1"></i>Dibuka oleh: {{ $activeRegister->user->name }}</span>
                    <span><i class="fa-solid fa-clock me-1"></i>Dibuka: {{ $activeRegister->opened_at->format('d/m/Y H:i') }}</span>
                    <span><i class="fa-solid fa-wallet me-1"></i>Saldo Awal: Rp {{ number_format($activeRegister->opening_balance, 0, ',', '.') }}</span>
                    <span><i class="fa-solid fa-money-bill-wave me-1"></i>Saldo Saat Ini: Rp {{ number_format($activeRegister->closing_balance, 0, ',', '.') }}</span>
                </div>
                <button class="btn btn-danger mt-3" data-bs-toggle="modal" data-bs-target="#closeShiftModal">
                    <i class="fa-solid fa-door-closed me-2"></i>Tutup Shift Ini
                </button>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Shift</th>
                            <th>Dibuka Oleh</th>
                            <th>Waktu Buka</th>
                            <th>Waktu Tutup</th>
                            <th>Saldo Awal</th>
                            <th>Saldo Akhir</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($registers as $register)
                            <tr>
                                <td>{{ $register->name }}</td>
                                <td>{{ $register->user->name }}</td>
                                <td>{{ $register->opened_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $register->closed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td>Rp {{ number_format($register->opening_balance, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($register->closing_balance, 0, ',', '.') }}</td>
                                <td>
                                    @if($register->status === 'open')
                                        <span class="badge bg-success">Open</span>
                                    @else
                                        <span class="badge bg-secondary">Closed</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('atk.cash-registers.edit', $register) }}" class="btn btn-sm btn-warning">
                                            <i class="fa-solid fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('atk.cash-registers.destroy', $register) }}" onsubmit="return confirm('Yakin ingin menghapus shift ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if($activeRegister)
<div class="modal fade" id="closeShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('atk.cash-registers.close', $activeRegister) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tutup Shift</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Saldo Akhir Kas</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="closing_balance" class="form-control" value="{{ old('closing_balance', $activeRegister->closing_balance) }}" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tutup Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
