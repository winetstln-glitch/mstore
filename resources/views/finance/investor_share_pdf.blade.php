<!DOCTYPE html>
<html>
<head>
    <title>Laporan Bagi Hasil Investor</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header p {
            margin: 5px 0 0;
            color: #666;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid #444;
            padding: 8px 12px;
            vertical-align: middle;
        }

        thead tr {
            background-color: #2c3e50; /* Warna Header Biru Gelap Profesional */
            color: #fff;
        }

        th {
            text-align: center;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
        }
        
        th.text-end { text-align: right; }
        th.text-start { text-align: left; }

        /* Baris Koordinator (Parent) */
        tr.parent-row td {
            background-color: #f8f9fa;
            font-weight: bold;
            border-top: 2px solid #000;
        }

        /* Baris Investor (Child) */
        tr.child-row td {
            background-color: #fff;
            font-size: 10px;
            color: #555;
        }
        
        /* Indentasi untuk child row */
        .indent {
            padding-left: 30px;
            font-style: italic;
        }

        .text-end {
            text-align: right;
            font-family: 'Courier New', Courier, monospace; /* Monospace agar angka rapi */
            font-weight: bold;
        }

        /* Warna Khusus untuk Kolom */
        .col-share { color: #000; }
        .col-cash { color: #666; }
        .col-total { 
            background-color: #e3f2fd; /* Highlight biru muda untuk total */
            color: #000;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            background-color: #eee;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        @media print {
            body { margin: 0; }
            .header { border-bottom: none; }
            thead tr { background-color: #ddd !important; color: #000 !important; -webkit-print-color-adjust: exact; }
            tr.parent-row td { background-color: #eee !important; -webkit-print-color-adjust: exact; }
            .col-total { background-color: #ddd !important; -webkit-print-color-adjust: exact; font-weight: bold; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>Laporan Alokasi Dana Investor</h2>
        <p>Periode Laporan: {{ now()->format('d F Y') }} | Dicetak: {{ now()->format('H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40%;" class="text-start">Nama Investor</th>
                <th style="width: 20%;">Bagi Hasil (Profit)</th>
                <th style="width: 20%;">Dana Kas (Simpanan)</th>
                <th style="width: 20%;">Total Dana Investor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($coordinatorSummaries as $summary)
            <!-- Header Grup Koordinator -->
            <tr class="parent-row">
                <td colspan="4">
                    <span class="badge">REGION: {{ $summary->region->name ?? 'Umum' }}</span>
                    {{ $summary->name }}
                </td>
            </tr>
            
            <!-- Detail Investor -->
            @php
                $investorDetails = $investorDetailsByCoordinator[$summary->id] ?? [];
                $totalGroupProfit = 0;
                $totalGroupCash = 0;
            @endphp
            
            @forelse($investorDetails as $detail)
                @php
                    $individualTotal = $detail['profit_share'] + $detail['cash_fund'];
                    $totalGroupProfit += $detail['profit_share'];
                    $totalGroupCash += $detail['cash_fund'];
                @endphp
                <tr class="child-row">
                    <td class="indent">
                        <i class="fa-regular fa-user" style="opacity:0.5;"></i> {{ $detail['investor_name'] }}
                    </td>
                    <td class="text-end col-share">{{ number_format($detail['profit_share'], 0, ',', '.') }}</td>
                    <td class="text-end col-cash">{{ number_format($detail['cash_fund'], 0, ',', '.') }}</td>
                    <td class="text-end col-total"><strong>{{ number_format($individualTotal, 0, ',', '.') }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted" style="padding: 10px;">Tidak ada investor terdaftar di grup ini.</td>
                </tr>
            @endforelse

            <!-- Total per Koordinator (Optional tapi bagus) -->
            <tr class="parent-row" style="background-color: #e9ecef; font-size: 10px;">
                <td class="text-end">Total Koordinator {{ $summary->name }}</td>
                <td class="text-end text-muted">{{ number_format($totalGroupProfit, 0, ',', '.') }}</td>
                <td class="text-end text-muted">{{ number_format($totalGroupCash, 0, ',', '.') }}</td>
                <td class="text-end fw-bold">{{ number_format($totalGroupProfit + $totalGroupCash, 0, ',', '.') }}</td>
            </tr>
            
            <tr><td colspan="4" style="height: 15px; border: none;"></td></tr> <!-- Spacer -->
            
            @endforeach
        </tbody>
    </table>

    <!-- Footer Signature Area (Opsional) -->
    <div style="margin-top: 50px; page-break-inside: avoid;">
        <div style="float: left; width: 30%; text-align: center;">
            <div style="
