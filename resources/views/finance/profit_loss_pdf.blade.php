<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Laba Rugi</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 11px;
        }
        h1 {
            font-size: 16px;
            text-align: center;
            margin-bottom: 4px;
        }
        .period {
            text-align: center;
            margin-bottom: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px 6px;
        }
        th {
            background-color: #f0f0f0;
        }
        .text-right {
            text-align: right;
        }
        .section-header {
            font-weight: bold;
            background-color: #e6e6e6;
        }
        .spacer-row td {
            border: none;
            height: 6px;
            padding: 0;
        }
        /* Style tambahan untuk tabel rincian investor */
        .investor-header {
            background-color: #e6f7ff; /* Warna beda sedikit untuk membedakan */
            font-weight: bold;
        }
        .total-row {
            background-color: #f9f9f9;
            font-weight: bold;
            border-top: 2px solid #000;
        }
    </style>
</head>
<body>
    <h1>LAPORAN LABA RUGI</h1>
    <div class="period">
        @if(!empty($month))
            Periode: {{ \Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y') }}
        @else
            Periode: Semua Transaksi
        @endif
        @if(isset($coordinatorName) && $coordinatorName)
            <br>Pengurus: {{ $coordinatorName }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th class="text-right">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section-header">
                <td colspan="2">PENDAPATAN</td>
            </tr>
            <tr>
                <td>Pendapatan Member</td>
                <td class="text-right">{{ number_format($memberIncome, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pendapatan Voucher</td>
                <td class="text-right">{{ number_format($voucherIncome, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pendapatan Lain-lain</td>
                <td class="text-right">{{ number_format($otherIncome, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Total Pendapatan</strong></td>
                <td class="text-right"><strong>{{ number_format($totalRevenue, 0, ',', '.') }}</strong></td>
            </tr>

            <tr class="spacer-row">
                <td colspan="2"></td>
            </tr>

            <tr class="section-header">
                <td colspan="2">BEBAN POKOK PENDAPATAN</td>
            </tr>
            <tr>
                <td>Komisi Pengurus (±{{ number_format($coordRate, 1) }}%)</td>
                <td class="text-right">-{{ number_format($coordCommission, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pembayaran ISP (±{{ number_format($ispRate, 1) }}%)</td>
                <td class="text-right">-{{ number_format($ispPayment, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Dana Alat / Manajemen (±{{ number_format($toolRate, 1) }}%)</td>
                <td class="text-right">-{{ number_format($toolFund, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Total Beban Pokok Pendapatan</strong></td>
                <td class="text-right"><strong>-{{ number_format($totalCOGS, 0, ',', '.') }}</strong></td>
            </tr>

            <tr class="spacer-row">
                <td colspan="2"></td>
            </tr>

            <tr>
                <td><strong>Laba Kotor</strong></td>
                <td class="text-right"><strong>{{ number_format($grossProfit, 0, ',', '.') }}</strong></td>
            </tr>

            <tr class="spacer-row">
                <td colspan="2"></td>
            </tr>

            <tr class="section-header">
                <td colspan="2">BIAYA OPERASIONAL</td>
            </tr>
            <tr>
                <td>Server / Operasional</td>
                <td class="text-right">-{{ number_format($serverExpenses, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Transportasi</td>
                <td class="text-right">-{{ number_format($transportExpenses, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Konsumsi</td>
                <td class="text-right">-{{ number_format($consumptionExpenses, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Perbaikan</td>
                <td class="text-right">-{{ number_format($repairExpenses, 0, ',', '.') }}</td>
            </tr>
            @if(isset($otherExpensesBreakdown) && count($otherExpensesBreakdown) > 0)
                @foreach($otherExpensesBreakdown as $category => $amount)
                <tr>
                    <td>{{ $category }}</td>
                    <td class="text-right">-{{ number_format($amount, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            @elseif($otherOperatingExpenses != 0)
                <tr>
                    <td>Biaya Operasional Lain-lain</td>
                    <td class="text-right">-{{ number_format($otherOperatingExpenses, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr>
                <td><strong>Total Biaya Operasional</strong></td>
                <td class="text-right"><strong>-{{ number_format($operatingExpenses, 0, ',', '.') }}</strong></td>
            </tr>

            <tr class="spacer-row">
                <td colspan="2"></td>
            </tr>

            <tr>
                <td><strong>Laba Bersih</strong></td>
                <td class="text-right"><strong>{{ number_format($netProfit, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td>Cadangan Kas Investor ({{ $investorCashPercent }}%)</td>
                <td class="text-right">-{{ number_format($investorCashReserve, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Bagian Investor Setelah Dana Kas</strong></td>
                <td class="text-right"><strong>{{ number_format($investorShareAfterCash, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- END BAGIAN RINCIAN INVESTOR -->
    <div style="margin-top: 50px;">
        <table style="width: 100%; border: none;">
            <tr style="border: none;">
                <td style="border: none; text-align: center; width: 100%;">
                    <p>Mengetahui,</p>
                    <p><strong>Manager Pengelola</strong></p>
                    <br><br><br><br>
                    <p>({{ $managerName ?? '_______________________' }})</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
