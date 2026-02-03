<!DOCTYPE html>
<html lang="id">
<head>
    <title>Laporan Keuangan Pengurus</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 20px;
        }
        /* Judul Dokumen */
        h1 {
            font-size: 16px;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: bold;
        }
        h2 {
            font-size: 12px;
            margin: 15px 0 5px 0;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            text-transform: uppercase;
            background-color: #f9f9f9;
            padding: 4px;
        }
        
        /* Informasi Meta */
        .meta-info {
            text-align: center;
            margin-bottom: 20px;
            font-size: 10px;
        }

        /* Tabel Umum */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 10px;
            vertical-align: top;
        }
        th {
            background-color: #e0e0e0;
            text-align: center;
            font-weight: bold;
        }
        
        /* Utilitas Teks */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .text-muted { color: #555; font-style: italic; }
        
        /* Style Khusus Transaksi */
        .table-compact th, .table-compact td {
            padding: 4px;
            font-size: 9px;
        }
        .brackets { color: #000; } /* Hitam pekat untuk print BW */
        
        /* Tanda Tangan */
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .sign-box {
            width: 30%;
            text-align: center;
        }
        .sign-line {
            margin-top: 60px;
            border-top: 1px solid #000;
            padding-top: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <h1>Laporan Keuangan Pengurus</h1>
    <div class="meta-info">
        <div><strong>Nama Pengurus:</strong> {{ $coordinator->name }}</div>
        <div><strong>Wilayah:</strong> {{ $coordinator->region->name ?? '-' }}</div>
        <div><strong>Periode Laporan:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</div>
    </div>

    <!-- BAGIAN 1: RINGKASAN PENDAPATAN (REVENUE) -->
    <h2>1. Ringkasan Pendapatan</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 70%;">Keterangan Pendapatan</th>
                <th class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Pendapatan Member (Langganan)</td>
                <td class="text-right">{{ number_format($memberIncome, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pendapatan Voucher</td>
                <td class="text-right">{{ number_format($voucherIncome, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-right text-bold bg-light">TOTAL PENDAPATAN KOTOR</td>
                <td class="text-right text-bold bg-light">{{ number_format($grossRevenue, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- BAGIAN 2: PENGURANGAN (LABA RUGI SEDERHANA) -->
    <h2>2. Potongan dan Beban Pengurus</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 70%;">Keterangan Potongan</th>
                <th class="text-right">Nominal (Pengurang)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>(A) Komisi Pengurus</td>
                <!-- Gunakan tanda kurung agar jelas dikurangi meski print BW -->
                <td class="text-right brackets">({{ number_format($commission, 0, ',', '.') }})</td>
            </tr>
            <tr>
                <td>(B) Pengeluaran Operasional</td>
                <td class="text-right brackets">({{ number_format($expenses, 0, ',', '.') }})</td>
            </tr>
            <tr>
                <td class="text-right text-bold bg-light">SISA HASIL USAHA (NETTO)</td>
                <td class="text-right text-bold bg-light">{{ number_format($grossRevenue - $commission - $expenses, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- BAGIAN 3: REKONSILIASI KAS (CASH FLOW) -->
    <h2>3. Posisi Saldo Akhir</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 70%;">Keterangan Arus Kas</th>
                <th class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Saldo Hasil Usaha (Dari Bagian 2)</td>
                <td class="text-right">{{ number_format($grossRevenue - $commission - $expenses, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Dikurang: Sudah Disetor ke Pusat</td>
                <td class="text-right brackets">({{ number_format($deposited, 0, ',', '.') }})</td>
            </tr>
            <tr style="background-color: #e8f4fd;">
                <td class="text-right text-bold">SISA SALDO / WAJIB SETOR</td>
                <td class="text-right text-bold">{{ number_format($netBalance, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- BAGIAN 4: DETAIL INVESTOR (JIKA ADA) -->
    @if(isset($investorDetails) && $investorDetails->count() > 0)
    <h2>4. Rincian Dana Kas Investor</h2>
    <table class="table-compact">
        <thead>
            <tr>
                <th>Nama Investor</th>
                <th class="text-right">Alokasi Dana Kas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($investorDetails as $row)
            <tr>
                <td>{{ $row->investor_name }}</td>
                <td class="text-right">{{ number_format($row->cash_fund, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr style="background-color: #f0f0f0;">
                <td class="text-right text-bold">Total Dana Kas</td>
                <td class="text-right text-bold">
                    {{ number_format($investorDetails->sum('cash_fund'), 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- BAGIAN 5: RIWAYAT TRANSAKSI MANUAL -->
    <h2>5. Riwayat Transaksi Manual</h2>
    <table class="table-compact">
        <thead>
            <tr>
                <th width="70" class="text-center">Tanggal</th>
                <th width="50" class="text-center">Tipe</th>
                <th width="120">Kategori</th>
                <th>Keterangan / Deskripsi</th>
                <th width="110" class="text-right">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
            <tr>
                <td class="text-center">{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                <td class="text-center">
                    {{-- Badge Teks Sederhana --}}
                    @if($transaction->type == 'income') <span>IN</span> @else <span>OUT</span> @endif
                </td>
                <td>{{ $transaction->category }}</td>
                <td>
                    <div>{{ $transaction->description }}</div>
                    @if($transaction->reference_number)
                    <div class="text-muted">Ref: {{ $transaction->reference_number }}</div>
                    @endif
                </td>
                <td class="text-right {{ $transaction->type == 'income' ? '' : 'brackets' }}">
                    @if($transaction->type != 'income') ( @endif
                    {{ number_format($transaction->amount, 0, ',', '.') }}
                    @if($transaction->type != 'income') ) @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted" style="padding: 20px;">
                    Tidak ada transaksi manual yang diinput pada periode ini.
                </td>
            </tr>
            @endforelse
            
            <!-- Total Transaksi Manual -->
            @if($transactions->count() > 0)
            <tr style="background-color: #f0f0f0; font-weight: bold;">
                <td colspan="4" class="text-right">Total Transaksi Manual:</td>
                <td class="text-right">
                    {{ number_format($transactions->where('type', 'income')->sum('amount') - $transactions->where('type', 'expense')->sum('amount'), 0, ',', '.') }}
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    <!-- FOOTER & TANDA TANGAN -->
    <div style="font-size: 9px; color: #777; margin-top: 10px; border-top: 1px dashed #ccc; padding-top: 5px;">
        <em>Catatan: Nilai Komisi Pengurus dihitung otomatis oleh sistem berdasarkan % dari pendapatan kotor. Laporan ini digenerate pada {{ now()->format('d M Y H:i:s') }}.</em>
    </div>

    <!-- AREA TANDA TANGAN -->
    <div class="signature-section">
        <div class="sign-box">
            <div>Mengetahui,</div>
            <div class="sign-line">Manager Finance</div>
        </div>
        <div class="sign-box">
            <div>Diperiksa,</div>
            <div class="sign-line">Staff Admin</div>
        </div>
        <div class="sign-box">
            <div>Pengurus,</div>
            <div class="sign-line">{{ $coordinator->name }}</div>
        </div>
    </div>

</body>
</html>
