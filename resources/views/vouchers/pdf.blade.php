<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Voucher Hotspot</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; }
        th { background: #f3f4f6; text-align: left; font-weight: 700; }
    </style>
    </head>
<body>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Password</th>
                <th>Profile</th>
                <th>Durasi</th>
                <th>Quota(MB)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vouchers as $v)
                <tr>
                    <td>{{ $v->username }}</td>
                    <td>{{ $v->password }}</td>
                    <td>{{ $v->profile }}</td>
                    <td>{{ $v->duration_seconds }}</td>
                    <td>{{ $v->quota_mb }}</td>
                    <td>{{ $v->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
