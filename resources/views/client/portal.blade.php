@extends('layouts.app')

@section('title', 'Portal Pelanggan')

@section('content')
<div class="container py-3">
    <div class="alert alert-success" role="alert">
        <h5 class="mb-1"><i class="fa fa-info-circle me-1"></i> Selamat Datang di Portal Pelanggan</h5>
        <div>Gunakan portal ini untuk pembayaran tagihan, cetak invoice, kirim tiket, dan cek jatuh tempo.</div>
    </div>

    <div class="row g-3">
        <div class="col-md-3 col-sm-6">
            <a href="{{ $currentInvoice ? route('client.invoices.show', $currentInvoice) : route('client.invoices.index') }}" class="text-decoration-none text-reset">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-2"><i class="fa fa-file-text text-info" style="font-size: 28px;"></i></div>
                            <div>
                                <div class="small text-muted">Invoice Saat Ini</div>
                                <div class="fw-semibold">{{ $currentInvoice?->code ?? '-' }}</div>
                                <div class="small {{ ($currentInvoice?->status ?? '') === 'paid' ? 'text-success' : 'text-secondary' }}">
                                    {{ strtoupper($currentInvoice->status ?? '-') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-2"><i class="fa fa-money text-danger" style="font-size: 28px;"></i></div>
                        <div>
                            <div class="small text-muted">Jumlah Tagihan</div>
                            <div class="fw-semibold">Rp {{ number_format($totalDue, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-2"><i class="fa fa-bookmark text-warning" style="font-size: 28px;"></i></div>
                        <div>
                            <div class="small text-muted">Service</div>
                            <div class="fw-semibold">{{ $devicesCount }} Device</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-2"><i class="fa fa-clock-o text-success" style="font-size: 28px;"></i></div>
                        <div>
                            <div class="small text-muted">Sisa Waktu</div>
                            <div class="fw-semibold" id="due_countdown">
                                @if($dueInvoice?->due_date)
                                    {{ $dueInvoice->due_date->diffForHumans() }}
                                @else
                                    -
                                @endif
                            </div>
                            @if($dueInvoice?->due_date)
                                <div class="small text-muted">{{ $dueInvoice->due_date->format('d M Y') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-3">
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <strong>Status Koneksi</strong>
                </div>
                <div class="card-body p-2">
                    <div class="mb-2 p-2 border rounded d-flex align-items-center">
                        <div class="me-2 text-info"><i class="fa fa-calendar" style="font-size:24px"></i></div>
                        <div>
                            <div class="small text-muted">Tanggal Aktif</div>
                            <div class="fw-semibold">{{ $activeSince ? $activeSince->format('M/d/Y') : '-' }}</div>
                        </div>
                    </div>
                    <div class="mb-2 p-2 border rounded d-flex align-items-center">
                        <div class="me-2 text-danger"><i class="fa fa-server" style="font-size:24px"></i></div>
                        <div>
                            <div class="small text-muted">Perangkat</div>
                            <div class="fw-semibold">{{ $deviceMac ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="mb-2 p-2 border rounded d-flex align-items-center">
                        <div class="me-2 text-warning"><i class="fa fa-hourglass-half" style="font-size:24px"></i></div>
                        <div>
                            <div class="small text-muted">Waktu Online</div>
                            <div class="fw-semibold">{{ $uptime ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="p-2 border rounded d-flex align-items-center">
                        <div class="me-2 text-success"><i class="fa fa-area-chart" style="font-size:24px"></i></div>
                        <div>
                            <div class="small text-muted">Penggunaan Data</div>
                            <div class="fw-semibold">{{ $dataUsage ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="list-group shadow-sm">
                <div class="list-group-item fw-semibold">Navigasi Pelanggan</div>
                <a href="{{ route('profile.edit') }}" class="list-group-item list-group-item-action">
                    <i class="fa fa-user me-2"></i> Profil Saya
                </a>
                <a href="{{ route('client.credentials.show') }}" class="list-group-item list-group-item-action">
                    <i class="fa fa-lock me-2"></i> Ganti Password
                </a>
                <a href="{{ route('client.invoices.index') }}" class="list-group-item list-group-item-action">
                    <i class="fa fa-receipt me-2"></i> Tagihan & Pembayaran
                </a>
                <a href="{{ route('tickets.index') }}" class="list-group-item list-group-item-action">
                    <i class="fa fa-ticket me-2"></i> Tiket Laporan
                </a>
                <a href="{{ route('client.connection') }}" class="list-group-item list-group-item-action">
                    <i class="fa fa-signal me-2"></i> Info Koneksi
                </a>
            </div>
        </div>
        <div class="col-md-9">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#payment" role="tab">
                        <i class="fa fa-clock-o"></i> Histori Pembayaran
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#activity" role="tab">
                        Aktivitas Terakhir
                    </a>
                </li>
            </ul>
            <div class="tab-content border-start border-end border-bottom p-3 shadow-sm">
                <div class="tab-pane fade show active" id="payment" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Tanggal</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentInvoices as $inv)
                                    <tr>
                                        <td>{{ $inv->code }}</td>
                                        <td>{{ $inv->created_at?->format('d M Y') }}</td>
                                        <td>Rp {{ number_format($inv->amount,0,',','.') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $inv->status === 'paid' ? 'success' : ($inv->status === 'pending' ? 'warning' : 'secondary') }}">
                                                {{ strtoupper($inv->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">Tidak ada data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="activity" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Aktivitas</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTickets as $t)
                                    <tr>
                                        <td>{{ $t->created_at?->format('d M Y H:i') }}</td>
                                        <td>Tiket: {{ $t->subject ?? 'No subject' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $t->status === 'open' ? 'warning' : ($t->status === 'closed' ? 'secondary' : 'info') }}">
                                                {{ strtoupper($t->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">Belum ada aktivitas</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if(!is_null($dueSeconds))
<script>
    (function(){
        let remaining = {{ $dueSeconds }};
        const el = document.getElementById('due_countdown');
        if (!el) return;
        function fmt(sec){
            if (sec <= 0) return 'Jatuh tempo';
            const d = Math.floor(sec/86400);
            const h = Math.floor((sec%86400)/3600);
            const m = Math.floor((sec%3600)/60);
            const s = sec%60;
            return `${d}h ${h}j ${m}m ${s}d`;
        }
        el.textContent = fmt(remaining);
        setInterval(function(){
            remaining--;
            el.textContent = fmt(remaining);
        }, 1000);
    })();
</script>
@endif
@endpush
