@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-warning">
            <div class="card-header py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-coins me-2 text-warning"></i>{{ __('Rincian Kasbon Teknisi') }}</h5>
                    @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('staf-keuangan'))
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addKasbonModal">
                                <i class="fa-solid fa-plus me-1"></i>{{ __('Tambah Kasbon Biasa') }}
                            </button>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addKasbonLoanModal">
                                <i class="fa-solid fa-plus me-1"></i>{{ __('Tambah Kasbon Angsuran') }}
                            </button>
                        </div>
                    @endif
                </div>
                
                <form action="{{ route('technicians.kasbon.index') }}" method="GET" class="w-100 border-top pt-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-bold text-muted mb-1">{{ __('Filter Teknisi') }}</label>
                            <select name="user_id" class="form-select form-select-sm js-search-select">
                                <option value="">{{ __('Semua Teknisi') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->role->name ?? __('User') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-bold text-muted mb-1">{{ __('Status') }}</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">{{ __('Semua') }}</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Belum Diproses') }}</option>
                                <option value="processed" {{ request('status') === 'processed' ? 'selected' : '' }}>{{ __('Sudah Diproses') }}</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-bold text-muted mb-1">{{ __('Tanggal Mulai') }}</label>
                            <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-bold text-muted mb-1">{{ __('Tanggal Selesai') }}</label>
                            <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-6 col-md-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <i class="fa-solid fa-filter me-1"></i>{{ __('Filter') }}
                            </button>
                            <a href="{{ route('technicians.kasbon.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body">
                <!-- Recap Section -->
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-chart-pie me-2"></i>{{ __('Rekap per Teknisi') }}</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Teknisi') }}</th>
                                <th class="text-end">{{ __('Total Kasbon Biasa (Pending)') }}</th>
                                <th class="text-end">{{ __('Total Pinjaman Aktif') }}</th>
                                <th class="text-end">{{ __('Total Cicilan') }}</th>
                                <th class="text-end">{{ __('Sisa Pinjaman') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recap as $item)
                                <tr>
                                    <td>{{ $item['user']->name }}</td>
                                    <td class="text-end text-danger fw-bold">Rp {{ number_format($item['total_kasbon_biasa'], 0, ',', '.') }}</td>
                                    <td class="text-end text-warning fw-bold">Rp {{ number_format($item['total_pinjaman_aktif'], 0, ',', '.') }}</td>
                                    <td class="text-end text-success fw-bold">Rp {{ number_format($item['total_cicilan'], 0, ',', '.') }}</td>
                                    <td class="text-end text-danger fw-bold">Rp {{ number_format($item['sisa_pinjaman'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Kasbon Biasa Section -->
                <h6 class="fw-bold mb-3 mt-4"><i class="fa-solid fa-file-invoice-dollar me-2"></i>{{ __('Kasbon Biasa (Sekali Potong)') }}</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-hover align-middle table-responsive-mobile">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('No') }}</th>
                                <th>{{ __('Teknisi') }}</th>
                                <th>{{ __('Tanggal') }}</th>
                                <th>{{ __('Jumlah') }}</th>
                                <th>{{ __('Keterangan') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end pe-3">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($adjustments as $index => $adjustment)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-medium">{{ $adjustment->user->name }}</div>
                                    </td>
                                    <td class="small text-muted">
                                        {{ $adjustment->date->translatedFormat('d M Y') }}
                                    </td>
                                    <td class="fw-bold text-danger">
                                        Rp {{ number_format($adjustment->amount, 0, ',', '.') }}
                                    </td>
                                    <td>{{ $adjustment->description ?? '-' }}</td>
                                    <td>
                                        @if($adjustment->status === 'pending')
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ __('Belum Diproses') }}</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">{{ __('Sudah Diproses') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('staf-keuangan'))
                                            @if($adjustment->status !== 'processed')
                                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editKasbonModal-{{ $adjustment->id }}">
                                                    <i class="fa-solid fa-edit"></i>
                                                </button>
                                                <form action="{{ route('salary-adjustments.destroy', $adjustment) }}" method="POST" class="d-inline" data-confirm="{{ __('Hapus kasbon ini?') }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </td>
                                </tr>

                                <!-- Edit Kasbon Biasa Modal -->
                                <div class="modal fade" id="editKasbonModal-{{ $adjustment->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('salary-adjustments.update', $adjustment) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">{{ __('Edit Kasbon Biasa') }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">{{ __('Teknisi') }}</label>
                                                        <input type="text" class="form-control" value="{{ $adjustment->user->name }}" readonly>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="amount-{{ $adjustment->id }}" class="form-label fw-bold">{{ __('Jumlah') }}</label>
                                                        <input type="number" name="amount" id="amount-{{ $adjustment->id }}" class="form-control" required min="1" value="{{ $adjustment->amount }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="date-{{ $adjustment->id }}" class="form-label fw-bold">{{ __('Tanggal') }}</label>
                                                        <input type="date" name="date" id="date-{{ $adjustment->id }}" class="form-control" required value="{{ $adjustment->date->format('Y-m-d') }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="description-{{ $adjustment->id }}" class="form-label fw-bold">{{ __('Keterangan') }}</label>
                                                        <textarea name="description" id="description-{{ $adjustment->id }}" class="form-control" rows="3">{{ $adjustment->description }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                                                    <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fa-solid fa-inbox text-muted fa-3x mb-2"></i>
                                        <div class="text-muted">{{ __('Tidak ada data kasbon biasa') }}</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mb-4">
                    {{ $adjustments->appends(request()->query())->links() }}
                </div>

                <!-- Kasbon Angsuran Section -->
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-credit-card me-2"></i>{{ __('Kasbon Angsuran') }}</h6>
                @forelse($loans as $loan)
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="fw-medium">
                                {{ $loan->user->name }}
                                <span class="badge bg-{{ $loan->status === 'active' ? 'warning' : 'success' }} ms-2">
                                    {{ $loan->status === 'active' ? __('Aktif') : __('Selesai') }}
                                </span>
                            </div>
                            @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('staf-keuangan'))
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editKasbonLoanModal-{{ $loan->id }}">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addInstallmentModal-{{ $loan->id }}">
                                        <i class="fa-solid fa-receipt me-1"></i>{{ __('Tambah Cicilan') }}
                                    </button>
                                    <form action="{{ route('kasbon-loans.destroy', $loan) }}" method="POST" class="d-inline" data-confirm="{{ __('Hapus kasbon angsuran ini?') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <strong>{{ __('Pokok Pinjaman') }}</strong>
                                    <div class="text-danger fw-bold">Rp {{ number_format($loan->principal_amount, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <strong>{{ __('Sisa Pinjaman') }}</strong>
                                    <div class="text-warning fw-bold">Rp {{ number_format($loan->remaining, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <strong>{{ __('Tanggal Mulai') }}</strong>
                                    <div>{{ $loan->start_date->translatedFormat('d M Y') }}</div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <strong>{{ __('Tenor') }}</strong>
                                    <div>{{ $loan->tenor_months ? $loan->tenor_months . ' ' . __('bulan') : '-' }}</div>
                                </div>
                            </div>
                            @if($loan->description)
                                <div class="mt-2">
                                    <strong>{{ __('Keterangan') }}</strong>
                                    <div>{{ $loan->description }}</div>
                                </div>
                            @endif
                            <hr>
                            <h6 class="fw-bold mb-2">{{ __('Riwayat Cicilan') }}</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Tanggal') }}</th>
                                            <th>{{ __('Jumlah') }}</th>
                                            <th>{{ __('Keterangan') }}</th>
                                            <th>{{ __('Potong Gaji?') }}</th>
                                            <th class="text-end pe-3">{{ __('Aksi') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($loan->installments as $installment)
                                            <tr>
                                                <td>{{ $installment->date->translatedFormat('d M Y') }}</td>
                                                <td class="text-danger fw-bold">Rp {{ number_format($installment->amount, 0, ',', '.') }}</td>
                                                <td>{{ $installment->description ?? '-' }}</td>
                                                <td>
                                                    @if($installment->salary_adjustment_id)
                                                        <span class="badge bg-success-subtle text-success-emphasis">{{ __('Ya') }}</span>
                                                    @else
                                                        <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ __('Tidak') }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-end pe-3">
                                                    @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('staf-keuangan'))
                                                        <form action="{{ route('kasbon-loans.installments.destroy', [$loan, $installment]) }}" method="POST" class="d-inline" data-confirm="{{ __('Hapus cicilan ini?') }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-3">
                                                    <div class="text-muted">{{ __('Tidak ada riwayat cicilan') }}</div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Kasbon Angsuran Modal -->
                    <div class="modal fade" id="editKasbonLoanModal-{{ $loan->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('kasbon-loans.update', $loan) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{ __('Edit Kasbon Angsuran') }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">{{ __('Teknisi') }}</label>
                                            <input type="text" class="form-control" value="{{ $loan->user->name }}" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="principal_amount-{{ $loan->id }}" class="form-label fw-bold">{{ __('Pokok Pinjaman') }}</label>
                                            <input type="number" name="principal_amount" id="principal_amount-{{ $loan->id }}" class="form-control" required min="1" value="{{ $loan->principal_amount }}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="start_date-{{ $loan->id }}" class="form-label fw-bold">{{ __('Tanggal Mulai') }}</label>
                                            <input type="date" name="start_date" id="start_date-{{ $loan->id }}" class="form-control" required value="{{ $loan->start_date->format('Y-m-d') }}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="tenor_months-{{ $loan->id }}" class="form-label fw-bold">{{ __('Tenor (bulan)') }}</label>
                                            <input type="number" name="tenor_months" id="tenor_months-{{ $loan->id }}" class="form-control" min="1" value="{{ $loan->tenor_months }}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="monthly_installment-{{ $loan->id }}" class="form-label fw-bold">{{ __('Cicilan Bulanan (opsional)') }}</label>
                                            <input type="number" name="monthly_installment" id="monthly_installment-{{ $loan->id }}" class="form-control" min="0" value="{{ $loan->monthly_installment }}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="description-{{ $loan->id }}" class="form-label fw-bold">{{ __('Keterangan') }}</label>
                                            <textarea name="description" id="description-{{ $loan->id }}" class="form-control" rows="3">{{ $loan->description }}</textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                                        <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Add Installment Modal -->
                    <div class="modal fade" id="addInstallmentModal-{{ $loan->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('kasbon-loans.installments.store', $loan) }}" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{ __('Tambah Cicilan') }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="installment_amount-{{ $loan->id }}" class="form-label fw-bold">{{ __('Jumlah') }}</label>
                                            <input type="number" name="amount" id="installment_amount-{{ $loan->id }}" class="form-control" required min="1" max="{{ $loan->remaining }}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="installment_date-{{ $loan->id }}" class="form-label fw-bold">{{ __('Tanggal') }}</label>
                                            <input type="date" name="date" id="installment_date-{{ $loan->id }}" class="form-control" required value="{{ date('Y-m-d') }}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="installment_description-{{ $loan->id }}" class="form-label fw-bold">{{ __('Keterangan') }}</label>
                                            <textarea name="description" id="installment_description-{{ $loan->id }}" class="form-control" rows="3"></textarea>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" name="potong_gaji" id="potong_gaji-{{ $loan->id }}" class="form-check-input" checked>
                                            <label for="potong_gaji-{{ $loan->id }}" class="form-check-label fw-bold">{{ __('Potong via Gaji') }}</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                                        <button type="submit" class="btn btn-success">{{ __('Simpan') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 mb-4">
                        <i class="fa-solid fa-inbox text-muted fa-3x mb-2"></i>
                        <div class="text-muted">{{ __('Tidak ada data kasbon angsuran') }}</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Kasbon Biasa -->
<div class="modal fade" id="addKasbonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('salary-adjustments.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Tambah Kasbon Biasa') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="type" value="kasbon">
                    
                    <div class="mb-3">
                        <label for="user_id" class="form-label fw-bold">{{ __('Teknisi') }}</label>
                        <select name="user_id" id="user_id" class="form-select" required>
                            <option value="">{{ __('Pilih Teknisi') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label fw-bold">{{ __('Jumlah') }}</label>
                        <input type="number" name="amount" id="amount" class="form-control" required min="1">
                    </div>
                    
                    <div class="mb-3">
                        <label for="date" class="form-label fw-bold">{{ __('Tanggal') }}</label>
                        <input type="date" name="date" id="date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">{{ __('Keterangan') }}</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Kasbon Angsuran -->
<div class="modal fade" id="addKasbonLoanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('kasbon-loans.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Tambah Kasbon Angsuran') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="loan_user_id" class="form-label fw-bold">{{ __('Teknisi') }}</label>
                        <select name="user_id" id="loan_user_id" class="form-select" required>
                            <option value="">{{ __('Pilih Teknisi') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="loan_principal_amount" class="form-label fw-bold">{{ __('Pokok Pinjaman') }}</label>
                        <input type="number" name="principal_amount" id="loan_principal_amount" class="form-control" required min="1">
                    </div>
                    <div class="mb-3">
                        <label for="loan_start_date" class="form-label fw-bold">{{ __('Tanggal Mulai') }}</label>
                        <input type="date" name="start_date" id="loan_start_date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label for="loan_tenor_months" class="form-label fw-bold">{{ __('Tenor (bulan, opsional)') }}</label>
                        <input type="number" name="tenor_months" id="loan_tenor_months" class="form-control" min="1">
                    </div>
                    <div class="mb-3">
                        <label for="loan_monthly_installment" class="form-label fw-bold">{{ __('Cicilan Bulanan (opsional)') }}</label>
                        <input type="number" name="monthly_installment" id="loan_monthly_installment" class="form-control" min="0">
                    </div>
                    <div class="mb-3">
                        <label for="loan_description" class="form-label fw-bold">{{ __('Keterangan') }}</label>
                        <textarea name="description" id="loan_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('Simpan') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
