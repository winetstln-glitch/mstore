<!DOCTYPE html>
<html>
<head>
    <title>Investor Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-end {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Data Investor</h2>
        @if(isset($month) && $month)
            <p>Periode: {{ date('F Y', strtotime($month)) }}</p>
        @endif
        <p>Dibuat pada: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Koordinator</th>
                <th>Telepon</th>
                <th class="text-end">Total Investasi</th>
                <th class="text-end">Saldo Bersih</th>
            </tr>
        </thead>
        <tbody>
            @foreach($investors as $index => $investor)
            @php
                $totalInvestment = $investor->income_transactions_sum_amount ?? 0;
                $totalExpense = $investor->expense_transactions_sum_amount ?? 0;
                $netBalance = $totalInvestment - $totalExpense;
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $investor->name }}</td>
                <td>{{ $investor->coordinator->name ?? '-' }}</td>
                <td>{{ $investor->phone ?? '-' }}</td>
                <td class="text-end">{{ number_format($totalInvestment, 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format($netBalance, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
