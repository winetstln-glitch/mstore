@extends('layouts.app')
@section('title', 'Laporan Wash')

@section('content')
<div class="container-fluid py-4 wash-reports-page">
    
    <!-- Judul & Filter -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 report-toolbar">
                <h5 class="mb-0 fw-bold">Laporan Keuangan Wash</h5>
                <div class="btn-group wash-report-export">
                    <a class="btn btn-sm btn-outline-secondary" id="btnExportPdf">
                        <i class="bi bi-file-earmark-pdf"></i> Ekspor PDF
                    </a>
                    <a class="btn btn-sm btn-outline-success" id="btnExportExcel">
                        <i class="bi bi-file-earmark-excel"></i> Ekspor Excel
                    </a>
                </div>
            </div>

            <!-- Navigasi Tab -->
            <ul class="nav nav-tabs wash-report-tabs" id="reportTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily-content" type="button">Harian</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly-content" type="button">Bulanan</button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Isi Laporan -->
    <div class="tab-content" id="reportTabContent">
        
        <!-- =========================== -->
        <!-- TAB HARIAN -->
        <!-- =========================== -->
        <div class="tab-pane fade show active" id="daily-content">
            
            <!-- Filter Tanggal -->
            <form method="get" class="row g-2 align-items-center mb-3 justify-content-end daily-filter-form">
                <input type="hidden" name="view" value="daily">
                <div class="col-auto"><label class="form-label mb-0 fw-bold">Dari:</label></div>
                <div class="col-auto"><input type="date" name="start_date" value="{{ $startDate }}" class="form-control"></div>
                <div class="col-auto"><label class="form-label mb-0 fw-bold">Sampai:</label></div>
                <div class="col-auto"><input type="date" name="end_date" value="{{ $endDate }}" class="form-control"></div>
                <div class="col-auto">
                    <select name="vehicle_plate" class="form-select">
                        <option value="">Semua Plat</option>
                        @foreach(($knownVehiclePlates ?? []) as $plateOption)
                            <option value="{{ $plateOption }}" {{ ($vehiclePlate ?? '') === $plateOption ? 'selected' : '' }}>{{ $plateOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-primary">Lihat Data</button></div>
            </form>

            <!-- RINGKASAN KEUANGAN WASH -->
            <div class="table-responsive table-responsive-mobile mb-4">
                <table class="table table-bordered table mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th style="width: 33%;">Pemasukan Wash</th>
                            <th style="width: 33%;">Pengeluaran Wash</th>
                            <th style="width: 34%;">Laba / Rugi Wash</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="text-end align-middle" style="font-size: 1.1rem;">
                            <td class="text-success fw-bold">Rp {{ number_format($dailyWashIncome,0,',','.') }}</td>
                            <td class="text-danger fw-bold">Rp {{ number_format($dailyWashExpense,0,',','.') }}</td>
                            <td class="fw-bold">Rp {{ number_format($dailyWashIncome - $dailyWashExpense,0,',','.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- RINGKASAN KEUANGAN CAFFE / WARKOP -->
            <div class="table-responsive table-responsive-mobile mb-4">
                <table class="table table-bordered table mb-0">
                    <thead class="table-warning">
                        <tr>
                            <th style="width: 33%;">Modal Awal Caffe / Warkop</th>
                            <th style="width: 33%;">Pendapatan Caffe / Warkop</th>
                            <th style="width: 34%;">Selisih Caffe / Warkop</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="text-end align-middle fw-bold">
                            <td class="text-danger">Rp {{ number_format($dailyCaffeInitialCapital,0,',','.') }}</td>
                            <td class="text-success">Rp {{ number_format($dailyCaffeRevenue,0,',','.') }}</td>
                            <td class="{{ ($dailyCaffeRevenue - $dailyCaffeInitialCapital) < 0 ? 'text-danger' : '' }}">Rp {{ number_format($dailyCaffeRevenue - $dailyCaffeInitialCapital,0,',','.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- RINGKASAN KEUANGAN TOTAL -->
            <div class="table-responsive table-responsive-mobile mb-4">
                <table class="table table-bordered table mb-0">
                    <thead class="table">
                        <tr>
                            <th style="width: 33%;">Total Pemasukan</th>
                            <th style="width: 33%;">Total Pengeluaran</th>
                            <th style="width: 34%;">Laba / Rugi Kotor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="text-end align-middle" style="font-size: 1.1rem;">
                            <td class="text-success fw-bold">Rp {{ number_format($dailyIncome,0,',','.') }}</td>
                            <td class="text-danger fw-bold">Rp {{ number_format($dailyExpense,0,',','.') }}</td>
                            <td class="fw-bold">Rp {{ number_format($dailyIncome - $dailyExpense,0,',','.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TABEL 1: RINCIAN PEMASUKAN (Full Width) -->
            <h6 class="fw-bold mt-4 text-decoration-underline">A. Rincian Pemasukan (Harian)</h6>
            <div class="table-responsive table-responsive-mobile mb-4">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="table">
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 10%;">Tanggal</th>
                            <th style="width: 10%;">Waktu</th>
                            <th style="width: 10%;">No. Antri</th>
                            <th style="width: 15%;">No. Transaksi</th>
                            <th style="width: 15%;">Kasir</th>
                            <th style="width: 15%;">Metode Pembayaran</th>
                            <th class="text-end" style="width: 20%;">Nominal (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyIncomeRows as $index => $r)
                        <tr @if(($r->notes ?? null) === 'bonus_cuci_10x') class="table-success" @endif>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $r->created_at->format('Y-m-d') }}</td>
                            <td>{{ $r->created_at->format('H:i') }}</td>
                            <td>
                                {{ $r->queue_number ?? '-' }}
                                @if(($r->notes ?? null) === 'bonus_cuci_10x')
                                    <br><span class="badge bg-success ms-1 mt-1"><i class="fa-solid fa-gift me-1"></i>Bonus Gratis</span>
                                @endif
                            </td>
                            <td class="font-monospace">{{ $r->transaction_number }}</td>
                            <td>{{ $r->user->name ?? '-' }}</td>
                            <td>
                                @if(($r->notes ?? null) === 'bonus_cuci_10x')
                                    <span class="badge bg-success mb-1"><i class="fa-solid fa-gift me-1"></i>BONUS</span><br>
                                @endif
                                <span class="badge bg-secondary">{{ strtoupper($r->payment_method) }}</span>
                            </td>
                            <td class="text-end">
                                {{ number_format($r->total_amount,0,',','.') }}
                                @if(($r->notes ?? null) === 'bonus_cuci_10x' && ($r->discount_amount ?? 0) > 0)
                                    <br><small class="text-success fw-bold"><i class="fa-solid fa-percent me-1"></i>Diskon Bonus: Rp {{ number_format($r->discount_amount,0,',','.') }}</small>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center py-3">Tidak ada data pemasukan</td></tr>
                        @endforelse
                        
                        <!-- Total Footer -->
                        <tr class="table fw-bold">
                            <td colspan="7" class="text-end">Total Pemasukan:</td>
                            <td class="text-end">Rp {{ number_format($dailyIncome,0,',','.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TABEL 2: RINCIAN PENGELUARAN (Full Width) -->
            <h6 class="fw-bold mt-4 text-decoration-underline">B. Rincian Pengeluaran (Harian)</h6>
            <div class="table-responsive table-responsive-mobile mb-4">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="table">
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 20%;">Tanggal</th>
                            <th style="width: 40%;">Deskripsi / Keterangan</th>
                            <th class="text-end" style="width: 35%;">Nominal (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyExpenseRows as $index => $r)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($r->transaction_date)->format('Y-m-d') }}</td>
                            <td>{{ $r->description }}</td>
                            <td class="text-end text-danger">{{ number_format($r->amount,0,',','.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-3">Tidak ada data pengeluaran</td></tr>
                        @endforelse

                        <!-- Total Footer -->
                        <tr class="table fw-bold">
                            <td colspan="3" class="text-end">Total Pengeluaran:</td>
                            <td class="text-end">Rp {{ number_format($dailyExpense,0,',','.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TABEL 3 & 4: ANALISIS (Full Width, Stacked) -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <h6 class="fw-bold text-decoration-underline">C. Breakdown per Layanan</h6>
                    <table class="table table-bordered table-sm">
                        <thead><tr><th>Layanan</th><th class="text-end">Qty</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @forelse($dailyByService as $r)
                            <tr><td>{{ $r->service_name }}</td><td class="text-end">{{ number_format($r->total_qty,0,',','.') }}</td><td class="text-end">Rp {{ number_format($r->amount,0,',','.') }}</td></tr>
                            @empty
                            <tr><td colspan="3" class="text-center">-</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6 mb-4">
                    <h6 class="fw-bold text-decoration-underline">D. Breakdown per Metode Bayar</h6>
                    <table class="table table-bordered table-sm">
                        <thead><tr><th>Metode</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @php
                                $dailyCash = (float) (collect($dailyByPayment)->firstWhere('payment_method', 'cash')->amount ?? 0);
                                $dailyQris = (float) (collect($dailyByPayment)->firstWhere('payment_method', 'qris')->amount ?? 0);
                                $dailyTransfer = (float) (collect($dailyByPayment)->firstWhere('payment_method', 'transfer')->amount ?? 0);
                                $dailySetoranCash = $dailyCash - (float) $dailyExpense;
                                $loyaltyBonusCount = $dailyIncomeRows->filter(fn($r) => ($r->notes ?? null) === 'bonus_cuci_10x')->count();
                            @endphp
                            @forelse($dailyByPayment as $r)
                            <tr><td>{{ strtoupper($r->payment_method) }}</td><td class="text-end">Rp {{ number_format($r->amount,0,',','.') }}</td></tr>
                            @empty
                            <tr><td colspan="2" class="text-center">-</td></tr>
                            @endforelse
                            <tr class="table-warning fw-bold">
                                <td>Setoran Cash (Cash - Pengeluaran)</td>
                                <td class="text-end {{ $dailySetoranCash < 0 ? 'text-danger' : '' }}">Rp {{ number_format($dailySetoranCash,0,',','.') }}</td>
                            </tr>
                            @if($loyaltyBonusCount > 0)
                            <tr class="table-success">
                                <td><strong>Transaksi Bonus Gratis</strong></td>
                                <td class="text-end"><strong>{{ $loyaltyBonusCount }}x</strong></td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

        </div> <!-- End Daily Tab -->

        <!-- =========================== -->
        <!-- TAB BULANAN -->
        <!-- =========================== -->
        <div class="tab-pane fade" id="monthly-content">
            
            <!-- Filter Bulan -->
            <form method="get" class="row g-2 align-items-center mb-3 justify-content-end monthly-filter-form">
                <input type="hidden" name="view" value="monthly">
                <div class="col-auto"><label class="form-label mb-0 fw-bold">Bulan:</label></div>
                <div class="col-auto"><input type="month" name="month" value="{{ $month }}" class="form-control"></div>
                <div class="col-auto">
                    <select name="vehicle_plate" class="form-select">
                        <option value="">Semua Plat</option>
                        @foreach(($knownVehiclePlates ?? []) as $plateOption)
                            <option value="{{ $plateOption }}" {{ ($vehiclePlate ?? '') === $plateOption ? 'selected' : '' }}>{{ $plateOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-primary">Lihat Data</button></div>
            </form>

            <!-- RINGKASAN BULANAN WASH -->
            <div class="table-responsive table-responsive-mobile mb-4">
                <table class="table table-bordered table mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th style="width: 33%;">Pemasukan Wash (Bulanan)</th>
                            <th style="width: 33%;">Pengeluaran Wash (Bulanan)</th>
                            <th style="width: 34%;">Laba / Rugi Wash (Bulanan)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="text-end align-middle fw-bold">
                            <td class="text-success">Rp {{ number_format($monthlyWashIncome,0,',','.') }}</td>
                            <td class="text-danger">Rp {{ number_format($monthlyWashExpense,0,',','.') }}</td>
                            <td>Rp {{ number_format($monthlyWashIncome - $monthlyWashExpense,0,',','.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- RINGKASAN BULANAN CAFFE / WARKOP -->
            <div class="table-responsive table-responsive-mobile mb-4">
                <table class="table table-bordered table mb-0">
                    <thead class="table-warning">
                        <tr>
                            <th style="width: 33%;">Modal Awal Caffe / Warkop (Bulanan)</th>
                            <th style="width: 33%;">Pendapatan Caffe / Warkop (Bulanan)</th>
                            <th style="width: 34%;">Selisih Caffe / Warkop (Bulanan)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="text-end align-middle fw-bold">
                            <td class="text-danger">Rp {{ number_format($monthlyCaffeInitialCapital,0,',','.') }}</td>
                            <td class="text-success">Rp {{ number_format($monthlyCaffeRevenue,0,',','.') }}</td>
                            <td class="{{ ($monthlyCaffeRevenue - $monthlyCaffeInitialCapital) < 0 ? 'text-danger' : '' }}">Rp {{ number_format($monthlyCaffeRevenue - $monthlyCaffeInitialCapital,0,',','.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- RINGKASAN BULANAN TOTAL -->
            <div class="table-responsive table-responsive-mobile mb-4">
                <table class="table table-bordered table mb-0">
                    <thead class="table">
                        <tr>
                            <th style="width: 33%;">Total Pemasukan Bulanan</th>
                            <th style="width: 33%;">Total Pengeluaran Bulanan</th>
                            <th style="width: 34%;">Laba Bersih Bulanan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="text-end align-middle fw-bold">
                            <td class="text-success">Rp {{ number_format($monthlyIncome,0,',','.') }}</td>
                            <td class="text-danger">Rp {{ number_format($monthlyExpense,0,',','.') }}</td>
                            <td>Rp {{ number_format($monthlyIncome - $monthlyExpense,0,',','.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TABEL BULANAN -->
            <div class="row">
                <div class="col-12 mb-4">
                    <h6 class="fw-bold text-decoration-underline">Rekap Harian ({{ $month }})</h6>
                    <div class="table-responsive table-responsive-mobile">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 20%;">Tanggal</th>
                                    <th class="text-end" style="width: 26%;">Pemasukan</th>
                                    <th class="text-end" style="width: 26%;">Pengeluaran</th>
                                    <th class="text-end" style="width: 28%;">Laba / Rugi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $incomeMap = collect($monthlyDailyIncome)->keyBy('d');
                                    $expenseMap = collect($monthlyDailyExpense)->keyBy('d');
                                    $days = collect($incomeMap->keys())->merge($expenseMap->keys())->unique()->sort();
                                @endphp
                                @forelse($days as $d)
                                @php
                                    $inc = (float)($incomeMap[$d]->total ?? 0);
                                    $exp = (float)($expenseMap[$d]->total ?? 0);
                                @endphp
                                <tr>
                                    <td>{{ $d }}</td>
                                    <td class="text-end">Rp {{ number_format($inc,0,',','.') }}</td>
                                    <td class="text-end">Rp {{ number_format($exp,0,',','.') }}</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($inc - $exp,0,',','.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center">Tidak ada data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <h6 class="fw-bold text-decoration-underline">Statistik per Layanan (Bulanan)</h6>
                    <table class="table table-bordered table-sm">
                        <thead><tr><th>Layanan</th><th class="text-end">Qty</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @forelse($monthlyByService as $r)
                            <tr><td>{{ $r->service_name }}</td><td class="text-end">{{ number_format($r->total_qty,0,',','.') }}</td><td class="text-end">Rp {{ number_format($r->amount,0,',','.') }}</td></tr>
                            @empty
                            <tr><td colspan="3" class="text-center">-</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6 mb-4">
                    <h6 class="fw-bold text-decoration-underline">Statistik per Metode (Bulanan)</h6>
                    <table class="table table-bordered table-sm">
                        <thead><tr><th>Metode</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @php
                                $monthlyCash = (float) (collect($monthlyByPayment)->firstWhere('payment_method', 'cash')->amount ?? 0);
                                $monthlyQris = (float) (collect($monthlyByPayment)->firstWhere('payment_method', 'qris')->amount ?? 0);
                                $monthlyTransfer = (float) (collect($monthlyByPayment)->firstWhere('payment_method', 'transfer')->amount ?? 0);
                                $monthlySetoranCash = $monthlyCash - (float) $monthlyExpense;
                            @endphp
                            @forelse($monthlyByPayment as $r)
                            <tr><td>{{ strtoupper($r->payment_method) }}</td><td class="text-end">Rp {{ number_format($r->amount,0,',','.') }}</td></tr>
                            @empty
                            <tr><td colspan="2" class="text-center">-</td></tr>
                            @endforelse
                            <tr class="table-warning fw-bold">
                                <td>Setoran Cash (Cash - Pengeluaran)</td>
                                <td class="text-end {{ $monthlySetoranCash < 0 ? 'text-danger' : '' }}">Rp {{ number_format($monthlySetoranCash,0,',','.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div> <!-- End Monthly Tab -->

    </div>
</div>

<!-- Script Otomatis Tab -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('month') && !urlParams.has('start_date') && !urlParams.has('end_date')) {
            new bootstrap.Tab(document.querySelector('#monthly-tab')).show();
        }
    });
</script>
<script>
    // Build export URLs preserving current filters
    (function() {
        function q(name) { return new URLSearchParams(window.location.search).get(name); }
        function bindDownload(button) {
            if (!button) return;
            button.addEventListener('click', function(e) {
                if (!button.href) return;
                e.preventDefault();
                var iframe = document.getElementById('wash-report-download-frame');
                if (!iframe) {
                    iframe = document.createElement('iframe');
                    iframe.id = 'wash-report-download-frame';
                    iframe.style.display = 'none';
                    document.body.appendChild(iframe);
                }
                var currentLabel = button.innerHTML;
                button.classList.add('disabled');
                button.setAttribute('aria-disabled', 'true');
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';
                var downloadUrl = new URL(button.href, window.location.origin);
                downloadUrl.searchParams.set('_ts', Date.now().toString());
                iframe.src = downloadUrl.toString();
                setTimeout(function() {
                    button.classList.remove('disabled');
                    button.removeAttribute('aria-disabled');
                    button.innerHTML = currentLabel;
                }, 1800);
            });
        }

        var pdf = document.getElementById('btnExportPdf');
        var xls = document.getElementById('btnExportExcel');
        if (pdf) {
            var params = new URLSearchParams();
            if (q('start_date')) params.set('start_date', q('start_date'));
            if (q('end_date')) params.set('end_date', q('end_date'));
            if (q('month')) params.set('month', q('month'));
            if (q('vehicle_plate')) params.set('vehicle_plate', q('vehicle_plate'));
            pdf.href = '{{ route('wash.reports.pdf') }}' + (params.toString() ? ('?' + params.toString()) : '');
            bindDownload(pdf);
        }
        if (xls) {
            var params2 = new URLSearchParams();
            if (q('start_date')) params2.set('start_date', q('start_date'));
            if (q('end_date')) params2.set('end_date', q('end_date'));
            if (q('month')) params2.set('month', q('month'));
            if (q('vehicle_plate')) params2.set('vehicle_plate', q('vehicle_plate'));
            xls.href = '{{ route('wash.reports.excel') }}' + (params2.toString() ? ('?' + params2.toString()) : '');
            bindDownload(xls);
        }
    })();
</script>
@push('styles')
<style>
    .wash-reports-page {
        padding-left: 0.35rem;
        padding-right: 0.35rem;
    }

    @media (max-width: 767.98px) {
        .wash-reports-page .report-toolbar {
            flex-direction: column;
            gap: 0.6rem;
        }

        .wash-reports-page .wash-report-export {
            width: 100%;
            display: flex;
            gap: 0.4rem;
        }

        .wash-reports-page .wash-report-export .btn {
            flex: 1;
            min-height: 42px;
            border-radius: 0.75rem;
        }

        .wash-reports-page .daily-filter-form,
        .wash-reports-page .monthly-filter-form {
            justify-content: stretch !important;
            gap: 0.4rem;
        }

        .wash-reports-page .daily-filter-form .col-auto,
        .wash-reports-page .monthly-filter-form .col-auto {
            width: 100%;
        }

        .wash-reports-page .daily-filter-form .btn,
        .wash-reports-page .monthly-filter-form .btn {
            width: 100%;
            min-height: 42px;
        }

        .wash-reports-page .table-responsive {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.9rem;
            padding: 0.25rem;
        }

        .wash-reports-page .table-responsive-mobile td {
            align-items: flex-start;
            gap: 0.55rem;
            padding-top: 0.6rem;
            padding-bottom: 0.6rem;
        }

        .wash-reports-page .table-responsive-mobile td::before {
            font-size: 0.68rem;
            letter-spacing: 0.25px;
        }
    }
</style>
@endpush
@endsection
