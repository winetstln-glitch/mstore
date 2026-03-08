@extends('layouts.app')

@section('title', 'Network Ping & Speed Analyzer')

@push('styles')
<style>
    :root {
        --analyzer-shell-bg: radial-gradient(1200px 500px at 10% -20%, #243b75 0%, rgba(11, 15, 26, 0) 60%), radial-gradient(1000px 500px at 90% 120%, #1d4ed8 0%, rgba(11, 15, 26, 0) 55%), #0b0f1a;
        --analyzer-shell-text: #f1f5f9;
        --analyzer-muted-text: #94a3b8;
        --analyzer-card-bg: rgba(23, 32, 53, 0.82);
        --analyzer-card-border: rgba(255, 255, 255, 0.08);
        --analyzer-pill-bg: rgba(23, 32, 53, 0.82);
        --analyzer-control-bg: #0f172a;
        --analyzer-control-text: #e2e8f0;
        --analyzer-control-border: #334155;
        --analyzer-go-bg: rgba(255, 255, 255, 0.2);
    }
    [data-bs-theme="light"] {
        --analyzer-shell-bg: radial-gradient(1200px 500px at 10% -20%, rgba(59, 130, 246, 0.2) 0%, rgba(255, 255, 255, 0) 60%), radial-gradient(1000px 500px at 90% 120%, rgba(14, 165, 233, 0.2) 0%, rgba(255, 255, 255, 0) 55%), #eff6ff;
        --analyzer-shell-text: #0f172a;
        --analyzer-muted-text: #475569;
        --analyzer-card-bg: rgba(255, 255, 255, 0.9);
        --analyzer-card-border: rgba(30, 41, 59, 0.12);
        --analyzer-pill-bg: rgba(255, 255, 255, 0.92);
        --analyzer-control-bg: #ffffff;
        --analyzer-control-text: #0f172a;
        --analyzer-control-border: #cbd5e1;
        --analyzer-go-bg: rgba(15, 23, 42, 0.12);
    }
    .network-shell {
        background: var(--analyzer-shell-bg);
        border-radius: 1.5rem;
        color: var(--analyzer-shell-text);
        padding: 1.5rem;
    }
    .network-shell .text-secondary {
        color: var(--analyzer-muted-text) !important;
    }
    .glass-card {
        background: var(--analyzer-card-bg);
        border: 1px solid var(--analyzer-card-border);
        border-radius: 1.5rem;
        backdrop-filter: blur(16px);
    }
    .metric-value {
        font-weight: 800;
        letter-spacing: -0.02em;
    }
    .speed-circle {
        transform: rotate(-90deg);
        transform-origin: 50% 50%;
        transition: stroke-dashoffset .4s ease;
    }
    .status-pill {
        border-left: 4px solid #22c55e;
        border-radius: 1rem;
        padding: .75rem 1rem;
        background: var(--analyzer-pill-bg);
    }
    .status-dot {
        width: .75rem;
        height: .75rem;
        border-radius: 999px;
        background: #22c55e;
        box-shadow: 0 0 0 0 rgba(34, 197, 94, .8);
        animation: pulse-dot 1.8s infinite;
    }
    .analyzer-controls {
        width: 100%;
    }
    .control-field {
        min-width: 220px;
        background-color: var(--analyzer-control-bg);
        color: var(--analyzer-control-text);
        border-color: var(--analyzer-control-border);
    }
    .control-field:focus {
        background-color: var(--analyzer-control-bg);
        color: var(--analyzer-control-text);
    }
    .go-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.6rem;
        height: 1.6rem;
        border-radius: 999px;
        background: var(--analyzer-go-bg);
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .02em;
    }
    .speedtest-btn {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        font-weight: 700;
    }
    @keyframes pulse-dot {
        0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, .8); }
        70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
        100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }
    @media (max-width: 767.98px) {
        .network-shell {
            border-radius: 1rem;
            padding: 1rem;
        }
        .glass-card {
            border-radius: 1rem;
        }
        .status-pill {
            width: 100%;
        }
        #currentPing,
        #downloadSpeed {
            font-size: 2.35rem !important;
        }
        .analyzer-controls {
            flex-direction: column;
            align-items: stretch;
        }
        .control-field,
        #runTestBtn {
            width: 100%;
            min-width: 0;
        }
        #networkChartWrap {
            height: 260px !important;
        }
    }
</style>
@endpush

@section('content')
<div class="network-shell shadow-sm">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-4">
        <div>
            <h3 class="mb-1 fw-bold d-flex align-items-center gap-2">
                <i data-lucide="zap" class="text-warning"></i>
                NetPulse Pro
            </h3>
            <p class="text-secondary mb-0">Sistem Pemantauan Jaringan Terpadu</p>
        </div>
        <div class="status-pill d-flex align-items-center gap-3">
            <span class="status-dot"></span>
            <div>
                <div class="text-uppercase small text-secondary fw-semibold">Status Jaringan</div>
                <div id="networkType" class="fw-bold text-success">Mendeteksi...</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-4">
            <div class="glass-card p-4 h-100">
                <div class="small text-uppercase text-secondary fw-semibold mb-2">Latensi Jaringan</div>
                <div class="d-flex align-items-end gap-2">
                    <h1 id="currentPing" class="metric-value mb-0" style="font-size: 3.5rem;">--</h1>
                    <span class="text-secondary fs-4 pb-2">ms</span>
                </div>
                <div class="d-flex gap-4 mt-3">
                    <div>
                        <div class="small text-secondary">JITTER</div>
                        <div id="currentJitter" class="fw-semibold text-info">-- ms</div>
                    </div>
                    <div>
                        <div class="small text-secondary">LOSS</div>
                        <div id="currentLoss" class="fw-semibold text-danger">0.0%</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-8">
            <div class="glass-card p-4 h-100">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <div class="small text-uppercase text-secondary fw-semibold mb-2">Kecepatan Unduh Real</div>
                        <div class="d-flex align-items-end gap-2">
                            <h1 id="downloadSpeed" class="metric-value mb-0 text-primary" style="font-size: 3.5rem;">0.00</h1>
                            <span class="text-secondary fs-4 pb-2">Mbps</span>
                        </div>
                        <div class="small text-secondary mt-2">
                            Upload: <span id="uploadSpeed" class="text-info fw-semibold">0.00 Mbps</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center" style="width: 9rem; height: 9rem;">
                        <svg width="140" height="140" viewBox="0 0 140 140">
                            <circle cx="70" cy="70" r="58" stroke="#334155" stroke-width="8" fill="none"></circle>
                            <circle id="speedCircle" class="speed-circle" cx="70" cy="70" r="58" stroke="#3b82f6" stroke-width="8" fill="none" stroke-linecap="round" stroke-dasharray="364.4" stroke-dashoffset="364.4"></circle>
                        </svg>
                    </div>
                </div>
                <div class="progress mt-3" style="height: .45rem; background: #334155;">
                    <div id="speedBar" class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="glass-card p-3 h-100">
                <div class="small text-secondary">IP Publik (IP ISP)</div>
                <div id="publicIp" class="h5 fw-bold mb-0">Mendeteksi...</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="glass-card p-3 h-100">
                <div class="small text-secondary">IP Lokal</div>
                <div id="localIp" class="h5 fw-bold mb-0">Mendeteksi...</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="glass-card p-3 h-100">
                <div class="small text-secondary">MAC Address</div>
                <div id="deviceMac" class="h5 fw-bold mb-0">Mendeteksi...</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="glass-card p-3 h-100">
                <div class="small text-secondary">Vendor Perangkat</div>
                <div id="vendorName" class="h5 fw-bold mb-0">Mendeteksi...</div>
            </div>
        </div>
    </div>

    <div class="glass-card p-4 mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                <i data-lucide="bar-chart-3" class="text-primary"></i>
                Stabilitas Koneksi
            </h5>
            <div class="d-flex gap-2 analyzer-controls">
                <input id="ssidInput" class="form-control form-control-sm control-field" placeholder="SSID, contoh: mastore.net">
                <select id="targetServer" class="form-select form-select-sm control-field">
                    <option value="8.8.8.8">Google DNS (8.8.8.8)</option>
                    <option value="1.1.1.1">Cloudflare DNS (1.1.1.1)</option>
                    <option value="9.9.9.9">Quad9 DNS (9.9.9.9)</option>
                </select>
                <button type="button" id="runTestBtn" class="btn btn-sm btn-primary speedtest-btn"><span class="go-badge">GO</span>Speedtest</button>
            </div>
        </div>
        <div id="networkChartWrap" style="height: 320px;">
            <canvas id="networkChart"></canvas>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-6 col-xl-2">
            <div class="glass-card p-3">
                <div class="small text-secondary">Ping Rata-rata</div>
                <div id="avgPing" class="h4 fw-bold mb-0">-- ms</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-2">
            <div class="glass-card p-3">
                <div class="small text-secondary">Kecepatan Puncak</div>
                <div id="peakSpeed" class="h4 fw-bold mb-0">0.00 Mbps</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-2">
            <div class="glass-card p-3">
                <div class="small text-secondary">Stabilitas</div>
                <div id="stabilityScore" class="h4 fw-bold mb-0">0%</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-2">
            <div class="glass-card p-3">
                <div class="small text-secondary">SSID</div>
                <div id="connectedSsid" class="h6 fw-bold mb-0">Tidak terdeteksi</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-2">
            <div class="glass-card p-3">
                <div class="small text-secondary">Router</div>
                <div id="routerName" class="h6 fw-bold mb-0">Tidak terdeteksi</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-2">
            <div class="glass-card p-3">
                <div class="small text-secondary">ISP</div>
                <div id="ispName" class="h6 fw-bold mb-0">Tidak terdeteksi</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) {
            window.lucide.createIcons();
        }

        const currentPing = document.getElementById('currentPing');
        const currentJitter = document.getElementById('currentJitter');
        const currentLoss = document.getElementById('currentLoss');
        const downloadSpeed = document.getElementById('downloadSpeed');
        const uploadSpeed = document.getElementById('uploadSpeed');
        const speedBar = document.getElementById('speedBar');
        const speedCircle = document.getElementById('speedCircle');
        const networkType = document.getElementById('networkType');
        const avgPing = document.getElementById('avgPing');
        const peakSpeed = document.getElementById('peakSpeed');
        const stabilityScore = document.getElementById('stabilityScore');
        const runTestBtn = document.getElementById('runTestBtn');
        const networkChartWrap = document.getElementById('networkChartWrap');
        const targetServer = document.getElementById('targetServer');
        const ssidInput = document.getElementById('ssidInput');
        const publicIp = document.getElementById('publicIp');
        const localIp = document.getElementById('localIp');
        const deviceMac = document.getElementById('deviceMac');
        const vendorName = document.getElementById('vendorName');
        const connectedSsid = document.getElementById('connectedSsid');
        const routerName = document.getElementById('routerName');
        const ispName = document.getElementById('ispName');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const circleMax = 364.4;
        let chartLabels = [];
        let pingSeries = [];
        let downloadSeries = [];
        let running = false;
        let testTimer = null;
        let bridgeSyncTimer = null;

        const ctx = document.getElementById('networkChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Ping (ms)',
                        data: [],
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.15)',
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'Download (Mbps)',
                        data: [],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.15)',
                        tension: 0.35,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#cbd5e1'
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(148, 163, 184, 0.18)' }
                    },
                    y: {
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(148, 163, 184, 0.18)' }
                    }
                }
            }
        });

        function getThemeMode() {
            const value = document.documentElement.getAttribute('data-bs-theme');
            return value === 'light' ? 'light' : 'dark';
        }

        function applyChartTheme() {
            const isLight = getThemeMode() === 'light';
            chart.options.plugins.legend.labels.color = isLight ? '#334155' : '#cbd5e1';
            chart.options.scales.x.ticks.color = isLight ? '#475569' : '#94a3b8';
            chart.options.scales.y.ticks.color = isLight ? '#475569' : '#94a3b8';
            chart.options.scales.x.grid.color = isLight ? 'rgba(71, 85, 105, 0.2)' : 'rgba(148, 163, 184, 0.18)';
            chart.options.scales.y.grid.color = isLight ? 'rgba(71, 85, 105, 0.2)' : 'rgba(148, 163, 184, 0.18)';
            chart.update('none');
        }

        applyChartTheme();
        window.addEventListener('themeChanged', applyChartTheme);
        if (window.MutationObserver) {
            const observer = new MutationObserver(applyChartTheme);
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
        }

        if (window.ResizeObserver && networkChartWrap) {
            const resizeObserver = new ResizeObserver(function () {
                chart.resize();
            });
            resizeObserver.observe(networkChartWrap);
        }

        const detectedType = navigator.connection && navigator.connection.effectiveType
            ? navigator.connection.effectiveType.toUpperCase()
            : 'LAN / Wi-Fi';
        let detectedBand = '';

        function parseFrequencyToBandLabel(value) {
            const frequency = Number(value);
            if (!Number.isFinite(frequency) || frequency <= 0) {
                return '';
            }
            if (frequency >= 5925 && frequency <= 7125) {
                return '6 GHz';
            }
            if (frequency >= 4900 && frequency < 5925) {
                return '5 GHz';
            }
            if (frequency >= 2400 && frequency < 2500) {
                return '2.4 GHz';
            }
            return '';
        }

        function parseBandLabel(value) {
            if (value === null || value === undefined) {
                return '';
            }
            if (typeof value === 'number') {
                return parseFrequencyToBandLabel(value);
            }
            const text = String(value).trim();
            if (!text) {
                return '';
            }
            if (/^\d+(\.\d+)?$/.test(text)) {
                return parseFrequencyToBandLabel(text);
            }
            const normalized = text.toLowerCase().replace(/\s+/g, '');
            if (normalized.includes('6ghz')) {
                return '6 GHz';
            }
            if (normalized.includes('5ghz')) {
                return '5 GHz';
            }
            if (normalized.includes('2.4ghz') || normalized.includes('24ghz')) {
                return '2.4 GHz';
            }
            return '';
        }

        function extractNetworkBand(payload) {
            if (!payload || typeof payload !== 'object') {
                return '';
            }
            const directBandKeys = ['band', 'wifi_band', 'network_band', 'frequency_band'];
            for (const key of directBandKeys) {
                const label = parseBandLabel(payload[key]);
                if (label) {
                    return label;
                }
            }
            const frequencyKeys = ['frequency', 'freq', 'frequency_mhz', 'frequencyMhz', 'wifi_frequency', 'wifiFrequency'];
            for (const key of frequencyKeys) {
                const label = parseBandLabel(payload[key]);
                if (label) {
                    return label;
                }
            }
            return '';
        }

        function setDetectedBand(value) {
            const label = parseBandLabel(value);
            if (!label) {
                return false;
            }
            detectedBand = label;
            networkType.textContent = `Wi-Fi ${detectedBand}`;
            return true;
        }

        function renderDefaultNetworkType() {
            if (!detectedBand) {
                networkType.textContent = detectedType;
            }
        }

        renderDefaultNetworkType();

        const urlSsid = new URLSearchParams(window.location.search).get('ssid');
        const cachedSsid = localStorage.getItem('networkAnalyzerSsid');
        const initialSsid = urlSsid || cachedSsid || '';
        if (initialSsid) {
            ssidInput.value = initialSsid;
        }

        function sanitizeSsid(value) {
            if (value === null || value === undefined) {
                return '';
            }
            const normalized = String(value).trim().replace(/^"(.*)"$/, '$1');
            if (!normalized || normalized.toLowerCase() === '<unknown ssid>') {
                return '';
            }
            return normalized;
        }

        function extractSsidFromPayload(payload) {
            if (!payload) {
                return '';
            }
            const keys = ['ssid', 'SSID', 'network_ssid', 'wifi_ssid', 'currentSsid'];
            for (const key of keys) {
                const candidate = sanitizeSsid(payload[key]);
                if (candidate) {
                    return candidate;
                }
            }
            return '';
        }

        function parseNativePayload(rawValue) {
            if (rawValue === null || rawValue === undefined) {
                return null;
            }
            if (typeof rawValue === 'object') {
                return rawValue;
            }
            const text = String(rawValue).trim();
            if (!text) {
                return null;
            }
            if (text.startsWith('{') || text.startsWith('[')) {
                try {
                    return JSON.parse(text);
                } catch (error) {
                    return null;
                }
            }
            return { ssid: text };
        }

        function applyDetectedSsid(value) {
            const ssid = sanitizeSsid(value);
            if (!ssid) {
                return false;
            }
            const previous = (ssidInput.value || '').trim();
            ssidInput.value = ssid;
            localStorage.setItem('networkAnalyzerSsid', ssid);
            connectedSsid.textContent = ssid;
            return previous !== ssid;
        }

        function getAndroidBridge() {
            return window.Android || window.android || window.MstoreAndroid || null;
        }

        function syncFromAndroidBridge() {
            const bridge = getAndroidBridge();
            if (!bridge) {
                return false;
            }
            let changed = false;
            const previousBand = detectedBand;
            const methods = ['getCurrentSsid', 'getWifiSsid', 'getSSID', 'getConnectedSsid', 'getWifiInfo', 'getNetworkInfo', 'getWifiFrequency', 'getCurrentFrequency', 'getFrequencyMhz'];
            for (const methodName of methods) {
                const method = bridge[methodName];
                if (typeof method !== 'function') {
                    continue;
                }
                try {
                    const result = method.call(bridge);
                    const payload = parseNativePayload(result);
                    setDetectedBand(extractNetworkBand(payload));
                    if (methodName === 'getWifiFrequency' || methodName === 'getCurrentFrequency' || methodName === 'getFrequencyMhz') {
                        setDetectedBand(result);
                    }
                    const ssidFromPayload = extractSsidFromPayload(payload);
                    if (applyDetectedSsid(ssidFromPayload)) {
                        changed = true;
                    }
                } catch (error) {
                    continue;
                }
            }
            if (previousBand !== detectedBand) {
                changed = true;
            }
            return changed;
        }

        function detectAndroidWebViewSsid() {
            const bridge = window.Android || window.android || window.MstoreAndroid;
            if (!bridge) {
                return false;
            }
            return syncFromAndroidBridge();
        }

        function startAndroidBridgeSync() {
            const bridge = getAndroidBridge();
            if (!bridge) {
                return;
            }
            const refresh = function () {
                if (document.hidden) {
                    return;
                }
                if (syncFromAndroidBridge()) {
                    loadNetworkIdentity();
                }
            };
            refresh();
            if (bridgeSyncTimer) {
                clearInterval(bridgeSyncTimer);
                bridgeSyncTimer = null;
            }
            bridgeSyncTimer = setInterval(refresh, 8000);
            document.addEventListener('visibilitychange', refresh);
        }

        window.mstoreSetNetworkInfo = function (payload) {
            const parsed = parseNativePayload(payload);
            const beforeBand = detectedBand;
            setDetectedBand(extractNetworkBand(parsed));
            const ssidFromPayload = extractSsidFromPayload(parsed);
            const ssidChanged = applyDetectedSsid(ssidFromPayload);
            const bandChanged = beforeBand !== detectedBand;
            if (ssidChanged || bandChanged) {
                loadNetworkIdentity();
            }
        };

        function setRunButtonState(isRunning) {
            if (isRunning) {
                runTestBtn.innerHTML = '<span class="go-badge">GO</span>Stop Tes';
                runTestBtn.classList.remove('btn-primary');
                runTestBtn.classList.add('btn-danger');
                return;
            }
            runTestBtn.innerHTML = '<span class="go-badge">GO</span>Speedtest';
            runTestBtn.classList.remove('btn-danger');
            runTestBtn.classList.add('btn-primary');
        }

        function updateVisuals(speed) {
            const capped = Math.min(speed, 200);
            speedBar.style.width = `${(capped / 200) * 100}%`;
            speedCircle.style.strokeDashoffset = `${circleMax - (capped / 200) * circleMax}`;
        }

        function recalcSummary() {
            if (pingSeries.length === 0) {
                return;
            }
            const avg = pingSeries.reduce((a, b) => a + b, 0) / pingSeries.length;
            const peak = Math.max(...downloadSeries, 0);
            const pingVariance = Math.max(...pingSeries) - Math.min(...pingSeries);
            const score = Math.max(0, Math.min(100, Math.round(100 - pingVariance - avg / 2)));
            avgPing.textContent = `${avg.toFixed(1)} ms`;
            peakSpeed.textContent = `${peak.toFixed(2)} Mbps`;
            stabilityScore.textContent = `${score}%`;
        }

        async function detectLocalIp() {
            if (!window.RTCPeerConnection) {
                return null;
            }

            return new Promise((resolve) => {
                let done = false;
                const conn = new RTCPeerConnection({ iceServers: [] });
                conn.createDataChannel('probe');

                conn.onicecandidate = (event) => {
                    if (!event.candidate || done) {
                        return;
                    }
                    const candidate = event.candidate.candidate || '';
                    const match = candidate.match(/(\d{1,3}(?:\.\d{1,3}){3})/);
                    if (match && match[1]) {
                        done = true;
                        conn.close();
                        resolve(match[1]);
                    }
                };

                conn.createOffer()
                    .then((offer) => conn.setLocalDescription(offer))
                    .catch(() => null);

                setTimeout(() => {
                    if (!done) {
                        done = true;
                        conn.close();
                        resolve(null);
                    }
                }, 1500);
            });
        }

        async function fetchPublicIpClientSide() {
            const endpoints = [
                'https://api64.ipify.org?format=json',
                'https://api.ip.sb/geoip',
            ];
            try {
                for (const endpoint of endpoints) {
                    const response = await fetch(endpoint, { cache: 'no-store' });
                    if (!response.ok) {
                        continue;
                    }
                    const payload = await response.json();
                    if (payload && payload.ip) {
                        return payload.ip;
                    }
                }
                return null;
            } catch (error) {
                return null;
            }
        }

        async function loadNetworkIdentity() {
            const localAddress = await detectLocalIp();
            try {
                const currentSsid = (ssidInput.value || '').trim();
                const publicClientIp = await fetchPublicIpClientSide();
                const queryParams = new URLSearchParams();
                if (localAddress) {
                    queryParams.set('local_ip', localAddress);
                }
                if (publicClientIp) {
                    queryParams.set('public_ip', publicClientIp);
                }
                if (currentSsid) {
                    queryParams.set('ssid', currentSsid);
                }
                const query = queryParams.toString() ? `?${queryParams.toString()}` : '';
                const response = await fetch(`{{ route('network.analyzer.info') }}${query}`, {
                    headers: {
                        'X-Network-Type': detectedType
                    }
                });
                const payload = await response.json();

                publicIp.textContent = payload.public_ip || publicClientIp || '-';
                localIp.textContent = localAddress || payload.local_ip || payload.client_ip || '-';
                deviceMac.textContent = payload.device_mac || 'Tidak tersedia';
                vendorName.textContent = payload.vendor || payload.isp || 'Tidak terdeteksi';
                connectedSsid.textContent = payload.network_ssid || currentSsid || 'Tidak terdeteksi';
                if (payload.router_name) {
                    routerName.textContent = payload.router_host
                        ? `${payload.router_name} (${payload.router_host})`
                        : payload.router_name;
                } else {
                    routerName.textContent = 'Tidak terdeteksi';
                }
                ispName.textContent = payload.isp || payload.vendor || 'Tidak terdeteksi';
            } catch (error) {
                publicIp.textContent = '-';
                localIp.textContent = '-';
                deviceMac.textContent = 'Tidak tersedia';
                vendorName.textContent = 'Tidak terdeteksi';
                connectedSsid.textContent = 'Tidak terdeteksi';
                routerName.textContent = 'Tidak terdeteksi';
                ispName.textContent = 'Tidak terdeteksi';
            }
        }

        async function measureDownloadMbps() {
            const bytes = 1500000;
            const start = performance.now();
            const response = await fetch(`{{ route('network.analyzer.speed.download') }}?bytes=${bytes}&v=${Date.now()}`, { cache: 'no-store' });
            const buffer = await response.arrayBuffer();
            const seconds = Math.max((performance.now() - start) / 1000, 0.001);
            return (buffer.byteLength * 8) / (seconds * 1000000);
        }

        async function measureUploadMbps() {
            const bytes = 800000;
            const payload = new Uint8Array(bytes);
            crypto.getRandomValues(payload.subarray(0, Math.min(65536, payload.length)));
            const start = performance.now();
            const response = await fetch(`{{ route('network.analyzer.speed.upload') }}?v=${Date.now()}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/octet-stream'
                },
                body: payload
            });
            if (!response.ok) {
                throw new Error('Upload gagal');
            }
            const seconds = Math.max((performance.now() - start) / 1000, 0.001);
            return (bytes * 8) / (seconds * 1000000);
        }

        async function fetchPing(target) {
            const response = await fetch(`{{ route('network.analyzer.ping') }}?target=${encodeURIComponent(target)}&count=4`, { cache: 'no-store' });
            if (!response.ok) {
                throw new Error('Ping gagal');
            }
            return response.json();
        }

        async function tick() {
            const target = targetServer.value || '1.1.1.1';
            try {
                const pingResult = await fetchPing(target);
                const down = await measureDownloadMbps();
                const up = await measureUploadMbps();

                const ping = Number(pingResult.latency_ms ?? 0);
                const jitter = Number(pingResult.jitter_ms ?? 0);
                const loss = Number(pingResult.loss_percent ?? 100);

                currentPing.textContent = ping > 0 ? ping.toFixed(0) : '--';
                currentJitter.textContent = `${jitter.toFixed(1)} ms`;
                currentLoss.textContent = `${loss.toFixed(1)}%`;
                downloadSpeed.textContent = down.toFixed(2);
                uploadSpeed.textContent = `${up.toFixed(2)} Mbps`;
                updateVisuals(down);

                chartLabels.push(new Date().toLocaleTimeString('id-ID', { hour12: false }));
                pingSeries.push(Number(ping.toFixed(1)));
                downloadSeries.push(Number(down.toFixed(2)));

                if (chartLabels.length > 20) {
                    chartLabels.shift();
                    pingSeries.shift();
                    downloadSeries.shift();
                }

                chart.data.labels = chartLabels;
                chart.data.datasets[0].data = pingSeries;
                chart.data.datasets[1].data = downloadSeries;
                chart.update('none');

                recalcSummary();
            } catch (error) {
                currentPing.textContent = '--';
                currentJitter.textContent = '-- ms';
                currentLoss.textContent = '--';
                downloadSpeed.textContent = '0.00';
                uploadSpeed.textContent = '0.00 Mbps';
                updateVisuals(0);
            }
        }

        function resetSeries() {
            chartLabels = [];
            pingSeries = [];
            downloadSeries = [];
            chart.data.labels = [];
            chart.data.datasets[0].data = [];
            chart.data.datasets[1].data = [];
            chart.update();
            avgPing.textContent = '-- ms';
            peakSpeed.textContent = '0.00 Mbps';
            stabilityScore.textContent = '0%';
        }

        runTestBtn.addEventListener('click', async function () {
            if (running) {
                running = false;
                if (testTimer) {
                    clearInterval(testTimer);
                    testTimer = null;
                }
                setRunButtonState(false);
                return;
            }

            running = true;
            resetSeries();
            setRunButtonState(true);

            await tick();
            testTimer = setInterval(function () {
                if (running) {
                    tick();
                }
            }, 5000);
        });

        ssidInput.addEventListener('change', function () {
            const value = (ssidInput.value || '').trim();
            if (value) {
                localStorage.setItem('networkAnalyzerSsid', value);
            } else {
                localStorage.removeItem('networkAnalyzerSsid');
            }
            loadNetworkIdentity();
        });

        ssidInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                ssidInput.blur();
                loadNetworkIdentity();
            }
        });

        detectAndroidWebViewSsid();
        startAndroidBridgeSync();
        setRunButtonState(false);
        loadNetworkIdentity();
    });
</script>
@endpush
