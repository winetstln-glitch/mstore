<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Wash</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 6px 0; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f2f2f2; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Laporan Car Wash</h1>
    <div class="muted">Rentang Harian: {{ $startDate }} s/d {{ $endDate }} | Bulan: {{ $month }}</div>

    <table>
        <tr>
            <th>Ringkasan Harian</th>
            <th class="right">Nominal</th>
            <th>Ringkasan Bulanan</th>
            <th class="right">Nominal</th>
        </tr>
        <tr>
            <td>Pemasukan</td><td class="right">{{ number_format($dailyIncome,0,',','.') }}</td>
            <td>Pemasukan</td><td class="right">{{ number_format($monthlyIncome,0,',','.') }}</td>
        </tr>
        <tr>
            <td>Pengeluaran</td><td class="right">{{ number_format($dailyExpense,0,',','.') }}</td>
            <td>Pengeluaran</td><td class="right">{{ number_format($monthlyExpense,0,',','.') }}</td>
        </tr>
        <tr>
            <td><strong>Laba</strong></td><td class="right"><strong>{{ number_format($dailyIncome-$dailyExpense,0,',','.') }}</strong></td>
            <td><strong>Laba</strong></td><td class="right"><strong>{{ number_format($monthlyIncome-$monthlyExpense,0,',','.') }}</strong></td>
        </tr>
        <tr>
            <td>Modal Awal Caffe</td><td class="right">{{ number_format($dailyCaffeInitialCapital,0,',','.') }}</td>
            <td>Modal Awal Caffe</td><td class="right">{{ number_format($monthlyCaffeInitialCapital,0,',','.') }}</td>
        </tr>
        <tr>
            <td>Pendapatan Caffe</td><td class="right">{{ number_format($dailyCaffeRevenue,0,',','.') }}</td>
            <td>Pendapatan Caffe</td><td class="right">{{ number_format($monthlyCaffeRevenue,0,',','.') }}</td>
        </tr>
        <tr>
            <td><strong>Selisih Caffe</strong></td><td class="right"><strong>{{ number_format($dailyCaffeRevenue-$dailyCaffeInitialCapital,0,',','.') }}</strong></td>
            <td><strong>Selisih Caffe</strong></td><td class="right"><strong>{{ number_format($monthlyCaffeRevenue-$monthlyCaffeInitialCapital,0,',','.') }}</strong></td>
        </tr>
    </table>

    <table>
        <tr>
            <th>Membership & Loyalty</th>
            <th class="right">Harian</th>
            <th>Keterangan</th>
            <th class="right">Bulanan</th>
        </tr>
        <tr>
            <td>Member Aktif</td>
            <td class="right">{{ number_format($memberActiveCount,0,',','.') }}</td>
            <td>Member Baru</td>
            <td class="right">{{ number_format($memberNewMonthlyCount,0,',','.') }}</td>
        </tr>
        <tr>
            <td>Member Baru</td>
            <td class="right">{{ number_format($memberNewDailyCount,0,',','.') }}</td>
            <td>Reward Redemption</td>
            <td class="right">{{ number_format($monthlyRewardRedemptionCount,0,',','.') }}</td>
        </tr>
        <tr>
            <td>Reward Redemption</td>
            <td class="right">{{ number_format($dailyRewardRedemptionCount,0,',','.') }}</td>
            <td>Total Top Member Ditampilkan</td>
            <td class="right">{{ number_format($topMembers->count(),0,',','.') }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr><th colspan="4">Level Distribution</th></tr>
            <tr>
                <th>Level</th>
                <th class="right">Member</th>
                <th class="right">Diskon</th>
                <th class="right">Priority Rank</th>
            </tr>
        </thead>
        <tbody>
            @foreach($levelDistribution as $level)
            <tr>
                <td>{{ $level->name }}</td>
                <td class="right">{{ number_format($level->members_count,0,',','.') }}</td>
                <td class="right">{{ rtrim(rtrim(number_format((float) $level->discount_percent, 2, ',', '.'), '0'), ',') }}%</td>
                <td class="right">{{ number_format((int) $level->priority_rank,0,',','.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <thead>
            <tr><th colspan="6">Top Member</th></tr>
            <tr>
                <th>Member</th>
                <th>No Member</th>
                <th>Level</th>
                <th class="right">Trx</th>
                <th class="right">Visit</th>
                <th class="right">Spending</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topMembers as $member)
            <tr>
                <td>{{ $member->name }}</td>
                <td>{{ $member->member_number }}</td>
                <td>{{ $member->level?->name ?? 'Bronze Member' }}</td>
                <td class="right">{{ number_format((int) $member->total_transactions,0,',','.') }}</td>
                <td class="right">{{ number_format((int) $member->total_visits,0,',','.') }}</td>
                <td class="right">{{ number_format((float) $member->total_spending,0,',','.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <thead>
            <tr><th colspan="8">Loyalty Progress</th></tr>
            <tr>
                <th>Member</th>
                <th>No Member</th>
                <th>Level</th>
                <th>Plat</th>
                <th class="right">Progress</th>
                <th class="right">Sisa</th>
                <th class="right">Lifetime</th>
                <th>Terakhir</th>
            </tr>
        </thead>
        <tbody>
            @foreach($loyaltyProgressRows as $row)
            <tr>
                <td>{{ $row->member_name }}</td>
                <td>{{ $row->member_number }}</td>
                <td>{{ $row->level_name }}</td>
                <td>{{ $row->vehicle_plate }}</td>
                <td class="right">{{ $row->progress }}/{{ $row->target }}</td>
                <td class="right">{{ $row->remaining }}</td>
                <td class="right">{{ number_format((int) $row->lifetime_paid_count,0,',','.') }}</td>
                <td>{{ $row->last_paid_at?->format('d-m-Y H:i') ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <thead>
            <tr><th colspan="7">Rincian Pemasukan Harian</th></tr>
            <tr>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>No Antri</th>
                <th>No Transaksi</th>
                <th>Kasir</th>
                <th>Metode</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dailyIncomeRows as $r)
            <tr>
                <td>{{ $r->created_at->format('Y-m-d') }}</td>
                <td>{{ $r->created_at->format('H:i') }}</td>
                <td>{{ $r->queue_number ?? '-' }}</td>
                <td>{{ $r->transaction_number }}</td>
                <td>{{ $r->user->name ?? '-' }}</td>
                <td>{{ strtoupper($r->payment_method) }}</td>
                <td class="right">{{ number_format($r->total_amount,0,',','.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <thead>
            <tr><th colspan="3">Rincian Pengeluaran Harian</th></tr>
            <tr>
                <th>Tanggal</th>
                <th>Deskripsi</th>
                <th class="right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dailyExpenseRows as $r)
            <tr>
                <td>{{ \Carbon\Carbon::parse($r->transaction_date)->format('Y-m-d') }}</td>
                <td>{{ $r->description }}</td>
                <td class="right">{{ number_format($r->amount,0,',','.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
