<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Journal;
use App\Models\Setting;
use App\Models\TechnicianAttendance;
use App\Models\TechnicianDailySchedule;
use App\Models\WashCustomer;
use App\Models\WashService;
use App\Models\WashServicePriceRule;
use App\Models\WashStockItem;
use App\Models\WashStockMovement;
use App\Models\WashTransaction;
use App\Models\WashTransactionItem;
use App\Models\WashMember;
use App\Models\WashMemberLevel;
use App\Models\WashLoyaltyCounter;
use App\Models\WashRewardVoucher;
use App\Services\AccountingPoster;
use App\Services\AuditLogService;
use App\Services\Wash\WashLoyaltyService;
use App\Services\Wash\WashMembershipService;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class WashTransactionController extends Controller implements HasMiddleware
{
    use \App\Traits\SendsNotifications;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:wash.view', only: ['dashboard']),
            new Middleware('permission:wash.pos', only: ['pos', 'checkCustomer', 'store']),
            new Middleware('permission:wash.report', only: ['index', 'show', 'receipt', 'exportPdf', 'exportExcel', 'whatsappReceipt']),
            new Middleware('permission:wash.manage', only: ['update', 'destroy', 'bulkDestroy']),
        ];
    }

    private $brands = [
        'Motor' => [
            'Honda', 'Yamaha', 'Suzuki', 'Kawasaki', 'Vespa', 'KTM', 'Harley Davidson', 'BMW Motorrad', 'Ducati', 'Triumph', 'Royal Enfield', 'TVS', 'Benelli', 'Sym', 'Kymco', 'Viar', 'Gesits', 'Volta', 'Alva', 'Polytron', 'Davigo', 'Smoot', 'Selis', 'United', 'Zero', 'Aprilia', 'Moto Guzzi', 'Husqvarna', 'Bajaj', 'Minerva', 'Happy', 'Kaisar', 'Nozomi',
        ],
        'Mobil' => [
            'Toyota', 'Honda', 'Daihatsu', 'Mitsubishi', 'Suzuki', 'Nissan', 'Mazda', 'Wuling', 'Hyundai', 'Kia', 'Isuzu', 'BMW', 'Mercedes-Benz', 'Audi', 'Volkswagen', 'Lexus', 'Land Rover', 'Jeep', 'Ford', 'Chevrolet', 'Peugeot', 'Renault', 'Chery', 'DFSK', 'MG', 'Subaru', 'Volvo', 'Mini', 'Porsche', 'Ferrari', 'Lamborghini', 'Jaguar', 'Maserati', 'McLaren', 'Aston Martin', 'Bentley', 'Rolls-Royce', 'Tesla', 'BYD', 'Neta', 'Citroen', 'Tata', 'Proton', 'Holden', 'Opel', 'Fiat', 'Alfa Romeo', 'Datsun', 'Hino', 'UD Trucks', 'Scania', 'Foton',
        ],
    ];

    private function getLoyaltyTarget(): int
    {
        return (int) Setting::getValue('wash_loyalty_target', 11);
    }

    public function dashboard()
    {
        $today = now()->format('Y-m-d');
        $month = now()->format('Y-m');
        $user = Auth::user();
        $todayAttendance = TechnicianAttendance::where('user_id', Auth::id())
            ->whereDate('clock_in', today())
            ->first();
        $isWashOnly = $user->hasRole('karyawan-wash');
        $attendanceRole = $isWashOnly ? 'karyawan-wash' : 'kasir-wash';
        
        // Fetch wash employees (karyawan-wash) specifically as requested
        $washRoleUserIds = \App\Models\User::query()
            ->where('is_active', true)
            ->whereHas('role', function ($query) {
                $query->where('name', 'karyawan-wash');
            })
            ->pluck('id');

        $presentEmployees = TechnicianAttendance::query()
            ->with(['user.washEmployee'])
            ->whereDate('clock_in', today())
            ->whereIn('status', ['present', 'late'])
            ->whereIn('user_id', $washRoleUserIds)
            ->get();

        // Get job counts for these employees today
        $washEmployeeIds = $presentEmployees->map(fn($a) => $a->user->washEmployee?->id)->filter()->toArray();
        $jobCounts = WashTransactionItem::whereIn('employee_id', $washEmployeeIds)
            ->whereDate('created_at', today())
            ->groupBy('employee_id')
            ->select('employee_id', DB::raw('count(*) as total_jobs'))
            ->pluck('total_jobs', 'employee_id');

        // Attach job count to attendance record
        foreach ($presentEmployees as $attendance) {
            $employeeId = $attendance->user->washEmployee?->id;
            $attendance->total_jobs = $jobCounts[$employeeId] ?? 0;
        }

        $presentCount = $presentEmployees->unique('user_id')->count();
        $attendanceOverview = [
            'role' => 'Karyawan Wash',
            'total' => $washRoleUserIds->count(),
            'present' => $presentCount,
            'not_present' => max($washRoleUserIds->count() - $presentCount, 0),
        ];
        $shiftSchedule = TechnicianDailySchedule::query()
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->first();

        $dailySales = WashTransaction::whereDate('created_at', $today)->sum('total_amount');
        $monthlySales = WashTransaction::where('created_at', 'like', "$month%")->sum('total_amount');
        $transactionCount = WashTransaction::whereDate('created_at', $today)->count();
        $dailyAttendanceCount = TechnicianAttendance::whereDate('clock_in', $today)
            ->whereIn('status', ['present', 'late'])
            ->distinct('user_id')
            ->count('user_id');

        $startDate = now()->subDays(6)->toDateString();
        $endDate = now()->toDateString();
        $serviceTrendMap = WashTransactionItem::join('wash_transactions as t', 't.id', '=', 'wash_transaction_items.wash_transaction_id')
            ->whereBetween(DB::raw('DATE(t.created_at)'), [$startDate, $endDate])
            ->select(DB::raw('DATE(t.created_at) as date'), DB::raw('SUM(wash_transaction_items.quantity) as total_qty'))
            ->groupBy(DB::raw('DATE(t.created_at)'))
            ->orderBy('date')
            ->pluck('total_qty', 'date');

        $serviceTrendLabels = [];
        $serviceTrendData = [];
        for ($i = 6; $i >= 0; $i--) {
            $dateKey = now()->subDays($i)->toDateString();
            $serviceTrendLabels[] = now()->subDays($i)->translatedFormat('d M');
            $serviceTrendData[] = (int) ($serviceTrendMap[$dateKey] ?? 0);
        }

        // Top selling services
        $topServices = WashTransactionItem::select('service_name', DB::raw('sum(quantity) as total_qty'))
            ->groupBy('service_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $loyaltyTotalCustomers = 0;
        $loyaltyActiveVouchers = 0;
        $loyaltyUsedVouchers = 0;
        $loyaltyExpiredVouchers = 0;
        $loyaltyTopCustomer = null;
        $totalMembers = 0;
        $bronzeMembers = 0;
        $silverMembers = 0;
        $goldMembers = 0;
        $platinumMembers = 0;
        $membershipGrowth = 0;
        $rewardRedemptionCount = 0;
        $repeatCustomerRate = 0;
        $topMember = null;
        if (Schema::hasTable('wash_loyalty_counters') && Schema::hasTable('wash_reward_vouchers')) {
            $loyaltyTotalCustomers = WashLoyaltyCounter::query()->count();
            $now = now();
            $loyaltyActiveVouchers = WashRewardVoucher::query()
                ->where('status', 'available')
                ->where(function ($q) use ($now) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
                })
                ->count();
            $loyaltyUsedVouchers = WashRewardVoucher::query()->where('status', 'used')->count();
            $loyaltyExpiredVouchers = WashRewardVoucher::query()
                ->where(function ($q) use ($now) {
                    $q->where('status', 'expired')
                        ->orWhere(function ($q2) use ($now) {
                            $q2->where('status', 'available')->whereNotNull('expires_at')->where('expires_at', '<=', $now);
                        });
                })
                ->count();
            $loyaltyTopCustomer = WashLoyaltyCounter::query()
                ->with('customer')
                ->orderByDesc('lifetime_paid_count')
                ->first();
        }
        if (Schema::hasTable('wash_members') && Schema::hasTable('wash_member_levels')) {
            $totalMembers = WashMember::query()->count();
            $bronzeLevelId = WashMemberLevel::query()->where('code', 'bronze')->value('id');
            $silverLevelId = WashMemberLevel::query()->where('code', 'silver')->value('id');
            $goldLevelId = WashMemberLevel::query()->where('code', 'gold')->value('id');
            $platinumLevelId = WashMemberLevel::query()->where('code', 'platinum')->value('id');
            $bronzeMembers = $bronzeLevelId ? WashMember::query()->where('wash_member_level_id', $bronzeLevelId)->count() : 0;
            $silverMembers = $silverLevelId ? WashMember::query()->where('wash_member_level_id', $silverLevelId)->count() : 0;
            $goldMembers = $goldLevelId ? WashMember::query()->where('wash_member_level_id', $goldLevelId)->count() : 0;
            $platinumMembers = $platinumLevelId ? WashMember::query()->where('wash_member_level_id', $platinumLevelId)->count() : 0;
            $membershipGrowth = WashMember::query()->whereDate('joined_at', '>=', now()->startOfMonth())->count();
            $topMember = WashMember::query()->with('level')->orderByDesc('total_spending')->first();
        }
        if (Schema::hasTable('wash_reward_redemptions')) {
            $rewardRedemptionCount = \App\Models\WashRewardRedemption::query()
                ->whereDate('redeemed_at', '>=', now()->startOfMonth())
                ->count();
        }
        if (Schema::hasTable('wash_transactions')) {
            $uniqueCustomers = WashTransaction::query()
                ->whereNotNull('vehicle_plate')
                ->whereRaw("TRIM(COALESCE(vehicle_plate, '')) <> ''")
                ->distinct('vehicle_plate')
                ->count('vehicle_plate');
            $repeatCustomers = WashTransaction::query()
                ->selectRaw("UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(vehicle_plate, ''), ' ', ''), '-', ''), '.', ''), '/', '')) as normalized_plate")
                ->whereNotNull('vehicle_plate')
                ->whereRaw("TRIM(COALESCE(vehicle_plate, '')) <> ''")
                ->groupBy('normalized_plate')
                ->havingRaw('COUNT(*) >= 2')
                ->get()
                ->count();
            $repeatCustomerRate = $uniqueCustomers > 0 ? round(($repeatCustomers / $uniqueCustomers) * 100, 2) : 0;
        }

        return view('wash.dashboard', compact(
            'dailySales',
            'monthlySales',
            'transactionCount',
            'dailyAttendanceCount',
            'serviceTrendLabels',
            'serviceTrendData',
            'topServices',
            'todayAttendance',
            'attendanceOverview',
            'shiftSchedule',
            'presentEmployees',
            'loyaltyTotalCustomers',
            'loyaltyActiveVouchers',
            'loyaltyUsedVouchers',
            'loyaltyExpiredVouchers',
            'loyaltyTopCustomer',
            'totalMembers',
            'bronzeMembers',
            'silverMembers',
            'goldMembers',
            'platinumMembers',
            'membershipGrowth',
            'rewardRedemptionCount',
            'repeatCustomerRate',
            'topMember'
        ));
    }

    public function pos()
    {
        $services = WashService::query()
            ->where('is_active', true)
            ->with(['priceRules' => function ($q) {
                $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
            }])
            ->orderBy('vehicle_type')
            ->orderBy('service_category')
            ->orderBy('size_tier')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $brands = $this->brands;
        $employees = \App\Models\WashEmployee::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $allUsers = \App\Models\User::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $holidaySchedule = $this->resolveHolidayPricingSchedule();
        $knownVehiclePlates = $this->getKnownVehiclePlates();

        return view('wash.pos', compact('services', 'brands', 'employees', 'allUsers', 'holidaySchedule', 'knownVehiclePlates'));
    }

    public function checkCustomer(Request $request)
    {
        $phone = $request->query('phone');
        $vehiclePlate = $request->query('vehicle_plate');
        $customerName = $request->query('customer_name');
        $customer = null;
        if (is_string($phone) && trim($phone) !== '') {
            $customer = WashCustomer::where('phone', $phone)->first();
        }

        $loyalty = app(WashLoyaltyService::class);
        $membership = app(WashMembershipService::class);
        $plate = $loyalty->normalizePlate((string) $vehiclePlate);
        $target = $loyalty->target();

        $member = null;
        if ($plate !== '') {
            $member = $membership->findMemberByPlate($plate);
        }
        if (! $member && is_string($phone) && trim($phone) !== '') {
            $member = $membership->findMemberByPhone((string) $phone);
        }
        $memberDiscountPercent = $membership->calculateDiscountPercent($member);

        if ($plate === '' || ! Schema::hasTable('wash_loyalty_counters')) {
            return response()->json([
                'found' => (bool) $customer,
                'name' => $customer?->name ?? $customerName,
                'progress' => 0,
                'target' => $target,
                'remaining' => $target,
                'voucher_count' => 0,
                'voucher_codes' => [],
                'loyalty_basis' => 'plate',
                'loyalty_mode' => $loyalty->bonusMode(),
                'instant_bonus_eligible' => false,
                'instant_bonus_note' => null,
                'member_found' => (bool) $member,
                'member_number' => $member?->member_number,
                'member_name' => $member?->name,
                'member_status' => $member?->status,
                'member_level_code' => $member?->level?->code,
                'member_level_name' => $member?->level?->name,
                'member_discount_percent' => $memberDiscountPercent,
            ]);
        }

        $counter = $loyalty->getOrCreateCounter($customer, $plate);
        $progress = $loyalty->progress($counter);
        $vouchers = Schema::hasTable('wash_reward_vouchers')
            ? $loyalty->availableVouchersForPlate($plate)
            : collect();

        $voucherCodes = $vouchers->pluck('code')->take(10)->values()->all();

        $instantBonus = $loyalty->checkInstantBonusEligibility($plate);

        $found = (bool) $customer || ((int) $counter->lifetime_paid_count) > 0;

        return response()->json([
            'found' => $found,
            'name' => $customer?->name ?? $customerName,
            'progress' => (int) ($progress['progress'] ?? 0),
            'target' => (int) ($progress['target'] ?? $target),
            'remaining' => (int) ($progress['remaining'] ?? $target),
            'voucher_count' => (int) $vouchers->count(),
            'voucher_codes' => $voucherCodes,
            'loyalty_basis' => 'plate',
            'loyalty_mode' => $instantBonus['mode'],
            'instant_bonus_eligible' => (bool) ($instantBonus['eligible'] ?? false),
            'instant_bonus_note' => $instantBonus['note'] ?? null,
            'member_found' => (bool) $member,
            'member_number' => $member?->member_number,
            'member_name' => $member?->name,
            'member_status' => $member?->status,
            'member_level_code' => $member?->level?->code,
            'member_level_name' => $member?->level?->name,
            'member_discount_percent' => $memberDiscountPercent,
            'member_total_transactions' => (int) ($member?->total_transactions ?? 0),
            'member_total_visits' => (int) ($member?->total_visits ?? 0),
            'member_total_spending' => (float) ($member?->total_spending ?? 0),
        ]);
    }

    public function store(Request $request, \App\Services\OutboxEventService $outboxEventService)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:wash_services,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.rule_id' => 'nullable|integer|exists:wash_service_price_rules,id',
            'payment_method' => 'required|string',
            'cash_amount' => 'nullable|numeric',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'vehicle_plate' => 'nullable|string',
            'vehicle_brand' => 'nullable|string',
            'use_voucher' => 'nullable|boolean',
            'voucher_code' => 'nullable|string',
            'kasbon_type' => 'nullable|string',
            'kasbon_user_id' => 'nullable|exists:users,id',
            'kasbon_name' => 'nullable|string',
            'skip_auto_redeem_voucher' => 'nullable|boolean',
            'bonus_apply_mode' => 'nullable|string|in:now,save',
        ]);

        try {
            DB::beginTransaction();
            $normalizedPlateInput = $this->normalizePlate((string) $request->vehicle_plate);
            $vehiclePlateForStore = trim((string) $request->vehicle_plate);
            if ($vehiclePlateForStore === '' && $normalizedPlateInput !== '') {
                $vehiclePlateForStore = $normalizedPlateInput;
            }

            // Handle Customer
            $customer = null;
            if ($request->customer_phone) {
                $customer = WashCustomer::firstOrCreate(
                    ['phone' => $request->customer_phone],
                    ['name' => $request->customer_name ?? 'Guest']
                );

                // Update name if provided
                if ($request->customer_name && $customer->name === 'Guest') {
                    $customer->update(['name' => $request->customer_name]);
                }
            }

            $member = null;
            if (is_string($request->customer_phone) && trim((string) $request->customer_phone) !== '' && $vehiclePlateForStore !== '') {
                $memberName = trim((string) ($request->customer_name ?? $customer?->name ?? ''));
                $member = app(WashMembershipService::class)->ensureMember(
                    $memberName,
                    (string) $request->customer_phone,
                    $vehiclePlateForStore,
                    $request->vehicle_brand ? (string) $request->vehicle_brand : null
                );
            }

            $total = 0;
            $items = [];
            $holidaySchedule = $this->resolveHolidayPricingSchedule();
            $isHolidayPricingActive = (bool) ($holidaySchedule['active'] ?? false);

            foreach ($request->items as $itemData) {
                $service = WashService::find($itemData['id']);
                $selectedRuleId = isset($itemData['rule_id']) ? (int) $itemData['rule_id'] : null;
                $selectedRule = null;
                if (! is_null($selectedRuleId)) {
                    $selectedRule = WashServicePriceRule::query()
                        ->where('id', $selectedRuleId)
                        ->where('wash_service_id', $service->id)
                        ->where('is_active', true)
                        ->first();
                    if (! $selectedRule) {
                        throw new \RuntimeException('Aturan harga layanan tidak valid.');
                    }
                }

                $basePrice = $selectedRule ? (float) $selectedRule->price : (float) $service->price;
                $holidayAdjustment = null;
                $price = $basePrice;
                if ($isHolidayPricingActive && ! is_null($service->holiday_price)) {
                    $holidayAdjustment = (float) $service->holiday_price;
                    $price = max(0, $basePrice + $holidayAdjustment);
                }
                $subtotal = $price * $itemData['quantity'];
                $total += $subtotal;
                $serviceName = (string) $service->name;
                if ($selectedRule) {
                    $ruleLabel = preg_replace('/^(Kecil|Sedang|Besar|Extra Besar)\s*-\s*/i', '', (string) $selectedRule->label);
                    $ruleLabel = trim((string) $ruleLabel);
                    if ($ruleLabel === '') {
                        $ruleLabel = (string) $selectedRule->label;
                    }
                    $serviceName .= ' ('.$ruleLabel.')';
                }

                $items[] = [
                    'wash_service_id' => $service->id,
                    'service_name' => $serviceName,
                    'base_price' => $basePrice,
                    'holiday_adjustment' => $holidayAdjustment,
                    'price' => $price,
                    'quantity' => $itemData['quantity'],
                    'subtotal' => $subtotal,
                    'employee_id' => $itemData['employee_id'] ?? null,
                ];

            }

            $discountAmount = 0;
            $memberDiscountAmount = 0;
            $discountType = null;
            $discountNote = null;
            $instantBonusApplied = false;
            $autoRedeemVoucher = false; // 🔥 Baru: voucher terbit + langsung dipakai di transaksi INI
            $loyaltyService = app(WashLoyaltyService::class);
            $voucherCode = trim((string) $request->input('voucher_code', ''));
            $useRewardVoucher = (bool) $request->input('use_voucher') || $voucherCode !== '';

            if ($customer) {
                $customer->increment('visit_count');
            }

            // CHECK ELIGIBILITY: remaining = 1 → transaksi INI = target
            // - mode instant_discount: instant_bonus_applied = true
            // - mode voucher: autoRedeemVoucher = true (voucher dibuat + langsung dipakai)
            $isWashOnly = false;
            foreach ($items as $item) {
                $svc = WashService::find($item['wash_service_id']);
                if ($svc && $svc->vehicle_type !== 'coffee') { $isWashOnly = true; break; }
                $svcName = strtolower(trim((string) ($item['service_name'] ?? '')));
                if ($svcName !== '' && $svcName !== 'kopi' && $svcName !== 'caffe' && $svcName !== 'warkop'
                    && !str_contains($svcName, 'kopi') && !str_contains($svcName, 'caffe') && !str_contains($svcName, 'warkop')) {
                    $isWashOnly = true; break;
                }
            }
            $bonusCheck = $loyaltyService->checkInstantBonusEligibility($normalizedPlateInput);
            $loyaltyMode = $bonusCheck['mode'] ?? 'voucher';

            $skipAutoRedeem = (bool) $request->input('skip_auto_redeem_voucher', false);
            $bonusApplyMode = (string) $request->input('bonus_apply_mode', 'now');
            if ($bonusApplyMode === 'save') {
                $skipAutoRedeem = true;
            }

            if ($isWashOnly && !$useRewardVoucher && ($bonusCheck['eligible'] ?? false) && $normalizedPlateInput !== '') {
                if (count($items) !== 1 || ((int) ($items[0]['quantity'] ?? 0)) !== 1) {
                    throw new \RuntimeException('Bonus cuci ke-'.$bonusCheck['target'].' (gratis) hanya berlaku untuk 1 transaksi dengan 1 layanan (qty 1). Silakan pisahkan transaksi.');
                }
                $serviceFirst = WashService::find($items[0]['wash_service_id']);
                if ($serviceFirst && $serviceFirst->vehicle_type === 'coffee') {
                    throw new \RuntimeException('Bonus cuci gratis tidak berlaku untuk layanan caffe.');
                }
                if ($loyaltyMode === 'instant_discount') {
                    if ($skipAutoRedeem) {
                        $instantBonusApplied = false;
                        $discountAmount = 0;
                        $discountType = null;
                        $discountNote = 'instant_bonus_saved_for_later:eligible_'.$bonusCheck['target'].'x';
                    } else {
                        $instantBonusApplied = true;
                        $discountAmount = $total;
                        $discountType = 'instant_bonus_'.$bonusCheck['target'].'x';
                        $discountNote = 'instant_bonus_'.$bonusCheck['target'].'x:gratis_cuci';
                    }
                } elseif ($skipAutoRedeem) {
                    $autoRedeemVoucher = false;
                    $discountAmount = 0;
                    $discountType = null;
                    $discountNote = 'reward_voucher_saved_for_later:eligible_'.$bonusCheck['target'].'x';
                } else {
                    $autoRedeemVoucher = true;
                    $discountAmount = $total;
                    $discountType = 'reward_voucher';
                    $discountNote = 'auto_reward_voucher:pending_code_'.uniqid();
                }
            }

            $memberDiscountPercent = 0;
            if (! $useRewardVoucher && ! $instantBonusApplied && ! $autoRedeemVoucher && $member) {
                $membership = app(WashMembershipService::class);
                // Membership level is upgraded after a successful paid transaction,
                // so the discount applied here always reflects the member's level
                // before this transaction. Any new level becomes effective on the
                // next paid visit without changing the existing POS flow.
                $memberDiscountPercent = $membership->calculateDiscountPercent($member);
                $memberDiscountAmount = $membership->calculateDiscountAmount((float) $total, (float) $memberDiscountPercent);
                if ($memberDiscountAmount > 0) {
                    $discountAmount += $memberDiscountAmount;
                    $discountType = 'member_discount';
                    $discountNote = 'member_discount:'.rtrim(rtrim(number_format((float) $memberDiscountPercent, 2, '.', ''), '0'), '.').'%';
                }
            }

            if ($useRewardVoucher) {
                if ($instantBonusApplied || $autoRedeemVoucher) {
                    throw new \RuntimeException('Tidak bisa pakai voucher di transaksi bonus gratis. Batalkan salah satu.');
                }
                if ($request->payment_method === 'kasbon') {
                    throw new \RuntimeException('Voucher tidak bisa digunakan untuk transaksi kasbon.');
                }
                if ($voucherCode === '') {
                    throw new \RuntimeException('Pilih kode voucher terlebih dahulu.');
                }
                if (count($items) !== 1 || ((int) ($items[0]['quantity'] ?? 0)) !== 1) {
                    throw new \RuntimeException('Voucher hanya berlaku untuk 1 transaksi dengan 1 layanan (qty 1).');
                }
                $service = WashService::find($items[0]['wash_service_id']);
                if ($service && $service->vehicle_type === 'coffee') {
                    throw new \RuntimeException('Voucher tidak berlaku untuk layanan caffe.');
                }
                // PRE-VALIDATE reward type vs vehicle_type BEFORE commit. (final validation inside redeemVoucher again)
                if (Schema::hasTable('wash_reward_vouchers')) {
                    $preCheckVoucher = \App\Models\WashRewardVoucher::query()->where('code', strtoupper($voucherCode))->first();
                    if ($preCheckVoucher && $service && !$loyaltyService->vehicleTypeMatchesReward($service->vehicle_type, $preCheckVoucher->reward_type)) {
                        throw new \RuntimeException('Voucher hanya berlaku untuk '.$loyaltyService->rewardTypeLabel($preCheckVoucher->reward_type).'.');
                    }
                }
                $discountAmount = $total;
                $discountType = 'reward_voucher';
                $discountNote = 'reward_voucher:'.strtoupper($voucherCode);
            }

            $finalTotal = max(0, $total - $discountAmount);

            // Determine if transaction is Caffe/Warkop or Wash
            $isCaffe = false;
            foreach ($items as $item) {
                $service = WashService::find($item['wash_service_id']);
                if ($service && $service->vehicle_type === 'coffee') {
                    $isCaffe = true;
                    break;
                }
                if (strtolower($item['service_name']) === 'kopi' || 
                    strtolower($item['service_name']) === 'caffe' || 
                    strtolower($item['service_name']) === 'warkop' ||
                    str_contains(strtolower($item['service_name']), 'kopi') ||
                    str_contains(strtolower($item['service_name']), 'caffe') ||
                    str_contains(strtolower($item['service_name']), 'warkop')) {
                    $isCaffe = true;
                    break;
                }
            }

            $prefix = $isCaffe ? 'Caffe' : 'Wash';
            $today = today();
            $tomorrow = today()->addDay();
            
            // Generate Queue Number (Reset daily for overall count)
            $lastQueue = WashTransaction::where('created_at', '>=', $today)
                ->where('created_at', '<', $tomorrow)
                ->max('queue_number');
            $queueNumber = ($lastQueue ?? 0) + 1;
            
            // Step 1: Create transaction FIRST with a dummy unique transaction number (using ID placeholder to prevent conflict)
            $transaction = WashTransaction::create([
                'user_id' => Auth::id(),
                'wash_customer_id' => $customer ? $customer->id : null,
                'wash_member_id' => $member?->id,
                'transaction_number' => 'TEMP-' . uniqid(), // Temporary unique value
                'queue_number' => $queueNumber,
                'total_amount' => $finalTotal,
                'discount_amount' => $discountAmount,
                'member_discount_amount' => $memberDiscountAmount,
                'payment_method' => $discountType === 'reward_voucher' ? 'voucher' : $request->payment_method,
                'cash_amount' => $request->cash_amount,
                'change_amount' => $request->cash_amount ? ($request->cash_amount - $finalTotal) : 0,
                'customer_name' => $request->customer_name ?? ($customer ? $customer->name : null),
                'vehicle_plate' => $vehiclePlateForStore,
                'vehicle_brand' => $request->vehicle_brand,
                'notes' => $discountNote,
                'status' => 'posted',
                'posted_at' => now(),
                'kasbon_type' => $request->payment_method === 'kasbon' ? $request->kasbon_type : null,
                'kasbon_user_id' => $request->payment_method === 'kasbon' && $request->kasbon_type === 'employee' ? $request->kasbon_user_id : null,
                'kasbon_name' => $request->payment_method === 'kasbon' && $request->kasbon_type === 'outsider' ? $request->kasbon_name : null,
                'kasbon_settled' => false,
            ]);
            
            // Step 2: Now find the last sequence number for today with same prefix and date
            $dateStrForSearch = $transaction->created_at->format('dmy');
            $lastTransaction = WashTransaction::where('created_at', '>=', $today)
                ->where('created_at', '<', $tomorrow)
                ->where('transaction_number', 'LIKE', $prefix . '-' . $dateStrForSearch . '-%')
                ->where('id', '!=', $transaction->id)
                ->orderBy('id', 'desc')
                ->first();
                
            $lastSequence = 0;
            if ($lastTransaction) {
                $parts = explode('-', $lastTransaction->transaction_number);
                if (count($parts) === 3) {
                    $lastSequence = (int)$parts[2];
                }
            }
            $sequenceNumber = $lastSequence + 1;
            
            // Step 3: Update transaction with date format: Wash-140526-001 (ddmmyy)
            $dateStr = $transaction->created_at->format('dmy'); // ddmmyy
            $finalTransactionNumber = $prefix . '-' . $dateStr . '-' . str_pad($sequenceNumber, 3, '0', STR_PAD_LEFT);
            
            // Try to update with retry in case of race condition (though unlikely since we used temp first)
            $retryCount = 0;
            $maxRetries = 3;
            $success = false;
            
            while (!$success && $retryCount < $maxRetries) {
                try {
                    $transaction->update(['transaction_number' => $finalTransactionNumber]);
                    $success = true;
                } catch (\Illuminate\Database\QueryException $e) {
                    if (str_contains($e->getMessage(), 'UNIQUE constraint failed') || str_contains($e->getMessage(), '1062 Duplicate entry')) {
                        $sequenceNumber++;
                        $finalTransactionNumber = $prefix . '-' . str_pad($sequenceNumber, 3, '0', STR_PAD_LEFT);
                        $retryCount++;
                    } else {
                        throw $e;
                    }
                }
            }
            
            if (!$success) {
                // Fallback to longer format if short one fails
                $finalTransactionNumber = $prefix . '-' . $dateStr . '-' . $transaction->id;
                $transaction->update(['transaction_number' => $finalTransactionNumber]);
            }

            foreach ($items as $item) {
                $transaction->items()->create($item);
                
                // Handle stock deduction if service linked to stock item
                $service = WashService::find($item['wash_service_id']);
                if ($service && $service->wash_stock_item_id) {
                    $stockItem = WashStockItem::find($service->wash_stock_item_id);
                    if ($stockItem) {
                        // Check stock availability
                        if ($stockItem->current_stock < $item['quantity']) {
                            throw new \RuntimeException("Stok {$stockItem->name} tidak mencukupi! Stok tersedia: {$stockItem->current_stock}, dibutuhkan: {$item['quantity']}");
                        }
                        
                        // Deduct stock
                        $stockItem->decrement('current_stock', $item['quantity']);
                        
                        // Create stock movement record
                        WashStockMovement::create([
                            'wash_stock_item_id' => $stockItem->id,
                            'transaction_id' => $transaction->id,
                            'movement_type' => 'out',
                            'quantity' => $item['quantity'],
                            'unit_price' => $stockItem->last_buy_price,
                            'total_amount' => $stockItem->last_buy_price ? $stockItem->last_buy_price * $item['quantity'] : 0,
                            'movement_date' => now()->toDateString(),
                            'notes' => 'Penjualan: ' . $item['service_name'],
                            'user_id' => Auth::id(),
                        ]);
                    }
                }
            }

            $redeemedVoucher = null;
            if ($discountType === 'reward_voucher' && ! $autoRedeemVoucher) {
                // Redeem voucher jika user MEMILIH voucher manual (dari input)
                $redeemedVoucher = $loyaltyService->redeemVoucher($voucherCode, $transaction, $total, $items);
            }
            // 🔥 Jika autoRedeemVoucher: TIDAK panggil redeemVoucher di sini.
            // Voucher akan di-issue + langsung di-redeem di dalam incrementOnPaidTransaction (di bawah),
            // karena kita butuh counter dulu bertambah menjadi >= target agar voucher terbit.

            // Update default cash balance only if payment is not kasbon
            if ($request->payment_method !== 'kasbon') {
                $cash = \App\Models\Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);
                $cash->balance = (float) $cash->balance + (float) $finalTotal;
                $cash->save();
            } else {
                // Jika payment_method adalah kasbon, buat SalaryAdjustment untuk muncul di halaman rincian kasbon
                $itemsListStr = collect($items)->map(fn($item) => "{$item['service_name']} x{$item['quantity']}")->join(', ');
                $kasbonDescription = $request->kasbon_type === 'employee' 
                    ? "Kasbon karyawan: {$itemsListStr}" 
                    : "Kasbon: {$itemsListStr}";
                
                \App\Models\SalaryAdjustment::create([
                    'user_id' => $request->kasbon_type === 'employee' ? $request->kasbon_user_id : Auth::id(),
                    'type' => 'kasbon',
                    'amount' => $finalTotal,
                    'date' => now()->toDateString(),
                    'description' => $kasbonDescription . ' (' . $transaction->transaction_number . ')',
                    'status' => 'pending',
                ]);
            }

            // 🔥 Use Outbox Pattern for atomic event creation
            $outboxEvent = $outboxEventService->createOutboxEvent(
                aggregateType: \App\Models\WashTransaction::class,
                aggregateId: (string) $transaction->id,
                eventType: 'WashTransactionCreated',
                payload: [
                    'model_class' => \App\Models\WashTransaction::class,
                    'model_id' => $transaction->id,
                ]
            );

            DB::commit();

            // Dispatch OutboxEventProcessorJob after commit
            \App\Jobs\OutboxEventProcessorJob::dispatch($outboxEvent->id);

            $rewardVoucherCreatedCode = null;
            $loyaltyProgress = null;
            $voucherSavedForLater = false;
            $savedVoucherCode = null;
            $membershipLevelUpgraded = false;
            $membershipOldLevel = null;
            $membershipNewLevel = null;
            $memberFresh = null;

            if ($transaction->member_discount_amount > 0) {
                try {
                    app(AuditLogService::class)->logAction('wash_membership.discount_applied', $transaction, [
                        'wash_member_id' => $transaction->wash_member_id,
                        'amount' => (float) $transaction->member_discount_amount,
                        'note' => $transaction->notes,
                    ]);
                } catch (\Throwable) {
                }
            }

            if ($transaction->wash_member_id) {
                try {
                    $membership = app(WashMembershipService::class);
                    $sync = $membership->syncAfterTransaction($transaction);
                    $memberFresh = $sync['member'] ?? null;
                    $membershipLevelUpgraded = (bool) ($sync['level_upgraded'] ?? false);
                    $membershipOldLevel = $sync['old_level'] ?? null;
                    $membershipNewLevel = $sync['new_level'] ?? null;
                    if ($membershipLevelUpgraded && $memberFresh && $membershipNewLevel instanceof \App\Models\WashMemberLevel) {
                        $membership->sendLevelUpWhatsApp($memberFresh, $membershipNewLevel);
                    }
                } catch (\Throwable) {
                }
            }

            // Increment loyalty + buat voucher (jika mencapai target)
            // - reward_voucher (user pilih manual): skip (sudah di-redeem di atas)
            // - autoRedeemVoucher: JALAN (buat voucher + langsung redeem via parameter $autoRedeemCreatedVoucher)
            // - lainnya (instant / member / tanpa diskon): JALAN
            if (! ($discountType === 'reward_voucher' && ! $autoRedeemVoucher)) {
                try {
                    $loyalty = app(WashLoyaltyService::class);
                    $result = $loyalty->incrementOnPaidTransaction($transaction, false, $autoRedeemVoucher);
                    $created = $result['created_voucher'] ?? null;
                    $loyaltyProgress = $result['progress'] ?? null;
                    $autoRedeemedCode = $result['redeemed_voucher_code'] ?? null;
                    if ($created instanceof \App\Models\WashRewardVoucher) {
                        $rewardVoucherCreatedCode = $created->code;
                        if (! $autoRedeemVoucher) {
                            $voucherSavedForLater = true;
                            $savedVoucherCode = $created->code;
                        }
                    }
                    if ($autoRedeemVoucher && $autoRedeemedCode) {
                        $finalNote = 'auto_reward_voucher:'.$autoRedeemedCode;
                        $transaction->update(['notes' => $finalNote]);
                        $redeemedVoucher = $created;
                    }
                    if ($voucherSavedForLater && $savedVoucherCode) {
                        $finalNote = 'reward_voucher_saved:'.$savedVoucherCode;
                        $transaction->update(['notes' => $finalNote]);
                    }

                    if ($memberFresh) {
                        $target = is_array($loyaltyProgress) ? (int) ($loyaltyProgress['target'] ?? 10) : 10;
                        $progressValue = is_array($loyaltyProgress) ? (int) ($loyaltyProgress['progress'] ?? 0) : 0;
                        $remaining = is_array($loyaltyProgress) ? (int) ($loyaltyProgress['remaining'] ?? $target) : $target;
                        app(WashMembershipService::class)->sendAfterTransactionWhatsApp($memberFresh, [
                            'loyalty_progress' => $progressValue,
                            'loyalty_target' => $target,
                            'loyalty_remaining' => $remaining,
                            'reward_voucher_code' => $rewardVoucherCreatedCode,
                            'auto_redeemed_voucher_code' => $autoRedeemedCode,
                            'voucher_saved_for_later' => $voucherSavedForLater,
                            'saved_voucher_code' => $savedVoucherCode,
                        ]);
                    }
                } catch (\Throwable) {
                }
            }

            $itemsList = collect($items)->map(fn($item) => "- {$item['service_name']} x{$item['quantity']}")->join("\n");
            $paymentMethodLabel = $discountType === 'reward_voucher' ? 'VOUCHER' : strtoupper($request->payment_method);
            $cashierName = Auth::user()?->name ?? 'Sistem';
            $vehiclePlate = $vehiclePlateForStore ?: '-';
            $customerName = $request->customer_name ?? ($customer ? $customer->name : 'Tamu');
            
            $queueDisplay = $transaction->queue_display;
            $queuePriorityLabel = $transaction->queue_priority_label;
            $queueServiceOrder = $transaction->queue_service_order_today;

            $message = "🧼 *TRANSAKSI WASH BARU*\n\n";
            $message .= "📋 *No. Antrian:* {$queueNumber}\n";
            $message .= "🚦 *Queue Priority:* {$queuePriorityLabel} ({$queueDisplay})\n";
            $message .= "🏁 *Urutan Layanan Hari Ini:* #{$queueServiceOrder}\n";
            $message .= "💰 *Total:* Rp " . number_format($finalTotal, 0, ',', '.') . "\n";
            $message .= "💳 *Metode:* {$paymentMethodLabel}\n";
            $message .= "👤 *Pelanggan:* {$customerName}\n";
            $message .= "🚗 *Plat:* {$vehiclePlate}\n";
            $message .= "👤 *Kasir:* {$cashierName}\n";
            $message .= "📦 *Layanan:*\n{$itemsList}";

            $this->sendGroupNotification($message, 'wash', ['telegram']);

            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->id,
                'queue_number' => $queueNumber,
                'queue_display' => $transaction->queue_display,
                'queue_priority_label' => $transaction->queue_priority_label,
                'queue_priority_sort' => $transaction->queue_priority_sort,
                'queue_service_order_today' => $transaction->queue_service_order_today,
                'discount_type' => $discountType,
                'instant_bonus_applied' => $instantBonusApplied,
                'instant_bonus_target' => $instantBonusApplied ? ($bonusCheck['target'] ?? null) : null,
                'voucher_saved_for_later' => $voucherSavedForLater,
                'saved_voucher_code' => $savedVoucherCode,
                'redeemed_voucher_code' => $redeemedVoucher?->code,
                'reward_voucher_created_code' => $rewardVoucherCreatedCode,
                'loyalty_progress' => $loyaltyProgress,
                'member_number' => $memberFresh?->member_number ?? $member?->member_number,
                'member_level' => $memberFresh?->level?->code ?? $member?->level?->code,
                'member_discount_percent' => $memberDiscountPercent ?? 0,
                'member_discount_amount' => (float) ($memberDiscountAmount ?? 0),
                'membership_level_upgraded' => $membershipLevelUpgraded,
                'membership_new_level' => $membershipNewLevel?->code,
                'membership_new_level_effective_from' => $membershipLevelUpgraded ? 'next_transaction' : null,
                'receipt_url' => route('wash.transactions.receipt', $transaction),
                'message' => $instantBonusApplied ? 'Bonus cuci ke-'.($bonusCheck['target'] ?? '').' GRATIS diterapkan.' : ($voucherSavedForLater ? 'Voucher bonus disimpan untuk kunjungan berikutnya.' : 'Transaction successful'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function index(Request $request)
    {
        $query = WashTransaction::with(['user', 'items', 'washCustomer', 'member.level']);

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                $request->start_date.' 00:00:00',
                $request->end_date.' 23:59:59',
            ]);
        }
        if ($request->filled('vehicle_plate')) {
            $this->applyVehiclePlateFilter($query, (string) $request->input('vehicle_plate'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $normalizedSearch = $this->normalizePlate($search);
            $query->where(function ($q) use ($search, $normalizedSearch) {
                $q->where('transaction_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhereRaw(
                      "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(vehicle_plate, ''), ' ', ''), '-', ''), '.', ''), '/', '')) LIKE ?",
                      ['%' . $normalizedSearch . '%']
                  )
                  ->orWhereHas('items', function ($qi) use ($search) {
                      $qi->where('service_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($qi) use ($search) {
                      $qi->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $per = $request->get('per_page', '10');
        if ($per === 'all') {
            $perPage = max(1, (int) $query->count());
        } else {
            $perPage = (int) $per;
            if (! in_array($perPage, [10, 20], true)) {
                $perPage = 10;
            }
        }

        $transactions = $query->latest()->paginate($perPage)->appends($request->query());

        $knownVehiclePlates = $this->getKnownVehiclePlates();

        return view('wash.transactions.index', compact('transactions', 'knownVehiclePlates'));
    }

    private function applyVehiclePlateFilter($query, string $plate): void
    {
        $normalized = $this->normalizePlate($plate);
        if ($normalized === '') {
            return;
        }
        $query->whereRaw(
            "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(vehicle_plate, ''), ' ', ''), '-', ''), '.', ''), '/', '')) LIKE ?",
            ['%' . $normalized . '%']
        );
    }

    private function normalizePlate(string $plate): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $plate));
    }

    private function getKnownVehiclePlates(): array
    {
        $transactions = WashTransaction::query()
            ->whereNotNull('vehicle_plate')
            ->whereRaw("TRIM(COALESCE(vehicle_plate, '')) <> ''")
            ->select('vehicle_plate', 'vehicle_brand')
            ->orderByDesc('created_at')
            ->get();

        $unique = [];
        foreach ($transactions as $transaction) {
            $raw = trim((string) $transaction->vehicle_plate);
            $normalized = $this->normalizePlate($raw);
            if ($normalized === '' || isset($unique[$normalized])) {
                continue;
            }
            $unique[$normalized] = [
                'plate' => $raw,
                'brand' => trim((string) ($transaction->vehicle_brand ?? '')),
            ];
        }

        return array_values($unique);
    }

    public function show(WashTransaction $transaction)
    {
        $transaction->loadMissing(['user', 'items', 'washCustomer', 'member.level']);

        return view('wash.transactions.show', compact('transaction'));
    }

    public function update(Request $request, WashTransaction $transaction)
    {
        if (! Auth::user()->hasPermission('wash.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'vehicle_plate' => 'nullable|string|max:50',
            'vehicle_brand' => 'nullable|string|max:100',
            'payment_method' => 'required|in:cash,qris,transfer,edc,kasbon,voucher',
            'cash_amount' => 'nullable|numeric|min:0',
            'kasbon_type' => 'nullable|in:employee,outsider',
            'kasbon_user_id' => 'nullable|integer|exists:users,id',
            'kasbon_name' => 'nullable|string|max:255',
            'status' => 'nullable|in:draft,lunas,posted',
            'notes' => 'nullable|string|max:1000',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => ['nullable','string','max:60','regex:/^(none|member_discount|reward_voucher|manual_bonus|instant_bonus(_\d+x)?)$/'],
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $notesUpdatedByUser = array_key_exists('notes', $validated);
        $oldNotes = (string) ($transaction->notes ?? '');
        $oldNotesLower = strtolower(trim($oldNotes));
        // Daftar prefix yang KRITIS (tidak boleh dihapus karena untuk deteksi isRedemptionTransaction / auto-redeem)
        $criticalPrefixes = ['auto_reward_voucher:', 'reward_voucher:', 'instant_bonus_', 'bonus_cuci_', 'voucher_free:'];
        $hasCriticalPrefixOld = false;
        $criticalPrefixOld = '';
        foreach ($criticalPrefixes as $pfx) {
            if (str_starts_with($oldNotesLower, $pfx)) {
                $hasCriticalPrefixOld = true;
                $criticalPrefixOld = $pfx;
                break;
            }
        }

        DB::transaction(function () use ($validated, $transaction, $notesUpdatedByUser, $hasCriticalPrefixOld, $criticalPrefixOld, $oldNotes) {
            $paymentMethod = strtolower((string) $validated['payment_method']);
            $cashAmount = $paymentMethod === 'cash' ? (float) ($validated['cash_amount'] ?? 0) : null;

            $itemPayload = collect($validated['items']);
            $transactionItems = $transaction->items()->whereIn('id', $itemPayload->pluck('id'))->get()->keyBy('id');
            if ($transactionItems->count() !== $itemPayload->count()) {
                abort(422, 'Data item transaksi tidak valid.');
            }

            $grossTotal = 0;
            foreach ($itemPayload as $item) {
                $line = $transactionItems->get((int) $item['id']);
                $qty = (int) $item['quantity'];
                $subtotal = ((float) $line->price) * $qty;
                $line->update([
                    'quantity' => $qty,
                    'subtotal' => $subtotal,
                ]);
                $grossTotal += $subtotal;
            }

            // Use user-specified discount_amount (if any) for corrections
            $userDiscount = (float) ($validated['discount_amount'] ?? 0);
            if ($userDiscount > 0) {
                $discountAmount = min($userDiscount, $grossTotal);
            } else {
                $discountAmount = min((float) ($transaction->discount_amount ?? 0), $grossTotal);
            }
            $finalTotal = max(0, $grossTotal - $discountAmount);
            $changeAmount = $paymentMethod === 'cash'
                ? max(0, $cashAmount - $finalTotal)
                : 0;

            $updateData = [
                'customer_name' => $validated['customer_name'] ?? null,
                'vehicle_plate' => $validated['vehicle_plate'] ?? null,
                'vehicle_brand' => $validated['vehicle_brand'] ?? null,
                'payment_method' => $paymentMethod,
                'cash_amount' => $cashAmount,
                'change_amount' => $changeAmount,
                'total_amount' => $finalTotal,
                'discount_amount' => $discountAmount,
            ];

            if (isset($validated['discount_type']) && $validated['discount_type'] !== '') {
                $updateData['discount_type'] = $validated['discount_type'] === 'none' ? null : $validated['discount_type'];
            }
            if ($notesUpdatedByUser) {
                $newNotes = (string) ($validated['notes'] ?? '');
                $newNotesLower = strtolower(trim($newNotes));
                // 🔥 PROTEKSI PREFIX KRITIS: Jika notes lama punya prefix kritis, tapi notes baru TIDAK punya prefix apapun
                // → user kemungkinan tidak sengaja menghapus → TETAPKAN prefix lama di awal.
                if ($hasCriticalPrefixOld && $newNotesLower !== '' && !str_starts_with($newNotesLower, $criticalPrefixOld)) {
                    // Jika user menambahkan notes lain, prefix lama dipertahankan + user notes dipisah |
                    if (str_starts_with($oldNotesLower, $criticalPrefixOld)) {
                        $oldPrefixPart = explode('|', $oldNotes, 2)[0] ?? $oldNotes;
                        $newNotes = trim($oldPrefixPart) . ' | ' . trim($newNotes);
                    }
                }
                // Jika user menghapus notes (empty string) tapi ada prefix kritis → tetap simpan prefix saja
                if ($hasCriticalPrefixOld && trim($newNotes) === '') {
                    $newNotes = explode('|', $oldNotes, 2)[0] ?? $oldNotes;
                }
                $updateData['notes'] = $newNotes;
            }

            if (isset($validated['status'])) {
                $updateData['status'] = $validated['status'];
            }

            if ($paymentMethod === 'kasbon') {
                $updateData['kasbon_type'] = $validated['kasbon_type'] ?? null;
                $updateData['kasbon_user_id'] = $validated['kasbon_user_id'] ?? null;
                $updateData['kasbon_name'] = $validated['kasbon_name'] ?? null;
                $updateData['kasbon_settled'] = false;
            } else {
                $updateData['kasbon_type'] = null;
                $updateData['kasbon_user_id'] = null;
                $updateData['kasbon_name'] = null;
                $updateData['kasbon_settled'] = false;
            }

            $transaction->update($updateData);

            $journals = Journal::where('source_type', 'wash_transaction')
                ->where('source_id', $transaction->id)
                ->with('entries')
                ->get();

            foreach ($journals as $journal) {
                foreach ($journal->entries as $entry) {
                    if ((float) $entry->debit > 0) {
                        $entry->update(['debit' => $finalTotal, 'credit' => 0]);
                    } elseif ((float) $entry->credit > 0) {
                        $entry->update(['debit' => 0, 'credit' => $finalTotal]);
                    }
                }
            }
        });

        // If status is now lunas/posted and not yet counted, count it
        // PENTING: Hanya count jika loyalty_counted_at BELUM ADA (mencegah double counting).
        // loyalty_counted_at sudah dicek di dalam incrementOnPaidTransaction, jadi di sini cukup panggil saja.
        // JIKA user mengubah plat nomor transaksi yang sudah di-count: harus manual rollback dulu.
        if (in_array($transaction->status, ['lunas', 'posted'])) {
            $loyaltyService = app(\App\Services\Wash\WashLoyaltyService::class);
            $loyaltyService->incrementOnPaidTransaction($transaction);
        }

        return redirect()
            ->route('wash.transactions.index', request()->query())
            ->with('success', __('Transaction updated successfully.'));
    }

    public function loyaltyRollback(Request $request, WashTransaction $transaction)
    {
        if (! Auth::user()->hasPermission('wash.manage')) {
            abort(403, 'Unauthorized action.');
        }
        $loyalty = app(WashLoyaltyService::class);
        $result = $loyalty->rollbackLastIncrement($transaction);

        return back()->with(
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message'] ?? 'Gagal rollback counter loyalty.'
        );
    }

    public function loyaltyManualVoucher(Request $request, WashTransaction $transaction)
    {
        if (! Auth::user()->hasPermission('wash.manage')) {
            abort(403, 'Unauthorized action.');
        }
        $validated = $request->validate([
            'reason' => 'nullable|string|max:200',
            'expires_days' => 'nullable|integer|min:1|max:3650',
            'reward_type' => 'nullable|string|in:free_wash,free_wash_car,free_wash_motor',
        ]);
        $loyalty = app(WashLoyaltyService::class);
        $transaction->loadMissing(['items.service']);
        $expires = isset($validated['expires_days']) ? (int) $validated['expires_days'] : 90;
        $voucher = $loyalty->issueManualVoucher(
            (string) $transaction->vehicle_plate,
            $validated['reason'] ?? null,
            $transaction,
            $validated['reward_type'] ?? null,
            $expires
        );

        return back()->with('success', 'Voucher manual berhasil dibuat: ' . $voucher->code . ' (berlaku ' . $expires . ' hari, tipe: ' . $loyalty->rewardTypeLabel($voucher->reward_type) . ')');
    }

    public function loyaltyRetroactive(Request $request, WashTransaction $transaction)
    {
        if (! Auth::user()->hasPermission('wash.manage')) {
            abort(403, 'Unauthorized action.');
        }
        $validated = $request->validate([
            'mode' => 'required|string|in:retro_voucher,retro_settle',
        ]);
        $loyalty = app(WashLoyaltyService::class);
        $transaction->loadMissing(['items']);
        $result = $loyalty->retroactivelyApplyAsBonus($transaction, $validated['mode']);

        return back()->with(
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message'] ?? 'Gagal retroactive bonus.'
        );
    }

    public function destroy(WashTransaction $transaction)
    {
        if (! Auth::user()->hasPermission('wash.manage')) {
            abort(403, 'Unauthorized action.');
        }

        DB::transaction(function () use ($transaction) {
            $journals = Journal::where('source_type', 'wash_transaction')
                ->where('source_id', $transaction->id)
                ->get();

            foreach ($journals as $journal) {
                $journal->entries()->delete();
                $journal->delete();
            }

            $transaction->items()->delete();
            $transaction->delete();
        });

        return back()->with('success', __('Transaction deleted successfully.'));
    }

    public function bulkDestroy(Request $request)
    {
        if (! Auth::user()->hasPermission('wash.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:wash_transactions,id',
        ]);

        $transactions = WashTransaction::with('items')
            ->whereIn('id', $validated['ids'])
            ->get();

        DB::transaction(function () use ($transactions) {
            foreach ($transactions as $transaction) {
                $journals = Journal::where('source_type', 'wash_transaction')
                    ->where('source_id', $transaction->id)
                    ->get();

                foreach ($journals as $journal) {
                    $journal->entries()->delete();
                    $journal->delete();
                }

                $transaction->items()->delete();
                $transaction->delete();
            }
        });

        return back()->with('success', __('Selected transactions deleted successfully.'));
    }

    public function exportPdf(Request $request)
    {
        $query = WashTransaction::with('user', 'items');

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                $request->start_date.' 00:00:00',
                $request->end_date.' 23:59:59',
            ]);
        }
        if ($request->filled('vehicle_plate')) {
            $this->applyVehiclePlateFilter($query, (string) $request->input('vehicle_plate'));
        }

        $transactions = $query->latest()->get();

        $pdf = Pdf::loadView('wash.transactions.pdf', compact('transactions'));

        return $pdf->download('wash_transactions.pdf');
    }

    public function exportExcel(Request $request)
    {
        $query = WashTransaction::with('user', 'items');

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                $request->start_date.' 00:00:00',
                $request->end_date.' 23:59:59',
            ]);
        }
        if ($request->filled('vehicle_plate')) {
            $this->applyVehiclePlateFilter($query, (string) $request->input('vehicle_plate'));
        }

        $transactions = $query->latest()->get();

        return response()->streamDownload(function () use ($transactions) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues(['Date', 'Transaction Number', 'Customer', 'Vehicle', 'Services', 'Total Amount', 'Payment Method']));

            foreach ($transactions as $trx) {
                $services = $trx->items->pluck('service_name')->implode(', ');
                $writer->addRow(Row::fromValues([
                    $trx->created_at->format('Y-m-d H:i'),
                    $trx->transaction_number,
                    $trx->user->name ?? 'Guest',
                    $trx->vehicle_type,
                    $services,
                    $trx->total_amount,
                    $trx->payment_method,
                ]));
            }

            $writer->close();
        }, 'wash_transactions.xlsx');
    }

    public function receipt(WashTransaction $transaction)
    {
        $transaction->loadMissing(['user', 'items', 'member.level']);
        [$washVisitCount, $washVisitsToNextBonus] = $this->calculateLoyaltyProgressUntilTransaction($transaction);

        return view('wash.transactions.receipt', compact('transaction', 'washVisitCount', 'washVisitsToNextBonus'));
    }

    public function whatsappReceipt(Request $request, WashTransaction $transaction)
    {
        $request->validate([
            'phone' => 'required|string',
            'receipt_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
        ]);
        $phone = $this->normalizePhone($request->input('phone'));
        $link = route('wash.transactions.receipt', $transaction);
        $date = $transaction->created_at ? $transaction->created_at->format('d-m-Y H:i') : now()->format('d-m-Y H:i');
        $items = $transaction->items()->get()->map(function ($it) {
            return [
                'nama_layanan' => $it->service_name,
                'harga' => number_format($it->price, 0, ',', '.'),
                'penyesuaian_hari_raya' => is_null($it->holiday_adjustment)
                    ? '-'
                    : (((float) $it->holiday_adjustment >= 0 ? '+' : '-').number_format(abs((float) $it->holiday_adjustment), 0, ',', '.')),
            ];
        })->toArray();
        $holidayAdjustmentTotal = (float) $transaction->items()
            ->selectRaw('COALESCE(SUM(COALESCE(holiday_adjustment, 0) * quantity), 0) as total')
            ->value('total');
        $holidayGreeting = abs($holidayAdjustmentTotal) > 0
            ? 'Selamat Hari Raya! Semoga berkah dan kebahagiaan selalu menyertai Anda.'
            : '';
        $subtotal = (float) $transaction->items()->sum('subtotal');
        $vars = [
            'nama_usaha' => config('app.name'),
            'alamat' => Setting::getValue('store_address', ''),
            'no_hp' => Setting::getValue('store_phone', ''),
            'invoice' => $transaction->transaction_number,
            'tanggal' => $date,
            'nama_customer' => $transaction->customer_name ?? '-',
            'jenis_kendaraan' => $transaction->vehicle_brand ?? '-',
            'plat_nomor' => $transaction->vehicle_plate ?? '-',
            'subtotal' => number_format($subtotal, 0, ',', '.'),
            'diskon' => number_format($transaction->discount_amount ?? 0, 0, ',', '.'),
            'penyesuaian_hari_raya_total' => number_format($holidayAdjustmentTotal, 0, ',', '.'),
            'penyesuaian_hari_raya_tanda' => $holidayAdjustmentTotal >= 0 ? '+' : '-',
            'total' => number_format($transaction->total_amount, 0, ',', '.'),
            'metode_bayar' => strtoupper($transaction->payment_method),
            'status' => 'LUNAS',
            'items' => $items,
            'receipt_url' => $link,
            'ucapan_hari_raya' => $holidayGreeting,
        ];
        $tpl = Setting::where('key', 'whatsapp_wash_receipt_template')->value('value')
            ?? "*STRUK LAYANAN CUCI KENDARAAN*\nNo: {{invoice}}\nTanggal: {{tanggal}}\n\n{{#each items}}• {{nama_layanan}} - Rp{{harga}}\n{{/each}}\n\nTotal Bayar: Rp{{total}}";
        $wa = app(WhatsAppService::class);
        $message = $wa->renderTemplate($tpl, $vars);
        if ($request->hasFile('receipt_image')) {
            $file = $request->file('receipt_image');
            $wa->sendMessageWithMedia(
                $phone,
                $message,
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName() ?: ('struk-wash-'.$transaction->transaction_number.'.png'),
                'receipt',
                null
            );
        } else {
            $wa->sendMessage($phone, $message, 'receipt', null);
        }

        return response()->json(['success' => true]);
    }

    private function resolveHolidayPricingSchedule(): array
    {
        $startRaw = trim((string) Setting::getValue('wash_holiday_pricing_start_date', ''));
        $endRaw = trim((string) Setting::getValue('wash_holiday_pricing_end_date', ''));
        $startDate = null;
        $endDate = null;

        try {
            if ($startRaw !== '') {
                $startDate = Carbon::createFromFormat('Y-m-d', $startRaw)->startOfDay();
            }
        } catch (\Throwable) {
            $startDate = null;
        }

        try {
            if ($endRaw !== '') {
                $endDate = Carbon::createFromFormat('Y-m-d', $endRaw)->endOfDay();
            }
        } catch (\Throwable) {
            $endDate = null;
        }

        $now = now();
        $active = $startDate && $endDate && $now->between($startDate, $endDate);

        return [
            'active' => $active,
            'start_date' => $startDate?->toDateString(),
            'end_date' => $endDate?->toDateString(),
        ];
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }



    private function calculateLoyaltyProgressUntilTransaction(WashTransaction $transaction): array
    {
        $loyalty = app(WashLoyaltyService::class);

        // 🔥 If THIS TRANSACTION is a redemption/bonus wash, do NOT count it as a visit!
        // Return special values: [null, null] to indicate "this is a bonus transaction itself"
        if ($loyalty->isRedemptionTransaction($transaction)) {
            return [null, null];
        }

        $plateRaw = (string) ($transaction->vehicle_plate ?? '');
        $plate = $loyalty->normalizePlate($plateRaw);
        if ($plate === '') {
            return [0, null];
        }

        $target = $loyalty->target();

        // Get all transactions for the same customer (by plate) up to this transaction
        $allTransactionsUntil = WashTransaction::query()
            ->with('items.service')
            ->whereIn('status', ['lunas', 'posted'])
            ->where('total_amount', '>', 0)
            ->where('created_at', '<=', $transaction->created_at)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        // Filter in PHP for ACCURATE results (avoids issues with missing services and plate formatting)
        $transactionsUntil = collect();
        foreach ($allTransactionsUntil as $t) {
            // 1. Check exact plate match using normalizePlate
            $tPlate = $loyalty->normalizePlate((string) ($t->vehicle_plate ?? ''));
            if ($tPlate !== $plate) {
                continue;
            }

            // 2. FIRST: EXCLUDE ANY REDEMPTIONS (free/bonus washes)
            if ($loyalty->isRedemptionTransaction($t)) {
                continue;
            }

            // 3. Check if transaction has any non-coffee items (use relationship, fall back to service_name)
            $hasNonCoffee = false;
            foreach ($t->items as $item) {
                $isCoffee = false;
                $service = $item->service;
                if ($service && strtolower((string) ($service->vehicle_type ?? '')) === 'coffee') {
                    $isCoffee = true;
                } else {
                    $serviceName = strtolower(trim((string) ($item->service_name ?? '')));
                    if (
                        $serviceName === 'kopi' ||
                        $serviceName === 'caffe' ||
                        $serviceName === 'warkop' ||
                        str_contains($serviceName, 'kopi') ||
                        str_contains($serviceName, 'caffe') ||
                        str_contains($serviceName, 'warkop')
                    ) {
                        $isCoffee = true;
                    }
                }
                if (!$isCoffee) {
                    $hasNonCoffee = true;
                    break;
                }
            }
            if (!$hasNonCoffee) {
                continue;
            }

            $transactionsUntil->push($t);
        }

        $totalCount = $transactionsUntil->count();
        $cycleCount = 0;
        $currentCycleVisit = 0; // 1-based cycle visit count (1..target)
        $lastTransactionWasBonus = false;

        foreach ($transactionsUntil as $index => $t) {
            $isLastTransaction = ($index === ($totalCount - 1));

            // SIMULATE LOGIC DI WashLoyaltyService::incrementOnPaidTransaction!
            $cycleCount++;
            $currentCycleVisit++;

            if ($cycleCount >= $target) {
                if ($isLastTransaction) {
                    // 🔥 THIS TRANSACTION (current one being viewed) IS THE ONE THAT EARNS BONUS!
                    // Do NOT reset yet! Show "ke-11, bonus tercapai"!
                    $lastTransactionWasBonus = true;
                    break;
                } else {
                    // This bonus was earned in a PAST transaction, reset normally
                    $cycleCount = 0;
                    $currentCycleVisit = 0; // reset after a full cycle / bonus issued
                }
            }
        }

        if ($lastTransactionWasBonus) {
            // Current transaction: exactly hits target → display "ke-target, bonus tercapai"
            return [
                $target,
                0
            ];
        }

        // Hitung remaining sesuai logic di WashLoyaltyService::progress()
        $progress = $cycleCount;
        $remaining = $target - $progress;
        if ($progress === 0) {
            $remaining = $target;
        }

        // Return: [cycle-based visit count (1..target, 0 if not started), remaining]
        return [
            $currentCycleVisit,
            $remaining
        ];
    }
}
