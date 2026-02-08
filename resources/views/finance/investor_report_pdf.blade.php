<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Investor - {{ \Carbon\Carbon::parse($selectedMonth)->format('F Y') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
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
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px 12px;
        }
        th {
            background-color: #f8f9fa;
            text-align: left;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .fw-bold {
            font-weight: bold;
        }
        .text-danger {
            color: #dc3545;
        }
        .text-success {
            color: #198754;
        }
        .bg-light {
            background-color: #f8f9fa;
        }
        .section-header {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Pembagian Hasil Investor</h1>
        <p>Periode: {{ \Carbon\Carbon::parse($selectedMonth)->format('F Y') }}</p>
        @if(isset($coordinatorName) && $coordinatorName)
            <p>Pengurus: {{ $coordinatorName }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Keterangan</th>
                <th class="text-end">Nilai (IDR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Total Pendapatan Kotor</strong></td>
                <td class="text-end fw-bold">{{ number_format($grossRevenue, 0, ',', '.') }}</td>
            </tr>
            
            <!-- Deductions -->
            <tr>
                <td colspan="2" class="section-header">Potongan / Alokasi Dana</td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">Komisi Pengurus</td>
                <td class="text-end text-danger">-{{ number_format($commission, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">Pembagian ISP</td>
                <td class="text-end text-danger">-{{ number_format($ispShare, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">Dana Peralatan / Manajemen</td>
                <td class="text-end text-danger">-{{ number_format($toolFund, 0, ',', '.') }}</td>
            </tr>
            
            <!-- Expenses -->
            <tr>
                <td colspan="2" class="section-header">Pengeluaran Operasional</td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">
                    Total Pengeluaran (Server, Ambil Barang, dll)
                </td>
                <td class="text-end text-danger">-{{ number_format($operationalExpenses, 0, ',', '.') }}</td>
            </tr>
            
            <tr>
                <td style="padding-left: 20px;">Kas Wajib Investor (5% dari Sisa)</td>
                <td class="text-end text-danger">-{{ number_format($investorCashFund, 0, ',', '.') }}</td>
            </tr>

            <!-- Result -->
            <tr style="background-color: #d1e7dd;">
                <td class="fw-bold text-success">Total Laba Bersih untuk Investor</td>
                <td class="text-end fw-bold text-success">{{ number_format($netProfit, 0, ',', '.') }}</td>
            </tr>
            
            <!-- Split -->
            <tr>
                <td colspan="2" class="section-header">Pembagian Per Investor</td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">Jumlah Investor</td>
                <td class="text-end fw-bold">{{ $investorCount }}</td>
            </tr>
            @if(!empty($investorNames))
            <tr>
                <td style="padding-left: 20px;">
                    Nama Investor:<br>
                    <small style="color: #666; font-style: italic;">{{ implode(', ', $investorNames) }}</small>
                </td>
                <td class="text-end"></td>
            </tr>
            @endif
            <tr style="background-color: #cfe2ff;">
                <td class="fw-bold">Laba Per Investor</td>
                <td class="text-end fw-bold">{{ number_format($profitPerInvestor, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 50px;">
        <table style="width: 100%; border: none;">
            <tr style="border: none;">
                <td style="border: none; text-align: center; width: 100%;">
                    <p>Mengetahui,</p>
                    <p><strong>Manager Pengelola</strong></p>
                    <br><br><br><br>
                    <p>(_______________________)</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d F Y H:i') }}</p>
    </div>
</body>
</html>
