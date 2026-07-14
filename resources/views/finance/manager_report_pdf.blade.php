<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan Manajemen</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif; /* Font resmi untuk dokumen */
            font-size: 11px;
            color: #000;
            margin: 20px;
        }
        
        /* JUDUL DOKUMEN */
        h1 {
            font-size: 16px;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 2px;
            font-weight: bold;
        }
        .subtitle {
            text-align: center;
            font-size: 10px;
            margin-bottom: 20px;
            font-style: italic;
            color: #444;
        }

        /* JUDUL BAGIAN (SECTION HEADERS) */
        h2 {
            font-size: 11px;
            text-transform: uppercase;
            background-color: #eee;
            padding: 4px;
            border: 1px solid #000;
            margin-top: 20px;
            margin-bottom: 0;
            font-weight: bold;
        }

        /* TABEL UMUM */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        th, td {
            border: 1px solid #000; /* Border Hitam Solid untuk print */
            padding: 5px 8px;
            vertical-align: top;
        }
        
        /* UTILITAS */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .text-muted { color: #666; font-size: 9px; }

        /* STYLE KHUSUS KOLOM */
        .col-label { width: 60%; text-align: left; }
        .col-amount { width: 40%; text-align: right; font-family: 'Courier New', monospace; }

        /* WARNA BARIS (Hanya untuk Screen, Print akan jadi BW) */
        . { background-color: #f9f9f9; }
        .bg-header { background-color: #e0e0e0; }
        .bg-total { background-color: #ddd; font-weight: bold; }

        /* SIGNATURE AREA */
        .signature-container {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .sign-box {
            width: 30%;
            text-align: center;
        }
        .sign-space {
            height: 60px;
        }
        .sign-line {
            border-top: 1px solid #000;
            padding-top: 5px;
            font-weight: bold;
        }

        /* PRINT CSS */
        @media print {
            body { margin: 0; }
            h2 { background-color: #ddd !important; -webkit-print-color-adjust: exact; }
            .bg-header, .bg-total { background-color: #eee !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <!-- HEADER DOKUMEN -->
    <h1>Laporan Hasil Usaha & Posisi Kas</h1>
    <div class="subtitle">
        Periode: 
        @php
            $period = $month
                ? \Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y')
                : 'Semua Periode';
        @endphp
        {{ $period }} <br>
        Dicetak: {{ now()->format('d-m-Y H:i') }}
    </div>

    <!-- BAGIAN 1: PENDAPATAN (LABA RUGI) -->
    <h2>1. Ringkasan Pendapatan & Beban</h2>
    <table>
        <thead>
            <tr class="bg-header">
                <th class="col-label">Uraian</th>
                <th class="col-amount">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <!-- PENDAPATAN -->
            <tr>
                <td>Pendapatan Member</td>
                <td class="text-right">{{ number_format($memberIncome, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pendapatan Voucher</td>
                <td class="text-right">{{ number_format($voucherIncome, 0, ',', '.') }}</td>
            </tr>
            <tr class="bg-total">
                <td>TOTAL PENDAPATAN KOTOR</td>
                <td class="text-right">{{ number_format($totalRevenue, 0, ',', '.') }}</td>
            </tr>

            <!-- PENGELUARAN -->
            <tr>
                <td colspan="2" style="background:#f0f0f0; font-size:9px; font-weight:bold;">POTONGAN & BIAYA</td>
            </tr>
            <tr>
                <td>Komisi Pengurus ({{ $coordRate ?? 0 }}%)</td>
                <!-- Gunakan tanda kurung untuk pengurang (Standar Akuntansi) -->
                <td class="text-right">({{ number_format($coordCommission, 0, ',', '.') }})</td>
            </tr>
            <tr>
                <td>Pengeluaran Transportasi</td>
                <td class="text-right">({{ number_format($transportExpenses, 0, ',', '.') }})</td>
            </tr>
            <tr>
                <td>Pengeluaran Konsumsi</td>
                <td class="text-right">({{ number_format($consumptionExpenses, 0, ',', '.') }})</td>
            </tr>
            <tr>
                <td>Pengeluaran Perbaikan</td>
                <td class="text-right">({{ number_format($repairExpenses, 0, ',', '.') }})</td>
            </tr>
            <tr>
                <td>Pengeluaran Lainnya</td>
                <td class="text-right">({{ number_format($otherOperatingExpenses, 0, ',', '.') }})</td>
            </tr>
            <tr class="bg-total">
                <td>TOTAL BIAYA & PENGELUARAN</td>
                <td class="text-right">({{ number_format($operatingExpenses, 0, ',', '.') }})</td>
            </tr>

            <!-- LABA BERSIH -->
            <tr style="background-color: #fff3cd;">
                <td class="text-bold">LABA BERSIH (NET INCOME)</td>
                <td class="text-right text-bold">{{ number_format($totalRevenue - $operatingExpenses - $coordCommission, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- BAGIAN 2: REKONSILIASI KAS -->
    <h2>2. Rekonsiliasi Saldo Kas</h2>
    <table>
        <thead>
            <tr class="bg-header">
                <th class="col-label">Uraian Arus Kas</th>
                <th class="col-amount">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Saldo Awal Laba Bersih</td>
                <td class="text-right">{{ number_format($totalRevenue - $operatingExpenses - $coordCommission, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Dikurang: Sudah Disetor ke Pusat</td>
                <td class="text-right">({{ number_format($deposited, 0, ',', '.') }})</td>
            </tr>
            <tr style="border: 2px solid #000;">
                <td class="text-bold">SISA SALDO (WAJIB SETOR)</td>
                <td class="text-right text-bold">{{ number_format($netBalance, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- BAGIAN 3: DETAIL PENGURUS (JIKA ADA) -->
    @if(!empty($coordinatorSummaries))
    <h2>3. Rincian per Pengurus</h2>
    <table class="table-compact">
        <thead>
            <tr class="bg-header">
                <th style="width:5%">No</th>
                <th style="width:30%">Nama Pengurus</th>
                <th class="text-right">Pendapatan</th>
                <th class="text-right">Komisi</th>
                <th class="text-right">Beban</th>
                <th class="text-right">Disetor</th>
                <th class="text-right">Sisa Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($coordinatorSummaries as $index => $row)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $row->name }}</td>
                <td class="text-right">{{ number_format($row->gross_revenue, 0, ',', '.') }}</td>
                <td class="text-right text-muted">({{ number_format($row->commission, 0, ',', '.') }})</td>
                <td class="text-right text-muted">({{ number_format($row->expenses, 0, ',', '.') }})</td>
                <td class="text-right text-muted">({{ number_format($row->deposited, 0, ',', '.') }})</td>
                <td class="text-right text-bold">{{ number_format($row->net_balance, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- BAGIAN 4: RINCIAN INVESTOR (DIPERBAIKI LOGIKA) -->
    @if(isset($investorSummaries) && count($investorSummaries) > 0)
    <h2>4. Rincian Pembagian Hasil Investor</h2>
    <table>
        <thead>
            <tr class="bg-header">
                <th style="width:35%">Nama Investor</th>
                <th class="text-right">Bagi Hasil (Profit)</th>
                <th class="text-right">Dana Kas (Simpanan)</th>
                <th class="text-right ">Total Alokasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($investorSummaries as $row)
                @php
                    // Koreksi Logika: Bagi Hasil dan Dana Kas adalah PLUS (Uang masuk)
                    $totalAllocation = $row->profit_share + $row->cash_fund;
                @endphp
            <tr>
                <td>{{ $row->investor_name }}</td>
                <td class="text-right">{{ number_format($row->profit_share, 0, ',', '.') }}</td>
                <td class="text-right text-muted">{{ number_format($row->cash_fund, 0, ',', '.') }}</td>
                <td class="text-right text-bold ">{{ number_format($totalAllocation, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- FOOTER TANDA TANGAN -->
    <div style="margin-top: 30px; font-size: 9px; color: #777; text-align: center;">
        Dokumen ini digenerate secara otomatis oleh sistem. Tanda kurung (...) menunjukkan pengurangan nilai.
    </div>

    <div class="signature-container">
        <div class="sign-box">
            <div>Dibuat Oleh,</div>
            <div class="sign-space"></div>
            <div class="sign-line">Staff Keuangan</div>
        </div>
        <div class="sign-box">
            <div>Diperiksa Oleh,</div>
            <div class="sign-space"></div>
            <div class="sign-line">Manager Keuangan</div>
        </div>
        <div class="sign-box">
            <div>Disetujui Oleh,</div>
            <div class="sign-space"></div>
            <div class="sign-line">{{ $managerName ?? 'Manager Pengelola' }}</div>
            <div>Manager Pengelola</div>
        </div>
    </div>

</body>
</html>
