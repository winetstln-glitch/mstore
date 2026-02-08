@extends('layouts.app')

@section('title', __('Laporan Keuangan Pengurus'))

@section('content')
<div class="container-fluid">
    <!-- HEADER FILTER (Sesuai saran tambahan sebelumnya) -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-2">
            <form action="{{ route('finance.coordinator.detail', $coordinator->id) }}" method="GET" class="row g-3 align-items-end">
                
                <div class="col-md-4">
                    <label class="form-label small text-muted fw-bold">Pilih Periode</label>
                    <select name="month" class="form-select form-select-sm">
                        <option value="{{ \Carbon\Carbon::parse($startDate)->format('Y-m') }}">{{ \Carbon\Carbon::parse($startDate)->format('F Y') }}</option>
                        @for($i = 1; $i <= 6; $i++)
                        @php
                            $d = date('Y-m', strtotime("-$i months"));
                        @endphp
                        <option value="{{ $d }}">{{ \Carbon\Carbon::parse($d)->format('F Y') }}</option>
                        @endfor
                        <option value="all">Semua Periode</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">Filter Tipe</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">{{ __('Semua') }}</option>
                        <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}">{{ __('Pemasukan') }}</option>
                        <option value="expense" {{ request('type') == 'expenses' ? 'selected' : '' }}">{{ __('Pengeluaran') }}</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- HEADER INFO (Dinamis) -->
    <h1 class="h3 mb-4 text-gray-800">Laporan Keuangan Pengurus</h1>
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Nama Pengurus:</strong> {{ $coordinator->name }}</p>
                    <p><strong>Wilayah:</strong> {{ $coordinator->region->name ?? '-' }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p><strong>Periode Laporan:</strong> 
                        @if(request('month') && request('month') != 'all')
                            {{ \Carbon\Carbon::parse(request('month'))->format('F Y') }}
                        @else
                            {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- BAGIAN 1: RINGKASAN PENDAPATAN -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">1. Ringkasan Pendapatan</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 70%;">Keterangan Pendapatan</th>
                            <th class="text-end">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Pendapatan Member (Langganan)</td>
                            <td class="text-end fw-bold">{{ number_format($memberIncome ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Pendapatan Voucher</td>
                            <td class="text-end fw-bold">{{ number_format($voucherIncome ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="table-success">
                            <td class="fw-bold">TOTAL PENDAPATAN KOTOR</td>
                            <td class="text-end fw-bold fs-5">
                                {{ number_format($grossRevenue ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- BAGIAN 2: PENGURANGAN -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-danger">2. Potongan dan Beban Pengurus</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 70%;">Keterangan Potongan</th>
                            <th class="text-end">Nominal (Pengurang)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>(A) Komisi Pengurus</td>
                            <td class="text-end text-danger">({{ number_format($commission ?? 0, 0, ',', '.') }})</td>
                        </tr>
                        <tr>
                            <td>(B) Pengeluaran Operasional</td>
                            <td class="text-end text-danger">({{ number_format($expenses ?? 0, 0, ',', '.') }})</td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold">SISA HASIL USAHA (NETTO)</td>
                            <td class="text-end fw-bold">
                                {{ number_format($grossRevenue - $commission - $expenses, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- BAGIAN 3: REKONSILIASI KAS (CASH FLOW) -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">3. Posisi Saldo Akhir</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 70%;">Keterangan Arus Kas</th>
                            <th class="text-end">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Saldo Hasil Usaha (Dari Bagian 2)</td>
                            <td class="text-end">
                                {{ number_format($grossRevenue - $commission - $expenses, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td>Dikurang: Sudah Disetor ke Pusat</td>
                            <td class="text-end text-danger">({{ number_format($deposited ?? 0, 0, ',', '.') }})</td>
                        </tr>
                        <tr class="table-primary">
                            <td class="text-end fw-bold">SISA SALDO / WAJIB SETOR</td>
                            <td class="text-end fw-bold fs-5">
                                {{ number_format($grossRevenue - $commission - $expenses - $deposited, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- BAGIAN 4: DETAIL INVESTOR (JIKA ADA) -->
    @if(isset($investorDetails) && $investorDetails->count() > 0)
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-info">4. Rincian Dana Kas Investor</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Nama Investor</th>
                            <th class="text-end">Alokasi Dana Kas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($investorDetails as $row)
                        <tr>
                            <td>{{ $row->investor_name }}</td>
                            <td class="text-end">{{ number_format($row->cash_fund, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        <tr class="table-secondary">
                            <td class="text-end fw-bold">Total Dana Kas</td>
                            <td class="text-end fw-bold">
                                {{ number_format($investorDetails->sum('cash_fund'), 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- BAGIAN 5: RIWAYAT TRANSAKSI MANUAL -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-secondary">5. Riwayat Transaksi Manual</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th width="70" class="text-center">Tanggal</th>
                            <th width="50" class="text-center">Tipe</th>
                            <th width="120">Kategori</th>
                            <th>Keterangan / Deskripsi</th>
                            <th width="110" class="text-end">Jumlah (Rp)</th>
                            <th width="100" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr>
                            <td class="text-center">{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $transaction->type == 'income' ? 'success' : 'danger' }} bg-opacity-10 text-{{ $transaction->type == 'income' ? 'success' : 'danger' }} border border-{{ $transaction->type == 'income' ? 'success' : 'danger' }}">
                                    {{ strtoupper($transaction->type) }}
                                </span>
                            </td>
                            <td>{{ $transaction->category }}</td>
                            <td>
                                <div class="fw-bold">{{ $transaction->category }}</div>
                                <div class="small text-muted">{{ $transaction->description }}</div>
                                @if($transaction->reference_number)
                                <div class="text-muted">Ref: {{ $transaction->reference_number }}</div>
                                @endif
                            </td>
                            <td class="text-end {{ $transaction->type == 'income' ? '' : 'text-danger' }}">
                                @if($transaction->type != 'income') ( @endif
                                {{ number_format($transaction->amount, 0, ',', '.') }}
                                @if($transaction->type != 'income') ) @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editTransactionModal"
                                    data-action="{{ route('finance.update', $transaction->id) }}"
                                    data-type="{{ $transaction->type }}"
                                    data-category="{{ $transaction->category }}"
                                    data-amount="{{ $transaction->amount }}"
                                    data-date="{{ $transaction->transaction_date->format('Y-m-d') }}"
                                    data-coordinator="{{ $transaction->coordinator_id }}"
                                    data-description="{{ $transaction->description }}"
                                    data-ref="{{ $transaction->reference_number }}">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <form action="{{ route('finance.destroy', $transaction->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted" style="padding: 20px;">
                                Tidak ada transaksi manual yang diinput pada periode ini.
                            </td>
                        </tr>
                        @endforelse
                        
                        <!-- Total Transaksi Manual -->
                        @if($transactions->count() > 0)
                        <tr class="table-secondary fw-bold">
                            <td colspan="4" class="text-end">Total Transaksi Manual:</td>
                            <td class="text-end">
                                {{ number_format($transactions->where('type', 'income')->sum('amount') - $transactions->where('type', 'expense')->sum('amount'), 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- FOOTER & TANDA TANGAN -->
    <div class="alert alert-secondary border-top border-dashed small text-muted mt-4">
        <em>Catatan: Nilai Komisi Pengurus dihitung otomatis oleh sistem berdasarkan % dari pendapatan kotor. Laporan ini digenerate pada {{ now()->format('d M Y H:i:s') }}.</em>
    </div>

    <!-- AREA TANDA TANGAN -->
    <div class="card shadow mb-4 border-0">
        <div class="card-body">
            <div class="d-flex justify-content-between mt-4 px-5">
                <div class="text-center">
                    <div class="mb-5">Mengetahui,</div>
                    <div class="fw-bold text-decoration-underline">Manager Finance</div>
                </div>
                <div class="text-center">
                    <div class="mb-5">Diperiksa,</div>
                    <div class="fw-bold text-decoration-underline">Staff Admin</div>
                </div>
                @if($coordinator)
                <div class="text-center">
                    <div class="mb-5">{{ __('Pengurus') }},</div>
                    <div class="fw-bold text-decoration-underline">{{ $coordinator->name }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

</div>

<!-- Edit Transaction Modal -->
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Edit Transaction') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTransactionForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type') }}</label>
                        <select name="type" id="edit_type" class="form-select" required>
                            <option value="income">{{ __('Pemasukan') }}</option>
                            <option value="expense">{{ __('Pengeluaran') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Category') }}</label>
                        <select name="category" id="edit_category" class="form-select" required>
                            <option value="Member Income">{{ __('Iuran Bulanan (Member Income)') }}</option>
                            <option value="Voucher Income">{{ __('Voucher (Voucher Income)') }}</option>
                            <option value="Installation Fee">{{ __('Biaya Pasang') }}</option>
                            <option value="Biaya Operasional">{{ __('Biaya Operasional (Bensin/Makan)') }}</option>
                            <option value="Pembelian Alat">{{ __('Beli Alat (Stok)') }}</option>
                            <option value="Gaji">{{ __('Gaji') }}</option>
                            <option value="Deposit to Company">{{ __('Setor ke Kantor (Deposit)') }}</option>
                            <option value="Ambil Barang">{{ __('Ambil Barang (Stok)') }}</option>
                            <option value="Lainnya">{{ __('Lainnya') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Amount') }}</label>
                        <input type="number" name="amount" id="edit_amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Coordinator') }} (Optional)</label>
                        <select name="coordinator_id" id="edit_coordinator_id" class="form-select">
                            <option value="{{ $coordinator->id }}">{{ $coordinator->name }}</option>
                             @foreach(\App\Models\Coordinator::where('id', '!=', $coordinator->id)->get() as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Date') }}</label>
                        <input type="date" name="transaction_date" id="edit_transaction_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                         <label class="form-label">{{ __('Ref') }}</label>
                         <input type="text" name="reference_number" id="edit_reference_number" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editModal = document.getElementById('editTransactionModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const action = button.getAttribute('data-action');
                const type = button.getAttribute('data-type');
                const category = button.getAttribute('data-category');
                const amount = button.getAttribute('data-amount');
                const date = button.getAttribute('data-date');
                const coordinator = button.getAttribute('data-coordinator');
                const description = button.getAttribute('data-description');
                const ref = button.getAttribute('data-ref');

                const form = document.getElementById('editTransactionForm');
                form.action = action;

                document.getElementById('edit_type').value = type;
                document.getElementById('edit_category').value = category;
                document.getElementById('edit_amount').value = amount;
                document.getElementById('edit_transaction_date').value = date;
                document.getElementById('edit_coordinator_id').value = coordinator || '';
                document.getElementById('edit_description').value = description;
                document.getElementById('edit_reference_number').value = ref;
            });
        }
    });
</script>
@endpush
@endsection
