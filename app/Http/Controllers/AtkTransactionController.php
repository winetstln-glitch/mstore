<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AgentDeposit;
use App\Models\AtkProduct;
use App\Models\AtkTransaction;
use App\Models\AtkTransactionItem;
use App\Models\Cash;
use App\Models\Coordinator;
use App\Models\Customer;
use App\Models\Investor;
use App\Models\Journal;
use App\Models\Setting;
use App\Models\TechnicianAttendance;
use App\Models\TechnicianDailySchedule;
use App\Services\AccountingPoster;
use App\Services\Atk\AtkCashService;
use App\Services\WhatsAppService;
use App\DTO\CashImpactData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class AtkTransactionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:atk.view', only: ['dashboard']),
            new Middleware('permission:atk.pos', only: ['pos', 'store']),
            new Middleware('permission:atk.report', only: ['index', 'show', 'receipt', 'exportPdf', 'exportExcel', 'whatsappReceipt']),
            new Middleware('permission:atk.manage', only: ['destroy', 'bulkDestroy']),
        ];
    }

    public function dashboard()
    {
        $today = now()->format('Y-m-d');
        $month = now()->format('Y-m');
        $user = Auth::user();
        $todayAttendance = TechnicianAttendance::where('user_id', Auth::id())
            ->whereDate('clock_in', today())
            ->first();
        $roleUserIds = \App\Models\User::query()
            ->where('is_active', true)
            ->whereHas('role', function ($query) {
                $query->where('name', 'kasir-atk');
            })
            ->pluck('id');
        $presentCount = $roleUserIds->isEmpty()
            ? 0
            : TechnicianAttendance::query()
                ->whereDate('clock_in', today())
                ->whereIn('status', ['present', 'late'])
                ->whereIn('user_id', $roleUserIds)
                ->distinct('user_id')
                ->count('user_id');
        $attendanceOverview = [
            'role' => 'kasir-atk',
            'total' => $roleUserIds->count(),
            'present' => $presentCount,
            'not_present' => max($roleUserIds->count() - $presentCount, 0),
        ];
        $shiftSchedule = TechnicianDailySchedule::query()
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->first();

        $dailySales = AtkTransaction::whereDate('created_at', $today)
            ->whereNull('reversed_at')
            ->where('status', '!=', 'reversed')
            ->sum('total_amount');
        $monthlySales = AtkTransaction::where('created_at', 'like', "$month%")
            ->whereNull('reversed_at')
            ->where('status', '!=', 'reversed')
            ->sum('total_amount');
        $transactionCount = AtkTransaction::whereDate('created_at', $today)
            ->whereNull('reversed_at')
            ->where('status', '!=', 'reversed')
            ->count();

        // Get Kas Utama directly from database
        $cash = Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);

        // Get float accounts
        $floatAccounts = \App\Models\AtkFloatAccount::where('status', 'active')->get();

        // Get current owner fund balance
        $currentOwnerBalance = \App\Models\OwnerFund::latest()->first()?->total_balance ?? 0;

        // Get today's expenses
        $todayExpenses = \App\Models\Transaction::where('type', 'expense')
            ->where('reference_number', 'like', 'ATK-EXP-%')
            ->whereDate('created_at', today())
            ->sum('amount');

        // Get today's top up
        $todayTopUp = AtkTransaction::whereDate('created_at', today())
            ->whereHas('items', function ($query) {
                $query->where('item_type', 'top_up');
            })
            ->withSum('items', 'nominal_transaksi')
            ->get()
            ->sum('items_sum_nominal_transaksi');

        // Get today's withdrawal (cash out)
        $todayWithdrawal = AtkTransaction::whereDate('created_at', today())
            ->whereHas('items', function ($query) {
                $query->where('item_type', 'cash_out');
            })
            ->withSum('items', 'nominal_transaksi')
            ->get()
            ->sum('items_sum_nominal_transaksi');

        // Get today's PPOB
        $todayPpob = AtkTransaction::whereDate('created_at', today())
            ->whereHas('items', function ($query) {
                $query->where('item_type', 'ppob');
            })
            ->withSum('items', 'nominal_transaksi')
            ->get()
            ->sum('items_sum_nominal_transaksi');

        // Get today's Transfer
        $todayTransfer = AtkTransaction::whereDate('created_at', today())
            ->whereHas('items', function ($query) {
                $query->where('item_type', 'bank');
            })
            ->withSum('items', 'nominal_transaksi')
            ->get()
            ->sum('items_sum_nominal_transaksi');

        // Calculate total fees
        $todayFees = AtkTransactionItem::whereHas('atkTransaction', function ($q) use ($today) {
            $q->whereDate('created_at', $today);
        })->sum('fee');
        
        $monthlyFees = AtkTransactionItem::whereHas('atkTransaction', function ($q) use ($month) {
            $q->where('created_at', 'like', "$month%");
        })->sum('fee');
        
        $todayFeesBank = AtkTransactionItem::where('item_type', 'bank')
            ->whereHas('atkTransaction', function ($q) use ($today) {
                $q->whereDate('created_at', $today);
            })->sum('fee');
            
        $todayFeesPpob = AtkTransactionItem::where('item_type', 'ppob')
            ->whereHas('atkTransaction', function ($q) use ($today) {
                $q->whereDate('created_at', $today);
            })->sum('fee');
            
        $todayFeesTopUp = AtkTransactionItem::where('item_type', 'top_up')
            ->whereHas('atkTransaction', function ($q) use ($today) {
                $q->whereDate('created_at', $today);
            })->sum('fee');
            
        $todayFeesCashOut = AtkTransactionItem::where('item_type', 'cash_out')
            ->whereHas('atkTransaction', function ($q) use ($today) {
                $q->whereDate('created_at', $today);
            })->sum('fee');

        // Top selling products
        $topProducts = AtkTransactionItem::select('product_name', DB::raw('sum(quantity) as total_qty'))
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return view('atk.dashboard', compact(
            'dailySales',
            'monthlySales',
            'transactionCount',
            'topProducts',
            'todayAttendance',
            'attendanceOverview',
            'shiftSchedule',
            'cash',
            'floatAccounts',
            'currentOwnerBalance',
            'todayExpenses',
            'todayTopUp',
            'todayWithdrawal',
            'todayPpob',
            'todayTransfer',
            'todayFees',
            'monthlyFees',
            'todayFeesBank',
            'todayFeesPpob',
            'todayFeesTopUp',
            'todayFeesCashOut'
        ));
    }

    public function pos()
    {
        $products = AtkProduct::where('category', 'ATK')->where('stock', '>', 0)->get();
        $services = AtkProduct::where('category', 'JASA POTOCOPY')->get();
        $bankServices = AtkProduct::where('category', 'JASA TRANSFER BANK')->get();
        $customers = Customer::orderBy('name')->get(['id', 'name', 'phone']);
        $coordinators = Coordinator::orderBy('name')->get(['id', 'name']);
        $investors = Investor::orderBy('name')->get(['id', 'name', 'coordinator_id']);
        $floatAccounts = \App\Models\AtkFloatAccount::where('status', 'active')->get();
        $cash = Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);

        // Get fee settings
        $feeSettings = [
            'bank' => [
                'percent' => (float) Setting::getValue('atk_fee_bank_percent', 0),
                'fixed' => (float) Setting::getValue('atk_fee_bank_fixed', 0),
            ],
            'cash_out' => [
                'percent' => (float) Setting::getValue('atk_fee_cashout_percent', 0),
                'fixed' => (float) Setting::getValue('atk_fee_cashout_fixed', 0),
            ],
            'top_up' => [
                'percent' => (float) Setting::getValue('atk_fee_topup_percent', 0),
                'fixed' => (float) Setting::getValue('atk_fee_topup_fixed', 0),
            ],
            'ppob' => [
                'percent' => (float) Setting::getValue('atk_fee_ppob_percent', 0),
                'fixed' => (float) Setting::getValue('atk_fee_ppob_fixed', 0),
            ],
        ];

        // Get fee profiles
        $feeProfiles = \App\Models\FeeProfile::where('module', 'atk')->where('is_active', true)->with('tiers')->get();

        return view('atk.pos', compact('products', 'services', 'bankServices', 'customers', 'coordinators', 'investors', 'floatAccounts', 'cash', 'feeSettings', 'feeProfiles'));
    }

    public function store(Request $request, \App\Services\FeeCalculationService $feeService, \App\Services\OutboxEventService $outboxEventService, AtkCashService $cashService)
    {
        \Illuminate\Support\Facades\Log::info('ATK Transaction Store Request:', $request->all());
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'nullable',
            'items.*.type' => 'nullable|string|in:product,service,manual,bank,customer_payment,cash_out,top_up,ppob',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.product_name' => 'nullable|string|max:255',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.nominal_transaksi' => 'nullable|numeric|min:0',
            'items.*.fee' => 'nullable|numeric|min:0',
            'items.*.calculated_fee' => 'nullable|numeric|min:0',
            'items.*.customer_name' => 'nullable|string|max:255',
            'items.*.float_account_id' => 'nullable|exists:atk_float_accounts,id',
            'items.*.fee_type' => 'nullable|string|in:outside,inside',
            'transaction_category' => 'nullable|string|in:penjualan_atk,pembayaran_pelanggan',
            'payment_method' => 'required|string',
            'cash_amount' => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

        $total = 0;
        $items = [];
        $feeLogData = [];

        $sumBankNominal = 0;
        $sumFee = 0;
        $sumRevenueSales = 0;
        $sumPPOBTopUpNominal = 0;
        $sumCustomerPaymentNominal = 0;
        $hpp = 0;
        $containsService = false;
        $totalCashIn = 0;
        $totalCashOut = 0;
        $totalFloatIn = 0;
        $totalFloatOut = 0;
        $cashOutFloatTransactions = [];
        $topUpFloatTransactions = [];
        $ppobFloatTransactions = [];
        $bankFloatTransactions = [];
        $paymentMethod = $request->payment_method;
        foreach ($request->items as $itemData) {
            \Illuminate\Support\Facades\Log::info('Item data:', $itemData);
            $itemType = $itemData['type'] ?? 'product';
            \Illuminate\Support\Facades\Log::info('Item type:', ['type' => $itemType]);
                if ($itemType === 'customer_payment') {
                    $nominal = (float) ($itemData['nominal_transaksi'] ?? 0);
                    if ($nominal <= 0) {
                        throw new \Exception('Nominal pembayaran pelanggan wajib diisi.');
                    }
                    $customerName = trim((string) ($itemData['customer_name'] ?? 'Pelanggan'));
                    $subtotal = $nominal;
                    $sumCustomerPaymentNominal += $subtotal;
                    // JANGAN tambah ke sumRevenueSales! Pembayaran pelanggan bukan pendapatan
                    if ($paymentMethod === 'cash') {
                        $totalCashIn += $subtotal;
                    }
                    $items[] = [
                        'product_id' => null,
                        'product_name' => 'Pembayaran Pelanggan - '.$customerName,
                        'price' => $subtotal,
                        'quantity' => 1,
                        'subtotal' => $subtotal,
                        'nominal_transaksi' => $subtotal,
                        'fee' => null,
                        'item_type' => 'customer_payment',
                    ];

                    continue;
                }

                if ($itemType === 'cash_out') {
                    $nominal = (float) ($itemData['nominal_transaksi'] ?? 0);
                    $fee = (float) ($itemData['fee'] ?? 0);
                    $feeType = $itemData['fee_type'] ?? 'outside';
                    $calculatedFee = (float) ($itemData['calculated_fee'] ?? $fee);
                    $floatAccountId = $itemData['float_account_id'] ?? null;
                    if ($nominal <= 0) {
                        throw new \Exception('Nominal cash out wajib diisi.');
                    }
                    if (!$floatAccountId) {
                        throw new \Exception('Akun float untuk cash out wajib dipilih.');
                    }
                    
                    if ($feeType === 'inside') {
                        // Fee inside: fee is deducted from nominal, so float gets nominal - fee
                        $floatAmount = $nominal - $fee;
                        $totalFloatIn += $floatAmount;
                        $totalCashOut += $nominal;
                        $total += $nominal;
                        $sumFee += $fee;
                        
                        $items[] = [
                            'product_id' => null,
                            'product_name' => 'Cash Out',
                            'price' => $nominal,
                            'quantity' => 1,
                            'subtotal' => $nominal,
                            'nominal_transaksi' => $nominal,
                            'fee' => $fee,
                            'fee_type' => $feeType,
                            'item_type' => 'cash_out',
                            'atk_float_account_id' => $floatAccountId,
                        ];
                        
                        $cashOutFloatTransactions[] = [
                            'float_account_id' => $floatAccountId,
                            'amount' => $floatAmount,
                            'nominal' => $nominal,
                            'fee' => $fee
                        ];
                    } else {
                        // Fee outside (default): nominal goes to float, fee is separate cash in
                        $totalFloatIn += $nominal;
                        $totalCashIn += $fee;
                        $totalCashOut += $nominal;
                        $total += $nominal + $fee;
                        $sumFee += $fee;
                        
                        $items[] = [
                            'product_id' => null,
                            'product_name' => 'Cash Out',
                            'price' => $nominal + $fee,
                            'quantity' => 1,
                            'subtotal' => $nominal + $fee,
                            'nominal_transaksi' => $nominal,
                            'fee' => $fee,
                            'fee_type' => $feeType,
                            'item_type' => 'cash_out',
                            'atk_float_account_id' => $floatAccountId,
                        ];
                        
                        $cashOutFloatTransactions[] = [
                            'float_account_id' => $floatAccountId,
                            'amount' => $nominal,
                            'nominal' => $nominal,
                            'fee' => $fee
                        ];
                    }
                    
                    $feeLogData[] = [
                        'transaction_type' => 'cash_out',
                        'nominal' => $nominal,
                        'calculated_fee' => $calculatedFee,
                        'manual_fee' => $calculatedFee !== $fee ? $fee : null,
                        'final_fee' => $fee,
                    ];
                    continue;
                }

                if ($itemType === 'top_up') {
                    $nominal = (float) ($itemData['nominal_transaksi'] ?? 0);
                    $fee = (float) ($itemData['fee'] ?? 0);
                    $calculatedFee = (float) ($itemData['calculated_fee'] ?? $fee);
                    $floatAccountId = $itemData['float_account_id'] ?? null;
                    if ($nominal <= 0) {
                        throw new \Exception('Nominal top up wajib diisi.');
                    }
                    if (!$floatAccountId) {
                        throw new \Exception('Akun float untuk top up wajib dipilih.');
                    }
                    if ($nominal < 1000) {
                        throw new \Exception('Nominal top up minimal Rp 1.000.');
                    }
                    if ($nominal > 100000000) {
                        throw new \Exception('Nominal top up maksimal Rp 100.000.000.');
                    }
                    $totalFloatOut += $nominal;
                    $total += $nominal + $fee;
                    $sumFee += $fee;
                    $sumPPOBTopUpNominal += $nominal;
                    if ($paymentMethod === 'cash') {
                        $totalCashIn += $nominal + $fee;
                    }
                    $items[] = [
                        'product_id' => null,
                        'product_name' => 'Top Up',
                        'price' => $nominal + $fee,
                        'quantity' => 1,
                        'subtotal' => $nominal + $fee,
                        'nominal_transaksi' => $nominal,
                        'fee' => $fee,
                        'item_type' => 'top_up',
                        'atk_float_account_id' => $floatAccountId,
                    ];
                    $feeLogData[] = [
                        'transaction_type' => 'top_up',
                        'nominal' => $nominal,
                        'calculated_fee' => $calculatedFee,
                        'manual_fee' => $calculatedFee !== $fee ? $fee : null,
                        'final_fee' => $fee,
                    ];
                    $topUpFloatTransactions[] = [
                        'float_account_id' => $floatAccountId,
                        'amount' => $nominal,
                        'nominal' => $nominal,
                        'fee' => $fee
                    ];
                    continue;
                }

                if ($itemType === 'ppob') {
                    $nominal = (float) ($itemData['nominal_transaksi'] ?? 0);
                    $fee = (float) ($itemData['fee'] ?? 0);
                    $calculatedFee = (float) ($itemData['calculated_fee'] ?? $fee);
                    $floatAccountId = $itemData['float_account_id'] ?? null;
                    if ($nominal <= 0) {
                        throw new \Exception('Nominal PPOB wajib diisi.');
                    }
                    if (!$floatAccountId) {
                        throw new \Exception('Akun float untuk PPOB wajib dipilih.');
                    }
                    if ($nominal < 1000) {
                        throw new \Exception('Nominal PPOB minimal Rp 1.000.');
                    }
                    if ($nominal > 100000000) {
                        throw new \Exception('Nominal PPOB maksimal Rp 100.000.000.');
                    }
                    $totalFloatOut += $nominal;
                    $total += $nominal + $fee;
                    $sumFee += $fee;
                    $sumPPOBTopUpNominal += $nominal;
                    if ($paymentMethod === 'cash') {
                        $totalCashIn += $nominal + $fee;
                    }
                    $items[] = [
                        'product_id' => null,
                        'product_name' => 'PPOB',
                        'price' => $nominal + $fee,
                        'quantity' => 1,
                        'subtotal' => $nominal + $fee,
                        'nominal_transaksi' => $nominal,
                        'fee' => $fee,
                        'item_type' => 'ppob',
                        'atk_float_account_id' => $floatAccountId,
                    ];
                    $feeLogData[] = [
                        'transaction_type' => 'ppob',
                        'nominal' => $nominal,
                        'calculated_fee' => $calculatedFee,
                        'manual_fee' => $calculatedFee !== $fee ? $fee : null,
                        'final_fee' => $fee,
                    ];
                    $ppobFloatTransactions[] = [
                        'float_account_id' => $floatAccountId,
                        'amount' => $nominal,
                        'nominal' => $nominal,
                        'fee' => $fee
                    ];
                    continue;
                }

                if ($itemType === 'bank') {
                    $nominal = (float) ($itemData['nominal_transaksi'] ?? 0);
                    $fee = (float) ($itemData['fee'] ?? 0);
                    $calculatedFee = (float) ($itemData['calculated_fee'] ?? $fee);
                    $floatAccountId = $itemData['float_account_id'] ?? null;
                    if ($nominal <= 0) {
                        throw new \Exception('Nominal transfer bank wajib diisi.');
                    }
                    if (!$floatAccountId) {
                        throw new \Exception('Akun float untuk transfer bank wajib dipilih.');
                    }
                    if ($nominal < 1000) {
                        throw new \Exception('Nominal transfer bank minimal Rp 1.000.');
                    }
                    if ($nominal > 100000000) {
                        throw new \Exception('Nominal transfer bank maksimal Rp 100.000.000.');
                    }
                    $totalFloatOut += $nominal;
                    $total += $nominal + $fee;
                    $sumFee += $fee;
                    $sumBankNominal += $nominal;
                    if ($paymentMethod === 'cash') {
                        $totalCashIn += $nominal + $fee;
                    }
                    $items[] = [
                        'product_id' => null,
                        'product_name' => 'Transfer Bank',
                        'price' => $nominal + $fee,
                        'quantity' => 1,
                        'subtotal' => $nominal + $fee,
                        'nominal_transaksi' => $nominal,
                        'fee' => $fee,
                        'item_type' => 'bank',
                        'atk_float_account_id' => $floatAccountId,
                    ];
                    $feeLogData[] = [
                        'transaction_type' => 'bank',
                        'nominal' => $nominal,
                        'calculated_fee' => $calculatedFee,
                        'manual_fee' => $calculatedFee !== $fee ? $fee : null,
                        'final_fee' => $fee,
                    ];
                    $bankFloatTransactions[] = [
                        'float_account_id' => $floatAccountId,
                        'amount' => $nominal,
                        'nominal' => $nominal,
                        'fee' => $fee
                    ];
                    continue;
                }

                if ($itemType === 'manual') {
                    $productName = trim((string) ($itemData['product_name'] ?? ''));
                    $price = (float) ($itemData['price'] ?? 0);
                    $quantity = (int) ($itemData['quantity'] ?? 1);
                    if ($productName === '') {
                        throw new \Exception('Nama item manual wajib diisi.');
                    }
                    if ($price <= 0) {
                        throw new \Exception('Harga item manual wajib diisi.');
                    }
                    if ($quantity <= 0) {
                        throw new \Exception('Qty item manual wajib diisi.');
                    }
                    $subtotal = $price * $quantity;
                    $total += $subtotal;
                    $sumRevenueSales += $subtotal;
                    if ($paymentMethod === 'cash') {
                        $totalCashIn += $subtotal;
                    }
                    $items[] = [
                        'product_id' => null,
                        'product_name' => $productName,
                        'price' => $price,
                        'quantity' => $quantity,
                        'subtotal' => $subtotal,
                        'nominal_transaksi' => null,
                        'fee' => null,
                        'item_type' => 'manual',
                    ];
                    continue;
                }

                $product = AtkProduct::lockForUpdate()->find($itemData['id']);
                if (! $product) {
                    throw new \Exception('Produk tidak ditemukan.');
                }

                $isService = strtoupper($product->category ?? '') === 'JASA POTOCOPY';
                $isBank = strtoupper($product->category ?? '') === 'JASA TRANSFER BANK';
                if ($isService) {
                    $containsService = true;
                }
                if (! $isService && ! $isBank) {
                    if ($product->stock < $itemData['quantity']) {
                        throw new \Exception("Stock for {$product->name} is insufficient.");
                    }
                }

                if ($isBank) {
                    $nominal = (float) ($itemData['nominal_transaksi'] ?? 0);
                    $fee = (float) ($itemData['fee'] ?? 0);
                    $calculatedFee = (float) ($itemData['calculated_fee'] ?? $fee);
                    $sumBankNominal += $nominal;
                    $sumFee += $fee;
                    $subtotal = $nominal + $fee;
                    $total += $nominal + $fee;
                    if ($paymentMethod === 'cash') {
                        $totalCashIn += $nominal + $fee;
                    }
                    $price = $nominal + $fee;
                    $feeLogData[] = [
                        'transaction_type' => 'bank',
                        'nominal' => $nominal,
                        'calculated_fee' => $calculatedFee,
                        'manual_fee' => $calculatedFee !== $fee ? $fee : null,
                        'final_fee' => $fee,
                    ];
                } else {
                    $price = $product->price;
                    $subtotal = $price * $itemData['quantity'];
                    $total += $subtotal;
                    $sumRevenueSales += $subtotal;
                    if ($paymentMethod === 'cash') {
                        $totalCashIn += $subtotal;
                    }
                }

                if (! $isService && ! $isBank) {
                    $product->decrement('stock', $itemData['quantity']);
                    if (! $isBank) {
                        $hpp += ((float) $product->cost_price) * (int) $itemData['quantity'];
                    }
                }

                $items[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $price,
                    'quantity' => $itemData['quantity'],
                    'subtotal' => $subtotal,
                    'nominal_transaksi' => $isBank ? $nominal : null,
                    'fee' => $isBank ? $fee : null,
                    'item_type' => $isBank ? 'bank' : ($isService ? 'service' : 'product'),
                ];
            }

            $transactionCategory = $request->input('transaction_category', 'penjualan_atk');
            if ($request->payment_method === 'hutang' && $containsService && empty($request->coordinator_id)) {
                throw new \Exception('Pilih pengurus untuk transaksi hutang jasa potocopy.');
            }
            if ($transactionCategory === 'pembayaran_pelanggan' && empty($request->coordinator_id)) {
                throw new \Exception('Pilih pengurus untuk transaksi pembayaran pelanggan.');
            }

            $lastQueue = AtkTransaction::whereDate('created_at', today())->max('queue_number');
            $queueNumber = $lastQueue ? $lastQueue + 1 : 1;

            // Hitung total cash_amount khusus untuk transaksi yang memiliki cash_out with fee_type outside
            $hasCashOut = $request->items && collect($request->items)->contains(fn($item) => ($item['type'] ?? '') === 'cash_out');
            $hasCashOutOutside = $hasCashOut && collect($request->items)->contains(fn($item) => ($item['type'] ?? '') === 'cash_out' && ($item['fee_type'] ?? 'outside') === 'outside');
            $totalFeeForCashOut = $hasCashOut 
                ? collect($request->items)
                    ->filter(fn($item) => ($item['type'] ?? '') === 'cash_out' && ($item['fee_type'] ?? 'outside') === 'outside')
                    ->sum('fee') 
                : 0;

            $payload = [
                'user_id' => Auth::id(),
                'queue_number' => $queueNumber,
                'transaction_number' => 'TRX-'.time(),
                'invoice_number' => 'INV-'.time(), // Added to satisfy legacy constraint
                'total_amount' => $total,
                'payment_method' => $request->payment_method,
                'cash_amount' => $hasCashOutOutside ? $totalFeeForCashOut : $request->cash_amount,
                'change_amount' => $hasCashOutOutside ? 0 : ($request->cash_amount ? ($request->cash_amount - $total) : 0),
                'coordinator_id' => ($request->payment_method === 'hutang' || $transactionCategory === 'pembayaran_pelanggan') ? ($request->coordinator_id ?? null) : null,
                'status' => 'posted', // Set to posted immediately for POS
                'posted_at' => now(),
            ];
            if (Schema::hasColumn('atk_transactions', 'transaction_category')) {
                $payload['transaction_category'] = $transactionCategory;
            }

            $transaction = AtkTransaction::create($payload);

            foreach ($items as $item) {
                $transaction->items()->create($item);
            }
            
            // Log fees
            foreach ($feeLogData as $feeData) {
                $feeService->logFee(array_merge($feeData, [
                    'transaction_id' => $transaction->id,
                    'module' => 'atk',
                    'user_id' => Auth::id(),
                ]));
            }

            if ($request->payment_method !== 'hutang') {
                // Lock Kas Utama FIRST to prevent race conditions
                $cash = $cashService->getLockedMainCash();
                $netCashChange = $totalCashIn - $totalCashOut;

                // Validate balance BEFORE any changes
                $cashService->validateBalance($cash, $netCashChange);

                foreach ($cashOutFloatTransactions as $coFt) {
                    $floatAccount = \App\Models\AtkFloatAccount::lockForUpdate()->findOrFail($coFt['float_account_id']);
                    $balanceBefore = $floatAccount->current_balance;
                    $floatAccount->current_balance += $coFt['amount'];
                    $floatAccount->save();

                    $floatTrans = \App\Models\AtkFloatTransaction::create([
                        'atk_float_account_id' => $floatAccount->id,
                        'transaction_type' => 'deposit',
                        'amount' => $coFt['amount'],
                        'balance_before' => $balanceBefore,
                        'balance_after' => $floatAccount->current_balance,
                        'description' => 'Cash Out - ' . $transaction->transaction_number,
                        'reference_type' => 'atk_transaction',
                        'reference_id' => $transaction->id,
                        'created_by' => Auth::id(),
                    ]);
                    // Journal handled by AtkTransaction::syncAccountingJournal()
                }

                foreach ($topUpFloatTransactions as $tuFt) {
                    $floatAccount = \App\Models\AtkFloatAccount::lockForUpdate()->findOrFail($tuFt['float_account_id']);
                    if ($floatAccount->current_balance < $tuFt['amount']) {
                        $requiredAmount = number_format($tuFt['amount'], 0, ',', '.');
                        $availableAmount = number_format($floatAccount->current_balance, 0, ',', '.');
                        $accountName = $floatAccount->name ?? $floatAccount->id;
                        throw new \Exception("Saldo akun float '{$accountName}' tidak cukup untuk top up! Dibutuhkan Rp {$requiredAmount}, tersedia Rp {$availableAmount}.");
                    }
                    $balanceBefore = $floatAccount->current_balance;
                    $floatAccount->current_balance -= $tuFt['amount'];
                    $floatAccount->save();

                    $floatTrans = \App\Models\AtkFloatTransaction::create([
                        'atk_float_account_id' => $floatAccount->id,
                        'transaction_type' => 'topup',
                        'amount' => $tuFt['amount'],
                        'balance_before' => $balanceBefore,
                        'balance_after' => $floatAccount->current_balance,
                        'description' => 'Top Up - ' . $transaction->transaction_number,
                        'reference_type' => 'atk_transaction',
                        'reference_id' => $transaction->id,
                        'created_by' => Auth::id(),
                    ]);
                    // Journal handled by AtkTransaction::syncAccountingJournal()
                }

                foreach ($ppobFloatTransactions as $ppFt) {
                    $floatAccount = \App\Models\AtkFloatAccount::lockForUpdate()->findOrFail($ppFt['float_account_id']);
                    if ($floatAccount->current_balance < $ppFt['amount']) {
                        $requiredAmount = number_format($ppFt['amount'], 0, ',', '.');
                        $availableAmount = number_format($floatAccount->current_balance, 0, ',', '.');
                        $accountName = $floatAccount->name ?? $floatAccount->id;
                        throw new \Exception("Saldo akun float '{$accountName}' tidak cukup untuk PPOB! Dibutuhkan Rp {$requiredAmount}, tersedia Rp {$availableAmount}.");
                    }
                    $balanceBefore = $floatAccount->current_balance;
                    $floatAccount->current_balance -= $ppFt['amount'];
                    $floatAccount->save();

                    $floatTrans = \App\Models\AtkFloatTransaction::create([
                        'atk_float_account_id' => $floatAccount->id,
                        'transaction_type' => 'ppob',
                        'amount' => $ppFt['amount'],
                        'balance_before' => $balanceBefore,
                        'balance_after' => $floatAccount->current_balance,
                        'description' => 'PPOB - ' . $transaction->transaction_number,
                        'reference_type' => 'atk_transaction',
                        'reference_id' => $transaction->id,
                        'created_by' => Auth::id(),
                    ]);
                    // Journal handled by AtkTransaction::syncAccountingJournal()
                }

                foreach ($bankFloatTransactions as $bnkFt) {
                    $floatAccount = \App\Models\AtkFloatAccount::lockForUpdate()->findOrFail($bnkFt['float_account_id']);
                    if ($floatAccount->current_balance < $bnkFt['amount']) {
                        $requiredAmount = number_format($bnkFt['amount'], 0, ',', '.');
                        $availableAmount = number_format($floatAccount->current_balance, 0, ',', '.');
                        $accountName = $floatAccount->name ?? $floatAccount->id;
                        throw new \Exception("Saldo akun float '{$accountName}' tidak cukup untuk transfer bank! Dibutuhkan Rp {$requiredAmount}, tersedia Rp {$availableAmount}.");
                    }
                    $balanceBefore = $floatAccount->current_balance;
                    $floatAccount->current_balance -= $bnkFt['amount'];
                    $floatAccount->save();

                    $floatTrans = \App\Models\AtkFloatTransaction::create([
                        'atk_float_account_id' => $floatAccount->id,
                        'transaction_type' => 'transfer',
                        'amount' => $bnkFt['amount'],
                        'balance_before' => $balanceBefore,
                        'balance_after' => $floatAccount->current_balance,
                        'description' => 'Transfer Bank - ' . $transaction->transaction_number,
                        'reference_type' => 'atk_transaction',
                        'reference_id' => $transaction->id,
                        'created_by' => Auth::id(),
                    ]);
                    // Journal handled by AtkTransaction::syncAccountingJournal()
                }

                $balanceBefore = $cash->balance;
                $balanceAfter = $balanceBefore + $netCashChange;

                if ($totalCashIn > 0) {
                    $cashService->recordMovement($cash, [
                        'atk_transaction_id' => $transaction->id,
                        'movement_type' => 'sale',
                        'direction' => 'in',
                        'amount' => $totalCashIn,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceBefore + $totalCashIn,
                        'idempotency_key' => "atk-cash-sale:{$transaction->id}",
                        'description' => 'Transaksi POS - Masuk - ' . $transaction->transaction_number,
                        'created_by' => Auth::id(),
                    ]);
                }
                if ($totalCashOut > 0) {
                    $balanceBeforeForOut = $totalCashIn > 0 ? $balanceBefore + $totalCashIn : $balanceBefore;
                    $cashService->recordMovement($cash, [
                        'atk_transaction_id' => $transaction->id,
                        'movement_type' => 'cash_out',
                        'direction' => 'out',
                        'amount' => $totalCashOut,
                        'balance_before' => $balanceBeforeForOut,
                        'balance_after' => $balanceAfter,
                        'idempotency_key' => "atk-cash-out:{$transaction->id}",
                        'description' => 'Transaksi POS - Keluar - ' . $transaction->transaction_number,
                        'created_by' => Auth::id(),
                    ]);
                } else if ($totalCashIn > 0) {
                    // If only cash in, apply projection here
                    $cashService->applyProjection($cash, $balanceAfter);
                }
            }

            // Reduce Agent Deposit by nominal transfer sum
            if ($sumBankNominal > 0) {
                $deposit = AgentDeposit::firstOrCreate(['name' => 'Deposit Agen Bank'], ['balance' => 0]);
                $deposit->balance = (float) $deposit->balance - (float) $sumBankNominal;
                $deposit->save();
            }

            // 🔥 Use Outbox Pattern (inside transaction!) for Atomicity!
            $outboxEvent = $outboxEventService->createOutboxEvent(
                aggregateType: \App\Models\AtkTransaction::class,
                aggregateId: (string) $transaction->id,
                eventType: 'AtkTransactionCreated',
                payload: [
                    'model_class' => \App\Models\AtkTransaction::class,
                    'model_id' => $transaction->id,
                ]
            );

            DB::commit();

            // Dispatch OutboxEventProcessorJob
            \App\Jobs\OutboxEventProcessorJob::dispatch($outboxEvent->id);

            // Reload cash to get updated balance
            $cash = \App\Models\Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);
            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->id,
                'message' => 'Transaction successful',
                'cash_balance' => $cash->balance,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('ATK Transaction Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => request()->all()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function index(Request $request)
    {
        $categories = ['ATK', 'JASA POTOCOPY', 'JASA TRANSFER BANK'];
        $query = AtkTransaction::with(['user', 'items.product']);

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                $request->start_date.' 00:00:00',
                $request->end_date.' 23:59:59',
            ]);
        }

        if ($request->filled('category')) {
            $query->whereHas('items.product', function ($q) use ($request) {
                $q->where('category', $request->get('category'));
            });
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                  ->orWhereHas('items', function ($qi) use ($search) {
                      $qi->where('product_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($qi) use ($search) {
                      $qi->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $transactions = $query->latest()->paginate(15)->appends($request->query());

        // Total revenue for current filter
        $sumQuery = AtkTransaction::query();
        if ($request->start_date && $request->end_date) {
            $sumQuery->whereBetween('created_at', [
                $request->start_date.' 00:00:00',
                $request->end_date.' 23:59:59',
            ]);
        }
        if ($request->filled('category')) {
            $sumQuery->whereHas('items.product', function ($q) use ($request) {
                $q->where('category', $request->get('category'));
            });
        }
        $totalRevenue = $sumQuery->sum('total_amount');

        return view('atk.transactions.index', compact('transactions', 'categories', 'totalRevenue'));
    }

    public function show(AtkTransaction $transaction)
    {
        $transaction->load('items.product');

        return view('atk.transactions.show', compact('transaction'));
    }

    public function destroy(AtkTransaction $transaction, \App\Services\Atk\AtkCashService $cashService)
    {
        if (! Auth::user()->hasPermission('atk.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $transaction->load(['items.product', 'cashMovements']);

        // Check if already reversed
        if ($transaction->status === 'reversed' || $transaction->reversed_at !== null) {
            return back()->with('error', __('Transaction already reversed.'));
        }

        DB::transaction(function () use ($transaction, $cashService) {
            $userId = Auth::id();

            // Mark transaction as reversed
            $transaction->update([
                'status' => 'reversed',
                'reversed_at' => now(),
            ]);

            // Reverse each associated cash movement
            foreach ($transaction->cashMovements as $movement) {
                if ($movement->reversed_at === null) {
                    $cashService->reverseMovement($movement, $userId);
                }
            }

            // Restock products
            foreach ($transaction->items as $item) {
                $product = $item->product;
                $itemType = $item->item_type ?? ($product ? (strtoupper($product->category ?? '') === 'JASA TRANSFER BANK' ? 'bank' : (strtoupper($product->category ?? '') === 'JASA POTOCOPY' ? 'service' : 'product')) : 'product');
                $category = $product ? strtoupper($product->category ?? '') : '';
                $isService = $category === 'JASA POTOCOPY';
                $isBank = $category === 'JASA TRANSFER BANK';

                if (! $isService && ! $isBank && $product) {
                    $product->increment('stock', $item->quantity);
                }
            }

            // Reverse float transactions (create opposite movements)
            $floatTrans = \App\Models\AtkFloatTransaction::where('reference_type', 'atk_transaction')
                ->where('reference_id', $transaction->id)
                ->get();

            foreach ($floatTrans as $ft) {
                if ($ft->reversed_at === null) {
                    $floatAccount = $ft->floatAccount;
                    if ($floatAccount) {
                        $oppositeType = $ft->transaction_type === 'deposit' ? 'withdrawal' : 'deposit';
                        $newBalance = $ft->transaction_type === 'deposit' 
                            ? $floatAccount->current_balance - $ft->amount 
                            : $floatAccount->current_balance + $ft->amount;

                        $floatAccount->update(['current_balance' => $newBalance]);

                        \App\Models\AtkFloatTransaction::create([
                            'atk_float_account_id' => $floatAccount->id,
                            'transaction_type' => $oppositeType,
                            'amount' => $ft->amount,
                            'balance_before' => $floatAccount->current_balance - ($oppositeType === 'deposit' ? $ft->amount : -$ft->amount),
                            'balance_after' => $newBalance,
                            'description' => 'Pembatalan - ' . $ft->description,
                            'reference_type' => 'atk_float_transaction',
                            'reference_id' => $ft->id,
                            'created_by' => $userId,
                        ]);

                        $ft->update(['reversed_at' => now(), 'reversed_by' => $userId]);
                    }
                }
            }

            // Reverse agent deposit if needed
            $sumBankNominal = $transaction->items()
                ->where('item_type', 'bank')
                ->sum('nominal_transaksi');

            if ($sumBankNominal > 0) {
                $deposit = \App\Models\AgentDeposit::firstOrCreate(['name' => 'Deposit Agen Bank'], ['balance' => 0]);
                $deposit->increment('balance', $sumBankNominal);
            }

            // Create reversal outbox event (so accounting can reverse journals)
            $outboxEvent = app(\App\Services\OutboxEventService::class)->createOutboxEvent(
                aggregateType: \App\Models\AtkTransaction::class,
                aggregateId: (string) $transaction->id,
                eventType: 'AtkTransactionReversed',
                payload: [
                    'model_class' => \App\Models\AtkTransaction::class,
                    'model_id' => $transaction->id,
                ]
            );
        });

        return back()->with('success', __('Transaction reversed successfully.'));
    }

    public function bulkDestroy(Request $request)
    {
        if (! Auth::user()->hasPermission('atk.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:atk_transactions,id',
        ]);

        $cashService = app(\App\Services\Atk\AtkCashService::class);

        DB::transaction(function () use ($validated, $cashService) {
            foreach ($validated['ids'] as $id) {
                $transaction = AtkTransaction::with(['items.product', 'cashMovements'])->find($id);
                
                if (! $transaction || $transaction->status === 'reversed' || $transaction->reversed_at !== null) {
                    continue;
                }

                $userId = Auth::id();

                // Mark transaction as reversed
                $transaction->update([
                    'status' => 'reversed',
                    'reversed_at' => now(),
                ]);

                // Reverse each associated cash movement
                foreach ($transaction->cashMovements as $movement) {
                    if ($movement->reversed_at === null) {
                        $cashService->reverseMovement($movement, $userId);
                    }
                }

                // Restock products
                foreach ($transaction->items as $item) {
                    $product = $item->product;
                    $itemType = $item->item_type ?? ($product ? (strtoupper($product->category ?? '') === 'JASA TRANSFER BANK' ? 'bank' : (strtoupper($product->category ?? '') === 'JASA POTOCOPY' ? 'service' : 'product')) : 'product');
                    $category = $product ? strtoupper($product->category ?? '') : '';
                    $isService = $category === 'JASA POTOCOPY';
                    $isBank = $category === 'JASA TRANSFER BANK';

                    if (! $isService && ! $isBank && $product) {
                        $product->increment('stock', $item->quantity);
                    }
                }

                // Reverse float transactions (create opposite movements)
                $floatTrans = \App\Models\AtkFloatTransaction::where('reference_type', 'atk_transaction')
                    ->where('reference_id', $transaction->id)
                    ->get();

                foreach ($floatTrans as $ft) {
                    if ($ft->reversed_at === null) {
                        $floatAccount = $ft->floatAccount;
                        if ($floatAccount) {
                            $oppositeType = $ft->transaction_type === 'deposit' ? 'withdrawal' : 'deposit';
                            $newBalance = $ft->transaction_type === 'deposit' 
                                ? $floatAccount->current_balance - $ft->amount 
                                : $floatAccount->current_balance + $ft->amount;

                            $floatAccount->update(['current_balance' => $newBalance]);

                            \App\Models\AtkFloatTransaction::create([
                                'atk_float_account_id' => $floatAccount->id,
                                'transaction_type' => $oppositeType,
                                'amount' => $ft->amount,
                                'balance_before' => $floatAccount->current_balance - ($oppositeType === 'deposit' ? $ft->amount : -$ft->amount),
                                'balance_after' => $newBalance,
                                'description' => 'Pembatalan - ' . $ft->description,
                                'reference_type' => 'atk_float_transaction',
                                'reference_id' => $ft->id,
                                'created_by' => $userId,
                            ]);

                            $ft->update(['reversed_at' => now(), 'reversed_by' => $userId]);
                        }
                    }
                }

                // Reverse agent deposit if needed
                $sumBankNominal = $transaction->items()
                    ->where('item_type', 'bank')
                    ->sum('nominal_transaksi');

                if ($sumBankNominal > 0) {
                    $deposit = \App\Models\AgentDeposit::firstOrCreate(['name' => 'Deposit Agen Bank'], ['balance' => 0]);
                    $deposit->increment('balance', $sumBankNominal);
                }
            }
        });

        return back()->with('success', __('Transactions reversed successfully.'));
    }

    public function receipt(AtkTransaction $transaction)
    {
        $transaction->load('items.product');

        return view('atk.transactions.receipt', compact('transaction'));
    }

    public function exportPdf(Request $request)
    {
        $query = AtkTransaction::with('user', 'items');

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                $request->start_date.' 00:00:00',
                $request->end_date.' 23:59:59',
            ]);
        }

        if ($request->filled('category')) {
            $query->whereHas('items.product', function ($q) use ($request) {
                $q->where('category', $request->get('category'));
            });
        }

        $transactions = $query->latest()->get();
        $pdf = Pdf::loadView('atk.transactions.pdf', compact('transactions'));

        return $pdf->download('atk_transactions.pdf');
    }

    public function exportExcel(Request $request)
    {
        $query = AtkTransaction::with('user', 'items');

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                $request->start_date.' 00:00:00',
                $request->end_date.' 23:59:59',
            ]);
        }

        if ($request->filled('category')) {
            $query->whereHas('items.product', function ($q) use ($request) {
                $q->where('category', $request->get('category'));
            });
        }

        $transactions = $query->latest()->get();

        return response()->streamDownload(function () use ($transactions) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues(['Date', 'Transaction Number', 'Customer', 'Item Produk', 'Total Amount', 'Payment Method']));

            foreach ($transactions as $trx) {
                $items = $trx->items->map(function ($item) {
                    return $item->product_name.' ('.$item->quantity.')';
                })->implode(', ');

                $writer->addRow(Row::fromValues([
                    $trx->created_at->format('Y-m-d H:i'),
                    $trx->transaction_number,
                    $trx->user->name ?? 'Guest',
                    $items,
                    $trx->total_amount,
                    $trx->payment_method,
                ]));
            }

            $writer->close();
        }, 'atk_transactions.xlsx');
    }

    public function whatsappReceipt(Request $request, AtkTransaction $transaction)
    {
        $request->validate([
            'phone' => 'required|string',
            'receipt_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
        ]);
        $phone = $this->normalizePhone($request->input('phone'));
        $link = route('atk.transactions.receipt', $transaction);
        $date = $transaction->created_at ? $transaction->created_at->format('d-m-Y H:i') : now()->format('d-m-Y H:i');
        $items = $transaction->items()->get()->map(function ($it) {
            return [
                'nama_produk' => $it->product_name,
                'qty' => $it->quantity,
                'harga' => number_format($it->price, 0, ',', '.'),
                'total' => number_format($it->subtotal, 0, ',', '.'),
            ];
        })->toArray();
        $subtotal = (float) $transaction->items()->sum('subtotal');
        $vars = [
            'nama_toko' => config('app.name'),
            'alamat_toko' => Setting::getValue('store_address', ''),
            'no_toko' => Setting::getValue('store_phone', ''),
            'invoice' => $transaction->invoice_number ?? $transaction->transaction_number,
            'tanggal' => $date,
            'nama_customer' => '-',
            'subtotal' => number_format($subtotal, 0, ',', '.'),
            'diskon' => number_format(0, 0, ',', '.'),
            'pajak' => number_format(0, 0, ',', '.'),
            'grand_total' => number_format($transaction->total_amount, 0, ',', '.'),
            'metode_bayar' => strtoupper($transaction->payment_method),
            'status' => $transaction->payment_method === 'hutang' ? 'HUTANG' : 'LUNAS',
            'link_pdf' => $link,
            'items' => $items,
        ];
        $tpl = Setting::where('key', 'whatsapp_atk_receipt_template')->value('value')
            ?? "*STRUK PEMBELIAN*\nNo: {{invoice}}\nTanggal: {{tanggal}}\n\n{{#each items}}• {{nama_produk}}\n{{qty}} x Rp{{harga}} = Rp{{total}}\n{{/each}}\n\nTotal: Rp{{grand_total}}";
        $wa = app(WhatsAppService::class);
        $message = $wa->renderTemplate($tpl, $vars);
        if ($request->hasFile('receipt_image')) {
            $file = $request->file('receipt_image');
            $wa->sendMessageWithMedia(
                $phone,
                $message,
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName() ?: ('struk-atk-'.$transaction->transaction_number.'.png'),
                'receipt',
                null
            );
        } else {
            $wa->sendMessage($phone, $message, 'receipt', null);
        }

        return response()->json(['success' => true]);
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
}
