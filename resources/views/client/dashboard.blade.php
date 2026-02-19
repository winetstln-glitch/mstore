@extends('layouts.app')

@section('title', 'Client Dashboard')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <strong>Profil</strong>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Nama:</strong> {{ $user->name }}</p>
                    <p class="mb-1"><strong>Email:</strong> {{ $user->email }}</p>
                    <p class="mb-0"><strong>Status Koneksi:</strong> <span class="badge {{ $connectionStatus === 'Active' ? 'bg-success' : 'bg-secondary' }}">{{ $connectionStatus }}</span></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Tautan Cepat</strong>
                </div>
                <div class="card-body d-flex gap-2">
                    <a href="{{ route('client.invoices.index') }}" class="btn btn-outline-primary">
                        <i class="fa-solid fa-receipt me-1"></i> Tagihan Saya
                    </a>
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-user-gear me-1"></i> Profil
                    </a>
                    <a href="{{ route('client.credentials.show') }}" class="btn btn-outline-warning">
                        <i class="fa-solid fa-key me-1"></i> Ganti Kredensial
                    </a>
                </div>
            </div>
        </div>
    </div>
    @if(!empty($deviceSummary))
    <div class="row mt-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <strong>Info Modem (GenieACS)</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4"><strong>Device ID</strong><br>{{ $deviceSummary['id'] }}</div>
                        <div class="col-md-4"><strong>IP</strong><br>{{ $deviceSummary['ip'] ?? '-' }}</div>
                        <div class="col-md-4"><strong>SSID</strong><br>{{ $deviceSummary['ssid'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
