<!DOCTYPE html>
<html>
<head>
    <title>Rekap Absensi & Gaji Teknisi</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .header p { margin: 5px 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background-color: #fafafa; }
        .footer { margin-top: 30px; text-align: right; font-size: 10px; color: #777; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Rekap Absensi & Gaji Teknisi</h2>
        <p>
            Periode: 
            @if($request->filled('month'))
                {{ \Carbon\Carbon::parse($request->month)->translatedFormat('F Y') }}
            @elseif($request->filled('date'))
                {{ \Carbon\Carbon::parse($request->date)->translatedFormat('d F Y') }}
            @else
                Semua Data
            @endif
        </p>
    </div>

    @foreach($summary as $data)
    <div style="margin-bottom: 30px; page-break-inside: avoid;">
        <h3 style="border-bottom: 2px solid #eee; padding-bottom: 5px; margin-bottom: 10px;">
            {{ $data['user']->name }} <small style="font-weight: normal; font-size: 0.8em; color: #666;">({{ $data['user']->email }})</small>
        </h3>
        
        <table>
            <thead>
                <tr>
                    <th width="120">Tanggal</th>
                    <th width="80">Jam Masuk</th>
                    <th width="80">Jam Pulang</th>
                    <th>Status</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['dates'] as $attendance)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($attendance->clock_in)->translatedFormat('d F Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') }}</td>
                    <td>{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '-' }}</td>
                    <td>
                        <span style="color: {{ $attendance->status == 'present' ? 'green' : 'orange' }}">
                            {{ __(ucfirst($attendance->status)) }}
                        </span>
                    </td>
                    <td>{{ $attendance->notes }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; width: 50%; margin-left: auto;">
            <table style="margin: 0; border: none;">
                <tr style="border: none;">
                    <td style="border: none; padding: 2px;">Total Hadir (Kerja)</td>
                    <td style="border: none; padding: 2px;" class="text-right">{{ $data['present_count'] }} hari</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none; padding: 2px;">Total Cuti/Izin/Sakit</td>
                    <td style="border: none; padding: 2px;" class="text-right">{{ $data['leave_count'] }} hari</td>
                </tr>
                <tr style="border: none; border-bottom: 1px solid #ccc;">
                    <td style="border: none; padding: 2px; font-weight: bold;">Total Hari Dibayar</td>
                    <td style="border: none; padding: 2px; font-weight: bold;" class="text-right">{{ $data['paid_days'] }} hari</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none; padding: 5px 2px;">Gaji Harian</td>
                    <td style="border: none; padding: 5px 2px;" class="text-right">Rp {{ number_format($data['daily_salary'], 0, ',', '.') }}</td>
                </tr>
                @if($data['total_bonus'] > 0)
                <tr style="border: none;">
                    <td style="border: none; padding: 5px 2px; color: green;">+ Bonus</td>
                    <td style="border: none; padding: 5px 2px; color: green;" class="text-right">Rp {{ number_format($data['total_bonus'], 0, ',', '.') }}</td>
                </tr>
                @endif
                @if($data['total_kasbon'] > 0)
                <tr style="border: none;">
                    <td style="border: none; padding: 5px 2px; color: red;">- Potongan Kasbon</td>
                    <td style="border: none; padding: 5px 2px; color: red;" class="text-right">Rp {{ number_format($data['total_kasbon'], 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr style="border-top: 2px solid #ccc;">
                    <td style="border: none; padding: 5px 2px; font-weight: bold; font-size: 1.1em;">Total Gaji</td>
                    <td style="border: none; padding: 5px 2px; font-weight: bold; font-size: 1.1em;" class="text-right">Rp {{ number_format($data['total_salary'], 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>
    @endforeach

    <div class="footer">
        Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}
    </div>
</body>
</html>