@extends('layouts.app')

@section('title', 'Info Koneksi')

@section('content')
<div class="container py-3">
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Status Koneksi</strong>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="h3 mb-0">
                            <i class="fa fa-globe me-1"></i>
                            {{ $connected ? 'Connected' : 'Disconnected' }}
                        </div>
                        <div class="text-muted small">MSTORE.NET</div>
                    </div>
                    <table class="table table-sm">
                        <tr>
                            <td class="text-muted">Aktif Sejak</td>
                            <td class="text-end">{{ $activeSince ? $activeSince->format('d M Y') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">PPPoE Username</td>
                            <td class="text-end">{{ $pppoeUsername ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">IP PPPoE</td>
                            <td class="text-end">{{ $pppoeIp ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">IP CPE</td>
                            <td class="text-end">{{ $ip ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Serial</td>
                            <td class="text-end">{{ $serial ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tipe Perangkat</td>
                            <td class="text-end">{{ $productClass ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Uptime</td>
                            <td class="text-end">{{ $uptime ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Panduan Koneksi</strong>
                </div>
                <div class="card-body">
                    <ol class="mb-2">
                        <li>Pastikan modem/ONT menyala dan terhubung ke jaringan.</li>
                        <li>Periksa kabel fiber/ethernet terpasang dengan benar.</li>
                        <li>Jika koneksi terputus, restart perangkat Anda.</li>
                        <li>Jika masih bermasalah, buat tiket melalui menu “Tiket Laporan”.</li>
                    </ol>
                    <a href="{{ route('tickets.index') }}" class="btn btn-outline-primary">
                        <i class="fa fa-ticket me-1"></i> Buka Tiket Laporan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
