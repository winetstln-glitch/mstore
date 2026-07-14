<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Member Wash</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }
        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 760px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 12px 40px rgba(15, 23, 42, 0.12);
            overflow: hidden;
        }
        .header {
            padding: 24px 28px;
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            color: #fff;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }
        .badge {
            background: #198754;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 12px;
            border-radius: 999px;
            letter-spacing: 0.08em;
        }
        .content {
            padding: 28px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        }
        .label {
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 6px;
        }
        .value {
            font-size: 18px;
            font-weight: 700;
        }
        .footer {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="page">
    <div class="card">
        <div class="header">
            <div class="header-top">
                <div>
                    <div style="font-size:12px; letter-spacing:0.08em; text-transform:uppercase; opacity:0.9;">Member Verification</div>
                    <h1 style="margin:8px 0 6px; font-size:30px;">{{ $member->name }}</h1>
                    <div>{{ $member->member_number }}</div>
                </div>
                <div class="badge">VALID</div>
            </div>
        </div>
        <div class="content">
            <div class="grid">
                <div>
                    <div class="label">Level</div>
                    <div class="value">{{ $member->level?->name ?? 'Bronze Member' }}</div>
                </div>
                <div>
                    <div class="label">Status</div>
                    <div class="value">{{ ucfirst((string) $member->status) }}</div>
                </div>
                <div>
                    <div class="label">WhatsApp</div>
                    <div class="value">{{ $member->whatsapp }}</div>
                </div>
                <div>
                    <div class="label">Kendaraan</div>
                    <div class="value">{{ $member->vehicles->pluck('vehicle_plate')->join(', ') ?: '-' }}</div>
                </div>
                <div>
                    <div class="label">Total Kunjungan</div>
                    <div class="value">{{ number_format((int) $member->total_visits, 0, ',', '.') }}</div>
                </div>
                <div>
                    <div class="label">Issued At</div>
                    <div class="value">{{ $card->issued_at?->format('d M Y H:i') ?? '-' }}</div>
                </div>
            </div>
            <div class="footer">
                Kartu member digital ini terverifikasi sebagai member aktif GT Wash.
            </div>
        </div>
    </div>
</div>
</body>
</html>
