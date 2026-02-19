<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card Cyberpunk - Downloadable</title>
    @if(empty($isPdf))
        <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Share+Tech+Mono&display=swap" rel="stylesheet">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    @endif
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: #111;
            font-family: {{ empty($isPdf) ? "'Share Tech Mono', monospace" : "DejaVu Sans, Arial, sans-serif" }};
            padding: 20px;
        }
        :root{
            --ui-font: {{ empty($isPdf) ? "'Inter', Arial, sans-serif" : "'DejaVu Sans', Arial, sans-serif" }};
        }

        /* Area Wrapper untuk tombol */
        .action-area {
            margin-bottom: 30px;
            text-align: center;
            z-index: 10;
        }

        .btn-download {
            background: linear-gradient(45deg, #00ff41, #00f3ff);
            border: none;
            padding: 15px 40px;
            font-family: {{ empty($isPdf) ? "'Orbitron', sans-serif" : "inherit" }};
            font-weight: bold;
            font-size: 16px;
            color: #000;
            border-radius: 50px;
            cursor: pointer;
            box-shadow: 0 0 20px rgba(0, 255, 65, 0.4);
            transition: transform 0.2s, box-shadow 0.2s;
            text-transform: uppercase;
        }

        .btn-download:hover {
            transform: scale(1.05);
            box-shadow: 0 0 30px rgba(0, 255, 65, 0.8);
        }

        /* Container Kartu (Tanpa efek 3D hover agar hasil download statis) */
        .id-card {
            width: 360px;
            height: 520px;
            /* Background Solid (Hindari backdrop-filter agar hasil download bersih) */
            background: linear-gradient(135deg, #1a2a3a 0%, #050505 100%);
            border: 2px solid #00ff41;
            box-shadow: 0 0 30px rgba(0, 255, 65, 0.2);
            border-radius: 15px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px 25px;
            color: #00ff41;
        }

        /* Grid Background Pattern */
        .id-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: 
                linear-gradient(rgba(0, 255, 65, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 255, 65, 0.05) 1px, transparent 1px);
            background-size: 20px 20px;
            z-index: 0;
        }

        /* Animasi Scan Line */
        .scan-line {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 5px;
            background: #00ff41;
            box-shadow: 0 0 15px #00ff41;
            animation: scan 4s infinite linear;
            z-index: 10;
            opacity: 0.8;
        }

        @keyframes scan {
            0% { top: -10%; }
            100% { top: 110%; }
        }

        /* Konten Utama */
        .content-wrapper {
            position: relative;
            z-index: 2;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Header */
        .header-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
            text-shadow: 0 0 10px #00ff41;
        }
        .logo-icon { font-size: 28px; }
        .logo-text {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: 24px;
            letter-spacing: 2px;
            color: #fff;
        }

        /* Foto Hexagon */
        .hex-container {
            position: relative;
            width: 150px;
            height: 170px;
            margin-bottom: 20px;
        }
        .hex-border {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: linear-gradient(160deg, #00ff41, #00f3ff);
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
        }
        .hex-img {
            position: absolute;
            top: 3px; left: 3px;
            width: calc(100% - 6px);
            height: calc(100% - 6px);
            background: #000;
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
            overflow: hidden;
        }
        .hex-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: grayscale(40%);
        }

        /* Teks Info */
        .profile-name {
            font-family: 'Orbitron', sans-serif;
            font-size: 26px;
            font-weight: 700;
            color: #fff;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .profile-title {
            font-size: 14px;
            color: #00f3ff;
            background: rgba(0, 243, 255, 0.1);
            padding: 5px 20px;
            border: 1px solid rgba(0, 243, 255, 0.3);
            border-radius: 20px;
            margin-bottom: 25px;
            letter-spacing: 1px;
        }

        /* Detail Data */
        .data-card {
            width: 100%;
            background: rgba(0, 0, 0, 0.8);
            border-left: 4px solid #00ff41;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 0 8px 8px 0;
        }
        .data-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 5px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 2px;
        }
        .data-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .label { color: #777; font-family: var(--ui-font); font-weight: 600; letter-spacing: .3px; }
        .value { color: #fff; font-family: var(--ui-font); font-weight: 700; letter-spacing: .2px; }

        /* Footer QR */
        .footer-id {
            margin-top: auto;
            display: flex;
            width: 100%;
            justify-content: space-between;
            align-items: center;
            padding-top: 10px;
            border-top: 1px solid rgba(0, 255, 65, 0.2);
        }
        .status-text {
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .dot { width: 8px; height: 8px; background: #00ff41; border-radius: 50%; box-shadow: 0 0 10px #00ff41; }
        .qr-img { width: 45px; height: 45px; background: #fff; padding: 2px; }

    </style>
</head>
<body>
    <?php
        $company = config('app.name', 'MStore');
        $isPdf = !empty($isPdf);
        // Logo
        $logoPublic = public_path('img/logo.png');
        $logoUrl = asset('img/logo.png');
        $logoSrc = file_exists($logoPublic) ? ($isPdf ? $logoPublic : $logoUrl) : null;
        // Avatar
        $avatarPublic = (isset($user) && $user->avatar) ? public_path('storage/' . $user->avatar) : null;
        $avatarUrl = (isset($user) && $user->avatar) ? asset('storage/' . $user->avatar) : null;
        $avatarSrc = ($avatarPublic && file_exists($avatarPublic)) ? ($isPdf ? $avatarPublic : $avatarUrl) : null;
        // Role/Division
        $role = isset($user) ? ($user->role?->label ?? $user->role?->name ?? 'Staff') : 'Staff';
        $department = $role;
        // Employee Code
        $code = isset($user) ? ('EMP-' . str_pad($user->id, 5, '0', STR_PAD_LEFT)) : 'EMP-00000';
        // QR
        $qrUrl = $qrUrl ?? "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={$code}";
        $qrSrc = $qrSrc ?? $qrUrl;
    ?>

    <!-- Tombol Download -->
    @if(empty($isPdf))
        <div class="action-area">
            <button class="btn-download" onclick="downloadCard()">
                Download ID Card (PNG)
            </button>
            <p style="color: #666; margin-top: 10px; font-size: 12px;">Klik tombol di atas untuk mengunduh gambar</p>
        </div>
    @endif

    <!-- ID Card Target -->
    <div id="id-card-capture" class="id-card">
        <!-- Animasi Garis -->
        @if(empty($isPdf))
            <div class="scan-line"></div>
        @endif

        <div class="content-wrapper">
            <!-- Logo -->
            <div class="header-logo">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Logo" style="width:28px;height:28px;border-radius:4px;background:#fff;padding:2px;">
                @else
                    <span class="logo-icon">⚡</span>
                @endif
                <span class="logo-text">{{ $company }}</span>
            </div>

            <!-- Foto Hexagon -->
            <div class="hex-container">
                <div class="hex-border"></div>
                <div class="hex-img">
                    @if($avatarSrc)
                        <img src="{{ $avatarSrc }}" alt="User">
                    @else
                        <img src="https://ui-avatars.com/api/?name={{ isset($user) ? urlencode($user->name) : 'User' }}&background=0b1016&color=fff" alt="User">
                    @endif
                </div>
            </div>

            <!-- Identitas -->
            <div class="profile-name">{{ isset($user) ? strtoupper($user->name) : 'EMPLOYEE NAME' }}</div>
            <div class="profile-title">{{ strtoupper($role) }}</div>

            <!-- Data Detail -->
            <div class="data-card">
                <div class="data-row">
                    <span class="label">ID KARYAWAN</span>
                    <span class="value">{{ $code }}</span>
                </div>
                <div class="data-row">
                    <span class="label">DIVISI</span>
                    <span class="value">{{ strtoupper($department) }}</span>
                </div>
                @if(isset($user) && !empty($user->email))
                <div class="data-row">
                    <span class="label">EMAIL</span>
                    <span class="value">{{ $user->email }}</span>
                </div>
                @endif
                @if(isset($user) && !empty($user->phone))
                <div class="data-row">
                    <span class="label">TELEPON</span>
                    <span class="value">{{ $user->phone }}</span>
                </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="footer-id">
                <div class="status-text">
                    <span class="dot"></span> STATUS: ACTIVE
                </div>
                
            </div>
        </div>
    </div>

    @if(empty($isPdf))
        <script>
            function downloadCard() {
                const card = document.getElementById('id-card-capture');
                html2canvas(card, {
                    useCORS: true,
                    scale: 2,
                    backgroundColor: null
                }).then(canvas => {
                    const link = document.createElement('a');
                    link.download = 'ID_Card.png';
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                });
            }
        </script>
    @endif
</body>
</html>
