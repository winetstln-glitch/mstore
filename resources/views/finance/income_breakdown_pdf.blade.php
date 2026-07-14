<!DOCTYPE html>
<html>
<head>
    <title>Rincian Alur Pendapatan</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 15px;
        }
        
        /* Judul & Header */
        h2 {
            text-align: center;
            margin: 0 0 5px 0;
            font-size: 16px;
            text-transform: uppercase;
        }
        .subtitle {
            text-align: center;
            margin-bottom: 15px;
            font-size: 11px;
            color: #666;
            font-style: italic;
        }

        /* Tabel Style */
        .table-container {
            width: 100%;
            overflow-x: auto; /* Agar bisa discroll di layar kecil */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            min-width: 900px; /* Pastikan tabel tidak gepeng */
        }
        th, td {
            border: 1px solid #444;
            padding: 6px 8px;
            text-align: right; /* Default kanan untuk angka */
        }
        
        /* Header Tabel */
        thead tr {
            background-color: #e9ecef;
        }
        th {
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            color: #000;
        }
        th:first-child, th:nth-child(2), th:nth-child(3) {
            text-align: left; /* Teks align kiri untuk kolom non-angka */
        }

        /* Baris Data */
        tbody tr:nth-child(even) {
            background-color: #fdfdfd;
        }
        tbody tr:hover {
            background-color: #f1f1f1;
        }
        td {
            font-size: 11px;
        }
        td:first-child, td:nth-child(2), td:nth-child(3) {
            text-align: left;
        }

        /* Utilitas Teks */
        .text-muted { color: #888; }
        .font-bold { font-weight: bold; }
        
        /* Formatting Akuntansi (Print Safe) */
        .val-minus { color: #d00; } /* Tetap merah di layar, tapi jelas di BW */
        .val-plus { font-weight: bold; }
        .row-total {
            background-color: #e2e6ea !important;
            font-weight: bold;
        }
        .row-highlight {
            background-color: #fff3cd !important; /* Kuning muda print-friendly */
        }

        /* Kolom spesifik */
        .col-text { text-align: left !important; }
        .col-center { text-align: center !important; }
        
        @media print {
            body { margin: 0; font-size: 10px; }
            .table-container { overflow: visible; }
            .no-print { display: none; }
            th { background-color: #ddd !important; -webkit-print-color-adjust: exact; }
            .row-highlight { background-color: #eee !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <h2>Rincian Alur Pendapatan (Audit Trail)</h2>
    <div class="subtitle">Menampilkan 10 Transaksi Terakhir</div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <!-- GROUP 1: INFORMASI DASAR -->
                    <th rowspan="2" class="col-text">Tanggal</th>
                    <th rowspan="2" class="col-text">Koordinator</th>
                    <th rowspan="2" class="col-text">Investor</th>
                    <th colspan="1" style="border-bottom: 1px solid #fff; background-color: #d1e7dd; color: #000;">Pendapatan</th>
                    
                    <!-- GROUP 2: POTONGAN (DEDUCTIONS) -->
                    <th colspan="3" style="border-bottom: 1px solid #fff; background-color: #f8d7da; color: #000;">Potongan & Biaya</th>
                    
                    <!-- GROUP 3: DISTRIBUSI (ALOKASI) -->
                    <th colspan="2" style="border-bottom: 1px solid #fff; background-color: #fff3cd; color: #000;">Distribusi Dana</th>
                </tr>
                <tr>
                    <!-- Sub Header Pendapatan -->
                    <th class="">Kotor</th>
                    
                    <!-- Sub Header Potongan -->
                    <th class="">Komisi ({{ $coordRate }}%)</th>
                    <th class="">ISP ({{ $ispRate }}%)</th>
                    <th class="">Alat ({{ $toolRate }}%)</th>
                    
                    <!-- Sub Header Distribusi -->
                    <th class="">Dana Kas</th>
                    <th class="" style="background-color: #cfe2ff !important;">Income Investor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($incomeBreakdowns as $breakdown)
                <tr>
                    <!-- Info Dasar -->
                    <td class="col-text">{{ $breakdown->date->format('d M Y') }}</td>
                    <td class="col-text">{{ $breakdown->coordinator_name }}</td>
                    <td class="col-text">{{ $breakdown->investor_names }}</td>
                    
                    <!-- Pendapatan -->
                    <td class="font-bold">{{ number_format($breakdown->gross_amount, 0, ',', '.') }}</td>
                    
                    <!-- Potongan (Gunakan Tanda Kurung untuk audit) -->
                    <td class="val-minus">({{ number_format($breakdown->commission, 0, ',', '.') }})</td>
                    <td class="val-minus">({{ number_format($breakdown->isp_share, 0, ',', '.') }})</td>
                    <td class="val-minus">({{ number_format($breakdown->tool_fund, 0, ',', '.') }})</td>
                    
                    <!-- Distribusi Akhir -->
                    <!-- Dana Kas dianggap 'Disisihkan', tidak minus -->
                    <td>{{ number_format($breakdown->cash_fund, 0, ',', '.') }}</td>
                    
                    <!-- Income Investor (Hasil Akhir) -->
                    <td class="row-highlight font-bold">
                        {{ number_format($breakdown->investor_share, 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
                
                <!-- BARIS TOTAL (Opsional, bagus untuk audit cepat) -->
                <tr class="row-total">
                    <td colspan="3" class="col-text"><strong>TOTAL PERIODE</strong></td>
                    <td>{{ number_format($incomeBreakdowns->sum('gross_amount'), 0, ',', '.') }}</td>
                    <td class="val-minus">({{ number_format($incomeBreakdowns->sum('commission'), 0, ',', '.') }})</td>
                    <td class="val-minus">({{ number_format($incomeBreakdowns->sum('isp_share'), 0, ',', '.') }})</td>
                    <td class="val-minus">({{ number_format($incomeBreakdowns->sum('tool_fund'), 0, ',', '.') }})</td>
                    <td>{{ number_format($incomeBreakdowns->sum('cash_fund'), 0, ',', '.') }}</td>
                    <td class="row-highlight">{{ number_format($incomeBreakdowns->sum('investor_share'), 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div style="font-size: 9px; color: #666; text-align: center; margin-top: 10px;">
        * Catatan: Tanda kurung (...) menunjukkan pengurangan dari pendapatan kotor. <br>
        Income Investor adalah sisa bersih setelah dikurangi semua potongan tetap dan biaya operasional (jika ada).
    </div>

</body>
</html>
