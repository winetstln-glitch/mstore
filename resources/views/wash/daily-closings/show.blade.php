@extends('layouts.app')

@section('title', 'Detail Penutupan Harian')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Detail Penutupan Harian</h1>
        <a href="{{ route('wash.daily-closings.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-file-invoice-dollar"></i> Detail Penutupan Tanggal {{ $closing->closing_date->format('d-m-Y') }}
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th>Pendapatan Wash</th>
                                    <td class="text-end">Rp {{ number_format($closing->wash_revenue, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>Pendapatan Caffe</th>
                                    <td class="text-end">Rp {{ number_format($closing->caffe_revenue, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>Total Pendapatan</th>
                                    <td class="text-end fw-bold text-primary">Rp {{ number_format($closing->wash_revenue + $closing->caffe_revenue, 0, ',', '.') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th>Total Pengeluaran</th>
                                    <td class="text-end text-danger">Rp {{ number_format($closing->total_expenses, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>Laba Kotor</th>
                                    <td class="text-end fw-bold">Rp {{ number_format($closing->gross_profit, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>Laba Bersih</th>
                                    <td class="text-end fw-bold text-success">Rp {{ number_format($closing->net_profit, 0, ',', '.') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th>Transaksi Member</th>
                                    <td class="text-end">{{ $closing->total_member_transactions }}</td>
                                </tr>
                                <tr>
                                    <th>Transaksi Non-Member</th>
                                    <td class="text-end">{{ $closing->total_non_member_transactions }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th>Dibuat Oleh</th>
                                    <td class="text-end">{{ $closing->closedBy->name }}</td>
                                </tr>
                                <tr>
                                    <th>Disetujui Oleh</th>
                                    <td class="text-end">{{ $closing->approvedBy->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Waktu Disetujui</th>
                                    <td class="text-end">{{ $closing->approved_at ? $closing->approved_at->format('d-m-Y H:i') : '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($closing->notes)
    <div class="card shadow mb-4">
        <div class="card-header bg-secondary text-white">
            <i class="fas fa-sticky-note"></i> Catatan
        </div>
        <div class="card-body">
            {{ $closing->notes }}
        </div>
    </div>
    @endif

    <div class="d-flex gap-2">
        @if($closing->status === 'draft' && auth()->user()->hasPermission('wash.closing.approve'))
        <form method="POST" action="{{ route('wash.daily-closings.approve', $closing) }}">
            @csrf
            <button type="submit" class="btn btn-success" onclick="return confirm('Yakin ingin menyetujui penutupan harian ini?')">
                <i class="fas fa-check"></i> Setujui
            </button>
        </form>
        @endif
    </div>
</div>
@endsection
