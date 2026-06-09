@extends('layouts.app')

@section('title', 'Fiber Monitoring')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">Fiber Monitoring</h4>
            <div class="text-muted small">Operasional NOC</div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="text-muted">Fiber Monitoring memakai data ODC/ODP/Closure dan incident/outage. Tahap awal menampilkan peta jaringan dan daftar outage fiber cut.</div>
            <div class="mt-3">
                <a href="{{ route('map.connections.index') }}" class="btn btn-sm btn-outline-primary">Buka Peta</a>
            </div>
        </div>
    </div>
</div>
@endsection
