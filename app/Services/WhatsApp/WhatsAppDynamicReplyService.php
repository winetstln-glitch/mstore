<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppMenu;
use App\Models\WhatsAppSession;
use App\Models\User;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\Package;
use App\Models\WeddingPackage;
use App\Models\WeddingBooking;
use App\Models\CctvPackage;
use App\Models\CctvBooking;
use App\Models\CctvSurvey;
use App\Models\WashCustomer as WashCustomerModel;
use App\Models\WashLoyaltyCounter;
use App\Models\WashMember;
use App\Models\WashRewardVoucher;
use App\Events\WhatsApp\WhatsAppAnalyticsObserved;
use App\Services\AiService;
use App\Services\KnowledgeBaseRetrievalService;
use App\Services\PaymentService;
use App\Services\NetworkDiagnosticService;
use App\Services\TechnicianAssignmentService;
use App\Services\Wash\WashLoyaltyService;
use App\Services\Wash\WashMembershipService;
use App\Actions\WhatsApp\RunDiagnosticAction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppDynamicReplyService
{
    protected $aiService;
    protected $paymentService;
    protected $networkDiagnosticService;
    protected $technicianAssignmentService;
    protected $runDiagnosticAction;
    protected $knowledgeBaseRetrieval;

    public function __construct(AiService $aiService, PaymentService $paymentService, NetworkDiagnosticService $networkDiagnosticService, TechnicianAssignmentService $technicianAssignmentService, RunDiagnosticAction $runDiagnosticAction, KnowledgeBaseRetrievalService $knowledgeBaseRetrieval)
    {
        $this->aiService = $aiService;
        $this->paymentService = $paymentService;
        $this->networkDiagnosticService = $networkDiagnosticService;
        $this->technicianAssignmentService = $technicianAssignmentService;
        $this->runDiagnosticAction = $runDiagnosticAction;
        $this->knowledgeBaseRetrieval = $knowledgeBaseRetrieval;
    }

    public function getReply(string $message, ?User $user = null, ?WhatsAppSession $session = null, ?string $mediaUrl = null, ?string $mediaType = null): array
    {
        $message = strtolower(trim($message));

        // Sentiment analysis and logging
        $sentiment = $this->analyzeSentiment($message);
        Log::info('WhatsApp message sentiment', [
            'from' => $session->phone ?? $user?->phone ?? 'unknown',
            'sentiment' => $sentiment,
            'message' => $message,
        ]);

        // Handle incoming media (future OCR/voice support)
        if ($mediaUrl && $session) {
            // This will be expanded in Phase 4 for OCR/voice processing
            // For now, inform user we've received media
            return $this->handleIncomingMedia($mediaUrl, $mediaType, $message, $user, $session);
        }

        if ($session && $session->current_node) {
            return $this->handleMultiTurnConversation($message, $user, $session);
        }

        $intent = $this->classifyIntent($message);

        if ($intent !== 'unknown') {
            return $this->handleIntent($intent, $message, $user, $session);
        }

        $menu = $this->findMatchingMenu($message);

        if ($menu) {
            $menu->incrementHitCount();
            return $this->buildReplyFromMenu($menu, $user, $session);
        }

        // Fallback to AI Assistant
        return $this->handleFallback($message, $user);
    }
    
    private function analyzeSentiment(string $message): string
    {
        $positiveWords = [
            'bagus', 'baik', 'sukses', 'berhasil', 'senang', 'puas', 'terima kasih', 'terimakasih', 'mantap', 'keren',
            'hebat', 'luar biasa', 'fantastis', 'menakjubkan', 'cepat', 'mudah', 'praktis', 'memuaskan', 'terpuaskan'
        ];
        
        $negativeWords = [
            'buruk', 'jelek', 'gagal', 'gagal', 'sedih', 'kecewa', 'marah', 'emosi', 'lelet', 'lambat', 'sulit',
            'rumit', 'tidak puas', 'kecewa', 'masalah', 'gangguan', 'error', 'bug', 'rusak', 'tidak berfungsi', 'mati'
        ];
        
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveWords as $word) {
            if (str_contains($message, $word)) {
                $positiveCount++;
            }
        }
        
        foreach ($negativeWords as $word) {
            if (str_contains($message, $word)) {
                $negativeCount++;
            }
        }
        
        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        }
        
        return 'neutral';
    }
    
    private function handleIncomingMedia(string $mediaUrl, ?string $mediaType, string $message, ?User $user, ?WhatsAppSession $session): array
    {
        $text = "Terima kasih! Kami telah menerima media Anda. Untuk saat ini, fitur OCR dan pengenalan suara masih dalam pengembangan. Silakan hubungi admin untuk bantuan lebih lanjut.";
        return [
            'type' => 'text',
            'text' => $text,
        ];
    }

    private function classifyIntent(string $message): string
    {
        if (Str::contains($message, 'member wash')) {
            return 'wash_member_help';
        }
        if (Str::contains($message, 'cek member') || Str::contains($message, 'status member')) {
            return 'wash_member_status';
        }
        if (Str::contains($message, 'cek level') || Str::contains($message, 'level member')) {
            return 'wash_member_level';
        }
        if (Str::contains($message, 'cek loyalty') || Str::contains($message, 'cek loyalti') || Str::contains($message, 'loyalty')) {
            return 'wash_loyalty_status';
        }
        if (Str::contains($message, 'status reward') || Str::contains($message, 'cek voucher')) {
            if (! Str::contains($message, 'hotspot') && ! Str::contains($message, 'internet')) {
                return 'wash_reward_status';
            }
        }

        $keywords = [
            'check_bill' => ['tagihan', 'invoice', 'bayar', 'pembayaran', 'tunggakan'],
            'report_outage' => ['internet mati', 'wifi mati', 'lemot', 'putus-putus', 'gangguan', 'koneksi lambat'],
            'check_ticket' => ['status tiket', 'cek tiket', 'tiket saya'],
            'check_outage' => ['gangguan area', 'cek gangguan', 'maintenance', 'info gangguan'],
            'check_connection' => ['cek koneksi', 'status koneksi', 'status internet', 'status wifi', 'ip saya', 'ip saya', 'ip internet'],
            'show_packages' => ['paket internet', 'internet', 'wifi', 'harga internet', 'daftar paket'],
            'request_installation' => ['pasang baru', 'instalasi', 'daftar internet', 'berlangganan'],
            'voucher' => ['voucher', 'hotspot'],
            'cctv' => ['cctv', 'kamera'],
            'wash' => ['wash', 'cuci', 'car wash'],
            'atk' => ['atk', 'alat tulis', 'stationery'],
            'wedding' => ['wedding', 'nikah', 'pernikahan'],
            'event' => ['event', 'eo', 'organizer'],
            'contact' => ['kontak', 'alamat', 'hubungi'],
            'menu' => ['menu', 'bantuan', 'help', 'halo', 'hai', 'hi', 'assalamualaikum', 'selamat pagi', 'selamat siang', 'selamat malam'],
            'ai_help' => ['bisa apa', 'apa yang bisa', 'fitur', 'cara'],
            'lead_qualification' => ['tanya paket', 'mau berlangganan', 'info lebih', 'selengkapnya', 'konsultasi'],
        ];

        foreach ($keywords as $intent => $words) {
            foreach ($words as $word) {
                if (Str::contains($message, $word)) {
                    return $intent;
                }
            }
        }

        return 'unknown';
    }

    private function handleIntent(string $intent, string $message, ?User $user, ?WhatsAppSession $session): array
    {
        switch ($intent) {
            case 'menu':
                return $this->handleShowMenu();

            case 'show_packages':
                return $this->handleShowPackages();

            case 'check_bill':
                return $this->handleCheckBillStart($session);

            case 'report_outage':
                return $this->handleReportOutageStart($session);

            case 'check_ticket':
                return $this->handleCheckTicketStart($session);
                
            case 'check_outage':
                return $this->handleCheckOutage($message, $user, $session);
                
            case 'check_connection':
                return $this->handleCheckConnectionStart($message, $user, $session);

            case 'request_installation':
                return $this->handleRequestInstallationStart($session);
                
            case 'lead_qualification':
                return $this->handleLeadQualificationStart($session);

            case 'voucher':
                return $this->handleVoucher($message);

            case 'cctv':
                return $this->handleCCTV($message, $session);

            case 'wash':
                return $this->handleWash();

            case 'wash_member_help':
                return $this->handleWashMemberHelp();

            case 'wash_member_status':
                return $this->handleWashMemberStatus($session);

            case 'wash_member_level':
                return $this->handleWashMemberLevel($session);

            case 'wash_loyalty_status':
                return $this->handleWashLoyaltyStatus($session);

            case 'wash_reward_status':
                return $this->handleWashRewardStatus($session);

            case 'atk':
                return $this->handleATK();

            case 'wedding':
                return $this->handleWedding($message, $session);

            case 'event':
                return $this->handleEvent($message, $session);

            case 'contact':
                return $this->handleContact();

            case 'ai_help':
                return $this->handleAIHelp();

            default:
                return $this->handleFallback($message, $user);
        }
    }

    private function handleWashMemberHelp(): array
    {
        return [
            'type' => 'text',
            'text' => "GT Wash Membership Digital tersedia gratis.\n\n"
                ."Untuk menjadi member, cukup gunakan:\n"
                ."• Nama\n"
                ."• WhatsApp\n"
                ."• Plat nomor\n\n"
                ."Perintah yang bisa Anda pakai:\n"
                ."• cek member\n"
                ."• cek level\n"
                ."• cek loyalty\n"
                ."• cek voucher",
        ];
    }

    private function handleWashMemberStatus(?WhatsAppSession $session): array
    {
        $member = $this->resolveWashMemberFromSession($session);
        if (! $member) {
            return [
                'type' => 'text',
                'text' => "Data member GT Wash belum ditemukan untuk nomor ini.\nPastikan Anda menggunakan nomor WhatsApp yang sama seperti saat transaksi di kasir.",
            ];
        }

        $member->loadMissing(['level', 'vehicles']);
        $primaryPlate = $member->vehicles->first()?->vehicle_plate ?? '-';

        return [
            'type' => 'text',
            'text' => "Nama:\n{$member->name}\n\n"
                ."Nomor Member:\n{$member->member_number}\n\n"
                ."Level:\n".($member->level?->name ?? 'Bronze Member')."\n\n"
                ."Total Kunjungan:\n".number_format((int) $member->total_visits, 0, ',', '.')."\n\n"
                ."Plat:\n{$primaryPlate}",
        ];
    }

    private function handleWashMemberLevel(?WhatsAppSession $session): array
    {
        $member = $this->resolveWashMemberFromSession($session);
        if (! $member) {
            return [
                'type' => 'text',
                'text' => "Level member belum bisa ditampilkan karena data membership untuk nomor ini belum ditemukan.",
            ];
        }

        $member->loadMissing('level');
        $level = $member->level;
        $discount = (float) ($level?->discount_percent ?? 0);
        $benefits = collect($level?->benefits ?? [])->map(fn ($item) => '• '.$item)->implode("\n");
        if ($benefits === '') {
            $benefits = '• Loyalty Program';
        }

        return [
            'type' => 'text',
            'text' => "Level Membership:\n".($level?->name ?? 'Bronze Member')."\n\n"
                ."Diskon:\n".rtrim(rtrim(number_format($discount, 2, ',', '.'), '0'), ',')."%\n\n"
                ."Total Transaksi:\n".number_format((int) $member->total_transactions, 0, ',', '.')."\n\n"
                ."Benefit:\n{$benefits}",
        ];
    }

    private function handleWashLoyaltyStatus(?WhatsAppSession $session): array
    {
        $phone = $session?->phone ?? '';
        $digits = preg_replace('/\D+/', '', (string) $phone);
        $alt = $digits;
        if (str_starts_with($digits, '62')) {
            $alt = '0'.substr($digits, 2);
        }

        $customer = WashCustomerModel::query()
            ->where('phone', $digits)
            ->orWhere('phone', $alt)
            ->orWhere('phone', '+'.$digits)
            ->first();

        if (! $customer) {
            return [
                'type' => 'text',
                'text' => "Untuk cek loyalty GT Wash, pastikan nomor WhatsApp Anda sama dengan nomor yang digunakan saat transaksi di kasir.",
            ];
        }

        $loyalty = app(WashLoyaltyService::class);
        $target = $loyalty->target();

        $counters = WashLoyaltyCounter::query()
            ->where('wash_customer_id', $customer->id)
            ->orderByDesc('last_paid_at')
            ->limit(5)
            ->get();

        if ($counters->count() === 0) {
            return [
                'type' => 'text',
                'text' => "Progress Loyalty: 0 / {$target}\nSisa: {$target} kali lagi\nReward: Gratis 1x Cuci",
            ];
        }

        $lines = [];
        foreach ($counters as $counter) {
            $p = $loyalty->progress($counter);
            $plate = $counter->vehicle_plate;
            $lines[] = "• {$plate}: {$p['progress']} / {$p['target']} (sisa {$p['remaining']})";
        }

        $text = "Progress Loyalty (GT Wash):\n\n".implode("\n", $lines)."\n\nReward: Gratis 1x Cuci";
        return ['type' => 'text', 'text' => $text];
    }

    private function handleWashRewardStatus(?WhatsAppSession $session): array
    {
        $phone = $session?->phone ?? '';
        $digits = preg_replace('/\D+/', '', (string) $phone);
        $alt = $digits;
        if (str_starts_with($digits, '62')) {
            $alt = '0'.substr($digits, 2);
        }

        $customer = WashCustomerModel::query()
            ->where('phone', $digits)
            ->orWhere('phone', $alt)
            ->orWhere('phone', '+'.$digits)
            ->first();

        if (! $customer) {
            return [
                'type' => 'text',
                'text' => "Untuk cek voucher reward GT Wash, pastikan nomor WhatsApp Anda sama dengan nomor yang digunakan saat transaksi di kasir.",
            ];
        }

        $now = now();
        $vouchers = WashRewardVoucher::query()
            ->where('wash_customer_id', $customer->id)
            ->where('status', 'available')
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        if ($vouchers->count() === 0) {
            return [
                'type' => 'text',
                'text' => "Voucher Reward: tidak ada voucher aktif.\nKetik \"cek loyalty\" untuk melihat progress.",
            ];
        }

        $lines = $vouchers->map(function ($v) {
            $exp = $v->expires_at ? $v->expires_at->format('d-m-Y') : '-';
            return "• {$v->code} ({$v->vehicle_plate}) exp: {$exp}";
        })->all();

        $text = "Voucher Reward Aktif:\n\n".implode("\n", $lines)."\n\nGunakan voucher saat transaksi di kasir (total Rp0).";
        return ['type' => 'text', 'text' => $text];
    }

    private function resolveWashMemberFromSession(?WhatsAppSession $session): ?WashMember
    {
        $phone = trim((string) ($session?->phone ?? ''));
        if ($phone === '') {
            return null;
        }

        return app(WashMembershipService::class)->findMemberByPhone($phone);
    }

    private function handleShowMenu(): array
    {
        $text = "📋 *MENU MSTORE*\n\n"
               . "1️⃣ Paket Internet\n"
               . "2️⃣ Instalasi Baru\n"
               . "3️⃣ Cek Tagihan\n"
               . "4️⃣ Status Pembayaran\n"
               . "5️⃣ Lapor Gangguan\n"
               . "6️⃣ Status Tiket\n"
               . "7️⃣ Voucher Internet\n"
               . "8️⃣ Hotspot\n"
               . "9️⃣ CCTV\n"
               . "🔟 GT Wash\n"
               . "1️⃣1️⃣ ATK MStore\n"
               . "1️⃣2️⃣ Wedding Organizer\n"
               . "1️⃣3️⃣ Event Organizer\n"
               . "1️⃣4️⃣ Kontak Kami\n\n"
               . "Silakan ketik nama layanan atau nomor menu.";

        return ['type' => 'text', 'text' => $text];
    }

    private function handleShowPackages(): array
    {
        try {
            $response = $this->aiService->getInternetPackages();
            return $this->convertAiResponseToWhatsApp($response);
        } catch (\Exception $e) {
            Log::error('Failed to get packages for WhatsApp: '.$e->getMessage());
            return [
                'type' => 'text',
                'text' => 'Maaf, gagal menampilkan paket internet saat ini.'
            ];
        }
    }

    private function handleCheckBillStart(?WhatsAppSession $session): array
    {
        if ($session) {
            $session->update([
                'current_node' => 'check_bill',
                'step' => 1,
                'payload' => []
            ]);
        }

        return [
            'type' => 'text',
            'text' => "📄 Untuk cek tagihan, silakan masukkan nomor pelanggan Anda:"
        ];
    }

    private function handleReportOutageStart(?WhatsAppSession $session): array
    {
        if ($session) {
            $session->update([
                'current_node' => 'report_outage',
                'step' => 1,
                'payload' => []
            ]);
        }

        return [
            'type' => 'text',
            'text' => "⚡ Untuk melaporkan gangguan, silakan masukkan nomor pelanggan Anda:"
        ];
    }

    private function handleCheckTicketStart(?WhatsAppSession $session): array
    {
        if ($session) {
            $session->update([
                'current_node' => 'check_ticket',
                'step' => 1,
                'payload' => []
            ]);
        }

        return [
            'type' => 'text',
            'text' => "🎫 Untuk cek status tiket, silakan masukkan nomor tiket Anda:"
        ];
    }

    private function handleRequestInstallationStart(?WhatsAppSession $session): array
    {
        if ($session) {
            $session->update([
                'current_node' => 'request_installation',
                'step' => 1,
                'payload' => []
            ]);
        }

        return [
            'type' => 'text',
            'text' => "🔧 Untuk permintaan instalasi baru, silakan masukkan nama lengkap Anda:"
        ];
    }
    
    private function handleLeadQualificationStart(?WhatsAppSession $session): array
    {
        if ($session) {
            $session->update([
                'current_node' => 'lead_qualification',
                'step' => 1,
                'payload' => []
            ]);
        }
        
        return [
            'type' => 'text',
            'text' => "Terima kasih atas minat Anda! Untuk kami membantu lebih lanjut, silakan masukkan nama lengkap Anda:"
        ];
    }
    
    private function handleLeadQualificationStep(string $message, WhatsAppSession $session): array
    {
        $step = $session->step;
        
        switch ($step) {
            case 1:
                $session->updatePayload(['name' => $message]);
                $session->update(['step' => 2]);
                return [
                    'type' => 'text',
                    'text' => "Halo {$message}! Sekarang, silakan pilih layanan yang Anda minati:\n\n"
                            . "1. Internet & Wifi\n2. CCTV\n3. Cuci Kendaraan\n4. ATK\n5. Wedding Organizer\n6. Event Organizer\n\n"
                            . "Silakan ketik nomor atau nama layanan!"
                ];
                
            case 2:
                $service = $this->mapServiceSelection($message);
                if (!$service) {
                    return [
                        'type' => 'text',
                        'text' => "Maaf, layanan tidak dikenali. Silakan pilih nomor 1-6 atau nama layanan yang Anda minati!"
                    ];
                }
                $session->updatePayload(['service' => $service]);
                $session->update(['step' => 3]);
                return [
                    'type' => 'text',
                    'text' => "Bagus! Anda memilih layanan {$service}. Sekarang silakan masukkan alamat lengkap Anda:"
                ];
                
            case 3:
                $session->updatePayload(['address' => $message]);
                $payload = $session->payload;
                $session->reset();
                
                Log::info('New WhatsApp lead qualified', [
                    'name' => $payload['name'],
                    'service' => $payload['service'],
                    'address' => $payload['address'],
                    'phone' => $session->phone
                ]);
                
                return [
                    'type' => 'text',
                    'text' => "Terima kasih banyak {$payload['name']}! Data Anda telah kami terima!\n\n"
                            . "Tim sales kami akan segera menghubungi Anda untuk layanan {$payload['service']} di {$payload['address']}.\n\n"
                            . "Untuk bantuan lebih lanjut, silakan hubungi kami via menu Kontak Kami!"
                ];
                
            default:
                $session->reset();
                return $this->handleShowMenu();
        }
    }
    
    private function mapServiceSelection(string $message): ?string
    {
        $message = strtolower(trim($message));
        
        $services = [
            '1' => 'Internet & Wifi',
            'internet' => 'Internet & Wifi',
            'wifi' => 'Internet & Wifi',
            '2' => 'CCTV',
            'cctv' => 'CCTV',
            '3' => 'Cuci Kendaraan',
            'wash' => 'Cuci Kendaraan',
            'cuci' => 'Cuci Kendaraan',
            '4' => 'ATK',
            'atk' => 'ATK',
            'alat tulis' => 'ATK',
            '5' => 'Wedding Organizer',
            'wedding' => 'Wedding Organizer',
            'nikah' => 'Wedding Organizer',
            '6' => 'Event Organizer',
            'event' => 'Event Organizer',
            'eo' => 'Event Organizer'
        ];
        
        foreach ($services as $key => $value) {
            if (str_contains($message, $key)) {
                return $value;
            }
        }
        
        return null;
    }

    private function handleCheckConnectionStart(string $message, ?User $user, ?WhatsAppSession $session): array
    {
        // First, try to get customer from user or phone
        $customer = null;
        if ($user) {
            $customer = $user->customer;
        }
        if (!$customer && $session) {
            $customer = Customer::where('phone', $session->phone)->first();
        }

        if (!$customer) {
            // Ask for customer ID or phone
            if ($session) {
                $session->update([
                    'current_node' => 'check_connection',
                    'step' => 1,
                    'payload' => []
                ]);
            }
            return [
                'type' => 'text',
                'text' => "Silakan masukkan nomor pelanggan atau nomor WhatsApp Anda untuk mengecek koneksi!"
            ];
        }

        // We have customer, show connection info
        return $this->showConnectionStatus($customer, $session);
    }

    private function handleCheckConnectionStep(string $message, WhatsAppSession $session): array
    {
        $step = $session->step;

        if ($step === 1) {
            $customer = Customer::where('customer_id', $message)
                ->orWhere('phone', $message)->first();
            if (!$customer) {
                return [
                    'type' => 'text',
                    'text' => "Maaf, nomor pelanggan tidak ditemukan. Silakan coba lagi!"
                ];
            }
            return $this->showConnectionStatus($customer, $session);
        }

        if ($step === 2) {
            // Restart connection
            $payload = $session->payload;
            $customer = Customer::find($payload['customer_id']);
            
            if (strtolower(trim($message)) === 'ya' || strtolower(trim($message)) === '1') {
                // Restart
                $success = $this->restartPppoeConnection($customer);
                if ($success) {
                    $session->reset();
                    return [
                        'type' => 'text',
                        'text' => "✅ Koneksi Anda sedang di-restart! Silakan tunggu 1-2 menit lalu coba kembali!"
                    ];
                }
                return [
                    'type' => 'text',
                    'text' => "❌ Gagal me-restart koneksi. Silakan hubungi admin!"
                ];
            }
            $session->reset();
            return [
                'type' => 'text',
                'text' => "✅ Baik, tidak jadi di-restart!"
            ];
        }

        $session->reset();
        return $this->handleFallback($message, null);
    }

    private function showConnectionStatus(Customer $customer, ?WhatsAppSession $session): array
    {
        $user = $customer->user;
        $package = $customer->package;
        $olt = $customer->olt;
        $odp = $customer->odp;
        $htb = $customer->htb;

        $statusText = "📊 STATUS KONEKSI\n";
        $statusText .= "Nama: {$customer->name}\n";
        $statusText .= "Nomor Pelanggan: {$customer->customer_id}\n";
        
        if ($package) {
            $statusText .= "Paket: {$package->name}\n";
            $statusText .= "Kecepatan: " . ($package->bandwidth?->name ?? '-') . "\n";
        }
        
        if ($user) {
            $statusText .= "Username PPPoE: {$user->radius_username}\n";
            $statusText .= "IP Terakhir: " . ($user->last_seen_ip ?? '-') . "\n";
            $statusText .= "Terakhir Online: " . ($user->last_seen_at?->format('d M Y H:i:s') ?? '-') . "\n";
        }
        
        if ($session) {
            $session->update([
                'current_node' => 'check_connection',
                'step' => 2,
                'payload' => ['customer_id' => $customer->id]
            ]);
            $statusText .= "\nApakah Anda ingin me-restart koneksi? (Ketik 'Ya' atau '1')";
        }
        return [
            'type' => 'text',
            'text' => $statusText
        ];
    }

    private function restartPppoeConnection(Customer $customer): bool
    {
        $user = $customer->user;
        if (!$user || !$user->radius_username) {
            return false;
        }
        // Get Router from OLT's Router, or get first active router
        $router = $customer->router ?? Router::where('is_active', true)->first();
        if (!$router) return false;

        try {
            $mikrotik = new \App\Services\MikrotikService($router);
            return $mikrotik->killActive($user->radius_username);
        } catch (\Exception $e) {
            Log::error("Failed to restart PPPoE: " . $e->getMessage());
            return false;
        }
    }

    private function handleCheckOutage(string $message, ?User $user, ?WhatsAppSession $session): array
    {
        // First, check for active outages
        $outages = \App\Models\AreaOutage::active()->get();
        
        if ($outages->isEmpty()) {
            return [
                'type' => 'text',
                'text' => "✅ Tidak ada gangguan/maintenance yang aktif saat ini!"
            ];
        }

        $text = "⚠️ DAFTAR GANGGUAN/MAINTENANCE AKTIF:\n\n";

        foreach ($outages as $idx => $outage) {
            $typeLabel = [
                'outage' => 'Gangguan',
                'maintenance' => 'Maintenance',
                'fiber_cut' => 'Fiber Cut',
                'olt_down' => 'OLT Down',
            ][$outage->type] ?? $outage->type;

            $text .= ($idx+1) . ". $typeLabel - {$outage->title}\n";
            if ($outage->description) {
                $text .= "   Detail: {$outage->description}\n";
            }
            if ($outage->started_at) {
                $text .= "   Mulai: {$outage->started_at->format('d M Y H:i')}\n";
            }
            if ($outage->estimated_finish_at) {
                $text .= "   Estimasi Selesai: {$outage->estimated_finish_at->format('d M Y H:i')}\n";
            }
            $text .= "\n";
        }

        // Check if customer is affected
        $customer = null;
        if ($user) $customer = $user->customer;
        if (!$customer && $session) $customer = \App\Models\Customer::where('phone', $session->phone)->first();
        
        if ($customer) {
            $affected = false;
            foreach ($outages as $outage) {
                if ($outage->affectsCustomer($customer)) {
                    $affected = true;
                    $text .= "⚠️ Perhatian: Area Anda terkena dampak gangguan/maintenance di atas!";
                    break;
                }
            }
        }

        return ['type' => 'text', 'text' => $text];
    }

    private function handleMultiTurnConversation(string $message, ?User $user, WhatsAppSession $session): array
    {
        $node = $session->current_node;

        switch ($node) {
            case 'check_bill':
                return $this->handleCheckBillStep($message, $session);

            case 'report_outage':
                return $this->handleReportOutageStep($message, $session);

            case 'check_ticket':
                return $this->handleCheckTicketStep($message, $session);

            case 'check_connection':
                return $this->handleCheckConnectionStep($message, $session);

            case 'request_installation':
                return $this->handleRequestInstallationStep($message, $session);

            case 'request_ticket':
                return $this->handleTicketRequestStep($message, $user, $session);

            case 'request_attendance':
                return $this->handleAttendanceRequestStep($message, $user, $session);
                
            case 'lead_qualification':
                return $this->handleLeadQualificationStep($message, $session);

            case 'wedding_booking':
                return $this->handleWeddingBookingStep($message, $session);

            case 'cctv_booking':
                return $this->handleCctvBookingStep($message, $session);

            default:
                $session->reset();
                return $this->handleFallback($message, $user);
        }
    }

    private function handleCheckBillStep(string $message, WhatsAppSession $session): array
    {
        $step = $session->step;

        if ($step === 1) {
            $customer = Customer::where('customer_id', $message)
                ->orWhere('phone', $message)
                ->first();

            if (!$customer) {
                return [
                    'type' => 'text',
                    'text' => "Maaf, nomor pelanggan tidak ditemukan. Silakan coba lagi:"
                ];
            }

            $invoices = Invoice::where('user_id', $customer->user_id)
                ->where('status', 'pending')
                ->latest()
                ->take(3)
                ->get();

            if ($invoices->isEmpty()) {
                $session->reset();
                return [
                    'type' => 'text',
                    'text' => "📄 *INFORMASI TAGIHAN*\n\nNama: {$customer->name}\nNomor Pelanggan: {$customer->customer_id}\n\nTidak ada tagihan yang harus dibayar."
                ];
            }

            $session->update([
                'step' => 2,
                'payload' => [
                    'customer_id' => $customer->id,
                    'invoice_id' => $invoices->first()->id,
                    'invoices' => $invoices->pluck('id', 'code')->toArray()
                ]
            ]);

            $text = "📄 *INFORMASI TAGIHAN*\n\n"
                   . "Nama: {$customer->name}\n"
                   . "Nomor Pelanggan: {$customer->customer_id}\n\n";

            foreach ($invoices as $index => $invoice) {
                $text .= "Tagihan #" . ($index + 1) . ":\n";
                $text .= "   Kode: {$invoice->code}\n";
                $text .= "   Jumlah: Rp " . number_format($invoice->amount, 0, ',', '.') . "\n";
                $text .= "   Jatuh Tempo: " . ($invoice->due_date ? $invoice->due_date->format('d M Y') : 'N/A') . "\n\n";
            }

            $text .= "💳 Ketik nomor tagihan yang ingin dibayar (misal: 1) atau ketik 'bayar' untuk bayar tagihan terbaru!";

            return ['type' => 'text', 'text' => $text];
        }

        if ($step === 2) {
            $payload = $session->payload;
            $customer = Customer::find($payload['customer_id']);
            $invoice = Invoice::find($payload['invoice_id']);

            if (!$invoice) {
                $session->reset();
                return [
                    'type' => 'text',
                    'text' => "Maaf, tagihan tidak ditemukan. Silakan coba lagi:"
                ];
            }

            try {
                $transaction = $this->paymentService->createQrisPayment(
                    paymentable: $invoice,
                    customerName: $customer->name,
                    phoneNumber: $customer->phone ?? $session->phone
                );

                $session->reset();

                $response = [
                    'type' => 'image',
                    'text' => "✅ QRIS telah dibuat!\n\n"
                           . "ID Pembayaran: {$transaction->reference_id}\n"
                           . "Tagihan: {$invoice->code}\n"
                           . "Jumlah: Rp " . number_format($invoice->amount, 0, ',', '.') . "\n\n"
                           . "Silakan scan QRIS untuk menyelesaikan pembayaran. QRIS berlaku 24 jam.",
                    'media_url' => $transaction->qr_url
                ];

                return $response;
            } catch (\Exception $e) {
                Log::error('Failed to create QRIS', ['error' => $e->getMessage()]);
                $session->reset();
                return [
                    'type' => 'text',
                    'text' => "Maaf, gagal membuat QRIS saat ini. Silakan coba lagi nanti atau hubungi admin."
                ];
            }
        }

        $session->reset();
        return $this->handleFallback($message, null);
    }

    private function handleReportOutageStep(string $message, WhatsAppSession $session): array
    {
        $step = $session->step;

        if ($step === 1) {
            // Step 1: Find customer and run diagnostic
            $customer = Customer::where('customer_id', $message)
                ->orWhere('phone', $message)
                ->first();

            if (!$customer) {
                return [
                    'type' => 'text',
                    'text' => "Maaf, nomor pelanggan tidak ditemukan. Silakan coba lagi:"
                ];
            }

            // Run diagnostic
            try {
                $diagnosticResponse = $this->runDiagnosticAction->execute($customer, $session);

                // Store diagnostic info in session
                if (isset($diagnosticResponse['diagnostic'])) {
                    $session->updatePayload([
                        'customer_id' => $customer->id,
                        'diagnostic_id' => $diagnosticResponse['diagnostic']->id,
                        'ticket_needed' => $diagnosticResponse['diagnostic']->ticket_needed
                    ]);
                    $session->update(['step' => 2]);
                }

                return $diagnosticResponse;
            } catch (\Exception $e) {
                Log::error('Failed to run diagnostic for WhatsApp', ['error' => $e->getMessage()]);
                
                $session->updatePayload(['customer_id' => $customer->id]);
                $session->update(['step' => 2]);
                
                return [
                    'type' => 'text',
                    'text' => "Terima kasih, {$customer->name}! Sekarang silakan jelaskan detail kendala yang Anda alami:"
                ];
            }
        }

        if ($step === 2) {
            // Step 2: Check if user typed "LANJUT" to create ticket
            $payload = $session->payload;
            $customer = Customer::find($payload['customer_id']);
            $diagnosticId = $payload['diagnostic_id'] ?? null;

            if (strtolower(trim($message)) === 'lanjut') {
                // Create ticket
                try {
                    $diagnostic = \App\Models\NetworkDiagnostic::find($diagnosticId);
                    
                    $ticket = Ticket::create([
                        'ticket_number' => Ticket::generateNumber(),
                        'customer_id' => $customer->id,
                        'subject' => 'Laporan Gangguan Internet - ' . ($diagnostic?->summary ?? 'Auto Diagnostic'),
                        'description' => $diagnostic?->summary ?? $message,
                        'type' => 'outage',
                        'priority' => $diagnostic?->priority ?? 'medium',
                        'status' => 'open',
                        'diagnostic_id' => $diagnosticId
                    ]);
                    
                    if ($diagnostic) {
                        $diagnostic->update(['ticket_id' => $ticket->id]);
                    }

                    event(new WhatsAppAnalyticsObserved([
                        'occurred_at' => now(),
                        'direction' => 'incoming',
                        'phone_number' => (string) ($session->phone_number ?? ''),
                        'whatsapp_session_id' => $session->id,
                        'intent' => 'gangguan',
                        'used_ai' => true,
                        'is_fallback' => false,
                        'ticket_id' => $ticket->id,
                        'meta' => [
                            'source' => 'whatsapp_dynamic_reply',
                            'flow' => 'report_outage',
                            'with_diagnostic' => (bool) $diagnosticId,
                        ],
                    ]));

                    // Auto assign technician
                    try {
                        $this->technicianAssignmentService->autoAssign($ticket);
                    } catch (\Exception $e) {
                        Log::error('Failed to auto assign technician', ['error' => $e->getMessage()]);
                    }

                    $session->reset();

                    return [
                        'type' => 'text',
                        'text' => "✅ *Tiket Berhasil Dibuat!*\n\n"
                               . "Nomor Tiket: {$ticket->ticket_number}\n"
                               . "Status: Open\n"
                               . "Prioritas: " . ucfirst($ticket->priority) . "\n\n"
                               . "Tim teknisi kami akan segera melakukan pengecekan dan menghubungi Anda!"
                    ];
                } catch (\Exception $e) {
                    Log::error('Failed to create ticket from diagnostic', ['error' => $e->getMessage()]);
                    $session->reset();
                    return [
                        'type' => 'text',
                        'text' => "Maaf, gagal membuat tiket. Silakan coba lagi atau hubungi admin!"
                    ];
                }
            }

            // If user didn't type "LANJUT", assume it's a message and create ticket normally
            $ticket = Ticket::create([
                'ticket_number' => Ticket::generateNumber(),
                'customer_id' => $customer->id,
                'subject' => 'Laporan Gangguan Internet',
                'description' => $message,
                'type' => 'outage',
                'priority' => 'medium',
                'status' => 'open',
                'diagnostic_id' => $diagnosticId
            ]);

            event(new WhatsAppAnalyticsObserved([
                'occurred_at' => now(),
                'direction' => 'incoming',
                'phone_number' => (string) ($session->phone_number ?? ''),
                'whatsapp_session_id' => $session->id,
                'intent' => 'gangguan',
                'used_ai' => true,
                'is_fallback' => false,
                'ticket_id' => $ticket->id,
                'meta' => [
                    'source' => 'whatsapp_dynamic_reply',
                    'flow' => 'report_outage',
                    'with_diagnostic' => (bool) $diagnosticId,
                ],
            ]));

            $session->reset();

            return [
                'type' => 'text',
                'text' => "✅ *Tiket Berhasil Dibuat!*\n\n"
                       . "Nomor Tiket: {$ticket->ticket_number}\n"
                       . "Status: Open\n\n"
                       . "Tim teknisi kami akan segera melakukan pengecekan dan menghubungi Anda!"
            ];
        }

        $session->reset();
        return $this->handleFallback($message, null);
    }

    private function handleCheckTicketStep(string $message, WhatsAppSession $session): array
    {
        $step = $session->step;

        if ($step === 1) {
            $ticket = Ticket::where('ticket_number', strtoupper($message))->first();

            if (!$ticket) {
                return [
                    'type' => 'text',
                    'text' => "Maaf, nomor tiket tidak ditemukan. Silakan coba lagi:"
                ];
            }

            $session->reset();

            $statusText = [
                'open' => 'Open',
                'in_progress' => 'In Progress',
                'resolved' => 'Resolved',
                'closed' => 'Closed',
            ][$ticket->status] ?? $ticket->status;

            $text = "🎫 *STATUS TIKET*\n\n"
                   . "Nomor Tiket: {$ticket->ticket_number}\n"
                   . "Status: {$statusText}\n"
                   . "Subjek: {$ticket->subject}\n"
                   . "Tanggal Dibuat: {$ticket->created_at->format('d M Y H:i')}\n\n"
                   . "Deskripsi: {$ticket->description}";

            if ($ticket->logs()->exists()) {
                $text .= "\n\n*Catatan Terbaru:*\n" . $ticket->logs()->latest()->first()->message;
            }

            return ['type' => 'text', 'text' => $text];
        }

        $session->reset();
        return $this->handleFallback($message, null);
    }

    private function handleRequestInstallationStep(string $message, WhatsAppSession $session): array
    {
        $step = $session->step;

        if ($step === 1) {
            $session->updatePayload(['name' => $message]);
            $session->update(['step' => 2]);
            return [
                'type' => 'text',
                'text' => "Terima kasih, {$message}! Sekarang silakan masukkan nomor WhatsApp Anda:"
            ];
        }

        if ($step === 2) {
            $session->updatePayload(['phone' => $message]);
            $session->update(['step' => 3]);
            return [
                'type' => 'text',
                'text' => "Bagus! Sekarang silakan masukkan alamat lengkap Anda:"
            ];
        }

        if ($step === 3) {
            $session->updatePayload(['address' => $message]);
            $session->reset();

            $payload = $session->payload;
            $text = "✅ *Permintaan Instalasi Diterima!*\n\n"
                   . "Nama: {$payload['name']}\n"
                   . "Nomor WhatsApp: {$payload['phone']}\n"
                   . "Alamat: {$payload['address']}\n\n"
                   . "Tim kami akan segera menghubungi Anda untuk proses selanjutnya!";

            return ['type' => 'text', 'text' => $text];
        }

        $session->reset();
        return $this->handleFallback($message, null);
    }

    private function handleVoucher(string $message): array
    {
        $text = "🎫 *VOUCHER INTERNET*\n\nUntuk informasi dan pembelian voucher internet, silakan kunjungi halaman voucher di aplikasi atau website MStore.";
        return ['type' => 'text', 'text' => $text];
    }

    private function handleCCTV(string $message, ?WhatsAppSession $session): array
    {
        if ($session && (str_contains($message, 'booking') || str_contains($message, 'instalasi') || str_contains($message, 'pasang'))) {
            return $this->handleCctvBookingStart($session);
        }

        if (str_contains($message, 'paket') || str_contains($message, 'harga')) {
            return $this->handleCctvPackages();
        }

        try {
            $response = $this->aiService->getCctvInfo();
            return $this->convertAiResponseToWhatsApp($response);
        } catch (\Exception $e) {
            Log::error('Failed to get CCTV info for WhatsApp: '.$e->getMessage());
            return [
                'type' => 'text',
                'text' => '📹 *LAYANAN CCTV*\n\nKami menyediakan layanan pemasangan CCTV profesional!'
            ];
        }
    }

    private function handleWash(): array
    {
        try {
            $response = $this->aiService->getWashServices();
            return $this->convertAiResponseToWhatsApp($response);
        } catch (\Exception $e) {
            Log::error('Failed to get Wash services for WhatsApp: '.$e->getMessage());
            return [
                'type' => 'text',
                'text' => '🚗 *GT WASH*\n\nLayanan cuci kendaraan berkualitas!'
            ];
        }
    }

    private function handleATK(): array
    {
        try {
            $response = $this->aiService->getAtkPromo();
            return $this->convertAiResponseToWhatsApp($response);
        } catch (\Exception $e) {
            Log::error('Failed to get ATK promo for WhatsApp: '.$e->getMessage());
            return [
                'type' => 'text',
                'text' => '📝 *ATK MSTORE*\n\nKami menyediakan berbagai kebutuhan ATK lengkap!'
            ];
        }
    }

    private function handleWedding(string $message, ?WhatsAppSession $session): array
    {
        if ($session && (str_contains($message, 'booking') || str_contains($message, 'pesan') || str_contains($message, 'jadwal'))) {
            return $this->handleWeddingBookingStart($session);
        }

        if (str_contains($message, 'paket') || str_contains($message, 'harga')) {
            return $this->handleWeddingPackages();
        }

        $text = "💍 *WEDDING & EVENT*\n\nKetik:\n• *paket wedding* / *harga wedding*\n• *booking wedding*";
        return ['type' => 'text', 'text' => $text];
    }

    private function handleEvent(string $message, ?WhatsAppSession $session): array
    {
        return $this->handleWedding($message, $session);
    }

    private function handleWeddingPackages(): array
    {
        $packages = WeddingPackage::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->limit(20)
            ->get();

        if ($packages->isEmpty()) {
            return ['type' => 'text', 'text' => 'Paket wedding belum tersedia saat ini.'];
        }

        $text = "💍 *PAKET WEDDING & EVENT*\n\n";
        foreach ($packages as $i => $p) {
            $no = $i + 1;
            $text .= "{$no}. {$p->name}\n";
            $text .= "   Harga: Rp ".number_format((int) $p->price, 0, ',', '.')."\n";
        }
        $text .= "\nKetik *booking wedding* untuk melakukan booking.";

        return ['type' => 'text', 'text' => $text];
    }

    private function handleCctvPackages(): array
    {
        $packages = CctvPackage::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->limit(20)
            ->get();

        if ($packages->isEmpty()) {
            return ['type' => 'text', 'text' => 'Paket CCTV belum tersedia saat ini.'];
        }

        $text = "📹 *PAKET CCTV INSTALLATION*\n\n";
        foreach ($packages as $i => $p) {
            $no = $i + 1;
            $count = $p->camera_count ? "{$p->camera_count} Camera" : 'Custom';
            $text .= "{$no}. {$p->name} ({$count})\n";
            $text .= "   Harga: Rp ".number_format((int) $p->price, 0, ',', '.')."\n";
        }
        $text .= "\nKetik *booking cctv* untuk melakukan booking instalasi.";

        return ['type' => 'text', 'text' => $text];
    }

    private function handleWeddingBookingStart(WhatsAppSession $session): array
    {
        $packages = WeddingPackage::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->limit(20)
            ->get();

        if ($packages->isEmpty()) {
            return ['type' => 'text', 'text' => 'Paket wedding belum tersedia saat ini.'];
        }

        $map = [];
        $text = "💍 *BOOKING WEDDING*\n\nPilih paket (kirim angka):\n";
        foreach ($packages as $i => $p) {
            $no = $i + 1;
            $map[(string) $no] = $p->id;
            $text .= "{$no}. {$p->name} - Rp ".number_format((int) $p->price, 0, ',', '.')."\n";
        }

        $session->update([
            'current_node' => 'wedding_booking',
            'step' => 1,
            'payload' => ['package_map' => $map],
        ]);

        return ['type' => 'text', 'text' => $text];
    }

    private function handleWeddingBookingStep(string $message, WhatsAppSession $session): array
    {
        $step = (int) $session->step;
        $payload = $session->payload ?? [];

        if ($step === 1) {
            $map = $payload['package_map'] ?? [];
            $selectedId = $map[(string) $message] ?? null;
            $package = $selectedId ? WeddingPackage::find($selectedId) : null;
            if (! $package) {
                return ['type' => 'text', 'text' => 'Pilihan paket tidak valid. Silakan kirim angka paket yang tersedia.'];
            }

            $session->update([
                'step' => 2,
                'payload' => array_merge($payload, [
                    'wedding_package_id' => $package->id,
                    'package_name' => $package->name,
                    'package_price' => (int) $package->price,
                ]),
            ]);

            return ['type' => 'text', 'text' => "Tanggal acara (contoh: 2026-12-31)?"];
        }

        if ($step === 2) {
            try {
                $date = \Carbon\Carbon::parse($message)->toDateString();
            } catch (\Throwable) {
                return ['type' => 'text', 'text' => 'Format tanggal tidak valid. Contoh: 2026-12-31'];
            }

            $session->update([
                'step' => 3,
                'payload' => array_merge($payload, ['event_date' => $date]),
            ]);

            return ['type' => 'text', 'text' => 'Lokasi acara?'];
        }

        if ($step === 3) {
            $location = trim($message);
            if ($location === '') {
                return ['type' => 'text', 'text' => 'Lokasi tidak boleh kosong.'];
            }

            $session->update([
                'step' => 4,
                'payload' => array_merge($payload, ['location' => $location]),
            ]);

            return ['type' => 'text', 'text' => 'Nama pelanggan?'];
        }

        if ($step === 4) {
            $name = trim($message);
            if ($name === '') {
                return ['type' => 'text', 'text' => 'Nama tidak boleh kosong.'];
            }

            $session->update([
                'step' => 5,
                'payload' => array_merge($payload, ['customer_name' => $name]),
            ]);

            return ['type' => 'text', 'text' => "Nomor WhatsApp (kirim *1* untuk pakai nomor ini: {$session->phone})"];
        }

        if ($step === 5) {
            $wa = trim($message) === '1' ? (string) $session->phone : trim($message);
            if ($wa === '') {
                return ['type' => 'text', 'text' => 'Nomor WhatsApp tidak boleh kosong.'];
            }

            $payload = array_merge($payload, ['customer_whatsapp' => $wa]);

            $booking = WeddingBooking::create([
                'customer_name' => (string) ($payload['customer_name'] ?? ''),
                'customer_whatsapp' => (string) ($payload['customer_whatsapp'] ?? ''),
                'event_date' => (string) ($payload['event_date'] ?? now()->toDateString()),
                'location' => (string) ($payload['location'] ?? '-'),
                'wedding_package_id' => (int) ($payload['wedding_package_id'] ?? 0),
                'status' => 'pending',
            ]);

            $base = (int) ($payload['package_price'] ?? 0);
            $dp = (int) round($base * 0.3);

            try {
                $transaction = app(\App\Services\Wedding\WeddingPaymentService::class)->createQris(
                    booking: $booking,
                    type: 'dp',
                    amount: $dp,
                    customerName: $booking->customer_name,
                    phoneNumber: $booking->customer_whatsapp,
                );
            } catch (\Throwable $e) {
                Log::error('Failed to create wedding QRIS from WhatsApp', ['error' => $e->getMessage()]);
                $session->reset();
                return [
                    'type' => 'text',
                    'text' => "Booking berhasil dibuat: {$booking->booking_number}\nNamun gagal membuat QRIS saat ini. Silakan hubungi admin.",
                ];
            }

            $session->reset();

            return [
                'type' => 'image',
                'text' => "✅ Booking dibuat!\n\nNo Booking: {$booking->booking_number}\nPaket: ".($payload['package_name'] ?? '-')."\nTanggal: {$booking->event_date->toDateString()}\nDP: Rp ".number_format($dp, 0, ',', '.')."\n\nSilakan scan QRIS berikut (berlaku 24 jam).",
                'media_url' => $transaction->qr_url,
            ];
        }

        $session->reset();
        return $this->handleFallback($message, null);
    }

    private function handleCctvBookingStart(WhatsAppSession $session): array
    {
        $packages = CctvPackage::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->limit(20)
            ->get();

        if ($packages->isEmpty()) {
            return ['type' => 'text', 'text' => 'Paket CCTV belum tersedia saat ini.'];
        }

        $map = [];
        $text = "📹 *BOOKING INSTALLASI CCTV*\n\nPilih paket (kirim angka):\n";
        foreach ($packages as $i => $p) {
            $no = $i + 1;
            $map[(string) $no] = $p->id;
            $count = $p->camera_count ? "{$p->camera_count} Camera" : 'Custom';
            $text .= "{$no}. {$p->name} ({$count}) - Rp ".number_format((int) $p->price, 0, ',', '.')."\n";
        }

        $session->update([
            'current_node' => 'cctv_booking',
            'step' => 1,
            'payload' => ['package_map' => $map],
        ]);

        return ['type' => 'text', 'text' => $text];
    }

    private function handleCctvBookingStep(string $message, WhatsAppSession $session): array
    {
        $step = (int) $session->step;
        $payload = $session->payload ?? [];

        if ($step === 1) {
            $map = $payload['package_map'] ?? [];
            $selectedId = $map[(string) $message] ?? null;
            $package = $selectedId ? CctvPackage::find($selectedId) : null;
            if (! $package) {
                return ['type' => 'text', 'text' => 'Pilihan paket tidak valid. Silakan kirim angka paket yang tersedia.'];
            }

            $session->update([
                'step' => 2,
                'payload' => array_merge($payload, [
                    'cctv_package_id' => $package->id,
                    'package_name' => $package->name,
                ]),
            ]);

            return ['type' => 'text', 'text' => 'Alamat lengkap instalasi?'];
        }

        if ($step === 2) {
            $address = trim($message);
            if ($address === '') {
                return ['type' => 'text', 'text' => 'Alamat tidak boleh kosong.'];
            }

            $session->update([
                'step' => 3,
                'payload' => array_merge($payload, ['address' => $address]),
            ]);

            return ['type' => 'text', 'text' => 'Nama pelanggan?'];
        }

        if ($step === 3) {
            $name = trim($message);
            if ($name === '') {
                return ['type' => 'text', 'text' => 'Nama tidak boleh kosong.'];
            }

            $session->update([
                'step' => 4,
                'payload' => array_merge($payload, ['customer_name' => $name]),
            ]);

            return ['type' => 'text', 'text' => "Ingin jadwalkan survey sekarang? ketik *ya* / *tidak*"];
        }

        if ($step === 4) {
            $want = strtolower(trim($message));
            $session->update([
                'payload' => array_merge($payload, ['want_survey' => $want === 'ya' || $want === 'y']),
                'step' => ($want === 'ya' || $want === 'y') ? 5 : 6,
            ]);

            if ($want === 'ya' || $want === 'y') {
                return ['type' => 'text', 'text' => 'Tanggal & jam survey (contoh: 2026-12-31 10:00)?'];
            }
        }

        if ($step === 5 || $step === 6) {
            $payload = $session->payload ?? [];

            $surveyDate = null;
            if (($payload['want_survey'] ?? false) && $step === 5) {
                try {
                    $surveyDate = \Carbon\Carbon::parse($message);
                } catch (\Throwable) {
                    return ['type' => 'text', 'text' => 'Format tanggal tidak valid. Contoh: 2026-12-31 10:00'];
                }
            }

            $booking = CctvBooking::create([
                'customer_name' => (string) ($payload['customer_name'] ?? ''),
                'customer_whatsapp' => (string) ($session->phone ?? ''),
                'address' => (string) ($payload['address'] ?? ''),
                'cctv_package_id' => (int) ($payload['cctv_package_id'] ?? 0),
                'status' => 'pending',
            ]);

            if ($surveyDate) {
                CctvSurvey::create([
                    'cctv_booking_id' => $booking->id,
                    'survey_date' => $surveyDate,
                    'location' => $booking->address,
                    'status' => 'pending',
                ]);
            }

            $session->reset();

            $text = "✅ Booking instalasi CCTV dibuat!\n\nNo Booking: {$booking->booking_number}\nPaket: ".($payload['package_name'] ?? '-')."\nWhatsApp: {$booking->customer_whatsapp}\n\nTim kami akan menghubungi Anda untuk survey & quotation.";
            if ($surveyDate) {
                $text .= "\nSurvey: ".$surveyDate->translatedFormat('d M Y H:i');
            }

            return ['type' => 'text', 'text' => $text];
        }

        $session->reset();
        return $this->handleFallback($message, null);
    }

    private function handleContact(): array
    {
        $text = "📞 *KONTAK KAMI*\n\nUntuk informasi lebih lanjut, silakan hubungi kami di:\nWhatsApp: [Nomor WhatsApp]\nTelepon: [Nomor Telepon]\nEmail: [Email]\nAlamat: [Alamat Kantor]\n\nJam Operasional: 08:00 - 17:00 WIB";
        return ['type' => 'text', 'text' => $text];
    }

    private function handleAIHelp(): array
    {
        try {
            $response = $this->aiService->getHelp('fitur');
            return $this->convertAiResponseToWhatsApp($response);
        } catch (\Exception $e) {
            Log::error('Failed to get AI help for WhatsApp: '.$e->getMessage());
            return $this->handleShowMenu();
        }
    }

    private function findMatchingMenu(string $message): ?WhatsAppMenu
    {
        $exactMatch = WhatsAppMenu::active()
            ->where('keyword', $message)
            ->first();

        if ($exactMatch) {
            return $exactMatch;
        }

        $menus = WhatsAppMenu::active()
            ->where('enable_fuzzy_match', true)
            ->orderBy('priority', 'desc')
            ->get();

        $bestMatch = null;
        $highestScore = 0;

        foreach ($menus as $menu) {
            $score = $this->calculateMatchScore($message, $menu->keyword);
            if ($score > 0.6 && $score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $menu;
            }
        }

        return $bestMatch;
    }

    private function calculateMatchScore(string $input, string $keyword): float
    {
        $inputWords = explode(' ', $input);
        $keywordWords = explode(' ', $keyword);

        $matches = 0;
        foreach ($keywordWords as $kw) {
            foreach ($inputWords as $iw) {
                similar_text(strtolower($kw), strtolower($iw), $percent);
                if ($percent > 70) {
                    $matches++;
                    break;
                }
            }
        }

        return $matches / max(count($keywordWords), 1);
    }

    private function buildReplyFromMenu(WhatsAppMenu $menu, ?User $user = null, ?WhatsAppSession $session = null): array
    {
        $responseText = $this->replaceVariables($menu->response_text, $user, $session);

        return [
            'type' => $menu->type,
            'text' => $responseText,
            'file_path' => $menu->file_path,
            'file_type' => $menu->file_type,
            'menu' => $menu,
        ];
    }

    private function replaceVariables(string $text, ?User $user = null, ?WhatsAppSession $session = null): string
    {
        $appUrl = rtrim((string) (config('app.url') ?: url('/')), '/');
        $variables = [
            '{nama_user}' => $user?->name ?? 'Teman',
            '{jam_sekarang}' => Carbon::now()->format('H:i'),
            '{tanggal_sekarang}' => Carbon::now()->translatedFormat('l, d F Y'),
            '{tahun}' => Carbon::now()->year,
            '{bulan}' => Carbon::now()->translatedFormat('F'),
            '{app_url}' => $appUrl,
        ];

        if ($session && $session->payload) {
            foreach ($session->payload as $key => $value) {
                $variables['{' . $key . '}'] = $value;
            }
        }

        return str_replace(array_keys($variables), array_values($variables), $text);
    }

    private function handleTicketRequestStep(string $message, ?User $user, WhatsAppSession $session): array
    {
        $step = $session->step;

        if ($step === 1) {
            $session->updatePayload(['ticket_title' => $message]);
            $session->setCurrentNode('request_ticket');

            return [
                'type' => 'text',
                'text' => "Terima kasih! Sekarang silakan jelaskan detail kendala yang Anda alami:",
            ];
        }

        if ($step === 2) {
            $session->updatePayload(['ticket_description' => $message]);
            $payload = $session->payload;

            $session->reset();

            return [
                'type' => 'text',
                'text' => "Terima kasih! Tiket Anda telah dibuat:\n\n"
                       . "Judul: {$payload['ticket_title']}\n"
                       . "Deskripsi: {$payload['ticket_description']}\n\n"
                       . "Tim kami akan segera menindaklanjuti!",
            ];
        }

        $session->reset();
        return $this->handleFallback($message, $user);
    }

    private function handleAttendanceRequestStep(string $message, ?User $user, WhatsAppSession $session): array
    {
        $session->reset();
        return [
            'type' => 'text',
            'text' => "Untuk melakukan absensi, silakan kunjungi aplikasi MStore atau gunakan fitur absensi di website!",
        ];
    }

    private function handleFallback(string $message, ?User $user): array
    {
        Log::info('WhatsApp fallback triggered', [
            'message' => $message,
            'user_id' => $user?->id,
        ]);

        $kbAnswer = $this->knowledgeBaseRetrieval->buildAnswerFromTopDocs($message, 2);
        if (is_string($kbAnswer) && trim($kbAnswer) !== '') {
            return ['type' => 'text', 'text' => $kbAnswer];
        }

        // Try to use AI Assistant
        try {
            $aiResponse = $this->aiService->processChat($message);
            return $this->convertAiResponseToWhatsApp($aiResponse);
        } catch (\Exception $e) {
            Log::error('Failed to get AI response for WhatsApp: '.$e->getMessage());
        }

        return [
            'type' => 'text',
            'text' => "Maaf, saya tidak memahami permintaan Anda.\n\n"
                   . "Silakan ketik *bantuan* untuk melihat layanan yang tersedia, "
                   . "atau hubungi tim support kami untuk bantuan lebih lanjut.",
        ];
    }

    private function convertAiResponseToWhatsApp($response): array
    {
        if (is_string($response)) {
            // Strip HTML tags for WhatsApp
            $cleanText = strip_tags($response);
            return ['type' => 'text', 'text' => $cleanText];
        }

        if (is_array($response)) {
            $text = '';

            if (isset($response['title'])) {
                $text .= "*{$response['title']}*\n\n";
            }

            if (isset($response['items']) && is_array($response['items'])) {
                foreach ($response['items'] as $index => $item) {
                    $cleanItem = strip_tags($item);
                    $text .= ($index + 1) . ". {$cleanItem}\n";
                }
            } elseif (isset($response['text'])) {
                $text .= strip_tags($response['text']);
            }

            if (isset($response['footer'])) {
                $text .= "\n" . strip_tags($response['footer']);
            }

            return ['type' => 'text', 'text' => $text];
        }

        return ['type' => 'text', 'text' => (string) $response];
    }
}
