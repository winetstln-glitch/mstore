<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>NOC Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f3f3f3; }
    </style>
</head>
<body>
    <h3>NOC Report</h3>
    <div>Tanggal: {{ $date }}</div>
    <br>
    <table>
        <thead>
            <tr>
                <th>Captured</th>
                <th>Health</th>
                <th>ONU Offline</th>
                <th>LOS</th>
                <th>PPPoE Active</th>
                <th>Outage Active</th>
                <th>Ticket Open</th>
            </tr>
        </thead>
        <tbody>
            @foreach($snapshots as $s)
                <tr>
                    <td>{{ $s->captured_at?->format('H:i:s') }}</td>
                    <td>{{ $s->network_health_score }}</td>
                    <td>{{ $s->onu_offline }}</td>
                    <td>{{ $s->onu_los }}</td>
                    <td>{{ $s->pppoe_active_sessions }}</td>
                    <td>{{ $s->outage_active }}</td>
                    <td>{{ $s->ticket_open }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

