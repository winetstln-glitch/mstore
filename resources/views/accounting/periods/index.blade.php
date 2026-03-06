@extends('layouts.app')
@section('title', 'Periode Akuntansi')
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Periode Akuntansi</h1>
        <a href="{{ route('accounting.periods.create') }}" class="btn btn-primary btn-lg w-100 w-md-auto">
            <i class="fas fa-plus me-1"></i> Buat Periode
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-left-success shadow-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-left-danger shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Periode</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle table-responsive-mobile" width="100%" cellspacing="0">
                    <thead class="table-light">
                    <tr>
                        <th>Nama</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($periods as $p)
                    <tr>
                        <td class="fw-bold">{{ $p->name }}</td>
                        <td>{{ $p->start_date }}</td>
                        <td>{{ $p->end_date }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $p->status === 'open' ? 'success' : 'secondary' }}">{{ strtoupper($p->status) }}</span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('accounting.periods.opening', $p) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-book-open me-1"></i> Saldo Awal
                                </a>
                                @if($p->status === 'open')
                                <form method="post" action="{{ route('accounting.periods.close', $p) }}" onsubmit="return confirm('Tutup periode {{ $p->name }}?')">
                                    @csrf
                                    <button class="btn btn-danger btn-sm">
                                        <i class="fas fa-lock me-1"></i> Tutup
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
