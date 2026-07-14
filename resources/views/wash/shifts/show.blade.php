@extends('layouts.app')

@section('title', 'Detail Shift')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Detail Shift</h1>
        <a href="{{ route('wash.shifts.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-clock"></i> Informasi Shift
                </div>
                <div class="card-body">
                    <table class table table-borderless">
                        <tr>
                            <th>Nama</th>
                            <td>{{ $shift->name }}</td>
                        </tr>
                        <tr>
                            <th>Waktu Mulai</th>
                            <td>{{ $shift->start_time }}</td>
                        </tr>
                        <tr>
                            <th>Waktu Selesai</th>
                            <td>{{ $shift->end_time }}</td>
                        </tr>
                        <tr>
                            <th>Deskripsi</th>
                            <td>{{ $shift->description ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge {{ $shift->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $shift->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-door-open"></i> Riwayat Sesi Shift
                </div>
                <div class="card-body">
                    @if($sessions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Kasir</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sessions as $session)
                                <tr>
                                    <td>{{ $session->opened_at->format('d-m-Y H:i') }}</td>
                                    <td>{{ $session->user->name ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ $session->status === 'open' ? 'bg-primary' : 'bg-success' }}">
                                            {{ $session->status === 'open' ? 'Buka' : 'Tutup' }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-center text-muted">Belum ada sesi untuk shift ini</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('wash.shifts.edit', $shift) }}" class="btn btn-warning">
            <i class="fas fa-edit"></i> Edit
        </a>
        <form method="POST" action="{{ route('wash.shifts.destroy', $shift) }}" onsubmit="return confirm('Yakin ingin menghapus shift ini?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash"></i> Hapus
            </button>
        </form>
    </div>
</div>
@endsection
