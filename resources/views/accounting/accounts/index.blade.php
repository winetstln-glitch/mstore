@extends('layouts.app')
@section('title', 'Master Akun')
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <h1 class="h3 mb-0 fw-bold">Master Akun</h1>
        <a href="{{ route('accounting.accounts.create') }}" class="btn btn-primary btn-lg w-100 w-md-auto">
            <i class="fas fa-plus me-1"></i> Tambah Akun
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-left-success shadow-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-left-danger shadow-sm">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-muted">Cari Kode / Nama</label>
                    <input type="text" name="q" class="form-control" value="{{ $search }}" placeholder="Contoh: 1101 atau Kas">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted">Jenis Akun</label>
                    <select name="type" class="form-select">
                        <option value="">Semua Jenis</option>
                        @foreach($types as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}" @selected($selectedType === $typeKey)>{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-outline-primary">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Akun</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle table-responsive-mobile" width="100%" cellspacing="0">
                    <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Jenis</th>
                        <th>Parent</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($accounts as $account)
                        <tr>
                            <td class="fw-bold">{{ $account->code }}</td>
                            <td>{{ $account->name }}</td>
                            <td>{{ $types[$account->type] ?? ucfirst($account->type) }}</td>
                            <td>
                                @if($account->parent)
                                    {{ $account->parent->code }} - {{ $account->parent->name }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $account->is_active ? 'success' : 'secondary' }}">
                                    {{ $account->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{ route('accounting.accounts.edit', $account) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form method="post" action="{{ route('accounting.accounts.destroy', $account) }}" onsubmit="return confirm('Hapus akun {{ $account->code }} - {{ $account->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada akun</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($accounts->hasPages())
                <div class="mt-3">
                    {{ $accounts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
