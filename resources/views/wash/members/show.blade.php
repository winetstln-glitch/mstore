@extends('layouts.app')

@section('title', 'Detail Member Wash')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h4 class="mb-1">{{ $member->name }}</h4>
            <div class="text-muted">{{ $member->member_number }} | {{ $member->whatsapp }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('wash.members.card', $member) }}" class="btn btn-primary">Unduh Kartu PDF</a>
            <a href="{{ route('wash.members.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Level Membership</div>
                            <div class="fw-semibold">{{ $member->level?->name ?? 'Bronze Member' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Joined At</div>
                            <div class="fw-semibold">{{ $member->joined_at?->format('d M Y H:i') ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Total Transaksi</div>
                            <div class="fw-semibold">{{ number_format((int) $member->total_transactions, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Total Kunjungan</div>
                            <div class="fw-semibold">{{ number_format((int) $member->total_visits, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Total Spending</div>
                            <div class="fw-semibold">Rp {{ number_format((float) $member->total_spending, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white">
                    <strong>Kendaraan Member</strong>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Plat</th>
                                    <th>Jenis</th>
                                    <th>Merk</th>
                                    <th>Model</th>
                                    <th>Warna</th>
                                    <th>Tahun</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($member->vehicles as $vehicle)
                                    <tr>
                                        <td class="fw-semibold">{{ $vehicle->vehicle_plate }}</td>
                                        <td>{{ $vehicle->vehicle_type ?? '-' }}</td>
                                        <td>{{ $vehicle->brand ?? '-' }}</td>
                                        <td>{{ $vehicle->model ?? '-' }}</td>
                                        <td>{{ $vehicle->color ?? '-' }}</td>
                                        <td>{{ $vehicle->year ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">Belum ada data kendaraan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <strong>Riwayat Transaksi Terakhir</strong>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>No. Transaksi</th>
                                    <th>Plat</th>
                                    <th>Metode</th>
                                    <th class="text-end">Total</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($member->transactions as $transaction)
                                    <tr>
                                        <td><a href="{{ route('wash.transactions.show', $transaction) }}">{{ $transaction->transaction_number }}</a></td>
                                        <td>{{ $transaction->vehicle_plate ?: '-' }}</td>
                                        <td>{{ strtoupper($transaction->payment_method) }}</td>
                                        <td class="text-end">Rp {{ number_format((float) $transaction->total_amount, 0, ',', '.') }}</td>
                                        <td>{{ $transaction->created_at?->format('d-m-Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">Belum ada transaksi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body text-center">
                    <div class="text-muted small mb-2">Digital Member Card</div>
                    <div class="fs-5 fw-bold">{{ $card->card_number }}</div>
                    <div class="mb-2">{{ $member->level?->name ?? 'Bronze Member' }}</div>
                    <img src="{{ $qrUrl }}" alt="QR Member" class="img-fluid rounded border p-2 bg-white mb-2" style="max-width: 220px;">
                    <div class="small text-muted">{{ $verificationUrl }}</div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <strong>Update Status Member</strong>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('wash.members.update', $member) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $member->email) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-control" rows="3">{{ old('address', $member->address) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" @selected(old('status', $member->status) === 'active')>Aktif</option>
                                <option value="inactive" @selected(old('status', $member->status) === 'inactive')>Tidak Aktif</option>
                                <option value="blacklist" @selected(old('status', $member->status) === 'blacklist')>Blacklist</option>
                            </select>
                        </div>
                        <button class="btn btn-primary w-100">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

