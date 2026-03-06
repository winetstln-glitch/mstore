<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Laporan Investor') }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #4e73df;
        }
        .header p {
            margin: 5px 0 0;
            color: #858796;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            padding: 8px;
            border: 1px solid #e3e6f0;
        }
        th {
            background-color: #f8f9fc;
            text-align: left;
            font-weight: bold;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-danger {
            color: #e74a3b;
        }
        .text-success {
            color: #1cc88a;
        }
        .text-primary {
            color: #4e73df;
        }
        . {
            background-color: #f8f9fc;
        }
        .bg-primary {
            background-color: #4e73df;
            color: white;
        }
        .bg-primary th {
            background-color: #4e73df;
            color: white;
        }
        .fw-bold {
            font-weight: bold;
        }
        .section-header {
            background-color: #eaecf4;
            color: #4e73df;
            font-weight: bold;
        }
        .section-title {
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: bold;
            color: #5a5c69;
            border-bottom: 2px solid #4e73df;
            padding-bottom: 5px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #858796;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('Laporan Laba Bersih Investor') }}</h1>
        <p>{{ \Carbon\Carbon::parse($selectedMonth)->format('F Y') }}</p>
        @if($coordinatorId)
            <p>{{ __('Koordinator') }}: {{ $investors->first()->coordinator->name ?? 'N/A' }}</p>
        @endif
    </div>

    <!-- Main Calculation Table -->
    <table>
        <thead>
            <tr>
                <th>{{ __('Keterangan') }}</th>
                <th class="text-end">{{ __('Nilai (IDR)') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ __('Total Pendapatan Kotor') }}</strong> <br> <small>(Member + Voucher)</small></td>
                <td class="text-end fw-bold">{{ number_format($grossRevenue, 0, ',', '.') }}</td>
            </tr>
            
            <!-- Deductions -->
            <tr>
                <td colspan="2" class="section-header">{{ __('Potongan / Alokasi Dana') }}</td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">{{ __('Komisi Pengurus') }}</td>
                <td class="text-end text-danger">-{{ number_format($commission, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">{{ __('Pembagian ISP') }}</td>
                <td class="text-end text-danger">-{{ number_format($ispShare, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">{{ __('Dana Peralatan / Manajemen') }}</td>
                <td class="text-end text-danger">-{{ number_format($toolFund, 0, ',', '.') }}</td>
            </tr>
            
            <!-- Expenses -->
            <tr>
                <td colspan="2" class="section-header">{{ __('Pengeluaran Operasional & Lainnya') }}</td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">
                    {{ __('Total Pengeluaran Operasional') }} <br>
                    <small>(Server, Ambil Barang, Material Kantor, dll)</small>
                </td>
                <td class="text-end text-danger">-{{ number_format($operationalExpenses, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">{{ __('Kas Wajib Investor (5% dari Sisa)') }}</td>
                <td class="text-end text-danger">-{{ number_format($investorCashFund, 0, ',', '.') }}</td>
            </tr>

            <!-- Net Profit -->
            <tr style="background-color: #e8f5e9;">
                <td class="fw-bold text-success" style="font-size: 14px;">{{ __('Total Laba Bersih untuk Investor') }}</td>
                <td class="text-end fw-bold text-success" style="font-size: 14px;">{{ number_format($netProfit, 0, ',', '.') }}</td>
            </tr>
            
            <!-- Summary Split -->
            <tr>
                <td colspan="2" class="section-header">{{ __('Pembagian Per Investor') }}</td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">{{ __('Jumlah Investor') }}</td>
                <td class="text-end fw-bold">{{ $investorCount }}</td>
            </tr>
            <tr style="background-color: #e3f2fd;">
                <td class="fw-bold text-primary" style="font-size: 14px;">{{ __('Laba Per Investor') }}</td>
                <td class="text-end fw-bold text-primary" style="font-size: 14px;">{{ number_format($profitPerInvestor, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Detailed Investor Breakdown -->
    <div class="section-title">{{ __('Rincian Pembagian Per Investor') }}</div>
    <table>
        <thead class="bg-primary">
            <tr>
                <th style="width: 30px; color: white;">#</th>
                <th style="color: white;">{{ __('Nama Investor') }}</th>
                <th style="color: white;">{{ __('Role') }}</th>
                <th class="text-end" style="color: white;">{{ __('Pembagian Laba (IDR)') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($investors as $index => $investor)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        {{ $investor->name }}
                        @if($investor->coordinator_id)
                            <br><small style="color: #858796;">Koordinator: {{ $investor->coordinator->name ?? '-' }}</small>
                        @endif
                    </td>
                    <td>{{ __('Investor') }}</td>
                    <td class="text-end fw-bold">{{ number_format($profitPerInvestor, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">{{ __('Tidak ada data investor.') }}</td>
                </tr>
            @endforelse
            <tr style="background-color: #eaecf4; font-weight: bold;">
                <td colspan="3" class="text-end">{{ __('Total Dibagikan') }}</td>
                <td class="text-end">{{ number_format($profitPerInvestor * $investors->count(), 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>{{ __('Dicetak pada') }}: {{ now()->format('d F Y H:i') }}</p>
    </div>
</body>
</html>
