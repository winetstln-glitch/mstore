@extends('layouts.app')
@section('title', 'Pengeluaran Wash')
@section('content')
<div class="container-fluid py-3 wash-expenses-page">
    <div class="d-flex justify-content-between align-items-center mb-3 expenses-header">
        <h5 class="m-0">Pengeluaran Wash</h5>
        <a href="{{ route('wash.expenses.create') }}" class="btn btn-primary">Tambah Pengeluaran</a>
    </div>
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <div class="card expenses-panel">
        <div class="card-body table-responsive table-responsive-mobile">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Deskripsi</th>
                        <th>Nominal</th>
                        <th>Ref</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $e)
                    <tr>
                        <td>{{ $e->transaction_date->format('Y-m-d') }}</td>
                        <td>{{ $e->description }}</td>
                        <td>Rp {{ number_format($e->amount,0,',','.') }}</td>
                        <td><span class="badge bg-secondary">{{ $e->reference_number }}</span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('wash.expenses.edit', $e->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('wash.expenses.destroy', $e->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus pengeluaran ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted">Belum ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent border-0 pt-2">
            {{ $expenses->links() }}
        </div>
    </div>
</div>
@push('styles')
<style>
    .wash-expenses-page .expenses-panel {
        border: 1px solid rgba(59, 130, 246, 0.15);
        border-radius: 1rem;
        overflow: hidden;
    }

    .wash-expenses-page .table thead th {
        background: rgba(148, 163, 184, 0.12);
    }

    @media (max-width: 767.98px) {
        .wash-expenses-page {
            padding-left: 0.35rem;
            padding-right: 0.35rem;
        }

        .wash-expenses-page .expenses-header {
            flex-direction: column;
            align-items: stretch !important;
            gap: 0.6rem;
        }

        .wash-expenses-page .expenses-header .btn {
            width: 100%;
            min-height: 42px;
            border-radius: 0.75rem;
        }

        .wash-expenses-page .table-responsive {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.9rem;
            padding: 0.25rem;
        }

        .wash-expenses-page .table-responsive-mobile td {
            align-items: flex-start;
            gap: 0.55rem;
        }
    }

    [data-bs-theme="dark"] .wash-expenses-page .expenses-panel {
        border-color: rgba(96, 165, 250, 0.28);
    }
</style>
@endpush
@endsection
