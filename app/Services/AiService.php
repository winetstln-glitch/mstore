<?php

namespace App\Services;

use App\Models\AtkProduct;
use App\Models\AtkTransaction;
use App\Models\AtkTransactionItem;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use App\Models\WashService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AiService
{
    protected $genieService;

    public function __construct(GenieACSService $genieService)
    {
        $this->genieService = $genieService;
    }

    /**
     * Process Chat Message
     */
    public function processChat($message)
    {
        $message = strtolower($message);

        // Intent Detection
        if (str_contains($message, 'help') || str_contains($message, 'bantuan') || str_contains($message, 'cara') || str_contains($message, 'fitur') || str_contains($message, 'apa itu')) {
            return $this->getHelp($message);
        }

        // --- PUBLIC INFORMATION (Guest Accessible) ---

        // CCTV Packages
        if (str_contains($message, 'cctv') || str_contains($message, 'kamera') || str_contains($message, 'pantau')) {
            return $this->getCctvInfo();
        }

        // Internet Packages
        if (str_contains($message, 'wifi') || str_contains($message, 'internet') || str_contains($message, 'paket') || str_contains($message, 'langganan') || str_contains($message, 'speed')) {
            return $this->getInternetPackages();
        }

        // ATK Promo
        if (str_contains($message, 'atk') || str_contains($message, 'alat tulis') || str_contains($message, 'sekolah') || str_contains($message, 'kantor') || str_contains($message, 'buku') || str_contains($message, 'pena')) {
            return $this->getAtkPromo();
        }

        // Wash Services
        if (str_contains($message, 'wash') || str_contains($message, 'cuci') || str_contains($message, 'steam') || str_contains($message, 'motor') || str_contains($message, 'mobil') || str_contains($message, 'bersih')) {
            return $this->getWashServices();
        }

        // Check authentication for data access (Private Data)
        if (! Auth::check()) {
            return 'Halo! Saya adalah Asisten AI MStore. Saya dapat menjelaskan fitur-fitur dan layanan kami kepada Anda.
            <br><br>
            <b>Layanan Publik (Bisa ditanyakan):</b>
            <ul>
                <li>Paket Internet & WiFi</li>
                <li>Pemasangan CCTV</li>
                <li>Produk ATK (Alat Tulis Kantor)</li>
                <li>Jasa Cuci Kendaraan (Auto Wash)</li>
            </ul>
            <br>
            Silakan login untuk mengakses data pribadi seperti Tagihan, Status Jaringan, dll.';
        }

        if (str_contains($message, 'unpaid') || str_contains($message, 'belum bayar') || str_contains($message, 'tunggakan')) {
            return $this->getUnpaidCustomers();
        }

        if (str_contains($message, 'offline') || str_contains($message, 'modem') || str_contains($message, 'mati') || str_contains($message, 'down')) {
            return $this->getOfflineModems();
        }

        if (str_contains($message, 'sales') || str_contains($message, 'penjualan')) {
            $data = $this->getSalesForecast();
            $forecast = number_format($data['forecast_next_day'], 0, ',', '.');
            $trend = $data['trend'];
            $confidence = $data['confidence'];

            return "Berdasarkan analisis saya, penjualan untuk besok diproyeksikan sebesar **Rp {$forecast}**.<br>Tren: **{$trend}** (Keyakinan: {$confidence}%).";
        }

        if (str_contains($message, 'stock') || str_contains($message, 'stok')) {
            $suggestions = $this->getRestockSuggestions();
            if ($suggestions->isEmpty()) {
                return 'Stok aman! Tidak ada kebutuhan restock mendesak.';
            }
            $list = [];
            foreach ($suggestions as $item) {
                $list[] = '<b>'.e($item['product_name']).'</b>: Habis dalam '.e($item['days_until_stockout']).' hari. Tambah +'.e($item['recommended_restock']).'.';
            }

            return [
                'type' => 'list',
                'title' => 'Rekomendasi Restock:',
                'items' => $list,
            ];
        }

        if (str_contains($message, 'network') || str_contains($message, 'jaringan')) {
            $data = $this->getNetworkInsights();
            $online = $data['devices_online'];
            $total = $data['devices_total'];
            $offline = $data['devices_offline'];
            $cpu = $data['router_cpu'] !== null ? $data['router_cpu'].'%' : 'N/A';
            $pppoe = $data['active_pppoe'] !== null ? $data['active_pppoe'] : 'N/A';

            return "<b>Status Jaringan: {$data['status']}</b><br>"
                 ."Perangkat Online: {$online}/{$total} | Offline: {$offline}<br>"
                 ."Router CPU: {$cpu} | PPPoE Aktif: {$pppoe}<br>"
                 ."<br><i>{$data['message']}</i>";
        }

        return "Maaf, saya tidak mengerti. Anda bisa bertanya tentang 'Penjualan', 'Stok', 'Jaringan', 'Tagihan Belum Bayar', 'Modem Offline', atau 'Bantuan'.";
    }

    /**
     * Get Usage Instructions (Knowledge Base)
     */
    public function getHelp($query)
    {
        $knowledgeBase = [
            'dashboard' => '**Dashboard** adalah pusat kendali Anda. Fitur utamanya meliputi:
                <ul>
                    <li>**Kesehatan Jaringan**: Metrik latensi dan packet loss real-time.</li>
                    <li>**Prediksi Penjualan**: Prediksi pendapatan AI untuk hari berikutnya.</li>
                    <li>**Restock Pintar**: Peringatan untuk produk yang perlu ditambah stoknya berdasarkan kecepatan penjualan.</li>
                    <li>**Wawasan Bisnis**: Indikator kinerja utama seperti pertumbuhan pendapatan dan produk terlaris.</li>
                </ul>',
            'transaction' => "Untuk mengelola **Transaksi**:
                <ol>
                    <li>Buka menu 'Transaksi'.</li>
                    <li>Lihat daftar transaksi pending, disetujui, dan ditolak.</li>
                    <li>Klik transaksi untuk melihat detail.</li>
                    <li>Gunakan tombol 'Setujui' atau 'Tolak' untuk memperbarui status.</li>
                </ol>",
            'inventory' => "Untuk mengelola **Inventaris** (Alat & Aset):
                <ul>
                    <li>**Tambah Barang**: Masuk ke Operasional > Tools & SDM > Inventory.</li>
                    <li>**Aset Saya**: Lihat aset yang ditugaskan kepada Anda di bawah 'Aset Saya'.</li>
                    <li>**Pelacakan**: Pantau penggunaan alat dan penugasan teknisi.</li>
                </ul>",
            'customer' => "Untuk mengelola **Pelanggan**:
                <ul>
                    <li>**Data Pelanggan**: Lihat semua pengguna terdaftar dan detailnya.</li>
                    <li>**Pemasangan Baru**: Lacak permintaan instalasi baru.</li>
                    <li>**Layanan Aktif**: Pantau sesi Hotspot dan PPPoE di bawah 'Pelanggan & Layanan'.</li>
                </ul>",
            'network' => 'Untuk mengelola **Infrastruktur Jaringan**:
                <ul>
                    <li>**Monitor Jaringan**: Integrasi GenieACS untuk pemantauan CPE.</li>
                    <li>**Peta Jaringan**: Peta visual ODP, ODC, dan jalur fiber Anda.</li>
                    <li>**Infrastruktur**: Kelola OLT, ODC, ODP, Closure, dan HTB.</li>
                    <li>**Router/NAS**: Konfigurasi router Mikrotik dan VPN Bridge.</li>
                    <li>**Kalkulator PON**: Hitung anggaran daya optik.</li>
                </ul>',
            'finance' => 'Untuk mengelola **Keuangan**:
                <ul>
                    <li>**Dashboard**: Tinjauan kesehatan keuangan.</li>
                    <li>**Akuntansi**: Akses Neraca Saldo, Laporan Laba Rugi, Neraca, Buku Besar, dan Arus Kas.</li>
                    <li>**Investor**: Kelola data investor dan saham.</li>
                </ul>',
            'atk' => 'Untuk mengelola **Toko ATK**:
                <ul>
                    <li>**POS**: Point of Sale untuk tugas kasir.</li>
                    <li>**Produk**: Kelola stok dan harga alat tulis.</li>
                    <li>**Laporan**: Lihat riwayat transaksi dan laporan penjualan.</li>
                </ul>',
            'wash' => 'Untuk mengelola **Cuci Kendaraan**:
                <ul>
                    <li>**POS**: Sistem kasir untuk layanan cuci mobil/motor.</li>
                    <li>**Karyawan**: Kelola staf pencuci dan komisi.</li>
                    <li>**Layanan**: Konfigurasi paket dan harga cuci.</li>
                </ul>',
            'operasional' => 'Untuk mengelola **Operasional**:
                <ul>
                    <li>**Tiket & Gangguan**: Tangani keluhan pelanggan dan masalah teknis.</li>
                    <li>**Teknisi**: Kelola data dan jadwal teknisi.</li>
                    <li>**Absensi**: Lacak kehadiran karyawan.</li>
                </ul>',
            'genieacs' => 'Untuk menggunakan **Integrasi GenieACS**:
                <ul>
                    <li>**Monitor**: Cek status real-time perangkat CPE (online/offline).</li>
                    <li>**Diagnostik**: Lihat kekuatan sinyal (RSSI), uptime, dan klien terhubung.</li>
                    <li>**Aksi**: Reboot perangkat atau reset ke pengaturan pabrik dari jarak jauh.</li>
                </ul>',
            'invoice' => "Untuk mengelola **Tagihan**:
                <ul>
                    <li>**Buat**: Tagihan sering dibuat secara otomatis berdasarkan siklus penagihan.</li>
                    <li>**Status**: Lacak status 'Pending', 'Lunas', atau 'Terlambat'.</li>
                    <li>**Pengingat**: Kirim pengingat pembayaran ke pelanggan dengan tagihan pending.</li>
                </ul>",
            'settings' => 'Di **Pengaturan**, Anda dapat:
                <ul>
                    <li>**Umum**: Konfigurasi nama situs, mata uang, dan zona waktu.</li>
                    <li>**Pengguna**: Kelola akun admin dan peran/izin.</li>
                    <li>**Notifikasi**: Atur peringatan email atau SMS untuk kejadian sistem.</li>
                </ul>',
        ];

        foreach ($knowledgeBase as $key => $answer) {
            if (str_contains($query, $key) || ($key === 'network' && (str_contains($query, 'jaringan') || str_contains($query, 'olt') || str_contains($query, 'odp') || str_contains($query, 'vpn')))) {
                return $answer; // Return HTML directly for rich text rendering
            }
        }

        // Default response if no specific feature mentioned
        return [
            'type' => 'list',
            'title' => 'Saya bisa membantu Anda dengan fitur berikut:',
            'items' => [
                '<b>Dashboard</b>: Ringkasan & Wawasan AI',
                '<b>Pelanggan</b>: Data, Pemasangan & Layanan',
                '<b>Jaringan</b>: Peta, GenieACS, OLT/ODP, VPN',
                '<b>Keuangan</b>: Akuntansi & Investor',
                '<b>Toko ATK</b>: POS & Inventaris',
                '<b>Cuci Kendaraan</b>: POS & Layanan Cuci',
                '<b>Operasional</b>: Tiket, Teknisi & Alat',
                '<b>Pengaturan</b>: Konfigurasi Sistem',
            ],
        ];
    }

    /**
     * Get Unpaid Customers
     */
    public function getUnpaidCustomers()
    {
        // Assuming 'pending' means unpaid based on migration
        $unpaidInvoices = Invoice::where('status', 'pending')
            ->with(['user.customer'])
            ->orderBy('due_date', 'asc')
            ->get();

        if ($unpaidInvoices->isEmpty()) {
            return 'Kabar baik! Tidak ada pelanggan yang menunggak saat ini.';
        }

        $list = [];
        foreach ($unpaidInvoices as $invoice) {
            $name = $invoice->user ? $invoice->user->name : 'Pengguna Tidak Diketahui';
            $amount = number_format($invoice->amount, 0, ',', '.');
            $dueDate = $invoice->due_date ? $invoice->due_date->format('d M Y') : 'N/A';

            $link = '#';
            if ($invoice->user && $invoice->user->customer) {
                $link = route('customers.show', $invoice->user->customer->id);
                $name = "<a href='".e($link)."' target='_blank' class='text-decoration-none fw-bold'>".e($name).'</a>';
            } else {
                $name = '<b>'.e($name).'</b>';
            }

            // Format: Name - Amount (Due Date)
            $list[] = "{$name} - Rp ".e($amount)." <br><small class='text-muted'>Jatuh Tempo: ".e($dueDate).'</small>';
        }

        return [
            'type' => 'list',
            'title' => 'Tagihan Belum Lunas ('.count($list).'):',
            'items' => $list,
        ];
    }

    /**
     * Get Offline Modems from GenieACS
     */
    public function getOfflineModems()
    {
        // Fetch all devices (limit 500 for performance)
        $devices = $this->genieService->getDevices(500, 0);

        $offlineDevices = [];
        $now = Carbon::now();
        $thresholdMinutes = 10; // Consider offline if no inform for 10 mins

        // First pass: identify offline devices and collect IDs/Usernames
        $offlineCandidates = [];
        $pppoeUsernames = [];
        $deviceIds = [];

        foreach ($devices as $device) {
            $lastInform = isset($device['_lastInform']) ? Carbon::parse($device['_lastInform']) : null;

            if (! $lastInform || $lastInform->diffInMinutes($now) > $thresholdMinutes) {
                $offlineCandidates[] = $device;

                // Collect identifiers for batch query
                if (isset($device['VirtualParameters']['pppoeUsername'])) {
                    $pppoeUsernames[] = $device['VirtualParameters']['pppoeUsername'];
                }
                if (isset($device['_id'])) {
                    $deviceIds[] = $device['_id'];
                }
            }
        }

        if (empty($offlineCandidates)) {
            return 'Semua modem online! Jaringan sehat.';
        }

        // Batch Query for Customer mapping
        // 1. Try via User.radius_username
        $usersByRadius = [];
        if (! empty($pppoeUsernames)) {
            $usersByRadius = User::whereIn('radius_username', $pppoeUsernames)
                ->with('customer')
                ->get()
                ->keyBy('radius_username');
        }

        // 2. Try via Customer.genieacs_device_id
        $customersByDeviceId = [];
        if (! empty($deviceIds)) {
            $customersByDeviceId = Customer::whereIn('genieacs_device_id', $deviceIds)
                ->with('user')
                ->get()
                ->keyBy('genieacs_device_id');
        }

        foreach ($offlineCandidates as $device) {
            $sn = $device['_deviceId']['_SerialNumber'] ?? 'Unknown SN';
            $lastInform = isset($device['_lastInform']) ? Carbon::parse($device['_lastInform']) : null;
            $lastSeen = $lastInform ? $lastInform->diffForHumans() : 'Tidak pernah';
            $pppoeUser = $device['VirtualParameters']['pppoeUsername'] ?? null;
            $deviceId = $device['_id'] ?? null;

            // Resolve Customer Name and Link
            $customerName = $pppoeUser ?? 'Pengguna Tidak Diketahui';
            $customerLink = '#';
            $resolved = false;

            // Try resolving via Radius Username
            if ($pppoeUser && isset($usersByRadius[$pppoeUser])) {
                $user = $usersByRadius[$pppoeUser];
                $customerName = $user->name; // User's name is usually better
                if ($user->customer) {
                    $customerLink = route('customers.show', $user->customer->id);
                    $resolved = true;
                }
            }

            // Fallback: Try resolving via GenieACS Device ID
            if (! $resolved && $deviceId && isset($customersByDeviceId[$deviceId])) {
                $customer = $customersByDeviceId[$deviceId];
                // Use user name if available
                if ($customer->user) {
                    $customerName = $customer->user->name;
                    $resolved = true;
                }
                $customerLink = route('customers.show', $customer->id);
            }

            // Format output
            if ($resolved) {
                $display = "<a href='".e($customerLink)."' target='_blank' class='text-decoration-none fw-bold'>".e($customerName).'</a>';
            } else {
                $display = '<b>'.e($customerName).'</b>';
            }

            $offlineDevices[] = "{$display} (".e($sn).") <br><small class='text-danger'>Offline sejak: ".e($lastSeen).'</small>';
        }

        if (empty($offlineDevices)) {
            return 'Semua modem online! Jaringan sehat.';
        }

        // Limit to top 20 to avoid overwhelming chat
        $displayList = array_slice($offlineDevices, 0, 20);
        $remaining = count($offlineDevices) - 20;
        if ($remaining > 0) {
            $displayList[] = "...dan {$remaining} lainnya.";
        }

        return [
            'type' => 'list',
            'title' => 'Modem Offline ('.count($offlineDevices).'):',
            'items' => $displayList,
        ];
    }

    /**
     * Analyze sales data to suggest restocking with "Smart Velocity" logic.
     * Uses weighted average (recent sales matter more) and predicts stockout date.
     */
    public function getRestockSuggestions()
    {
        $daysToAnalyze = 30;
        $startDate = Carbon::now()->subDays($daysToAnalyze);

        // Fetch daily sales per product for weighted calculation
        $salesData = AtkTransactionItem::select(
            'product_id',
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(quantity) as daily_qty')
        )
            ->whereHas('product')
            ->where('created_at', '>=', $startDate)
            ->groupBy('product_id', 'date')
            ->get()
            ->groupBy('product_id');

        $suggestions = [];

        // Preload all products in one query to avoid N+1
        $productIds = $salesData->keys()->toArray();
        $products = AtkProduct::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($salesData as $productId => $dailySales) {
            $product = $products[$productId] ?? null;
            if (! $product) {
                continue;
            }

            // Calculate Weighted Velocity
            // Recent 7 days = weight 2.0, Older days = weight 1.0
            $totalWeightedQty = 0;
            $totalWeight = 0;
            $recentCutoff = Carbon::now()->subDays(7);

            foreach ($dailySales as $record) {
                $recordDate = Carbon::parse($record->date);
                $weight = $recordDate->gte($recentCutoff) ? 2.0 : 1.0;

                $totalWeightedQty += ($record->daily_qty * $weight);
                $totalWeight += $weight;
            }

            // Normalize total weight to cover the full period properly
            // If we have missing days (0 sales), we should account for them in the denominator
            // Effective days = 7 days * 2 + 23 days * 1 = 14 + 23 = 37 "weight units"
            $effectiveWeightUnits = (7 * 2) + ($daysToAnalyze - 7);

            // Average Daily Sales (Weighted)
            $avgDailySales = $totalWeight > 0 ? $totalWeightedQty / $effectiveWeightUnits : 0;

            if ($avgDailySales <= 0) {
                continue;
            }

            $daysUntilStockout = $product->stock / $avgDailySales;
            $recommendedBuffer = $avgDailySales * 14; // 2 weeks buffer

            // Logic: Alert if stock runs out in < 7 days
            if ($daysUntilStockout < 7) {
                $suggestions[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'current_stock' => $product->stock,
                    'avg_daily_sales' => round($avgDailySales, 2),
                    'days_until_stockout' => round($daysUntilStockout, 1),
                    'recommended_restock' => ceil(max(0, $recommendedBuffer - $product->stock)),
                    'confidence' => 'High', // Based on consistent data presence
                    'reason' => 'Prediksi habis dalam '.round($daysUntilStockout, 1).' hari berdasarkan kecepatan penjualan tertimbang.',
                ];
            }
        }

        return collect($suggestions)->sortBy('days_until_stockout')->values();
    }

    /**
     * Predict future sales using Linear Regression (Least Squares).
     * Returns 7-day forecast with confidence score and trend analysis.
     */
    public function getSalesForecast()
    {
        $historyDays = 30;
        $forecastDays = 7;
        $startDate = Carbon::now()->subDays($historyDays);

        $dailySales = AtkTransaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_amount) as total')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Prepare data for regression: x = day index (0 to 29), y = total sales
        $points = [];
        $dates = [];
        $lastDate = Carbon::now()->subDays($historyDays);

        // Fill gaps with 0
        for ($i = 0; $i < $historyDays; $i++) {
            $currentDate = $lastDate->copy()->addDays($i + 1)->format('Y-m-d');
            $record = $dailySales->firstWhere('date', $currentDate);
            $y = $record ? $record->total : 0;

            $points[] = ['x' => $i, 'y' => $y];
            $dates[$currentDate] = $y;
        }

        // Calculate Linear Regression
        $regression = $this->calculateLinearRegression($points);
        $slope = $regression['slope'];
        $intercept = $regression['intercept'];
        $r2 = $regression['r2'];

        // Generate Forecast for next 7 days
        $forecast = [];
        for ($i = 0; $i < $forecastDays; $i++) {
            $futureDayIndex = $historyDays + $i;
            $predictedValue = ($slope * $futureDayIndex) + $intercept;

            // Basic Seasonality Adjustment (Weekend Multiplier)
            $futureDate = Carbon::now()->addDays($i + 1);
            if ($futureDate->isWeekend()) {
                $predictedValue *= 1.1; // Assume 10% bump on weekends (mock logic)
            }

            $forecast[] = [
                'date' => $futureDate->format('Y-m-d'),
                'value' => max(0, round($predictedValue)),
            ];
        }

        // Determine Trend and Confidence
        $trend = 'Stabil';
        if ($slope > 5000) {
            $trend = 'Naik 📈';
        } // Threshold: > 5k growth per day
        if ($slope < -5000) {
            $trend = 'Turun 📉';
        }

        $confidenceScore = min(98, max(40, round($r2 * 100))); // Cap between 40% and 98%

        return [
            'history' => $dailySales, // Keep for chart history
            'forecast' => $forecast,
            'forecast_next_day' => $forecast[0]['value'],
            'trend' => $trend,
            'confidence' => $confidenceScore,
            'slope' => $slope,
        ];
    }

    /**
     * Real Network Insights from GenieACS and Mikrotik.
     */
    public function getNetworkInsights()
    {
        // Use cache for 5 minutes - network state doesn't change rapidly
        return Cache::remember('ai_network_insights', 5, function () {
            // 1. GenieACS device health
            $deviceHealth = $this->genieService->getNetworkHealthSummary();

            // 2. Router health - query primary router
            $routerCount = Router::where('is_active', true)->count();
            $routerCpu = null;
            $routerMemory = null;
            $activePppoe = null;

            $primaryRouter = Router::where('is_active', true)->first();
            if ($primaryRouter) {
                try {
                    $mikrotik = new \App\Services\MikrotikService($primaryRouter);
                    if ($mikrotik->isConnected()) {
                        $resource = $mikrotik->getSystemResource();
                        if ($resource) {
                            $routerCpu = (int) ($resource['cpu-load'] ?? 0);
                            $totalMem = (int) ($resource['total-memory'] ?? 1);
                            $freeMem = (int) ($resource['free-memory'] ?? 0);
                            $routerMemory = $totalMem > 0 ? round(($totalMem - $freeMem) / $totalMem * 100) : null;
                        }
                        $activePppoe = $mikrotik->getPppoeActiveCount();
                    }
                } catch (\Exception $e) {
                    \Log::warning('AI Network Insights - Router query failed: '.$e->getMessage());
                }
            }

            // 3. Determine overall status based on real thresholds
            $totalDevices = $deviceHealth['total_devices'] ?? 0;
            $offline = $deviceHealth['offline'] ?? 0;
            $offlinePercent = $totalDevices > 0 ? ($offline / $totalDevices) * 100 : 0;

            $status = 'Sehat';
            $message = 'Jaringan beroperasi dalam parameter normal.';
            $aiTip = 'Semua sistem operasional.';

            // Critical: >20% offline OR CPU > 80%
            if ($offlinePercent > 20 || ($routerCpu !== null && $routerCpu > 80)) {
                $status = 'Kritis';
                $message = 'Masalah jaringan terdeteksi. '.$offline.' perangkat offline.';
                $aiTip = 'Periksa OLT dan backbone fiber utama segera.';
            }
            // Warning: >5% offline OR CPU > 60%
            elseif ($offlinePercent > 5 || ($routerCpu !== null && $routerCpu > 60)) {
                $status = 'Peringatan';
                $message = $offline.' perangkat offline dari total '.$totalDevices.'.';
                $aiTip = 'Monitor perangkat offline dan kinerja router.';
            }

            // Peak hour context (advisory only - doesn't change status)
            $hour = Carbon::now()->hour;
            if ($hour >= 19 && $hour <= 22 && $status === 'Sehat') {
                $message .= ' Volume trafik tinggi (Jam Sibuk).';
                $aiTip = 'Pertimbangkan optimasi QoS untuk layanan streaming saat jam sibuk.';
            }

            // Build response with backward-compatible keys for existing views
            return [
                'total_routers' => $routerCount,
                'devices_online' => $deviceHealth['online'] ?? 0,
                'devices_offline' => $offline,
                'devices_total' => $totalDevices,
                'avg_rx_power' => $deviceHealth['avg_rx_power'],
                'router_cpu' => $routerCpu,
                'router_memory' => $routerMemory,
                'active_pppoe' => $activePppoe,
                'status' => $status,
                'message' => $message,
                'ai_tip' => $aiTip,
                // Legacy keys - kept for backward compat, now null
                'avg_latency' => null,
                'packet_loss' => null,
                'is_simulated' => false,
            ];
        });
    }

    /**
     * Get Business Insights (Revenue Growth, Top Products)
     */
    public function getBusinessInsights()
    {
        // 1. Revenue Growth (Current Month vs Last Month)
        $currentMonthStart = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $currentMonthRevenue = AtkTransaction::where('created_at', '>=', $currentMonthStart)->sum('total_amount');
        $lastMonthRevenue = AtkTransaction::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->sum('total_amount');

        $growth = 0;
        if ($lastMonthRevenue > 0) {
            $growth = (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;
        } elseif ($currentMonthRevenue > 0) {
            $growth = 100;
        }

        // 2. Top Products (By Quantity Sold in last 30 days)
        $topProducts = AtkTransactionItem::select('product_id', DB::raw('SUM(quantity) as total_qty'))
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->with('product')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->product ? $item->product->name : ($item->product_name ?? 'Produk Tidak Diketahui'),
                    'qty' => $item->total_qty,
                ];
            });

        // 3. Repeat Customers (Customers with > 1 transaction in last 30 days)
        if (Schema::hasColumn('atk_transactions', 'customer_name')) {
            $repeatCustomers = AtkTransaction::select('customer_name')
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->whereNotNull('customer_name')
                ->where('customer_name', '!=', '')
                ->groupBy('customer_name')
                ->havingRaw('COUNT(*) > 1')
                ->count();
        } elseif (Schema::hasColumn('atk_transactions', 'user_id')) {
            $repeatCustomers = AtkTransaction::select('user_id')
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 1')
                ->count();
        } else {
            $repeatCustomers = 0;
        }

        // 4. Generate Insight Text
        $topProductName = $topProducts->first() ? $topProducts->first()['name'] : 'N/A';
        $growthText = $growth >= 0 ? 'naik' : 'turun';
        $insightText = "Pendapatan {$growthText} sebesar ".abs(round($growth, 1))."% dibandingkan bulan lalu. Produk terlaris: {$topProductName}.";

        return [
            'revenue_growth' => round($growth, 1),
            'current_month_revenue' => $currentMonthRevenue,
            'top_products' => $topProducts,
            'top_product' => $topProductName,
            'repeat_customers' => $repeatCustomers,
            'insight_text' => $insightText,
        ];
    }

    /**
     * Helper: Calculate Linear Regression
     */
    private function calculateLinearRegression($points)
    {
        $n = count($points);
        if ($n < 2) {
            return ['slope' => 0, 'intercept' => 0, 'r2' => 0];
        }

        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumXX = 0;
        $sumYY = 0;

        foreach ($points as $point) {
            $x = $point['x'];
            $y = $point['y'];

            $sumX += $x;
            $sumY += $y;
            $sumXY += ($x * $y);
            $sumXX += ($x * $x);
            $sumYY += ($y * $y);
        }

        // Avoid division by zero
        $denominator = ($n * $sumXX - $sumX * $sumX);
        if ($denominator == 0) {
            return ['slope' => 0, 'intercept' => $sumY / $n, 'r2' => 0];
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / $denominator;
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Calculate R-squared (Coefficient of Determination)
        $ssTot = 0;
        $ssRes = 0;
        $meanY = $sumY / $n;

        foreach ($points as $point) {
            $y = $point['y'];
            $x = $point['x'];
            $predictedY = $slope * $x + $intercept;

            $ssTot += pow($y - $meanY, 2);
            $ssRes += pow($y - $predictedY, 2);
        }

        $r2 = $ssTot != 0 ? 1 - ($ssRes / $ssTot) : 0;

        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'r2' => $r2,
        ];
    }

    /**
     * Get CCTV Information (Static)
     */
    public function getCctvInfo()
    {
        return [
            'type' => 'list',
            'title' => 'Paket Pemasangan CCTV Kami:',
            'items' => [
                '<b>Basic (Rumah Kecil)</b> - Rp 1.999.000<br>2 Kamera, DVR 4 Channel, Pemasangan.',
                '<b>Standard (Rumah & Toko)</b> - Rp 3.899.000<br>4 Kamera, DVR 8 Channel, Cloud View.',
                '<b>Premium (Gudang & Kantor)</b> - Rp 6.999.000<br>8 Kamera, NVR IP Camera, PoE Switch.',
            ],
        ];
    }

    /**
     * Get Internet Packages (Dynamic)
     */
    public function getInternetPackages()
    {
        try {
            if (! class_exists(Package::class) || ! Schema::hasTable('packages')) {
                return 'Maaf, informasi paket internet belum tersedia.';
            }

            $packages = Package::where('is_active', true)->orderBy('price')->get();
            if ($packages->isEmpty()) {
                return 'Maaf, belum ada paket internet yang tersedia saat ini.';
            }

            $list = [];
            foreach ($packages as $pkg) {
                $price = number_format($pkg->price, 0, ',', '.');
                $list[] = "<b>{$pkg->name} ({$pkg->speed} Mbps)</b><br>Rp {$price} / bulan<br><small>{$pkg->description}</small>";
            }

            return [
                'type' => 'list',
                'title' => 'Pilihan Paket Internet & WiFi:',
                'items' => $list,
            ];
        } catch (\Exception $e) {
            return 'Maaf, terjadi kesalahan saat mengambil data paket internet.';
        }
    }

    /**
     * Get ATK Promo (Dynamic)
     */
    public function getAtkPromo()
    {
        try {
            if (! class_exists(AtkProduct::class) || ! Schema::hasTable('atk_products')) {
                return 'Layanan ATK belum tersedia.';
            }

            $products = AtkProduct::where('stock', '>', 0)->latest()->take(5)->get();
            if ($products->isEmpty()) {
                return 'Stok ATK sedang kosong.';
            }

            $list = [];
            foreach ($products as $item) {
                $price = number_format($item->sell_price_retail, 0, ',', '.');
                $list[] = "<b>{$item->name}</b> - Rp {$price}";
            }

            return [
                'type' => 'list',
                'title' => 'Produk Alat Tulis Kantor Terbaru:',
                'items' => $list,
            ];
        } catch (\Exception $e) {
            return 'Maaf, data ATK tidak dapat diakses saat ini.';
        }
    }

    /**
     * Get Wash Services (Dynamic)
     */
    public function getWashServices()
    {
        try {
            if (! class_exists(WashService::class) || ! Schema::hasTable('wash_services')) {
                return 'Layanan Cuci Kendaraan belum tersedia.';
            }

            $services = WashService::all();
            if ($services->isEmpty()) {
                return 'Belum ada layanan cuci yang terdaftar.';
            }

            $list = [];
            foreach ($services as $srv) {
                $price = number_format($srv->price, 0, ',', '.');
                $type = ucfirst($srv->vehicle_type ?? 'Kendaraan');
                $list[] = "<b>{$srv->name} ({$type})</b> - Rp {$price}";
            }

            return [
                'type' => 'list',
                'title' => 'Layanan Cuci & Steam:',
                'items' => $list,
            ];
        } catch (\Exception $e) {
            return 'Maaf, info layanan cuci tidak tersedia.';
        }
    }
}
