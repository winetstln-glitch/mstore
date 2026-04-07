<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Shift</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        h2 { margin: 0 0 6px; font-size: 16px; }
        .meta { margin-bottom: 10px; color: #334155; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; vertical-align: middle; }
        th { background: #e2e8f0; font-weight: 700; text-align: center; }
        .center { text-align: center; }
        .name { font-weight: 700; }
        .muted { color: #64748b; font-size: 10px; }
    </style>
</head>
<body>
    <h2>Jadwal Shift Karyawan</h2>
    <div class="meta">
        Periode: {{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}<br>
        Shift 1: {{ $shift1Start }} - {{ $shift1End }} | Shift 2: {{ $shift2Start }} - {{ $shift2End }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 160px;">Karyawan</th>
                @foreach($weeks as $week)
                    <th style="width: 90px;">
                        W{{ $week['week_number'] }}<br>
                        <span class="muted">{{ $week['range'] }}</span>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($technicians as $tech)
                <tr>
                    <td>
                        <div class="name">{{ $tech->schedule_name ?? $tech->name }}</div>
                        <div class="muted">{{ $tech->position ?? 'Karyawan' }}</div>
                    </td>
                    @foreach($weeks as $week)
                        @php $status = $week['statuses'][$tech->id] ?? 'off'; @endphp
                        <td class="center">
                            @if($status === 'piket')
                                S1
                            @elseif($status === 'backup')
                                S2
                            @else
                                OFF
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
