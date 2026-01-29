@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detail Transaksi</h1>
        <a href="{{ route('atk.transactions.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Invoice #{{ $transaction->invoice_number }}</h6>
            <button class="btn btn-sm btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Cetak
            </button>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-sm-6">
                    <h6 class="mb-3">Detail:</h6>
                    <div><strong>Tanggal:</strong> {{ $transaction->created_at->format('d M Y H:i') }}</div>
                    <div><strong>Kasir:</strong> {{ $transaction->user->name }}</div>
                    <div><strong>Metode Pembayaran:</strong> {{ strtoupper($transaction->payment_method) }}</div>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <h6 class="mb-3">Pelanggan:</h6>
                    <div><strong>Nama:</strong> {{ $transaction->customer_name }}</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th class="center">#</th>
                            <th>Produk</th>
                            <th class="right">Harga Satuan</th>
                            <th class="center">Qty</th>
                            <th class="right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transaction->items as $index => $item)
                        <tr>
                            <td class="center">{{ $index + 1 }}</td>
                            <td class="left strong">{{ $item->product->name }}</td>
                            <td class="right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                            <td class="center">{{ $item->quantity }}</td>
                            <td class="right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Total Pembayaran:</th>
                            <th class="text-end">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($transaction->notes)
            <div class="mt-4">
                <strong>Catatan:</strong>
                <p class="text-muted">{{ $transaction->notes }}</p>
            </div>
            @endif
        </div>
    </div>
</div>

<style type="text/css" media="print">
    body * {
        visibility: hidden;
    }
    .card, .card * {
        visibility: visible;
    }
    .card {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none !important;
        border: none !important;
    }
    .btn {
        display: none !important;
    }
</style>
@endsection
