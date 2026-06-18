@extends('layouts.app')

@section('title', 'Penutupan Harian Wash')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Riwayat Penutupan Harian Wash & Caffe</h1>
        <a href="{{ route('wash.daily-closings.create') }}" class="btn btn-primary">
            <i class="fas fa-file-invoice"></i> Buat Penutupan Hari Ini
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Tanggal</th>
                            <th>Pendapatan Wash</th>
                            <th>Pendapatan Caffe</th>
                            <th>Total Pengeluaran</th>
                            <th>Laba Kotor</th>
                            <th>Laba Bersih</th>
                            <th>Dibuat Oleh</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($closings as $closing)
                        <tr>
                            <td>{{ $closing->closing_date->format('d-m-Y') }}</td>
                            <td>Rp {{ number_format($closing->wash_revenue, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($closing->caffe_revenue, 0, ',', '.') }}</td>
                            <td class="text-danger">Rp {{ number_format($closing->total_expenses, 0, ',', '.') }}</td>
                            <td class="text-primary">Rp {{ number_format($closing->gross_profit, 0, ',', '.') }}</td>
                            <td class="text-success">Rp {{ number_format($closing->net_profit, 0, ',', '.') }}</td>
                            <td>{{ $closing->closedBy->name }}</td>
                            <td>
                                <span class="badge {{ $closing->status === 'approved' ? 'bg-success' : ($closing->status === 'pending' ? 'bg-warning' : 'bg-secondary') }}">
                                    {{ ucfirst($closing->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('wash.daily-closings.show', $closing) }}" class="btn btn-sm btn-outline-info" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($closing->status === 'draft' && auth()->user()->hasPermission('wash.closing.approve'))
                                    <form method="POST" action="{{ route('wash.daily-closings.approve', $closing) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Setujui" onclick="return confirm('Yakin ingin menyetujui penutupan harian ini?')">
                                            <i class="fas fa-check"></i>
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
            {{ $closings->links() }}
        </div>
    </div>
</div>
@endsection
