@extends('layouts.app')

@section('title', __('Penagihan Hutang ATK'))

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-3">Penagihan Hutang</h5>
            <form class="row g-2" method="get">
                <div class="col-md-3">
                    <label class="form-label">Pengurus</label>
                    <select name="coordinator_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach($coordinators as $c)
                            <option value="{{ $c->id }}" {{ request('coordinator_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        <option value="belum" {{ request('status')=='belum' ? 'selected' : '' }}>Belum Lunas</option>
                        <option value="lunas" {{ request('status')=='lunas' ? 'selected' : '' }}>Lunas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jatuh Tempo Mulai</label>
                    <input type="date" name="due_start" class="form-control" value="{{ request('due_start') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jatuh Tempo Selesai</label>
                    <input type="date" name="due_end" class="form-control" value="{{ request('due_end') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary me-2">Filter</button>
                    <a href="{{ route('atk.debts.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>No. Transaksi</th>
                            <th>Pelanggan</th>
                            <th>Pengurus</th>
                            <th>Total</th>
                            <th>Dibayar</th>
                            <th>Sisa</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($debts as $trx)
                        @php
                            $sisa = ($trx->total_amount ?? 0) - ($trx->amount_paid ?? 0);
                        @endphp
                        <tr>
                            <td>{{ optional($trx->created_at)->format('Y-m-d') }}</td>
                            <td>{{ $trx->transaction_number }}</td>
                            <td>{{ $trx->customer_name }}<br><small class="text-muted">{{ $trx->customer_phone }}</small></td>
                            <td>{{ $trx->coordinator->name ?? '-' }}</td>
                            <td>Rp {{ number_format($trx->total_amount,0,',','.') }}</td>
                            <td>Rp {{ number_format($trx->amount_paid,0,',','.') }}</td>
                            <td class="{{ $sisa>0 ? 'text-danger' : 'text-success' }}">Rp {{ number_format($sisa,0,',','.') }}</td>
                            <td>{{ $trx->due_date ? \Carbon\Carbon::parse($trx->due_date)->format('Y-m-d') : '-' }}</td>
                            <td>
                                @if($trx->is_settled)
                                    <span class="badge bg-success">Lunas</span>
                                @else
                                    <span class="badge bg-warning text-dark">Belum</span>
                                @endif
                            </td>
                            <td>
                                @if(!$trx->is_settled)
                                <button class="btn btn-sm btn-primary" onclick="openSettle({{ $trx->id }}, {{ $sisa }})">Bayar</button>
                                @else
                                <button class="btn btn-sm btn-secondary" disabled>Lunas</button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center py-4 text-muted">Tidak ada data hutang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $debts->links() }}
        </div>
    </div>
</div>

<div class="modal fade" id="settleModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pelunasan Hutang</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="settleTrxId">
        <div class="mb-3">
            <label class="form-label">Metode</label>
            <select id="settleMethod" class="form-select">
                <option value="cash">Cash</option>
                <option value="transfer">Transfer</option>
                <option value="qris">QRIS</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Jumlah Bayar</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" class="form-control" id="settleAmount" min="1">
            </div>
            <div class="form-text">Sisa: <span id="settleRemainText">Rp 0</span></div>
        </div>
        <div class="mb-3">
            <label class="form-label">Jatuh Tempo Baru (opsional)</label>
            <input type="date" class="form-control" id="settleDueDate">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" onclick="submitSettle()">Simpan</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
let settleModal;
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('settleModal');
    if (window.bootstrap) {
        settleModal = new bootstrap.Modal(el);
    } else {
        el.classList.add('show');
        el.style.display = 'none';
    }
});
function openSettle(id, remain) {
    document.getElementById('settleTrxId').value = id;
    document.getElementById('settleAmount').value = remain;
    document.getElementById('settleRemainText').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(remain);
    if (settleModal) settleModal.show(); else {
        document.getElementById('settleModal').style.display = 'block';
    }
}
function submitSettle() {
    const id = document.getElementById('settleTrxId').value;
    const pay = parseFloat(document.getElementById('settleAmount').value || 0);
    const method = document.getElementById('settleMethod').value;
    if (pay <= 0) {
        alert('Jumlah bayar tidak valid');
        return;
    }
    const due = document.getElementById('settleDueDate').value;
    fetch('{{ url("atk/debts") }}/' + id + '/settle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ pay_amount: pay, method, due_date: due || null })
    }).then(r => r.json()).then(res => {
        if (res.success) {
            location.reload();
        } else {
            alert(res.message || 'Gagal menyimpan');
        }
    }).catch(() => alert('Terjadi kesalahan'));
}
</script>
@endpush
@endsection
