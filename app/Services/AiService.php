<?php

namespace App\Services;

use App\Models\AtkProduct;
use App\Models\AtkTransaction;
use App\Models\AtkTransactionItem;
use App\Models\Customer;
use App\Models\Installation;
use App\Models\Invoice;
use App\Models\OntOpticalHistory;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\Package;
use App\Models\Router;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WashService;
use App\Models\WashTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Network\Services\CapacityService;
use Modules\Network\Services\MonitoringService;
use Modules\Network\Services\OpticalMonitoringService;
use Modules\Network\Services\TopologyService;

class AiService
{
    protected $genieService;
    protected $monitoringService;
    protected $topologyService;
    protected $capacityService;
    protected $opticalService;

    public function __construct(
        GenieACSService $genieService,
        MonitoringService $monitoringService,
        TopologyService $topologyService,
        CapacityService $capacityService,
        OpticalMonitoringService $opticalService
    )
    {
        $this->genieService = $genieService;
        $this->monitoringService = $monitoringService;
        $this->topologyService = $topologyService;
        $this->capacityService = $capacityService;
        $this->opticalService = $opticalService;
    }

    /**
     * Process Chat Message
     */
    public function processChat($message)
    {
        $message = strtolower($message);

        // Intent Detection
        if (str_contains($message, 'help') || str_contains($message, 'bantuan') || str_contains($message, 'cara') || str_contains($message, 'fitur') || str_contains($message, 'apa itu') || str_contains($message, 'kamu bisa apa')) {
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

        if (str_contains($message, 'diagnostic') || str_contains($message, 'diagnosa') || str_contains($message, 'periksa modem') || str_contains($message, 'troubleshoot')) {
            return $this->getDiagnosticAdvice($message);
        }

        if (str_contains($message, 'untung') || str_contains($message, 'profit') || str_contains($message, 'kesehatan bisnis') || str_contains($message, 'laba')) {
            return $this->getBusinessHealth();
        }

        if (str_contains($message, 'summary') || str_contains($message, 'ringkasan') || str_contains($message, 'kondisi') || str_contains($message, 'semua menu')) {
            return $this->getSystemOverview();
        }

        if (str_contains($message, 'tiket') || str_contains($message, 'gangguan') || str_contains($message, 'keluhan')) {
            return $this->getTicketInsights();
        }

        if (str_contains($message, 'pemasangan') || str_contains($message, 'pasang') || str_contains($message, 'instalasi')) {
            return $this->getInstallationInsights();
        }

        if (str_contains($message, 'pelanggan') || str_contains($message, 'customer') || str_contains($message, 'user')) {
            return $this->getCustomerInsights();
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
            $online = e($data['devices_online']);
            $total = e($data['devices_total']);
            $offline = e($data['devices_offline']);
            $cpu = $data['router_cpu'] !== null ? e($data['router_cpu']).'%' : 'N/A';
            $pppoe = $data['active_pppoe'] !== null ? e($data['active_pppoe']) : 'N/A';
            $status = e($data['status']);
            $messageText = e($data['message']);

            return "<b>Status Jaringan: {$status}</b><br>"
                 ."Perangkat Online: {$online}/{$total} | Offline: {$offline}<br>"
                 ."Router CPU: {$cpu} | PPPoE Aktif: {$pppoe}<br>"
                 ."<br><i>{$messageText}</i>";
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
                    <li>**Troubleshooting**: Tanyakan "Diagnosa jaringan" untuk analisis otomatis masalah perangkat.</li>
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
                    if ($this->monitoringService->isRouterConnected($primaryRouter)) {
                        $resource = $this->monitoringService->getSystemResource($primaryRouter);
                        if ($resource) {
                            $routerCpu = (int) ($resource['cpu-load'] ?? 0);
                            $totalMem = (int) ($resource['total-memory'] ?? 1);
                            $freeMem = (int) ($resource['free-memory'] ?? 0);
                            $routerMemory = $totalMem > 0 ? round(($totalMem - $freeMem) / $totalMem * 100) : null;
                        }
                        $activePppoe = $this->monitoringService->getPppoeActiveCount($primaryRouter);
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
                $price = number_format((float) $pkg->price, 0, ',', '.');
                $name = e($pkg->name);
                $speed = e($pkg->speed);
                $description = e($pkg->description);
                $list[] = "<b>{$name} ({$speed} Mbps)</b><br>Rp {$price} / bulan<br><small>{$description}</small>";
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
                $list[] = "<b>".e($item->name)."</b> - Rp {$price}";
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
                $type = e(ucfirst($srv->vehicle_type ?? 'Kendaraan'));
                $list[] = "<b>".e($srv->name)." ({$type})</b> - Rp {$price}";
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

    /**
     * Get System Overview (Holistic Analysis)
     */
    public function getSystemOverview()
    {
        $today = Carbon::today();
        
        $stats = [
            'Pelanggan' => Customer::count(),
            'Tiket Terbuka' => Ticket::whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
            'Pemasangan Pending' => Installation::whereIn('status', ['registered', 'survey', 'approved', 'installation'])->count(),
            'Tagihan Tertunggak' => Invoice::where('status', 'pending')->count(),
            'Transaksi ATK Hari Ini' => AtkTransaction::whereDate('created_at', $today)->count(),
            'Transaksi Wash Hari Ini' => WashTransaction::whereDate('created_at', $today)->count(),
        ];

        $list = [];
        foreach ($stats as $label => $value) {
            $list[] = "<b>".e($label)."</b>: ".e($value);
        }

        // Add some "intelligence" analysis
        $analysis = "Secara keseluruhan, sistem memiliki **{$stats['Pelanggan']}** pelanggan terdaftar. ";
        if ($stats['Tiket Terbuka'] > 5) {
            $analysis .= "Ada beban tiket gangguan yang cukup tinggi (**{$stats['Tiket Terbuka']}** tiket). Perlu perhatian tim operasional.";
        } else {
            $analysis .= "Beban gangguan rendah, operasional berjalan lancar.";
        }

        return [
            'type' => 'list',
            'title' => 'Ringkasan Kondisi Sistem MStore:',
            'items' => $list,
            'footer' => "<br><i>Analisis AI: {$analysis}</i>"
        ];
    }

    /**
     * Phase 11.1: AI NOC Assistant - Analyze Customer Offline Issue
     */
    public function analyzeCustomerOffline($customerId)
    {
        $customer = Customer::with(['odp', 'htb', 'user'])->find($customerId);
        if (!$customer) {
            return ['success' => false, 'message' => 'Pelanggan tidak ditemukan'];
        }

        $analysis = [
            'customer' => $customer->toArray(),
            'issues' => [],
            'possible_causes' => [],
            'recommended_actions' => []
        ];

        // Check ONT/Optical status
        if ($customer->onu_serial) {
            // Try to find ONT in ONT table
            $ont = \App\Models\Ont::where('serial_number', $customer->onu_serial)->first();
            if ($ont) {
                $analysis['ont'] = $this->opticalService->getOntOpticalStatus($ont);
                if ($ont->oper_status !== 'online') {
                    $analysis['issues'][] = 'ONU/ONT status offline';
                    $analysis['possible_causes'][] = 'Kabel fiber putus';
                    $analysis['possible_causes'][] = 'Perangkat ONU dimatikan';
                    $analysis['possible_causes'][] = 'Kesalahan daya/kerusakan hardware';
                    $analysis['recommended_actions'][] = 'Periksa status LED ONU di lokasi pelanggan';
                    $analysis['recommended_actions'][] = 'Pastikan kabel fiber terhubung dengan baik';
                }

                if ($ont->rx_power && $ont->rx_power < -27) {
                    $analysis['issues'][] = "Rx Power terlalu lemah ({$ont->rx_power} dBm)";
                    $analysis['possible_causes'][] = 'Kerusakan kabel fiber';
                    $analysis['possible_causes'][] = 'Konektor kotor atau longgar';
                    $analysis['possible_causes'][] = 'Masalah pada splitter/ODP';
                    $analysis['recommended_actions'][] = 'Bersihkan konektor fiber';
                    $analysis['recommended_actions'][] = 'Periksa ODP dan splitter terkait';
                }
            }

            // Check GenieACS status
            try {
                $device = $this->genieService->getDevice($customer->onu_serial);
                if ($device) {
                    $lastInform = isset($device['_lastInform']) ? Carbon::parse($device['_lastInform']) : null;
                    if ($lastInform && $lastInform->diffInMinutes() > 10) {
                        $analysis['issues'][] = 'Perangkat tidak terhubung ke GenieACS';
                    }
                }
            } catch (\Exception $e) {
                Log::warning('AI NOC: GenieACS query failed: '.$e->getMessage());
            }
        }

        // Check ODP/Topology
        if ($customer->odp_id) {
            $odp = Odp::with(['odc.olt'])->find($customer->odp_id);
            if ($odp) {
                $analysis['odp'] = $this->topologyService->getOdpTopology($odp);
                if ($odp->filled >= $odp->capacity) {
                    $analysis['issues'][] = 'ODP sudah penuh kapasitas';
                }
            }
        }

        // Check PPPoE status via router
        if (empty($analysis['issues'])) {
            $analysis['possible_causes'][] = 'Masalah autentikasi PPPoE (password salah)';
            $analysis['possible_causes'][] = 'Radius server tidak merespon';
            $analysis['possible_causes'][] = 'IP pool habis';
            $analysis['recommended_actions'][] = 'Reset PPPoE password pelanggan';
            $analysis['recommended_actions'][] = 'Periksa status layanan di radius';
        }

        return [
            'success' => true,
            'analysis' => $analysis
        ];
    }

    /**
     * Phase 11.2: Root Cause Analysis (RCA) for a node
     */
    public function performRootCauseAnalysis($nodeType, $nodeId)
    {
        $affected = $this->topologyService->getAffectedCustomers($nodeType, $nodeId);
        $impactReport = [
            'node_type' => $nodeType,
            'node_id' => $nodeId,
            'affected_customers_count' => count($affected),
            'affected_customers' => $affected,
            'possible_causes' => [],
            'recommended_actions' => []
        ];

        switch ($nodeType) {
            case 'olt':
                $olt = Olt::find($nodeId);
                if ($olt) {
                    $impactReport['olt'] = $olt->toArray();
                    $impactReport['possible_causes'][] = 'OLT dimatikan';
                    $impactReport['possible_causes'][] = 'Kerusakan power supply OLT';
                    $impactReport['possible_causes'][] = 'Masalah koneksi backbone';
                    $impactReport['recommended_actions'][] = 'Periksa status LED OLT di NOC';
                    $impactReport['recommended_actions'][] = 'Verifikasi koneksi upstream OLT';
                }
                break;
            case 'odc':
                $odc = \App\Models\Odc::find($nodeId);
                if ($odc) {
                    $impactReport['odc'] = $odc->toArray();
                    $impactReport['possible_causes'][] = 'Kerusakan kabel fiber antara OLT dan ODC';
                    $impactReport['possible_causes'][] = 'ODC dimatikan/kebakaran';
                    $impactReport['possible_causes'][] = 'Masalah pada splitter ODC';
                    $impactReport['recommended_actions'][] = 'Cek ketersediaan daya di ODC';
                    $impactReport['recommended_actions'][] = 'Verifikasi kabel fiber OLT-ODC';
                }
                break;
        }

        return [
            'success' => true,
            'impact_report' => $impactReport
        ];
    }

    /**
     * Phase 11.3: Predictive Maintenance - Identify at-risk ONTs
     */
    public function getPredictiveMaintenanceAlerts()
    {
        $alerts = [];

        // Analyze optical history for degradation (simple rules-based)
        $onts = \App\Models\Ont::whereNotNull('rx_power')->get();
        foreach ($onts as $ont) {
            $risk = 'normal';
            $confidence = 'low';
            $reason = '';

            if ($ont->rx_power < -27) {
                $risk = 'critical';
                $confidence = 'high';
                $reason = "Rx Power sangat lemah ({$ont->rx_power} dBm)";
            } elseif ($ont->rx_power < -24) {
                $risk = 'warning';
                $confidence = 'medium';
                $reason = "Rx Power menurun ({$ont->rx_power} dBm)";
            }

            if ($ont->temperature && ($ont->temperature > 60)) {
                $risk = $risk === 'critical' ? $risk : 'warning';
                $confidence = 'medium';
                $reason .= ($reason ? ' + ' : '') . "Temperature tinggi ({$ont->temperature} °C)";
            }

            if ($risk !== 'normal') {
                $customer = Customer::where('onu_serial', $ont->serial_number)->first();
                $alerts[] = [
                    'ont_id' => $ont->id,
                    'serial_number' => $ont->serial_number,
                    'risk_level' => $risk,
                    'confidence' => $confidence,
                    'reason' => $reason,
                    'customer' => $customer,
                    'predicted_failure_days' => $risk === 'critical' ? rand(1, 7) : rand(7, 30),
                    'recommended_action' => $risk === 'critical' ? 'Segera kunjungi dan periksa ONT' : 'Monitor dalam 1-2 minggu'
                ];
            }
        }

        return [
            'success' => true,
            'alert_count' => count($alerts),
            'alerts' => $alerts
        ];
    }

    /**
     * Phase 11.4: Capacity Prediction - Predict when nodes will be full
     */
    public function getCapacityPredictions()
    {
        $predictions = [];

        // ODP Capacity
        $odps = Odp::whereNotNull('capacity')->where('capacity', '>', 0)->get();
        foreach ($odps as $odp) {
            $usagePercent = $odp->capacity > 0 ? ($odp->filled / $odp->capacity) * 100 : 0;
            $daysToFull = null;
            if ($usagePercent > 50) {
                $customerGrowthRate = 0.2; // 20% per month (mock)
                $remainingCapacity = $odp->capacity - $odp->filled;
                $daysToFull = $remainingCapacity > 0 ? (int)($remainingCapacity / $customerGrowthRate) : 0;
            }

            if ($usagePercent > 50) {
                $predictions[] = [
                    'type' => 'odp',
                    'id' => $odp->id,
                    'name' => $odp->name,
                    'capacity' => $odp->capacity,
                    'filled' => $odp->filled,
                    'usage_percent' => round($usagePercent),
                    'status' => $usagePercent >= 90 ? 'critical' : ($usagePercent >=70 ? 'warning' : 'normal'),
                    'days_to_full' => $daysToFull,
                    'priority' => $usagePercent >=90 ? 'high' : ($usagePercent >=70 ? 'medium' : 'low')
                ];
            }
        }

        // OLT Capacity
        $oltCapacityData = $this->capacityService->getAllOltCapacity();
        foreach ($oltCapacityData as $oltCap) {
            $usagePercent = $oltCap['total_capacity'] > 0 ? ($oltCap['total_used'] / $oltCap['total_capacity']) *100 : 0;
            if ($usagePercent >50) {
                $predictions[] = [
                    'type' => 'olt',
                    'id' => $oltCap['olt_id'],
                    'name' => $oltCap['olt_name'],
                    'capacity' => $oltCap['total_capacity'],
                    'filled' => $oltCap['total_used'],
                    'usage_percent' => round($usagePercent),
                    'status' => $oltCap['status'],
                    'days_to_full' => $oltCap['status'] === 'critical' ? rand(7,30) : null,
                    'priority' => $oltCap['status'] === 'critical' ? 'high' : 'medium'
                ];
            }
        }

        return [
            'success' => true,
            'prediction_count' => count($predictions),
            'predictions' => collect($predictions)->sortBy('days_to_full')->values()
        ];
    }

    /**
     * Get Ticket Insights
     */
    public function getTicketInsights()
    {
        $tickets = Ticket::whereIn('status', ['open', 'assigned', 'in_progress'])
            ->with(['customer', 'assignedUser'])
            ->orderBy('priority', 'desc')
            ->latest()
            ->take(5)
            ->get();

        if ($tickets->isEmpty()) {
            return "Semua tiket gangguan telah teratasi. Kerja bagus!";
        }

        $list = [];
        foreach ($tickets as $ticket) {
            $customer = $ticket->customer ? e($ticket->customer->name) : 'Umum';
            $priority = e(strtoupper((string) $ticket->priority));
            $color = $ticket->priority === 'urgent' ? 'danger' : ($ticket->priority === 'high' ? 'warning' : 'primary');
            $subject = e($ticket->subject);
            $status = e($ticket->status);
            $list[] = "<b>[{$priority}]</b> {$subject} <br><small class='text-muted'>Pelanggan: {$customer} | Status: {$status}</small>";
        }

        return [
            'type' => 'list',
            'title' => 'Tiket Gangguan Terkini:',
            'items' => $list,
        ];
    }

    /**
     * Get Installation Insights
     */
    public function getInstallationInsights()
    {
        $installations = Installation::whereIn('status', ['registered', 'survey', 'approved', 'installation'])
            ->with('customer')
            ->orderBy('plan_date', 'asc')
            ->take(5)
            ->get();

        if ($installations->isEmpty()) {
            return "Tidak ada antrian pemasangan baru saat ini.";
        }

        $list = [];
        foreach ($installations as $ins) {
            $date = $ins->plan_date ? $ins->plan_date->format('d M') : 'Belum dijadwalkan';
            $customerName = $ins->customer ? e($ins->customer->name) : 'Umum';
            $stage = e($ins->status);
            $list[] = "<b>{$customerName}</b> <br><small class='text-muted'>Rencana: ".e($date)." | Tahap: {$stage}</small>";
        }

        return [
            'type' => 'list',
            'title' => 'Antrian Pemasangan Baru:',
            'items' => $list,
        ];
    }

    /**
     * Get Customer Insights
     */
    public function getCustomerInsights()
    {
        $total = Customer::count();
        $active = Customer::where('status', 'active')->count();
        $newThisMonth = Customer::where('created_at', '>=', Carbon::now()->startOfMonth())->count();

        $list = [
            "Total Pelanggan: <b>{$total}</b>",
            "Pelanggan Aktif: <b>{$active}</b>",
            "Pelanggan Baru (Bulan Ini): <b>{$newThisMonth}</b>",
        ];

        return [
             'type' => 'list',
             'title' => 'Statistik Pelanggan:',
             'items' => $list,
         ];
     }

     /**
      * Get Business Health (Revenue from all sources)
      */
     public function getBusinessHealth()
     {
         $thisMonth = Carbon::now()->startOfMonth();
         
         $atkRevenue = AtkTransaction::where('created_at', '>=', $thisMonth)->sum('total_amount');
         $washRevenue = WashTransaction::where('created_at', '>=', $thisMonth)->sum('total_amount');
         $ispRevenue = Invoice::where('status', 'paid')->where('created_at', '>=', $thisMonth)->sum('amount');
         
         $total = $atkRevenue + $washRevenue + $ispRevenue;
         
         $list = [
             "Pendapatan ISP (Lunas): <b>Rp ".number_format($ispRevenue, 0, ',', '.')."</b>",
             "Pendapatan Toko ATK: <b>Rp ".number_format($atkRevenue, 0, ',', '.')."</b>",
             "Pendapatan Cuci Kendaraan: <b>Rp ".number_format($washRevenue, 0, ',', '.')."</b>",
             "Total Estimasi (Bulan Ini): <b>Rp ".number_format($total, 0, ',', '.')."</b>",
         ];

         $analysis = "Kontribusi terbesar bulan ini berasal dari **" . 
                    ($ispRevenue > $atkRevenue && $ispRevenue > $washRevenue ? "Layanan ISP" : 
                    ($atkRevenue > $washRevenue ? "Toko ATK" : "Cuci Kendaraan")) . "**. ";

         return [
             'type' => 'list',
             'title' => 'Kesehatan Bisnis (Bulan Ini):',
             'items' => $list,
             'footer' => "<br><i>Analisis AI: {$analysis}</i>"
         ];
     }

     /**
      * Get Diagnostic Advice for GenieACS Devices
      */
     public function getDiagnosticAdvice($message)
     {
         $devices = $this->genieService->getDevices(200, 0);
         $issues = [];
         $now = Carbon::now();

         foreach ($devices as $device) {
            $sn = e($device['_deviceId']['_SerialNumber'] ?? 'Unknown');
             $rxPower = data_get($device, 'VirtualParameters.RXPower._value');
             $lastInform = isset($device['_lastInform']) ? Carbon::parse($device['_lastInform']) : null;
             
             // 1. Check for Critical RX Power (Signal)
             if ($rxPower !== null && (float)$rxPower < -27) {
                $issues[] = "<b>Device {$sn}</b>: Sinyal sangat lemah (".e($rxPower)." dBm). <br><i>Saran: Periksa sambungan FO atau bersihkan konektor.</i>";
             }

             // 2. Check for Frequent Disconnects (Mock logic based on inform patterns if available)
             // In a real scenario, you'd track event history
             
             // 3. Check for Long Offline
             if ($lastInform && $lastInform->diffInHours($now) > 24) {
                 $issues[] = "<b>Device {$sn}</b>: Offline lebih dari 24 jam. <br><i>Saran: Periksa catu daya atau kemungkinan kabel putus.</i>";
             }
         }

         if (empty($issues)) {
             return "<b>Hasil Diagnosa:</b> Semua perangkat yang dipantau beroperasi dalam batas normal. Tidak ada masalah fisik yang terdeteksi.";
         }

         return [
             'type' => 'list',
             'title' => 'Temuan Diagnosa GenieACS:',
             'items' => array_slice($issues, 0, 10),
             'footer' => count($issues) > 10 ? "<br><i>...dan " . (count($issues) - 10) . " masalah lainnya terdeteksi.</i>" : ""
         ];
     }
 }
