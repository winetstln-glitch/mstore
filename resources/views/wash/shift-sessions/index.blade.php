@extends('layouts.app')

@section('title', 'Sesi Shift Wash')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Daftar Sesi Shift Wash & Caffe</h1>
        <a href="{{ route('wash.shift-sessions.create') }}" class="btn btn-primary">
            <i class="fas fa-door-open"></i> Buka Shift
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Shift</th>
                            <th>Kasir</th>
                            <th>Kasir Utama</th>
                            <th>Waktu Buka</th>
                            <th>Waktu Tutup</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sessions as $session)
                        <tr>
                            <td>{{ $session->shift->name ?? '-' }}</td>
                            <td>{{ $session->user->name }}</td>
                            <td>{{ $session->cashRegister->name ?? '-' }}</td>
                            <td>{{ $session->opened_at->format('d-m-Y H:i') }}</td>
                            <td>{{ $session->closed_at ? $session->closed_at->format('d-m-Y H:i') : '-' }}</td>
                            <td>
                                <span class="badge {{ $session->status === 'open' ? 'bg-primary' : 'bg-success' }}">
                                    {{ $session->status === 'open' ? 'Buka' : 'Tutup' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('wash.shift-sessions.show', $session) }}" class="btn btn-sm btn-outline-info" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($session->status === 'open')
                                    <a href="{{ route('wash.shift-sessions.edit', $session) }}" class="btn btn-sm btn-outline-warning" title="Tutup Shift">
                                        <i class="fas fa-door-closed"></i>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $sessions->links() }}
        </div>
    </div>
</div>
@endsection
