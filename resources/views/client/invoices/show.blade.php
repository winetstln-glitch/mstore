@extends('layouts.app')

@section('title', 'Invoice ' . ($invoice->code ?? $invoice->id))

@section('content')
<div class="container py-3 invoice-container">
    <div class="d-print-none text-center mb-3">
        <div class="btn-group btn-group-sm">
            <a id="print_btn" href="javascript:window.print();" class="btn btn-outline-secondary">
                <i class="fa fa-print"></i> Print
            </a>
            <a href="javascript:history.back();" class="btn btn-outline-secondary">
                <i class="fa fa-backward"></i> Kembali
            </a>
        </div>
    </div>

    <header class="mb-3">
        <div class="row align-items-center">
            <div class="col-sm-6 text-sm-start text-center mb-2 mb-sm-0">
                <div class="h4 mb-0">{{ config('app.name', 'MSTORE.NET') }}</div>
            </div>
            <div class="col-sm-6 text-sm-end text-center">
                <div class="h5 mb-0">{{ strtoupper($invoice->status) }}</div>
                <div class="text-muted"># {{ $invoice->code ?? ('INV-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT)) }}</div>
            </div>
        </div>
        <hr>
    </header>

    <div class="row mb-3">
        <div class="col-sm-6">
            <strong>Ditagihkan ke:</strong>
            <address class="mb-0">
                {{ $user->name }}<br>
                {{ $customer->address ?? '-' }}<br>
                {{ $customer->phone ?? '-' }}
            </address>
        </div>
        <div class="col-sm-6 text-sm-end">
            <strong>Dibayarkan ke:</strong>
            <address class="mb-0">
                {{ config('app.name', 'MSTORE.NET') }}<br>
                -
            </address>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <span class="fw-semibold">Ringkasan Layanan</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <td class="col-5"><strong>Paket Langganan</strong></td>
                            <td class="col-3 text-center"><strong>Periode Bayar</strong></td>
                            <td class="col-2 text-center"><strong>Deadline</strong></td>
                            <td class="col-2 text-end"><strong>Jumlah</strong></td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $devicesCount }} DEVICE</div>
                                <div class="text-muted small">( High Speed Internet Package Service )</div>
                            </td>
                            <td class="text-center">{{ $invoice->created_at?->format('F Y') }}</td>
                            <td class="text-center">{{ $invoice->due_date?->format('F d, Y') ?? '—' }}</td>
                            <td class="text-end">Rp {{ number_format($invoice->amount, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Biaya Sewa Perangkat</td>
                            <td class="text-center">—</td>
                            <td class="text-center">—</td>
                            <td class="text-end">Rp 0,00</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Sub Total :</strong></td>
                            <td class="text-end">Rp {{ number_format($invoice->amount, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Pajak :</strong><br><span class="text-1"><i>based on company & country regulation</i></span></td>
                            <td class="text-end align-top">Rp 0,00</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end border-bottom-0"><strong>Total :</strong></td>
                            <td class="text-end border-bottom-0">Rp {{ number_format($invoice->amount, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="table-responsive mb-3">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <td class="text-center"><strong>Transaction Date</strong></td>
                    <td class="text-center"><strong>Gateway</strong></td>
                    <td class="text-center"><strong>Transaction ID</strong></td>
                    <td class="text-center"><strong>Amount</strong></td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">{{ ($invoice->paid_at ?? $invoice->updated_at ?? $invoice->created_at)?->format('F d, Y') }}</td>
                    <td class="text-center">{{ $invoice->payment_gateway ?? 'Manual' }}</td>
                    <td class="text-center">{{ $invoice->code ?? ('INV-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT)) }}</td>
                    <td class="text-center">Rp {{ number_format($invoice->amount, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <footer class="text-center" style="font-size: 13px;font-style: italic;">
        <strong>NOTE :</strong> This is computer generated receipt and does not require physical signature.
    </footer>
</div>
@endsection
