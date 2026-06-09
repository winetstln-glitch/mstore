@extends('layouts.app')

@section('title', 'Membership Level Wash')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Membership Level GT Wash</h4>
            <div class="text-muted">Bronze, Silver, Gold, Platinum dengan diskon dan prioritas otomatis.</div>
        </div>
        <a href="{{ route('wash.members.index') }}" class="btn btn-outline-secondary">Kembali ke Member</a>
    </div>

    <div class="row g-3">
        @foreach($levels as $level)
            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="text-muted small">Level</div>
                                <div class="fs-5 fw-bold">{{ $level->name }}</div>
                            </div>
                            <span class="badge bg-primary">{{ number_format((float) $level->discount_percent, 0, ',', '.') }}%</span>
                        </div>
                        <div class="small text-muted mb-2">Range Transaksi</div>
                        <div class="fw-semibold mb-3">
                            {{ number_format((int) $level->min_transactions, 0, ',', '.') }}
                            -
                            {{ is_null($level->max_transactions) ? '∞' : number_format((int) $level->max_transactions, 0, ',', '.') }}
                        </div>
                        <div class="small text-muted mb-2">Benefit</div>
                        <div>
                            @foreach(($level->benefits ?? []) as $benefit)
                                <div>{{ $benefit }}</div>
                            @endforeach
                        </div>
                        <hr>
                        <div class="small text-muted">Prioritas Booking</div>
                        <div class="fw-semibold">Rank {{ $level->priority_rank }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

