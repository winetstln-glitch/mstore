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
                <td colspan="2">PENDAPATAN (REVENUE)</td>
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
                <td>Pendapatan Toko ATK</td>
                <td class="text-right">{{ number_format($atkRevenue, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pendapatan Wash</td>
                <td class="text-right">{{ number_format($washRevenue, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pendapatan Inventaris (Material)</td>
                <td class="text-right">{{ number_format($inventoryRevenue, 0, ',', '.') }}</td>
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
                <td colspan="2">BEBAN POKOK PENDAPATAN (COST OF REVENUE)</td>
            </tr>
            <tr>
                <td>Komisi Pengurus</td>
                <td class="text-right">-{{ number_format($coordCommission, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pembayaran ISP</td>
                <td class="text-right">-{{ number_format($ispPayment, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Tool Fund / Manajemen</td>
                <td class="text-right">-{{ number_format($toolFund, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>HPP Toko ATK</td>
                <td class="text-right">-{{ number_format($atkCOGS, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Biaya Inventaris (Material)</td>
                <td class="text-right">-{{ number_format($inventoryCost, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Total Beban Pokok Pendapatan</strong></td>
                <td class="text-right"><strong>-{{ number_format($totalCOGS, 0, ',', '.') }}</strong></td>
            </tr>

            <tr class="spacer-row">
                <td colspan="2"></td>
            </tr>

            <tr>
                <td><strong>Laba Kotor (Gross Profit)</strong></td>
                <td class="text-right"><strong>{{ number_format($grossProfit, 0, ',', '.') }}</strong></td>
            </tr>

            <tr class="spacer-row">
                <td colspan="2"></td>
            </tr>

            <tr class="section-header">
                <td colspan="2">BIAYA OPERASIONAL (OPERATING EXPENSES)</td>
            </tr>
            <tr>
                <td>Server / Operational</td>
                <td class="text-right">-{{ number_format($serverExpenses, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Transport</td>
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
            @if($otherOperatingExpenses != 0)
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
                <td><strong>Laba Bersih (Net Profit)</strong></td>
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

    @if(isset($investorSummaries) && count($investorSummaries) > 0)
        <br>
        <h2 style="font-size: 14px; margin-top: 10px; margin-bottom: 6px; text-align: left;">
            Rincian Pembagian Investor
        </h2>
        <table>
            <thead>
                <tr>
                    <th>Investor</th>
                    <th class="text-right">Bagian Investor Setelah Dana Kas</th>
                    <th class="text-right">Dana Kas Investor</th>
                    <th class="text-right">Total Pembagian</th>
                </tr>
            </thead>
            <tbody>
                @foreach($investorSummaries as $row)
                    @php
                        $totalShare = $row->profit_share + $row->cash_fund;
                    @endphp
                    <tr>
                        <td>{{ $row->investor_name }}</td>
                        <td class="text-right">-{{ number_format($row->profit_share, 0, ',', '.') }}</td>
                        <td class="text-right">-{{ number_format($row->cash_fund, 0, ',', '.') }}</td>
                        <td class="text-right">-{{ number_format($totalShare, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
