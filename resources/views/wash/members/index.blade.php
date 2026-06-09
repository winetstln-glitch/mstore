@extends('layouts.app')

@section('title', 'Wash Members')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h4 class="mb-1">Member GT Wash</h4>
            <div class="text-muted">Database member digital, level, kendaraan, dan spending.</div>
        </div>
        <a href="{{ route('wash.members.levels') }}" class="btn btn-outline-primary">Membership Level</a>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="q" class="form-control" placeholder="Cari nomor member / nama / WhatsApp / plat" value="{{ $q }}">
                </div>
                <div class="col-md-3">
                    <select name="level" class="form-select">
                        <option value="">Semua Level</option>
                        @foreach($levels as $levelOption)
                            <option value="{{ $levelOption->code }}" @selected($level === $levelOption->code)>{{ $levelOption->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="active" @selected($status === 'active')>Aktif</option>
                        <option value="inactive" @selected($status === 'inactive')>Tidak Aktif</option>
                        <option value="blacklist" @selected($status === 'blacklist')>Blacklist</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Level</th>
                            <th>Kendaraan</th>
                            <th class="text-end">Transaksi</th>
                            <th class="text-end">Kunjungan</th>
                            <th class="text-end">Spending</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($members as $member)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $member->name }}</div>
                                    <div class="small text-muted">{{ $member->member_number }} | {{ $member->whatsapp }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $member->level?->name ?? 'Bronze Member' }}</span>
                                </td>
                                <td>
                                    @if($member->vehicles->count() > 0)
                                        {{ $member->vehicles->pluck('vehicle_plate')->join(', ') }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format((int) $member->total_transactions, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((int) $member->total_visits, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format((float) $member->total_spending, 0, ',', '.') }}</td>
                                <td>
                                    @php
                                        $badge = $member->status === 'active' ? 'success' : ($member->status === 'inactive' ? 'secondary' : 'danger');
                                        $label = $member->status === 'active' ? 'Aktif' : ($member->status === 'inactive' ? 'Tidak Aktif' : 'Blacklist');
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">{{ $label }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('wash.members.show', $member) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Belum ada member wash.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $members->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

