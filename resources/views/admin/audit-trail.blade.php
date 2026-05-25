@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
        <h1 class="h2">Audit Trail</h1>
    </div>

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Pengguna</label>
                    <select name="user_id" class="form-select">
                        <option value="">Semua Pengguna</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Aksi</label>
                    <select name="action" class="form-select">
                        <option value="">Semua Aksi</option>
                        <option value="create" {{ request('action') == 'create' ? 'selected' : '' }}>Create</option>
                        <option value="update" {{ request('action') == 'update' ? 'selected' : '' }}>Update</option>
                        <option value="delete" {{ request('action') == 'delete' ? 'selected' : '' }}>Delete</option>
                        <option value="approve" {{ request('action') == 'approve' ? 'selected' : '' }}>Approve</option>
                        <option value="reject" {{ request('action') == 'reject' ? 'selected' : '' }}>Reject</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fa-solid fa-filter me-2"></i>Filter
                    </button>
                    <a href="{{ route('admin.audit-trail') }}" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Audit Log Table --}}
    <div class="card">
        <div class="card-body">
            @if($logs->isEmpty())
                <p class="text-center text-muted py-4">Belum ada data audit trail.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Pengguna</th>
                                <th>Aksi</th>
                                <th>Model</th>
                                <th>IP Address</th>
                                <th>Deskripsi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td>{{ $log->user?->name ?? 'Sistem' }}</td>
                                    <td>
                                        <span class="badge bg-{{ match($log->action) {
                                            'create' => 'success',
                                            'update' => 'warning',
                                            'delete' => 'danger',
                                            'approve' => 'info',
                                            'reject' => 'secondary',
                                            default => 'primary'
                                        } }}">
                                            {{ $log->action }}
                                        </span>
                                    </td>
                                    <td>{{ $log->model_type ? class_basename($log->model_type) : '-' }}</td>
                                    <td>{{ $log->ip_address ?? '-' }}</td>
                                    <td>{{ $log->description ?? '-' }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#logModal-{{ $log->id }}">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>

                                {{-- Modal Detail Log --}}
                                <div class="modal fade" id="logModal-{{ $log->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Detail Log Aktivitas</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold">Waktu</label>
                                                        <p class="mb-0">{{ $log->created_at->format('d/m/Y H:i:s') }}</p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold">Pengguna</label>
                                                        <p class="mb-0">{{ $log->user?->name ?? 'Sistem' }}</p>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold">Aksi</label>
                                                        <p class="mb-0">{{ $log->action }}</p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold">Model</label>
                                                        <p class="mb-0">{{ $log->model_type ? class_basename($log->model_type) . ' (ID: ' . $log->model_id . ')' : '-' }}</p>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">IP Address & User Agent</label>
                                                    <p class="mb-0">{{ $log->ip_address ?? '-' }}</p>
                                                    @if($log->user_agent)
                                                        <small class="text-muted">{{ $log->user_agent }}</small>
                                                    @endif
                                                </div>
                                                @if($log->old_values)
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Nilai Sebelum</label>
                                                        <pre class="bg-light p-2 rounded">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                                    </div>
                                                @endif
                                                @if($log->new_values)
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Nilai Sesudah</label>
                                                        <pre class="bg-light p-2 rounded">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                                    </div>
                                                @endif
                                                @if($log->description)
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Deskripsi</label>
                                                        <p class="mb-0">{{ $log->description }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
