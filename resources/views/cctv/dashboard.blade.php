@extends('layouts.app')

@section('title', __('CCTV Installation Dashboard'))

@section('content')
<div class="container-fluid">
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold">Instalasi Pending</div>
                    <div class="fs-3">{{ $stats['pending_installations'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold">Survey Pending</div>
                    <div class="fs-3">{{ $stats['pending_surveys'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold">Instalasi Bulan Ini</div>
                    <div class="fs-3">{{ $stats['installations_this_month'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold">Pendapatan</div>
                    <div class="fs-3">Rp {{ number_format($stats['revenue'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold">Teknisi Aktif</div>
                    <div class="fs-3">{{ $stats['active_technicians'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

