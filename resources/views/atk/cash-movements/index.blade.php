@extends('layouts.app')

@section('title', 'Mutasi Kas Utama')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Mutasi Kas Utama</h1>
            <small class="text-muted">Riwayat semua transaksi Kas Utama ATK</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('atk.cash-movements.create') }}" class="btn btn-warning">
                <i class="fas fa-wallet"></i> Koreksi Saldo Kas
            </a>
            <a href="{{ route('atk.dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('atk.cash-movements.index') }}" method="GET">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label>Tanggal Awal</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Tanggal Akhir</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Jenis Transaksi</label>
                        <select name="movement_type" class="form-control">
                            <option value="">Semua</option>
                            @foreach($types as $type)
                            <option value="{{ $type }}" {{ request('movement_type') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Kasir</label>
                        <select name="created_by" class="form-control">
                            <option value="">Semua</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('created_by') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('atk.cash-movements.index') }}" class="btn btn-light">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Ringkasan</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="text-muted">Saldo Awal</h6>
                            <h4 class="text-primary">Rp {{ number_format($initialBalance, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="text-muted">Total Kas Masuk</h6>
                            <h4 class="text-success">Rp {{ number_format($movements->where('direction', 'in')->sum('amount'), 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="text-muted">Total Kas Keluar</h6>
                            <h4 class="text-danger">Rp {{ number_format($movements->where('direction', 'out')->sum('amount'), 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Referensi</th>
                            <th>Produk</th>
                            <th>Kas Masuk</th>
                            <th>Kas Keluar</th>
                            <th>Saldo</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movements as $move)
                        <tr>
                            <td>{{ $move->created_at->format('d-m-Y H:i') }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($move->movement_type) }}</span></td>
                            <td>{{ $move->description ?? '-' }}</td>
                            <td>
                                @if($move->atkTransaction && $move->atkTransaction->items->count() > 0)
                                    <ul class="mb-0" style="font-size: 0.875rem;">
                                        @foreach($move->atkTransaction->items as $item)
                                            <li>{{ $item->product_name ?? $item->atk_product->name }} x {{ $item->quantity }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-success">{{ $move->direction === 'in' ? 'Rp ' . number_format($move->amount, 0, ',', '.') : '-' }}</td>
                            <td class="text-danger">{{ $move->direction === 'out' ? 'Rp ' . number_format($move->amount, 0, ',', '.') : '-' }}</td>
                            <td>Rp {{ number_format($move->balance_after, 0, ',', '.') }}</td>
                            <td>{{ $move->creator->name ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $movements->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
