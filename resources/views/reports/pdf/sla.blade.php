<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>SLA Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f3f3f3; }
    </style>
</head>
<body>
    <h3>SLA Report</h3>
    <div>From: {{ $from }}</div>
    <div>To: {{ $to }}</div>
    <br>
    <div>Compliance: {{ $summary['compliance_percent'] }}%</div>
    <div>Breach: {{ $summary['breach_percent'] }}%</div>
    <div>Breaches: {{ $summary['breaches'] }}</div>
    <br>
    <table>
        <thead>
            <tr>
                <th>No Tiket</th>
                <th>Status</th>
                <th>SLA</th>
                <th>Dibuat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($criticalTickets as $t)
                <tr>
                    <td>{{ $t->ticket_number }}</td>
                    <td>{{ $t->status }}</td>
                    <td>{{ $t->sla_status }}</td>
                    <td>{{ $t->created_at?->format('d M Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

