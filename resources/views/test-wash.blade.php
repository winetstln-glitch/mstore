@extends('layouts.app')

@section('title', 'Test Wash ERP')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">✅ Modul Wash ERP Sudah Terinstall!</h1>
    
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            Menu Wash ERP Yang Sudah Dibuat:
        </div>
        <div class="card-body">
            <ul>
                <li><a href="{{ route('wash.dashboard') }}">Dashboard</a></li>
                <li><a href="{{ route('wash.pos') }}">POS Wash</a></li>
                <li><a href="{{ route('wash.transactions.index') }}">Transaksi</a></li>
                <li><a href="{{ route('wash.expenses.index') }}">Pengeluaran</a></li>
                <li><a href="{{ route('wash.stock.index') }}">Stok Wash</a></li>
                <li><a href="{{ route('wash.services.index') }}">Manajemen Layanan</a></li>
                <li><a href="{{ route('wash.suppliers.index') }}">Supplier</a></li>
                <li><a href="{{ route('wash.shifts.index') }}">Daftar Shift</a></li>
                <li><a href="{{ route('wash.shift-sessions.index') }}">Sesi Shift</a></li>
                <li><a href="{{ route('wash.cash-registers.index') }}">Daftar Kasir</a></li>
                <li><a href="{{ route('wash.cash-movements.index') }}">Mutasi Kas</a></li>
                <li><a href="{{ route('wash.daily-closings.index') }}">Penutupan Harian</a></li>
                <li><a href="{{ route('wash.members.index') }}">Member</a></li>
                <li><a href="{{ route('wash.loyalty.index') }}">Loyalty Program</a></li>
                <li><a href="{{ route('wash.loyalty.vouchers') }}">Reward Voucher</a></li>
                <li><a href="{{ route('wash.members.levels') }}">Membership Level</a></li>
                <li><a href="{{ route('wash.loyalty.redemptions') }}">Riwayat Reward</a></li>
                <li><a href="{{ route('wash.reports.index') }}">Laporan Wash</a></li>
            </ul>
        </div>
    </div>
</div>
@endsection
