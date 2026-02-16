@extends('layouts.app')
@section('title', 'Pengeluaran Wash')
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">Pengeluaran Wash</h5>
        <a href="{{ route('wash.expenses.create') }}" class="btn btn-primary">Tambah Pengeluaran</a>
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
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $e)
                    <tr>
                        <td>{{ $e->transaction_date->format('Y-m-d') }}</td>
                        <td>{{ $e->description }}</td>
                        <td>Rp {{ number_format($e->amount,0,',','.') }}</td>
                        <td><span class="badge bg-secondary">{{ $e->reference_number }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted">Belum ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $expenses->links() }}
        </div>
    </div>
</div>
@endsection
