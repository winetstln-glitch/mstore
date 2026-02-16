@extends('layouts.app')
@section('title', 'Periode Akuntansi')
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Periode Akuntansi</h5>
        <a href="{{ route('accounting.periods.create') }}" class="btn btn-primary btn-sm">Buat Periode</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="card shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nama</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($periods as $p)
                    <tr>
                        <td>{{ $p->name }}</td>
                        <td>{{ $p->start_date }}</td>
                        <td>{{ $p->end_date }}</td>
                        <td>
                            <span class="badge bg-{{ $p->status === 'open' ? 'success' : 'secondary' }}">{{ strtoupper($p->status) }}</span>
                        </td>
                        <td class="d-flex gap-2">
                            <a href="{{ route('accounting.periods.opening', $p) }}" class="btn btn-sm btn-outline-secondary">Saldo Awal</a>
                            @if($p->status === 'open')
                            <form method="post" action="{{ route('accounting.periods.close', $p) }}" onsubmit="return confirm('Tutup periode {{ $p->name }}?')">
                                @csrf
                                <button class="btn btn-sm btn-danger">Tutup Periode</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
