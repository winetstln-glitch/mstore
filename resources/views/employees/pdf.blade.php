<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Karyawan</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        .header { display: table; width: 100%; margin-bottom: 12px; }
        .header .logo { display: table-cell; width: 64px; vertical-align: middle; }
        .header .meta { display: table-cell; vertical-align: middle; }
        .header h2 { margin: 0; font-size: 18px; }
        .header p { margin: 2px 0 0; font-size: 11px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; }
        th { background: #f3f4f6; text-align: left; font-weight: 700; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            @if(!empty($logo))
                <img src="{{ $logo }}" alt="Logo" style="height:48px;">
            @endif
        </div>
        <div class="meta">
            <h2>Data Karyawan</h2>
            <p>Dicetak: {{ $printedAt->format('d-m-Y H:i') }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>NIK</th>
                <th>Jabatan</th>
                <th>Departemen</th>
                <th>No HP</th>
                <th>Email</th>
                <th>Status</th>
                <th>Integrasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $employee)
                <tr>
                    <td>{{ $employee->full_name }}</td>
                    <td>{{ $employee->nik }}</td>
                    <td>{{ $employee->position }}</td>
                    <td>{{ $employee->department }}</td>
                    <td>{{ $employee->phone }}</td>
                    <td>{{ $employee->email }}</td>
                    <td>{{ $employee->employment_status }}</td>
                    <td>
                        @php
                            $integration = [];
                            if ($employee->user_id) $integration[] = 'User';
                            if ($employee->wash_employee_id) $integration[] = 'Wash';
                        @endphp
                        {{ $integration ? implode(', ', $integration) : 'Manual' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Belum ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
