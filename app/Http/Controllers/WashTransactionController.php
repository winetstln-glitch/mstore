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
use App\Models\WashTransaction;
use App\Models\WashTransactionItem;
use App\Services\AccountingPoster;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'presentEmployees'
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
        $customer = WashCustomer::where('phone', $phone)->first();
        [$loyaltyType, $loyaltyValue] = $this->resolveLoyaltyIdentifier($vehiclePlate);
        $visitCount = 0;
        if ($loyaltyType && $loyaltyValue) {
            $visitCount = $this->buildLoyaltyQuery($loyaltyType, $loyaltyValue)->count();
        }
        $nextBonusIn = 10 - ($visitCount % 10);
        if ($nextBonusIn === 0) {
            $nextBonusIn = 10;
        }

        if ($customer || $visitCount > 0) {
            return response()->json([
                'found' => true,
                'name' => $customer->name ?? $customerName,
                'visit_count' => $visitCount,
                'free_wash_eligibility' => $loyaltyType === 'plate' ? (int) ($customer->free_wash_eligibility ?? 0) : 0,
                'next_bonus_in' => $nextBonusIn,
                'loyalty_basis' => $loyaltyType,
            ]);
        }

        return response()->json([
            'found' => false,
            'visit_count' => 0,
            'free_wash_eligibility' => 0,
            'next_bonus_in' => 10,
            'loyalty_basis' => $loyaltyType,
        ]);
    }

    public function store(Request $request)
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
            'kasbon_type' => 'nullable|string',
            'kasbon_user_id' => 'nullable|exists:users,id',
            'kasbon_name' => 'nullable|string',
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
            $discountType = null;
            [$loyaltyType, $loyaltyValue] = $this->resolveLoyaltyIdentifier(
                $vehiclePlateForStore
            );
            $hasLoyaltyPlate = $loyaltyType === 'plate' && ! empty($loyaltyValue);
            $visitCountBefore = 0;
            $isTenthVisit = false;
            if ($hasLoyaltyPlate) {
                $visitCountBefore = $this->buildLoyaltyQuery($loyaltyType, $loyaltyValue)->lockForUpdate()->count();
                $isTenthVisit = (($visitCountBefore + 1) % 10) === 0;
            }

            if ($hasLoyaltyPlate && $customer && $request->use_voucher && $customer->free_wash_eligibility > 0) {
                if (count($items) > 0) {
                    $discountAmount = $items[0]['price'];
                    $discountType = 'voucher';
                    $customer->decrement('free_wash_eligibility');
                }
            }

            if ($discountAmount <= 0 && $isTenthVisit && count($items) > 0) {
                $discountAmount = $items[0]['price'];
                $discountType = 'loyalty';
            }

            if ($customer) {
                $nextVisitCount = ((int) $customer->visit_count) + 1;
                $customer->increment('visit_count');
                if ($hasLoyaltyPlate && $nextVisitCount % 10 === 0 && $discountType !== 'loyalty') {
                    $customer->increment('free_wash_eligibility');
                }
            }

            $finalTotal = max(0, $total - $discountAmount);
            $discountNote = null;
            if ($discountType === 'loyalty') {
                $discountNote = 'bonus_cuci_10x';
            } elseif ($discountType === 'voucher') {
                $discountNote = 'voucher_free_wash';
            }

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
            $dateStr = $today->format('Ymd'); // Format tanggal: 20260515
            
            // Generate Queue Number (Reset daily for overall count)
            $lastQueue = WashTransaction::where('created_at', '>=', $today)
                ->where('created_at', '<', $tomorrow)
                ->max('queue_number');
            $queueNumber = ($lastQueue ?? 0) + 1;
            
            // Create transaction with a 100% unique transaction number using timestamp + random
            $uniqueSuffix = time() . '-' . rand(100000, 999999);
            $transactionNumber = $prefix . '-' . $dateStr . '-' . $uniqueSuffix;
            
            $transaction = WashTransaction::create([
                'user_id' => Auth::id(),
                'wash_customer_id' => $customer ? $customer->id : null,
                'transaction_number' => $transactionNumber,
                'queue_number' => $queueNumber,
                'total_amount' => $finalTotal,
                'discount_amount' => $discountAmount,
                'payment_method' => $request->payment_method,
                'cash_amount' => $request->cash_amount,
                'change_amount' => $request->cash_amount ? ($request->cash_amount - $finalTotal) : 0,
                'customer_name' => $request->customer_name ?? ($customer ? $customer->name : null),
                'vehicle_plate' => $vehiclePlateForStore,
                'vehicle_brand' => $request->vehicle_brand,
                'notes' => $discountNote,
                'status' => $request->payment_method === 'kasbon' ? 'pending' : 'lunas',
                'kasbon_type' => $request->payment_method === 'kasbon' ? $request->kasbon_type : null,
                'kasbon_user_id' => $request->payment_method === 'kasbon' && $request->kasbon_type === 'employee' ? $request->kasbon_user_id : null,
                'kasbon_name' => $request->payment_method === 'kasbon' && $request->kasbon_type === 'outsider' ? $request->kasbon_name : null,
                'kasbon_settled' => false,
            ]);

            foreach ($items as $item) {
                $transaction->items()->create($item);
            }

            // Update default cash balance only if payment is not kasbon
            if ($request->payment_method !== 'kasbon') {
                $cash = \App\Models\Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);
                $cash->balance = (float) $cash->balance + (float) $finalTotal;
                $cash->save();

                $cashCode = $request->payment_method === 'cash' ? '1001' : '1002';
                $cashAccId = Account::where('code', $cashCode)->value('id');
                $revAccId = Account::where('code', '4005')->value('id');
                if ($cashAccId && $revAccId && $finalTotal > 0) {
                    $poster = app(AccountingPoster::class);
                    $poster->post(
                        'WASH-'.$transaction->transaction_number,
                        now()->toDateString(),
                        'Wash POS',
                        [
                            ['account_id' => $cashAccId, 'debit' => $finalTotal, 'credit' => 0, 'unit' => 'MSTORE'],
                            ['account_id' => $revAccId, 'debit' => 0, 'credit' => $finalTotal, 'unit' => 'MSTORE'],
                        ],
                        null,
                        'wash_transaction',
                        $transaction->id
                    );
                }
            }

            DB::commit();

            $itemsList = collect($items)->map(fn($item) => "- {$item['service_name']} x{$item['quantity']}")->join("\n");
            $paymentMethodLabel = strtoupper($request->payment_method);
            $cashierName = Auth::user()?->name ?? 'Sistem';
            $vehiclePlate = $vehiclePlateForStore ?: '-';
            $customerName = $request->customer_name ?? ($customer ? $customer->name : 'Tamu');
            
            $message = "🧼 *TRANSAKSI WASH BARU*\n\n";
            $message .= "📋 *No. Antrian:* {$queueNumber}\n";
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
                'visit_count' => $visitCountBefore + 1,
                'discount_type' => $discountType,
                'message' => 'Transaction successful'.($discountAmount > 0 ? ' (Bonus Applied)' : ''),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function index(Request $request)
    {
        $query = WashTransaction::with(['user', 'items', 'washCustomer']);

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                $request->start_date.' 00:00:00',
                $request->end_date.' 23:59:59',
            ]);
        }
        if ($request->filled('vehicle_plate')) {
            $this->applyVehiclePlateFilter($query, (string) $request->input('vehicle_plate'));
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

    public function show(WashTransaction $transaction)
    {
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
            'payment_method' => 'required|in:cash,qris',
            'cash_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($validated, $transaction) {
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

            $discountAmount = min((float) ($transaction->discount_amount ?? 0), $grossTotal);
            $finalTotal = max(0, $grossTotal - $discountAmount);
            $changeAmount = $paymentMethod === 'cash'
                ? max(0, $cashAmount - $finalTotal)
                : 0;

            $transaction->update([
                'customer_name' => $validated['customer_name'] ?? null,
                'vehicle_plate' => $validated['vehicle_plate'] ?? null,
                'vehicle_brand' => $validated['vehicle_brand'] ?? null,
                'payment_method' => $paymentMethod,
                'cash_amount' => $cashAmount,
                'change_amount' => $changeAmount,
                'total_amount' => $finalTotal,
            ]);

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

        return redirect()
            ->route('wash.transactions.index', request()->query())
            ->with('success', __('Transaction updated successfully.'));
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
        $transaction->loadMissing('user', 'items');
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

    private function resolveLoyaltyIdentifier(?string $vehiclePlate): array
    {
        $plate = $this->normalizePlate((string) $vehiclePlate);
        if ($plate !== '') {
            return ['plate', $plate];
        }

        return [null, null];
    }

    private function buildLoyaltyQuery(string $type, string $value)
    {
        $query = WashTransaction::query();
        $this->applyVehiclePlateFilter($query, $value);

        return $query;
    }

    private function normalizePlate(string $plate): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $plate));
    }

    private function applyVehiclePlateFilter($query, string $plate): void
    {
        $normalizedPlate = $this->normalizePlate($plate);
        if ($normalizedPlate === '') {
            return;
        }
        $query->whereRaw(
            "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(vehicle_plate, ''), ' ', ''), '-', ''), '.', ''), '/', '')) = ?",
            [$normalizedPlate]
        );
    }

    private function getKnownVehiclePlates(): array
    {
        $plates = WashTransaction::query()
            ->whereNotNull('vehicle_plate')
            ->whereRaw("TRIM(COALESCE(vehicle_plate, '')) <> ''")
            ->orderByDesc('created_at')
            ->pluck('vehicle_plate')
            ->all();

        $unique = [];
        foreach ($plates as $plate) {
            $raw = trim((string) $plate);
            $normalized = $this->normalizePlate($raw);
            if ($normalized === '' || isset($unique[$normalized])) {
                continue;
            }
            $unique[$normalized] = $raw;
        }

        return array_values($unique);
    }

    private function calculateLoyaltyProgressUntilTransaction(WashTransaction $transaction): array
    {
        [$type, $value] = $this->resolveLoyaltyIdentifier(
            (string) ($transaction->vehicle_plate ?? '')
        );

        if (! $type || ! $value || ! $transaction->created_at) {
            return [0, null];
        }

        $query = $this->buildLoyaltyQuery($type, $value);
        $query->where(function ($q) use ($transaction) {
            $q->where('created_at', '<', $transaction->created_at)
                ->orWhere(function ($q2) use ($transaction) {
                    $q2->where('created_at', $transaction->created_at)
                        ->where('id', '<=', $transaction->id);
                });
        });

        $visitCount = (int) $query->count();
        $progressInCycle = $visitCount % 10;
        $visitsToNextBonus = $progressInCycle === 0 ? 0 : (10 - $progressInCycle);

        return [$visitCount, $visitsToNextBonus];
    }
}
