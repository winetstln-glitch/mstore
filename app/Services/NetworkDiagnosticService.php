<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\NetworkDiagnostic;
use App\Models\NetworkIncident;
use App\Models\AreaOutage;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NetworkDiagnosticService
{
    protected $genieacs;
    protected $mikrotik;

    public function __construct(GenieACSService $genieacs, MikrotikService $mikrotik)
    {
        $this->genieacs = $genieacs;
        $this->mikrotik = $mikrotik;
    }

    /**
     * Run full diagnostic for a customer
     */
    public function runDiagnostic(Customer $customer, string $initialMessage = ''): NetworkDiagnostic
    {
        $diagnostic = NetworkDiagnostic::create([
            'customer_id' => $customer->id,
            'diagnosis_key' => 'DIAG-' . date('YmdHis') . '-' . strtoupper(str()->random(6)),
            'status' => 'running',
            'started_at' => now(),
            'checks' => [],
            'recommendations' => [],
        ]);

        $checks = [];

        // Step 1: Check Area Outages
        $areaCheck = $this->checkAreaOutages($customer);
        $checks['area_outage'] = $areaCheck;
        if ($areaCheck['issue']) {
            return $this->completeDiagnostic($diagnostic, $checks, 'area_outage');
        }

        // Step 2: Check Billing Status
        $billingCheck = $this->checkBillingStatus($customer);
        $checks['billing'] = $billingCheck;
        if ($billingCheck['issue']) {
            return $this->completeDiagnostic($diagnostic, $checks, 'billing');
        }

        // Step 3: Check ONU Status via GenieACS
        $onuCheck = $this->checkOnuStatus($customer);
        $checks['onu'] = $onuCheck;
        if ($onuCheck['issue']) {
            return $this->completeDiagnostic($diagnostic, $checks, $onuCheck['issue_type'] ?? 'onu_issue');
        }

        // Step 4: Check PPPoE Status via Mikrotik
        $pppoeCheck = $this->checkPppoeStatus($customer);
        $checks['pppoe'] = $pppoeCheck;
        if ($pppoeCheck['issue']) {
            return $this->completeDiagnostic($diagnostic, $checks, 'pppoe_issue');
        }

        // Step 5: Check Active Tickets
        $ticketCheck = $this->checkActiveTickets($customer);
        $checks['ticket'] = $ticketCheck;
        if ($ticketCheck['issue']) {
            return $this->completeDiagnostic($diagnostic, $checks, 'active_ticket');
        }

        return $this->completeDiagnostic($diagnostic, $checks, 'unknown');
    }

    /**
     * Check area outages
     */
    protected function checkAreaOutages(Customer $customer): array
    {
        $data = ['issue' => false, 'details' => null, 'outages' => []];

        $activeOutages = AreaOutage::active()->get();
        $activeIncidents = NetworkIncident::active()->get();

        foreach ($activeOutages as $outage) {
            if ($outage->affectsCustomer($customer)) {
                $data['issue'] = true;
                $data['details'] = $outage;
                $data['outages'][] = $outage;
                Log::info("Diagnostic: Customer {$customer->id} affected by area outage {$outage->id}");
                break;
            }
        }

        if (!$data['issue']) {
            foreach ($activeIncidents as $incident) {
                if ($incident->region_id === $customer->region_id) {
                    $data['issue'] = true;
                    $data['details'] = $incident;
                    $data['outages'][] = $incident;
                    Log::info("Diagnostic: Customer {$customer->id} affected by network incident {$incident->id}");
                    break;
                }
                if ($incident->odp_id === $customer->odp_id) {
                    $data['issue'] = true;
                    $data['details'] = $incident;
                    $data['outages'][] = $incident;
                    Log::info("Diagnostic: Customer {$customer->id} affected by network incident (ODP) {$incident->id}");
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * Check billing status
     */
    protected function checkBillingStatus(Customer $customer): array
    {
        $data = ['issue' => false, 'details' => null, 'is_isolated' => false, 'has_overdue' => false];

        if ($customer->is_isolated || $customer->status !== 'active') {
            $data['issue'] = true;
            $data['is_isolated'] = true;
        }

        $overdueInvoices = Invoice::where('customer_id', $customer->id)
            ->where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->get();

        if ($overdueInvoices->isNotEmpty()) {
            $data['issue'] = true;
            $data['has_overdue'] = true;
            $data['details'] = $overdueInvoices;
        }

        return $data;
    }

    /**
     * Check ONU status
     */
    protected function checkOnuStatus(Customer $customer): array
    {
        $data = ['issue' => false, 'issue_type' => null, 'device' => null, 'status' => 'unknown', 'optical_rx' => null];

        $user = $customer->user;
        if (!$user || !$user->onu_serial) {
            Log::info("Diagnostic: No ONU serial for customer {$customer->id}");
            return $data;
        }

        try {
            $device = $this->genieacs->findDeviceBySerial($user->onu_serial);
            if (!$device) {
                $data['issue'] = true;
                $data['issue_type'] = 'onu_offline';
                $data['status'] = 'offline';
                Log::info("Diagnostic: ONU not found for customer {$customer->id}");
                return $data;
            }

            $data['device'] = $device;
            $data['last_inform'] = $device['_lastInform'] ?? null;

            // Check last inform time
            if ($data['last_inform']) {
                $lastInform = Carbon::parse($data['last_inform']);
                if ($lastInform->diffInMinutes(now()) > 15) {
                    $data['issue'] = true;
                    $data['issue_type'] = 'onu_offline';
                    $data['status'] = 'offline';
                }
            }

            // Check optical power
            $rxPower = data_get($device, 'VirtualParameters.RXPower._value');
            if ($rxPower) {
                $data['optical_rx'] = (float)$rxPower;
                if ($data['optical_rx'] < -27) {
                    $data['issue'] = true;
                    $data['issue_type'] = 'onu_los';
                    $data['status'] = 'los';
                }
            }

            // Check for dying gasp
            $events = data_get($device, 'Events');
            if ($events && is_array($events)) {
                foreach ($events as $event) {
                    if (str_contains(strtolower($event), 'dying') || str_contains(strtolower($event), 'gasp')) {
                        $data['issue'] = true;
                        $data['issue_type'] = 'onu_power_loss';
                        $data['status'] = 'power_loss';
                        break;
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error("Diagnostic: GenieACS error for customer {$customer->id}: " . $e->getMessage());
        }

        return $data;
    }

    /**
     * Check PPPoE status
     */
    protected function checkPppoeStatus(Customer $customer): array
    {
        $data = ['issue' => false, 'status' => 'unknown', 'session' => null];

        $user = $customer->user;
        if (!$user || !$user->radius_username) {
            return $data;
        }

        $router = $customer->router ?? Router::where('is_active', true)->first();
        if (!$router) {
            return $data;
        }

        try {
            $this->mikrotik->setRouter($router);
            $activeSessions = $this->mikrotik->getActivePppoe();

            foreach ($activeSessions as $session) {
                if (isset($session['name']) && $session['name'] === $user->radius_username) {
                    $data['session'] = $session;
                    $data['status'] = 'online';
                    return $data;
                }
            }

            $data['issue'] = true;
            $data['status'] = 'offline';

        } catch (\Exception $e) {
            Log::error("Diagnostic: Mikrotik error for customer {$customer->id}: " . $e->getMessage());
        }

        return $data;
    }

    /**
     * Check active tickets
     */
    protected function checkActiveTickets(Customer $customer): array
    {
        $data = ['issue' => false, 'tickets' => []];

        $activeTickets = Ticket::where('customer_id', $customer->id)
            ->whereIn('status', ['open', 'in_progress', 'pending'])
            ->get();

        if ($activeTickets->isNotEmpty()) {
            $data['issue'] = true;
            $data['tickets'] = $activeTickets;
        }

        return $data;
    }

    /**
     * Complete diagnostic
     */
    protected function completeDiagnostic(NetworkDiagnostic $diagnostic, array $checks, string $diagnosisKey): NetworkDiagnostic
    {
        $summary = $this->generateSummary($checks, $diagnosisKey);
        $priority = $this->determinePriority($diagnosisKey, $checks);
        $ticketNeeded = $this->isTicketNeeded($diagnosisKey, $checks);
        $recommendations = $this->generateRecommendations($diagnosisKey, $checks);

        $diagnostic->update([
            'status' => 'completed',
            'completed_at' => now(),
            'checks' => $checks,
            'summary' => $summary,
            'priority' => $priority,
            'ticket_needed' => $ticketNeeded,
            'recommendations' => $recommendations,
            'genieacs_data' => $checks['onu']['device'] ?? null,
            'mikrotik_data' => $checks['pppoe']['session'] ?? null,
            'billing_data' => $checks['billing']['details'] ?? null,
            'area_outage_data' => $checks['area_outage']['outages'] ?? null,
        ]);

        return $diagnostic->fresh();
    }

    /**
     * Generate summary
     */
    protected function generateSummary(array $checks, string $diagnosisKey): string
    {
        $messages = [
            'area_outage' => 'Terdeteksi gangguan jaringan di area Anda. Tidak perlu membuat tiket baru.',
            'billing' => 'Layanan Anda terisolasi atau memiliki tagihan yang belum dibayar.',
            'onu_offline' => 'Perangkat ONU Anda tidak terhubung ke jaringan.',
            'onu_los' => 'Terdeteksi sinyal optik lemah/LOS pada perangkat ONU.',
            'onu_power_loss' => 'Perangkat ONU kemungkinan kehilangan daya (Dying Gasp).',
            'pppoe_issue' => 'PPPoE tidak aktif.',
            'active_ticket' => 'Anda masih memiliki tiket aktif yang sedang diproses.',
            'unknown' => 'Tidak ditemukan masalah utama, silakan restart router.',
        ];

        return $messages[$diagnosisKey] ?? 'Diagnosis selesai.';
    }

    /**
     * Determine priority
     */
    protected function determinePriority(string $diagnosisKey, array $checks): string
    {
        if ($diagnosisKey === 'onu_los') return 'high';
        if ($diagnosisKey === 'area_outage') return 'critical';
        if ($diagnosisKey === 'onu_offline') return 'medium';
        return 'low';
    }

    /**
     * Check if ticket is needed
     */
    protected function isTicketNeeded(string $diagnosisKey, array $checks): bool
    {
        $noTicket = ['area_outage', 'billing', 'active_ticket'];
        return !in_array($diagnosisKey, $noTicket);
    }

    /**
     * Generate recommendations
     */
    protected function generateRecommendations(string $diagnosisKey, array $checks): array
    {
        $recommendations = [
            'area_outage' => ['Tunggu informasi lebih lanjut tentang gangguan ini.', 'Anda akan diberitahu ketika layanan kembali normal.'],
            'billing' => ['Silakan lunasi tagihan Anda.', 'Hubungi CS jika ada pertanyaan tentang tagihan.'],
            'onu_offline' => ['Periksa kabel power ONU.', 'Pastikan adaptor ONU terpasang dengan benar.', 'Jika masih offline, silakan balas "LANJUT".'],
            'onu_los' => ['Periksa kabel fiber.', 'Pastikan konektor tidak longgar.', 'Balas "LANJUT" untuk membuat tiket.'],
            'onu_power_loss' => ['Periksa stop kontak dan kabel power.', 'Pastikan ONU mendapatkan daya.'],
            'pppoe_issue' => ['Restart router Anda.', 'Tunggu 2-5 menit.', 'Jika masih offline, balas "LANJUT".'],
            'active_ticket' => ['Anda masih memiliki tiket aktif, silakan tunggu update dari teknisi.'],
            'unknown' => ['Silakan restart router Anda.', 'Tunggu 2-5 menit.', 'Jika masih bermasalah, balas "LANJUT".'],
        ];

        return $recommendations[$diagnosisKey] ?? [];
    }
}
