<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>WhatsApp Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f3f3f3; }
    </style>
</head>
<body>
    <h3>WhatsApp Report</h3>
    <div>From: {{ $from }}</div>
    <div>To: {{ $to }}</div>
    <br>
    <div>Incoming: {{ $summary['incoming'] }}</div>
    <div>Outgoing: {{ $summary['outgoing'] }}</div>
    <div>AI Usage: {{ $summary['ai_usage'] }}</div>
    <br>
    <table>
        <thead>
            <tr>
                <th>Intent</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topIntents as $row)
                <tr>
                    <td>{{ $row['intent'] }}</td>
                    <td>{{ $row['total'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

