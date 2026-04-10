<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Shift</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; }
        h2 { margin: 0 0 6px; font-size: 16px; }
        h3 { margin: 14px 0 6px; font-size: 12px; }
        .meta { margin-bottom: 10px; color: #334155; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; vertical-align: top; }
        th { background: #e2e8f0; font-weight: 700; text-align: center; }
        .center { text-align: center; }
        .small { font-size: 9px; color: #475569; }
        .cell-off { background: #f8fafc; color: #64748b; font-weight: 700; }
        .cell-s1 { background: #16a34a; color: #ffffff; font-weight: 800; }
        .cell-s2 { background: #f59e0b; color: #111827; font-weight: 800; }
    </style>
</head>
<body>
    <h2>{{ ($mode ?? 'weekly') === 'daily' ? 'Jadwal Harian Karyawan' : 'Jadwal Shift Karyawan' }}</h2>
    <div class="meta">
        Periode: {{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}<br>
        Shift 1: {{ $shift1Start }} - {{ $shift1End }} | Shift 2: {{ $shift2Start }} - {{ $shift2End }}
    </div>

    @if(($mode ?? 'weekly') === 'daily')
        @foreach($groups as $group)
            @php $users = $group['users'] ?? collect(); @endphp
            @if($users->count() > 0)
                <h3>{{ $group['label'] }} ({{ $users->count() }})</h3>
                @foreach($calendarWeeks as $week)
                    <div class="small" style="margin-bottom: 6px;">
                        {{ $week['days'][0]->translatedFormat('d M Y') }} - {{ $week['days'][6]->translatedFormat('d M Y') }}
                    </div>
                    <table style="margin-bottom: 14px;">
                        <thead>
                            <tr>
                                <th style="width: 180px;">Karyawan</th>
                                @foreach($week['days'] as $day)
                                    <th style="width: 70px;">
                                        {{ $day->translatedFormat('D') }}<br>
                                        <span class="small">{{ $day->translatedFormat('d M') }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $tech)
                                <tr>
                                    <td>
                                        <div style="font-weight: 800;">{{ $tech->schedule_name ?? $tech->name }}</div>
                                        <div class="small">
                                            {{ $tech->schedule_position ?? ($tech->role?->label ?? 'Karyawan') }}
                                            @if(!empty($tech->schedule_department))
                                                • {{ $tech->schedule_department }}
                                            @endif
                                        </div>
                                    </td>
                                    @foreach($week['days'] as $day)
                                        @php
                                            $inMonth = ((int) $day->month) === (int) $month;
                                            $key = $day->format('Y-m-d');
                                            $daySchedules = $dailySchedules->get($key);
                                            $row = $daySchedules ? $daySchedules->firstWhere('user_id', $tech->id) : null;
                                            $status = $row ? $row->status : 'off';
                                            $cellClass = $status === 'piket' ? 'cell-s1' : ($status === 'backup' ? 'cell-s2' : 'cell-off');
                                            $label = $status === 'piket' ? 'S1' : ($status === 'backup' ? 'S2' : 'OFF');
                                        @endphp
                                        <td class="center {{ $cellClass }}">
                                            {{ $inMonth ? $label : '-' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            @endif
        @endforeach
    @else
        @foreach($groups as $group)
            @php $users = $group['users'] ?? collect(); @endphp
            @if($users->count() > 0)
                <h3>{{ $group['label'] }} ({{ $users->count() }})</h3>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 180px;">Karyawan</th>
                            @foreach($weeksData as $week)
                                <th style="width: 86px;">
                                    W{{ $week['week_number'] }}<br>
                                    <span class="small">{{ $week['week_start_display'] }} - {{ $week['week_end_display'] }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $tech)
                            <tr>
                                <td>
                                    <div style="font-weight: 800;">{{ $tech->schedule_name ?? $tech->name }}</div>
                                    <div class="small">
                                        {{ $tech->schedule_position ?? ($tech->role?->label ?? 'Karyawan') }}
                                        @if(!empty($tech->schedule_department))
                                            • {{ $tech->schedule_department }}
                                        @endif
                                    </div>
                                </td>
                                @foreach($weeksData as $week)
                                    @php
                                        $weekSchedules = $schedules->get($week['week_number']);
                                        $schedule = $weekSchedules ? $weekSchedules->where('user_id', $tech->id)->first() : null;
                                        $status = $schedule ? $schedule->status : 'off';
                                        $cellClass = $status === 'piket' ? 'cell-s1' : ($status === 'backup' ? 'cell-s2' : 'cell-off');
                                        $label = $status === 'piket' ? 'S1' : ($status === 'backup' ? 'S2' : 'OFF');
                                    @endphp
                                    <td class="center {{ $cellClass }}">{{ $label }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach
    @endif
</body>
</html>
