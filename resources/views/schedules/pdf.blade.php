<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Shift</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; margin: 0; padding: 0; }
        h2 { margin: 0 0 6px; font-size: 16px; }
        h3 { margin: 14px 0 6px; font-size: 12px; page-break-after: avoid; }
        .meta { margin-bottom: 10px; color: #334155; }
        .legend { margin-bottom: 15px; font-size: 9px; border: 1px solid #e2e8f0; padding: 8px; border-radius: 4px; background: #f8fafc; }
        .legend-item { display: inline-block; margin-right: 15px; }
        .legend-box { display: inline-block; width: 12px; height: 12px; vertical-align: middle; margin-right: 4px; border: 1px solid #cbd5e1; }
        
        table { width: 100%; border-collapse: collapse; table-layout: fixed; page-break-inside: avoid; margin-bottom: 20px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; vertical-align: middle; word-wrap: break-word; }
        th { background: #e2e8f0; font-weight: 700; text-align: center; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        
        .center { text-align: center; }
        .small { font-size: 8px; color: #475569; line-height: 1.2; }
        .employee-info { font-weight: 800; font-size: 10px; }
        
        /* Cell Colors - With !important for DomPDF consistency */
        .cell-off { background-color: #f8fafc !important; color: #64748b !important; font-weight: 700; }
        .cell-s1 { background-color: #16a34a !important; color: #ffffff !important; font-weight: 800; }
        .cell-s2 { background-color: #f59e0b !important; color: #111827 !important; font-weight: 800; }
        
        .page-break { page-break-before: always; }
        
        /* Footer Pagination Style */
        #footer { position: fixed; bottom: -30px; left: 0; right: 0; height: 30px; text-align: right; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>
    {{-- Pagination Script for DomPDF --}}
    <script type="text/php">
        if (isset($pdf)) {
            $x = 750;
            $y = 560;
            $text = "Halaman {PAGE_NUM} dari {PAGE_COUNT}";
            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $size = 8;
            $color = array(0.58, 0.64, 0.72);
            $word_space = 0.0;
            $char_space = 0.0;
            $angle = 0.0;
            $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
        }
    </script>

    <div id="footer">
        Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}
    </div>

    <h2>{{ ($mode ?? 'weekly') === 'daily' ? 'Jadwal Harian Karyawan' : 'Jadwal Shift Karyawan' }}</h2>
    <div class="meta">
        Periode: {{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}<br>
        Shift 1: {{ $shift1Start }} - {{ $shift1End }} | Shift 2: {{ $shift2Start }} - {{ $shift2End }}
    </div>

    <div class="legend">
        <strong>Keterangan:</strong>
        <div class="legend-item"><span class="legend-box cell-s1"></span> S1: Shift 1 (Piket)</div>
        <div class="legend-item"><span class="legend-box cell-s2"></span> S2: Shift 2 (Backup)</div>
        <div class="legend-item"><span class="legend-box cell-off"></span> OFF: Libur</div>
    </div>

    @php
        $statusMap = [
            'piket' => ['class' => 'cell-s1', 'label' => 'S1'],
            'backup' => ['class' => 'cell-s2', 'label' => 'S2'],
            'off' => ['class' => 'cell-off', 'label' => 'OFF'],
        ];
    @endphp

    @if(($mode ?? 'weekly') === 'daily')
        @foreach($groups as $group)
            @php $users = $group['users'] ?? collect(); @endphp
            @if($users->count() > 0)
                <div class="{{ !$loop->first ? 'page-break' : '' }}">
                    <h3>{{ $group['label'] }} ({{ $users->count() }})</h3>
                    @foreach($calendarWeeks as $week)
                        <div class="small" style="margin-bottom: 4px; font-weight: bold; color: #1e293b;">
                            Minggu: {{ $week['days'][0]->translatedFormat('d M Y') }} - {{ $week['days'][6]->translatedFormat('d M Y') }}
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 150px;">Karyawan</th>
                                    @foreach($week['days'] as $day)
                                        <th style="width: 65px;">
                                            {{ $day->translatedFormat('D') }}<br>
                                            <span class="small">{{ $day->translatedFormat('d/m') }}</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $tech)
                                    <tr>
                                        <td>
                                            <div class="employee-info">{{ $tech->schedule_name ?? $tech->name }}</div>
                                            <div class="small">
                                                {{ $tech->schedule_position ?? ($tech->role?->label ?? 'Karyawan') }}
                                            </div>
                                        </td>
                                        @foreach($week['days'] as $day)
                                            @php
                                                $inMonth = ((int) $day->month) === (int) $month;
                                                $key = $day->format('Y-m-d');
                                                $row = $dailySchedules->get($key)?->get($tech->id);
                                                $status = $row ? $row->status : 'off';
                                                $cfg = $statusMap[$status] ?? $statusMap['off'];
                                            @endphp
                                            <td class="center {{ $inMonth ? $cfg['class'] : '' }}">
                                                {{ $inMonth ? $cfg['label'] : '-' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endforeach
                </div>
            @endif
        @endforeach
    @else
        @foreach($groups as $group)
            @php $users = $group['users'] ?? collect(); @endphp
            @if($users->count() > 0)
                <div class="{{ !$loop->first ? 'page-break' : '' }}">
                    <h3>{{ $group['label'] }} ({{ $users->count() }})</h3>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 150px;">Karyawan</th>
                                @foreach($weeksData as $week)
                                    <th style="width: 80px;">
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
                                        <div class="employee-info">{{ $tech->schedule_name ?? $tech->name }}</div>
                                        <div class="small">
                                            {{ $tech->schedule_position ?? ($tech->role?->label ?? 'Karyawan') }}
                                        </div>
                                    </td>
                                    @foreach($weeksData as $week)
                                        @php
                                            $schedule = $schedules->get($week['week_number'])?->get($tech->id);
                                            $status = $schedule ? $schedule->status : 'off';
                                            $cfg = $statusMap[$status] ?? $statusMap['off'];
                                        @endphp
                                        <td class="center {{ $cfg['class'] }}">{{ $cfg['label'] }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endforeach
    @endif
</body>
</html>