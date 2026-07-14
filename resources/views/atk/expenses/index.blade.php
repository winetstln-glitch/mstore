@extends('layouts.app')
@section('title', 'Pengeluaran ATK')
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">Pengeluaran ATK</h5>
        <a href="{{ route('atk.expenses.create') }}" class="btn btn-primary">Tambah Pengeluaran</a>
    </div>
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-striped align-middle">
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
                                <a href="{{ route('atk.expenses.edit', $e->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('atk.expenses.destroy', $e->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus pengeluaran ini?');">
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
            {{ $expenses->links() }}
        </div>
    </div>
</div>
@endsection
