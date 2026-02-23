@extends('layouts.app')

@section('title', 'Panduan Koneksi VPN')

@section('content')
<div class="container py-3">
    <div class="card mb-3">
        <div class="card-header">
            Cara Menggunakan VPN Bridge ke Server Billing
        </div>
        <div class="card-body">
            <ol class="mb-3">
                <li>Buka menu Router, pilih router Anda.</li>
                <li>Klik tombol "Generate Script" untuk mendapatkan script konfigurasi.</li>
                <li>Salin script, lalu tempel di Winbox -> New Terminal.</li>
                <li>Pastikan interface VPN muncul dan status R (Running).</li>
                <li>Sistem akan mendeteksi IP Tunnel otomatis dan menggunakan IP tersebut sebagai target API.</li>
            </ol>
            <a href="{{ route('routers.index') }}" class="btn btn-primary">Buka Daftar Router</a>
        </div>
    </div>
    <div class="alert alert-info">
        Gunakan protokol L2TP untuk stabilitas. Jika dibutuhkan, tersedia opsi PPTP, SSTP, dan OpenVPN.
    </div>
</div>
@endsection
