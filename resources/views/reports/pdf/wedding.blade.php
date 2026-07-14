<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        th { background: #f5f5f5; }
        .mb-2 { margin-bottom: 8px; }
    </style>
</head>
<body>
    <h2 class="mb-2">Wedding Report</h2>
    <div class="mb-2">From: {{ $from }} | To: {{ $to }}</div>
    <div class="mb-2">Total Booking: {{ $summary['total_booking'] }} | Revenue: Rp {{ number_format($summary['revenue'], 0, ',', '.') }} | Pending Payment: {{ $summary['pending_payment'] }}</div>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Pelanggan</th>
                <th>WA</th>
                <th>Tanggal</th>
                <th>Lokasi</th>
                <th>Paket</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $b)
                <tr>
                    <td>{{ $b->booking_number }}</td>
                    <td>{{ $b->customer_name }}</td>
                    <td>{{ $b->customer_whatsapp }}</td>
                    <td>{{ $b->event_date?->toDateString() }}</td>
                    <td>{{ $b->location }}</td>
                    <td>{{ $b->package?->name }}</td>
                    <td>{{ $b->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

