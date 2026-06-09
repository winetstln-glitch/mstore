<?php

namespace App\Http\Controllers;

use App\Models\AtkProduct;
use App\Models\CctvPackage;
use App\Models\CrmLead;
use App\Models\Odp;
use App\Models\Package;
use App\Models\Setting;
use App\Models\TechnicianAttendance;
use App\Models\VoucherTemplate;
use App\Models\WashService;
use App\Models\WeddingPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LandingController extends Controller
{
    public function index()
    {
        $data = $this->buildLandingPayload();

        return view('landing.index', array_merge($data, [
            'currentServiceSlug' => null,
            'servicePage' => null,
            'pageTitle' => $data['storeName'].' - Solusi Internet, Event, Security, Wash, dan Retail',
            'pageDescription' => 'MStore adalah umbrella brand untuk Internet Fiber, Wedding & Event, Instalasi CCTV, GT Wash, dan ATK Store. Pilih layanan sesuai kebutuhan Anda.',
            'pageUrl' => route('landing'),
            'pageImage' => asset('img/cctv-monitor.png'),
        ]));
    }

    public function showService(string $service)
    {
        $data = $this->buildLandingPayload();
        $serviceCatalog = $data['serviceCatalog'];

        abort_unless(isset($serviceCatalog[$service]), 404);

        $servicePage = $serviceCatalog[$service];

        return view('landing.service', array_merge($data, [
            'servicePage' => $servicePage,
            'currentServiceSlug' => $service,
            'pageTitle' => $servicePage['meta_title'],
            'pageDescription' => $servicePage['meta_description'],
            'pageUrl' => $servicePage['url'],
            'pageImage' => $servicePage['image'],
        ]));
    }

    public function storeLead(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'email' => 'nullable|email|max:255',
            'service_interest' => 'nullable|string|max:64',
            'coverage_area' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:1000',
            'landing_page' => 'nullable|string|max:64',
            'detail_1_label' => 'nullable|string|max:100',
            'detail_1' => 'nullable|string|max:255',
            'detail_2_label' => 'nullable|string|max:100',
            'detail_2' => 'nullable|string|max:255',
            'detail_3_label' => 'nullable|string|max:100',
            'detail_3' => 'nullable|string|max:255',
            'utm_source' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_term' => 'nullable|string|max:255',
            'utm_content' => 'nullable|string|max:255',
        ]);

        $phone = $this->normalizePhone((string) $validated['phone']);
        if ($phone === '') {
            return back()->withInput()->withErrors([
                'phone' => 'Nomor HP tidak valid.',
            ]);
        }

        if (! Schema::hasTable('crm_leads')) {
            return back()->withInput()->withErrors([
                'name' => 'Lead capture belum tersedia. Jalankan migrasi database.',
            ]);
        }

        $detailLines = [];
        foreach ([1, 2, 3] as $index) {
            $label = trim((string) ($validated['detail_'.$index.'_label'] ?? ''));
            $value = trim((string) ($validated['detail_'.$index] ?? ''));
            if ($value !== '') {
                $detailLines[] = ($label !== '' ? $label : 'Detail '.$index).': '.$value;
            }
        }

        $messageBlocks = array_values(array_filter([
            trim((string) ($validated['message'] ?? '')),
            ...$detailLines,
            trim((string) ($validated['landing_page'] ?? '')) !== '' ? 'Landing Page: '.trim((string) $validated['landing_page']) : null,
        ], fn ($part) => ! is_null($part) && trim((string) $part) !== ''));

        $lead = CrmLead::create([
            'name' => trim((string) $validated['name']),
            'phone' => $phone,
            'email' => isset($validated['email']) ? trim((string) $validated['email']) : null,
            'service_interest' => isset($validated['service_interest']) ? trim((string) $validated['service_interest']) : null,
            'coverage_area' => isset($validated['coverage_area']) ? trim((string) $validated['coverage_area']) : null,
            'message' => count($messageBlocks) > 0 ? implode("\n", $messageBlocks) : null,
            'source' => 'landing',
            'status' => 'new',
            'utm_source' => $validated['utm_source'] ?? null,
            'utm_medium' => $validated['utm_medium'] ?? null,
            'utm_campaign' => $validated['utm_campaign'] ?? null,
            'utm_term' => $validated['utm_term'] ?? null,
            'utm_content' => $validated['utm_content'] ?? ($validated['landing_page'] ?? null),
        ]);

        try {
            $waNumber = Setting::getValue('whatsapp_number', '6281234567890');
        } catch (\Exception $e) {
            $waNumber = '6281234567890';
        }
        $interest = trim((string) ($lead->service_interest ?? ''));
        $interestText = $interest !== '' ? $interest : 'layanan';
        $areaText = trim((string) ($lead->coverage_area ?? ''));
        $landingPage = trim((string) ($validated['landing_page'] ?? ''));
        $messageParts = [
            'Halo, saya '.$lead->name.'.',
            'Saya tertarik '.$interestText.'.',
            $areaText !== '' ? 'Area: '.$areaText.'.' : null,
            $landingPage !== '' ? 'Halaman: '.$landingPage.'.' : null,
            'Lead ID: '.$lead->id.'.',
        ];
        $messageParts = array_values(array_filter($messageParts, fn ($part) => ! is_null($part) && trim((string) $part) !== ''));
        $waText = implode(' ', $messageParts);
        $waUrl = 'https://wa.me/'.$waNumber.'?text='.urlencode($waText);

        return back()->with([
            'success' => 'Terima kasih! Tim kami akan menghubungi Anda secepatnya.',
            'lead_whatsapp_url' => $waUrl,
        ]);
    }

    private function buildLandingPayload(): array
    {
        $user = Auth::user();
        $canAttendanceFromLanding = $user &&
            $user->hasPermission('attendance.create');
        $todayAttendance = null;

        if ($canAttendanceFromLanding) {
            $todayAttendance = TechnicianAttendance::where('user_id', $user->id)
                ->whereDate('clock_in', today())
                ->first();
        }

        // Safely fetch Packages
        try {
            $packages = Package::where('is_active', true)->orderBy('price')->get();
        } catch (\Exception $e) {
            $packages = collect([]);
        }

        // Safely fetch ATK Products
        try {
            if (class_exists(AtkProduct::class) && Schema::hasTable('atk_products')) {
                $atkProducts = AtkProduct::where('stock', '>', 0)->latest()->take(4)->get();
            } else {
                $atkProducts = collect([]);
            }
        } catch (\Exception $e) {
            $atkProducts = collect([]);
        }

        // Safely fetch Wash Services
        $washMainServices = collect([]);
        $washAddonServices = collect([]);
        try {
            if (class_exists(WashService::class) && Schema::hasTable('wash_services')) {
                $baseWashQuery = WashService::query()
                    ->with(['priceRules' => function ($query) {
                        $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
                    }]);
                if (Schema::hasColumn('wash_services', 'is_active')) {
                    $baseWashQuery->where('is_active', true);
                }
                $washServices = (clone $baseWashQuery)
                    ->orderBy('vehicle_type')
                    ->orderBy('service_category')
                    ->orderBy('size_tier')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();
                $washMainServices = $washServices->filter(function ($service) {
                    return in_array((string) ($service->service_category ?? 'main'), ['main', ''], true);
                })->values();
                $washAddonServices = $washServices->filter(function ($service) {
                    return in_array((string) ($service->service_category ?? ''), ['addon', 'skincare'], true);
                })->values();
            } else {
                $washServices = collect([]);
                $washMainServices = collect([]);
                $washAddonServices = collect([]);
            }
        } catch (\Exception $e) {
            $washServices = collect([]);
            $washMainServices = collect([]);
            $washAddonServices = collect([]);
        }

        // Get Store Identity from settings or default
        try {
            $waNumber = Setting::getValue('whatsapp_number', '6281234567890');
            $storeName = Setting::getValue('store_name', config('app.name', 'MStore'));
            $storeEmail = Setting::getValue('store_email', 'support@mstore.id');
            $storePhone = Setting::getValue('store_phone', '081234567890');
            $storeAddress = Setting::getValue('store_address', 'Jl. Raya Perjuangan No. 12a, Kebon Jeruk, Jakarta Barat');
        } catch (\Exception $e) {
            $waNumber = '6281234567890';
            $storeName = config('app.name', 'MStore');
            $storeEmail = 'support@mstore.id';
            $storePhone = '081234567890';
            $storeAddress = 'Jl. Raya Perjuangan No. 12a, Kebon Jeruk, Jakarta Barat';
        }

        // Safely fetch ODPs for Map
        try {
            if (class_exists(Odp::class) && Schema::hasTable('odps')) {
                $selectColumns = ['name', 'latitude', 'longitude', 'capacity', 'filled'];
                if (Schema::hasColumn('odps', 'kampung')) {
                    $selectColumns[] = 'kampung';
                }

                $odps = Odp::select($selectColumns)
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->get();
                $odps = $odps->map(function ($odp) {
                    $capacity = is_null($odp->capacity) ? null : (int) $odp->capacity;
                    $filled = is_null($odp->filled) ? null : (int) $odp->filled;
                    $availablePorts = null;

                    if (! is_null($capacity) && ! is_null($filled)) {
                        $availablePorts = max(0, $capacity - $filled);
                    }

                    $status = 'Unknown';
                    if (! is_null($availablePorts)) {
                        $status = $availablePorts > 0 ? 'Tersedia' : 'Penuh';
                    }

                    return [
                        'name' => (string) $odp->name,
                        'latitude' => $odp->latitude,
                        'longitude' => $odp->longitude,
                        'capacity' => $capacity,
                        'filled' => $filled,
                        'available_ports' => $availablePorts,
                        'status' => $status,
                        'kampung' => $odp->kampung ?? null,
                    ];
                })->values();
            } else {
                $odps = collect([]);
            }
        } catch (\Exception $e) {
            $odps = collect([]);
        }

        // Safely fetch Voucher Profiles
        try {
            if (class_exists(VoucherTemplate::class) && Schema::hasTable('voucher_templates')) {
                $voucherTemplates = VoucherTemplate::query()
                    ->where('is_active', true)
                    ->orderBy('price')
                    ->orderBy('name')
                    ->get();
            } else {
                $voucherTemplates = collect([]);
            }
        } catch (\Exception $e) {
            $voucherTemplates = collect([]);
        }

        try {
            $clockInStart = Setting::getValue('attendance_clock_in_start', '07:00');
            $clockInEnd = Setting::getValue('attendance_clock_in_end', '13:00');
            $clockOutStart = Setting::getValue('attendance_clock_out_start', '20:00');
            $clockOutEnd = Setting::getValue('attendance_clock_out_end', '01:00');
        } catch (\Exception $e) {
            $clockInStart = '07:00';
            $clockInEnd = '13:00';
            $clockOutStart = '20:00';
            $clockOutEnd = '01:00';
        }

        $weddingPackages = collect([]);
        try {
            if (class_exists(WeddingPackage::class) && Schema::hasTable('wedding_packages')) {
                $weddingPackages = WeddingPackage::query()
                    ->where('is_active', true)
                    ->orderBy('price')
                    ->orderBy('name')
                    ->limit(8)
                    ->get();
            }
        } catch (\Exception $e) {
            $weddingPackages = collect([]);
        }

        $cctvPackages = collect([]);
        try {
            if (class_exists(CctvPackage::class) && Schema::hasTable('cctv_packages')) {
                $cctvPackages = CctvPackage::query()
                    ->where('is_active', true)
                    ->orderBy('price')
                    ->orderBy('name')
                    ->limit(8)
                    ->get();
            }
        } catch (\Exception $e) {
            $cctvPackages = collect([]);
        }

        try {
            $weddingGallery = collect([
                Setting::getValue('wedding_service_1_image', ''),
                Setting::getValue('wedding_service_2_image', ''),
                Setting::getValue('wedding_service_3_image', ''),
            ])->filter(fn ($path) => trim((string) $path) !== '')->values();
        } catch (\Exception $e) {
            $weddingGallery = collect([]);
        }

        $serviceCatalog = $this->serviceCatalog([
            'packages' => $packages,
            'voucherTemplates' => $voucherTemplates,
            'weddingPackages' => $weddingPackages,
            'cctvPackages' => $cctvPackages,
            'washServices' => $washServices,
            'atkProducts' => $atkProducts,
        ]);

        return compact(
            'packages',
            'atkProducts',
            'washServices',
            'washMainServices',
            'washAddonServices',
            'waNumber',
            'odps',
            'voucherTemplates',
            'canAttendanceFromLanding',
            'todayAttendance',
            'clockInStart',
            'clockInEnd',
            'clockOutStart',
            'clockOutEnd',
            'storeName',
            'storeEmail',
            'storePhone',
            'storeAddress',
            'weddingPackages',
            'cctvPackages',
            'weddingGallery',
            'serviceCatalog'
        );
    }

    private function serviceCatalog(array $data): array
    {
        $internetCount = (int) $data['packages']->count();
        $voucherCount = (int) $data['voucherTemplates']->count();
        $weddingCount = (int) $data['weddingPackages']->count();
        $cctvCount = (int) $data['cctvPackages']->count();
        $washCount = (int) $data['washServices']->count();
        $atkCount = (int) $data['atkProducts']->count();
        $defaultImage = asset('img/cctv-monitor.png');

        return [
            'internet' => [
                'slug' => 'internet',
                'name' => 'Internet Fiber',
                'nav_label' => 'Internet',
                'icon' => 'fa-wifi',
                'url' => route('landing.services.internet'),
                'meta_title' => 'Internet Fiber MStore - Paket Rumah, Hotspot, dan Voucher',
                'meta_description' => 'Pilih paket internet fiber MStore untuk rumah, bisnis, hotspot, dan voucher online. Cek coverage area dan konsultasi via WhatsApp.',
                'image' => $defaultImage,
                'kicker' => 'MSTORE.NET',
                'hero_title' => 'Internet Fiber untuk Rumah, Bisnis, dan Hotspot',
                'hero_desc' => 'Pilih paket internet, cek coverage ODP, beli voucher hotspot online, dan lanjut registrasi dengan proses yang cepat.',
                'summary' => 'Paket rumahan, hotspot, voucher online, dan cek coverage area.',
                'stat' => $internetCount > 0 ? $internetCount.' paket aktif' : 'Paket siap ditampilkan',
                'highlights' => ['Paket rumah & bisnis', 'Voucher hotspot QRIS', 'Cek coverage area'],
                'form' => [
                    'interest' => 'internet',
                    'title' => 'Minta Penawaran Internet',
                    'description' => 'Isi data singkat, tim kami bantu cek coverage dan paket yang paling cocok.',
                    'coverage_label' => 'Alamat / Coverage Area',
                    'coverage_placeholder' => 'Contoh: Kampung X / RT/RW / patokan rumah',
                    'message_label' => 'Kebutuhan Internet',
                    'message_placeholder' => 'Contoh: butuh 50 Mbps untuk rumah, pemasangan minggu ini',
                    'details' => [
                        ['name' => 'detail_1', 'label' => 'Paket Diminati', 'placeholder' => 'Contoh: 30 Mbps / hotspot member'],
                        ['name' => 'detail_2', 'label' => 'Jadwal Pemasangan', 'placeholder' => 'Contoh: Sabtu pagi'],
                        ['name' => 'detail_3', 'label' => 'Catatan Lokasi', 'placeholder' => 'Contoh: dekat masjid / gang masuk mobil'],
                    ],
                ],
                'secondary_label' => 'Cek Coverage',
                'secondary_href' => '#coverage-area',
                'secondary_note' => $voucherCount > 0 ? $voucherCount.' voucher hotspot aktif' : 'Voucher hotspot tersedia',
            ],
            'wedding-event' => [
                'slug' => 'wedding-event',
                'name' => 'Wedding & Event',
                'nav_label' => 'Wedding & Event',
                'icon' => 'fa-ring',
                'url' => route('landing.services.wedding'),
                'meta_title' => 'Wedding & Event MStore - Paket Acara, Konsultasi, dan Booking',
                'meta_description' => 'Temukan paket wedding dan event yang fleksibel, konsultasi kebutuhan acara, dan booking cepat via WhatsApp.',
                'image' => $defaultImage,
                'kicker' => 'Wedding & Event',
                'hero_title' => 'Paket Wedding & Event yang Fleksibel dan Mudah Dibooking',
                'hero_desc' => 'Lihat paket wedding, konsultasikan detail acara, dan kirim kebutuhan Anda agar tim kami siapkan penawaran terbaik.',
                'summary' => 'Wedding organizer, event support, konsultasi acara, dan galeri referensi.',
                'stat' => $weddingCount > 0 ? $weddingCount.' paket wedding' : 'Konsultasi custom event',
                'highlights' => ['Konsultasi konsep acara', 'Booking via WhatsApp', 'DP via QRIS'],
                'form' => [
                    'interest' => 'wedding',
                    'title' => 'Kirim Detail Acara',
                    'description' => 'Isi tanggal dan kebutuhan acara, tim kami akan follow up untuk konsultasi paket.',
                    'coverage_label' => 'Lokasi Acara',
                    'coverage_placeholder' => 'Contoh: Gedung / rumah / kota acara',
                    'message_label' => 'Kebutuhan Acara',
                    'message_placeholder' => 'Contoh: akad + resepsi, butuh dokumentasi dan dekor',
                    'details' => [
                        ['name' => 'detail_1', 'label' => 'Tanggal Acara', 'placeholder' => 'Contoh: 15 September 2026'],
                        ['name' => 'detail_2', 'label' => 'Jumlah Tamu', 'placeholder' => 'Contoh: 300 pax'],
                        ['name' => 'detail_3', 'label' => 'Jenis Acara', 'placeholder' => 'Contoh: wedding / engagement / gathering'],
                    ],
                ],
                'secondary_label' => 'Chat WhatsApp',
                'secondary_href' => 'wa:booking wedding',
                'secondary_note' => 'Galeri dan paket bisa disesuaikan',
            ],
            'cctv' => [
                'slug' => 'cctv',
                'name' => 'CCTV Installation',
                'nav_label' => 'CCTV',
                'icon' => 'fa-video',
                'url' => route('landing.services.cctv'),
                'meta_title' => 'Instalasi CCTV MStore - Survey Gratis dan Paket Kamera',
                'meta_description' => 'Survey titik CCTV, pilih paket kamera sesuai kebutuhan, dan booking instalasi dengan proses cepat.',
                'image' => $defaultImage,
                'kicker' => 'Security Solutions',
                'hero_title' => 'Instalasi CCTV untuk Rumah, Toko, dan Kantor',
                'hero_desc' => 'Mulai dari survey gratis, pilih jumlah kamera, lalu jadwalkan pemasangan yang rapi dan terencana.',
                'summary' => 'Survey gratis, paket kamera, garansi, dan booking instalasi.',
                'stat' => $cctvCount > 0 ? $cctvCount.' paket CCTV' : 'Survey gratis tersedia',
                'highlights' => ['Survey gratis', 'Paket kamera siap pasang', 'DP via QRIS'],
                'form' => [
                    'interest' => 'cctv',
                    'title' => 'Booking Survey CCTV',
                    'description' => 'Isi kebutuhan lokasi dan jumlah titik kamera, tim kami akan jadwalkan survey.',
                    'coverage_label' => 'Lokasi Pemasangan',
                    'coverage_placeholder' => 'Contoh: ruko / rumah / kantor',
                    'message_label' => 'Kebutuhan CCTV',
                    'message_placeholder' => 'Contoh: butuh CCTV untuk area parkir dan pintu masuk',
                    'details' => [
                        ['name' => 'detail_1', 'label' => 'Jumlah Titik Kamera', 'placeholder' => 'Contoh: 4 titik'],
                        ['name' => 'detail_2', 'label' => 'Jenis Lokasi', 'placeholder' => 'Contoh: rumah / toko / kantor'],
                        ['name' => 'detail_3', 'label' => 'Jadwal Survey', 'placeholder' => 'Contoh: besok siang'],
                    ],
                ],
                'secondary_label' => 'Booking Survey',
                'secondary_href' => 'wa:survey cctv',
                'secondary_note' => 'Cocok untuk rumah, toko, dan kantor',
            ],
            'gt-wash' => [
                'slug' => 'gt-wash',
                'name' => 'GT Wash',
                'nav_label' => 'GT Wash',
                'icon' => 'fa-car-side',
                'url' => route('landing.services.wash'),
                'meta_title' => 'GT Wash - Cuci Mobil, Motor, Kedai Ms GT Wash, Membership, dan Loyalty',
                'meta_description' => 'Booking GT Wash untuk mobil dan motor. Nikmati membership digital gratis, loyalty 10x cuci gratis 1x, serta menu Kedai Ms GT Wash sambil menunggu kendaraan selesai.',
                'image' => $defaultImage,
                'kicker' => 'GT Wash',
                'hero_title' => 'Cuci Mobil & Motor dengan Membership Digital dan Kedai yang Lebih Nyaman',
                'hero_desc' => 'Lihat layanan wash, addon, membership digital gratis, loyalty 10x cuci gratis 1x, serta menu Kedai Ms GT Wash untuk menemani waktu tunggu Anda.',
                'summary' => 'Wash mobil/motor, addon, membership digital, loyalty reward, dan Kedai Ms GT Wash.',
                'stat' => $washCount > 0 ? $washCount.' layanan wash' : 'Booking wash tersedia',
                'highlights' => ['Membership gratis', 'Loyalty 10x gratis 1x', 'Kedai Ms GT Wash tersedia'],
                'form' => [
                    'interest' => 'wash',
                    'title' => 'Booking GT Wash',
                    'description' => 'Isi data kendaraan dan layanan yang diinginkan, tim kami akan konfirmasi jadwal dan antrean.',
                    'coverage_label' => 'Cabang / Lokasi Datang',
                    'coverage_placeholder' => 'Contoh: GT Wash cabang utama',
                    'message_label' => 'Layanan yang Diinginkan',
                    'message_placeholder' => 'Contoh: cuci mobil + wax / cuci motor premium',
                    'details' => [
                        ['name' => 'detail_1', 'label' => 'Plat Nomor', 'placeholder' => 'Contoh: B1234XYZ'],
                        ['name' => 'detail_2', 'label' => 'Jenis Kendaraan', 'placeholder' => 'Contoh: mobil / motor'],
                        ['name' => 'detail_3', 'label' => 'Jam Kedatangan', 'placeholder' => 'Contoh: jam 10 pagi'],
                    ],
                ],
                'secondary_label' => 'Booking Sekarang',
                'secondary_href' => 'wa:booking GT Wash',
                'secondary_note' => 'Wash, membership, dan menu kedai dalam satu tempat',
            ],
            'atk-store' => [
                'slug' => 'atk-store',
                'name' => 'ATK Store',
                'nav_label' => 'ATK',
                'icon' => 'fa-pen-ruler',
                'url' => route('landing.services.atk'),
                'meta_title' => 'ATK Store MStore - Produk Kantor, Sekolah, dan Pesan Cepat',
                'meta_description' => 'Belanja produk ATK populer, lihat promo produk, dan pesan cepat via WhatsApp.',
                'image' => $defaultImage,
                'kicker' => 'ATK Store',
                'hero_title' => 'Produk ATK untuk Kantor, Sekolah, dan Kebutuhan Harian',
                'hero_desc' => 'Lihat produk unggulan, kirim daftar kebutuhan, dan lanjutkan pemesanan dengan cepat melalui WhatsApp.',
                'summary' => 'Produk populer, promo, pemesanan cepat, dan pengadaan rutin.',
                'stat' => $atkCount > 0 ? $atkCount.' produk unggulan' : 'Pemesanan produk ATK',
                'highlights' => ['Produk populer siap pesan', 'Cocok untuk kantor & sekolah', 'Order via WhatsApp'],
                'form' => [
                    'interest' => 'atk',
                    'title' => 'Kirim Kebutuhan ATK',
                    'description' => 'Isi item atau kebutuhan rutin Anda, tim kami akan bantu siapkan penawaran dan stok.',
                    'coverage_label' => 'Alamat / Area Pengiriman',
                    'coverage_placeholder' => 'Contoh: sekolah / kantor / rumah',
                    'message_label' => 'Daftar Kebutuhan',
                    'message_placeholder' => 'Contoh: kertas A4, pulpen, map folder, spidol',
                    'details' => [
                        ['name' => 'detail_1', 'label' => 'Jenis Kebutuhan', 'placeholder' => 'Contoh: pengadaan kantor / perlengkapan sekolah'],
                        ['name' => 'detail_2', 'label' => 'Estimasi Jumlah', 'placeholder' => 'Contoh: 10 rim, 50 pulpen'],
                        ['name' => 'detail_3', 'label' => 'Waktu Kirim', 'placeholder' => 'Contoh: besok sore'],
                    ],
                ],
                'secondary_label' => 'Pesan via WhatsApp',
                'secondary_href' => 'wa:pesan ATK',
                'secondary_note' => 'Bisa untuk retail maupun kebutuhan rutin',
            ],
        ];
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (! is_string($digits) || $digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }
}
