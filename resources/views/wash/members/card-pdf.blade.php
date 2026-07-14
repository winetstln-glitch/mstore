<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kartu Member {{ $member->member_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; }
        .card { border: 1px solid #d1d5db; border-radius: 16px; padding: 24px; background: linear-gradient(135deg, #0f172a, #1d4ed8); color: #fff; }
        .badge { display: inline-block; padding: 4px 10px; background: rgba(255,255,255,.18); border-radius: 999px; font-size: 12px; }
        .muted { color: #dbeafe; }
        .row { width: 100%; }
        .col-left { width: 60%; display: inline-block; vertical-align: top; }
        .col-right { width: 35%; display: inline-block; vertical-align: top; text-align: right; }
        .qr { width: 180px; height: 180px; background: #fff; padding: 8px; border-radius: 12px; }
        .meta { margin-top: 18px; line-height: 1.7; }
        .small { font-size: 12px; }
        .title { font-size: 22px; font-weight: bold; margin-bottom: 8px; }
        .member-number { font-size: 18px; font-weight: bold; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="row">
            <div class="col-left">
                <div class="badge">GT Wash Digital Member Card</div>
                <div class="title" style="margin-top: 16px;">{{ $member->name }}</div>
                <div class="member-number">{{ $card->card_number }}</div>
                <div class="meta">
                    <div>Level: <strong>{{ $member->level?->name ?? 'Bronze Member' }}</strong></div>
                    <div>Kunjungan: <strong>{{ number_format((int) $member->total_visits, 0, ',', '.') }}</strong></div>
                    <div>Plat: <strong>{{ $member->vehicles->pluck('vehicle_plate')->join(', ') ?: '-' }}</strong></div>
                    <div>WhatsApp: <strong>{{ $member->whatsapp }}</strong></div>
                </div>
            </div>
            <div class="col-right">
                <img src="{{ $qrUrl }}" alt="QR Verification" class="qr">
                <div class="small muted" style="margin-top: 10px;">Member Verification</div>
            </div>
        </div>
    </div>

    <div style="margin-top: 18px;" class="small">
        Verifikasi: {{ $verificationUrl }}
    </div>
</body>
</html>

