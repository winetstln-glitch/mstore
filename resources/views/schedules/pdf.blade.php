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
        th, td { border: 1px solid #cbd5e1; padding: 6px; vertical-align: top; }
        th { background: #e2e8f0; font-weight: 700; text-align: center; }
        .center { text-align: center; }
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
                <th style="width: 70px;">Week</th>
                <th style="width: 120px;">Range</th>
                @foreach($technicians as $tech)
                    <th>{{ $tech->schedule_name ?? $tech->name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($weeks as $week)
                <tr>
                    <td class="center">Week {{ $week['week_number'] }}</td>
                    <td class="center">{{ $week['range'] }}</td>
                    @foreach($technicians as $tech)
                        @php $status = $week['statuses'][$tech->id] ?? 'off'; @endphp
                        <td class="center">
                            @if($status === 'piket')
                                Shift 1
                            @elseif($status === 'backup')
                                Shift 2
                            @else
                                Off
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
