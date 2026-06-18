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
use App\Services\WhatsAppService;
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

        $dailySales = AtkTransaction::whereDate('created_at', $today)->sum('total_amount');
        $monthlySales = AtkTransaction::where('created_at', 'like', "$month%")->sum('total_amount');
        $transactionCount = AtkTransaction::whereDate('created_at', $today)->count();

        // Get Kas Utama
        $cash = \App\Models\Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);

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
        $todayFees = AtkTransactionItem::whereHas('transaction', function ($q) use ($today) {
            $q->whereDate('created_at', $today);
        })->sum('fee');
        
        $monthlyFees = AtkTransactionItem::whereHas('transaction', function ($q) use ($month) {
            $q->where('created_at', 'like', "$month%");
        })->sum('fee');
        
        $todayFeesBank = AtkTransactionItem::where('item_type', 'bank')
            ->whereHas('transaction', function ($q) use ($today) {
                $q->whereDate('created_at', $today);
            })->sum('fee');
            
        $todayFeesPpob = AtkTransactionItem::where('item_type', 'ppob')
            ->whereHas('transaction', function ($q) use ($today) {
                $q->whereDate('created_at', $today);
            })->sum('fee');
            
        $todayFeesTopUp = AtkTransactionItem::where('item_type', 'top_up')
            ->whereHas('transaction', function ($q) use ($today) {
                $q->whereDate('created_at', $today);
            })->sum('fee');
            
        $todayFeesCashOut = AtkTransactionItem::where('item_type', 'cash_out')
            ->whereHas('transaction', function ($q) use ($today) {
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
        $cash = \App\Models\Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);

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

    public function store(Request $request, \App\Services\FeeCalculationService $feeService)
    {
        \Illuminate\Support\Facades\Log::info('ATK Transaction Store Request:', $request->all());
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'nullable',
            'items.*.type' => 'nullable|string|in:product,service,bank,customer_payment,cash_out,top_up,ppob',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.nominal_transaksi' => 'nullable|numeric|min:0',
            'items.*.fee' => 'nullable|numeric|min:0',
            'items.*.calculated_fee' => 'nullable|numeric|min:0',
            'items.*.customer_name' => 'nullable|string|max:255',
            'items.*.float_account_id' => 'nullable|exists:atk_float_accounts,id',
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
        $hpp = 0;
        $containsService = false;
        $totalCashIn = 0;
        $totalCashOut = 0;
        $totalFloatIn = 0;
        $totalFloatOut = 0;
        $cashOutFloatTransactions = [];
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
                    $total += $subtotal;
                    $sumRevenueSales += $subtotal;
                    $totalCashIn += $subtotal;
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
                    $calculatedFee = (float) ($itemData['calculated_fee'] ?? $fee);
                    $floatAccountId = $itemData['float_account_id'] ?? null;
                    if ($nominal <= 0) {
                        throw new \Exception('Nominal cash out wajib diisi.');
                    }
                    if (!$floatAccountId) {
                        throw new \Exception('Akun float untuk cash out wajib dipilih.');
                    }
                    $totalFloatIn += $nominal + $fee;
                    $totalCashOut += $nominal;
                    $total += $fee;
                    $sumFee += $fee;
                    $items[] = [
                        'product_id' => null,
                        'product_name' => 'Cash Out',
                        'price' => $fee,
                        'quantity' => 1,
                        'subtotal' => $fee,
                        'nominal_transaksi' => $nominal,
                        'fee' => $fee,
                        'item_type' => 'cash_out',
                    ];
                    $feeLogData[] = [
                        'transaction_type' => 'cash_out',
                        'nominal' => $nominal,
                        'calculated_fee' => $calculatedFee,
                        'manual_fee' => $calculatedFee !== $fee ? $fee : null,
                        'final_fee' => $fee,
                    ];
                    $cashOutFloatTransactions[] = [
                        'float_account_id' => $floatAccountId,
                        'amount' => $nominal + $fee,
                        'nominal' => $nominal,
                        'fee' => $fee
                    ];
                    continue;
                }

                if ($itemType === 'top_up') {
                    $nominal = (float) ($itemData['nominal_transaksi'] ?? 0);
                    $fee = (float) ($itemData['fee'] ?? 0);
                    $calculatedFee = (float) ($itemData['calculated_fee'] ?? $fee);
                    if ($nominal <= 0) {
                        throw new \Exception('Nominal top up wajib diisi.');
                    }
                    $total += $fee;
                    $sumFee += $fee;
                    $totalCashIn += $nominal + $fee;
                    $items[] = [
                        'product_id' => null,
                        'product_name' => 'Top Up',
                        'price' => $fee,
                        'quantity' => 1,
                        'subtotal' => $fee,
                        'nominal_transaksi' => $nominal,
                        'fee' => $fee,
                        'item_type' => 'top_up',
                    ];
                    $feeLogData[] = [
                        'transaction_type' => 'top_up',
                        'nominal' => $nominal,
                        'calculated_fee' => $calculatedFee,
                        'manual_fee' => $calculatedFee !== $fee ? $fee : null,
                        'final_fee' => $fee,
                    ];
                    continue;
                }

                if ($itemType === 'ppob') {
                    $nominal = (float) ($itemData['nominal_transaksi'] ?? 0);
                    $fee = (float) ($itemData['fee'] ?? 0);
                    $calculatedFee = (float) ($itemData['calculated_fee'] ?? $fee);
                    if ($nominal <= 0) {
                        throw new \Exception('Nominal PPOB wajib diisi.');
                    }
                    $total += $nominal + $fee;
                    $sumFee += $fee;
                    $totalCashIn += $nominal + $fee;
                    $items[] = [
                        'product_id' => null,
                        'product_name' => 'PPOB',
                        'price' => $fee,
                        'quantity' => 1,
                        'subtotal' => $fee,
                        'nominal_transaksi' => $nominal,
                        'fee' => $fee,
                        'item_type' => 'ppob',
                    ];
                    $feeLogData[] = [
                        'transaction_type' => 'ppob',
                        'nominal' => $nominal,
                        'calculated_fee' => $calculatedFee,
                        'manual_fee' => $calculatedFee !== $fee ? $fee : null,
                        'final_fee' => $fee,
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
                    $subtotal = $fee;
                    $total += ($nominal + $fee);
                    $totalCashIn += $nominal + $fee;
                    $price = $fee;
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
                    $totalCashIn += $subtotal;
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

            $payload = [
                'user_id' => Auth::id(),
                'queue_number' => $queueNumber,
                'transaction_number' => 'TRX-'.time(),
                'invoice_number' => 'INV-'.time(), // Added to satisfy legacy constraint
                'total_amount' => $total,
                'payment_method' => $request->payment_method,
                'cash_amount' => $request->cash_amount,
                'change_amount' => $request->cash_amount ? ($request->cash_amount - $total) : 0,
                'coordinator_id' => ($request->payment_method === 'hutang' || $transactionCategory === 'pembayaran_pelanggan') ? ($request->coordinator_id ?? null) : null,
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
                $cash = \App\Models\Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);
                $netCashChange = $totalCashIn - $totalCashOut;

                if ($netCashChange < 0 && abs($netCashChange) > $cash->balance) {
                    throw new \Exception('Saldo Kas Utama tidak cukup untuk transaksi ini.');
                }

                foreach ($cashOutFloatTransactions as $coFt) {
                    $floatAccount = \App\Models\AtkFloatAccount::findOrFail($coFt['float_account_id']);
                    $balanceBefore = $floatAccount->current_balance;
                    $floatAccount->current_balance += $coFt['amount'];
                    $floatAccount->save();

                    \App\Models\AtkFloatTransaction::create([
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
                }

                $balanceBefore = $cash->balance;
                $cash->balance = (float) $cash->balance + $netCashChange;
                $cash->save();

                if ($totalCashIn > 0) {
                    \App\Models\AtkCashMovement::create([
                        'movement_type' => 'sale',
                        'amount' => $totalCashIn,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $cash->balance,
                        'description' => 'Transaksi POS - Masuk - ' . $transaction->transaction_number,
                        'created_by' => Auth::id(),
                    ]);
                }
                if ($totalCashOut > 0) {
                    \App\Models\AtkCashMovement::create([
                        'movement_type' => 'withdrawal',
                        'amount' => $totalCashOut,
                        'balance_before' => $balanceBefore + $totalCashIn,
                        'balance_after' => $cash->balance,
                        'description' => 'Transaksi POS - Keluar - ' . $transaction->transaction_number,
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            // Reduce Agent Deposit by nominal transfer sum
            if ($sumBankNominal > 0) {
                $deposit = AgentDeposit::firstOrCreate(['name' => 'Deposit Agen Bank'], ['balance' => 0]);
                $deposit->balance = (float) $deposit->balance - (float) $sumBankNominal;
                $deposit->save();
            }

            $drCode = $request->payment_method === 'hutang' ? '1101' : ($request->payment_method === 'cash' ? '1001' : '1002');
            $drAccId = Account::where('code', $drCode)->value('id');
            $revAtkId = Account::where('code', '4003')->value('id');
            $revBankId = Account::where('code', '4004')->value('id');
            $depositId = Account::where('code', '1401')->value('id');
            $hppId = Account::where('code', '5001')->value('id');
            $inventoryId = Account::where('code', '1201')->value('id');
            
            $sumPPOBTopUpNominal = 0;
            $sumCustomerPaymentNominal = 0;
            foreach ($request->items as $itemData) {
                $itemType = $itemData['type'] ?? 'product';
                if (in_array($itemType, ['ppob', 'top_up'])) {
                    $sumPPOBTopUpNominal += (float) ($itemData['nominal_transaksi'] ?? 0);
                }
                if ($itemType === 'customer_payment') {
                    $sumCustomerPaymentNominal += (float) ($itemData['nominal_transaksi'] ?? 0);
                }
            }
            
            \Illuminate\Support\Facades\Log::info('Accounting data:', [
                'total' => $total,
                'sumRevenueSales' => $sumRevenueSales,
                'sumFee' => $sumFee,
                'sumBankNominal' => $sumBankNominal,
                'sumPPOBTopUpNominal' => $sumPPOBTopUpNominal,
                'sumCustomerPaymentNominal' => $sumCustomerPaymentNominal
            ]);
            
            if ($drAccId && $revAtkId && $revBankId && $depositId) {
                $lines = [];
                if ($total > 0) {
                    $lines[] = ['account_id' => $drAccId, 'debit' => $total, 'credit' => 0, 'unit' => 'ATK'];
                }
                if ($sumRevenueSales > 0) {
                    $lines[] = ['account_id' => $revAtkId, 'debit' => 0, 'credit' => $sumRevenueSales, 'unit' => 'ATK'];
                }
                if ($sumFee > 0) {
                    $lines[] = ['account_id' => $revBankId, 'debit' => 0, 'credit' => $sumFee, 'unit' => 'ATK'];
                }
                if ($sumBankNominal > 0) {
                    $lines[] = ['account_id' => $depositId, 'debit' => 0, 'credit' => $sumBankNominal, 'unit' => 'ATK'];
                }
                if ($sumPPOBTopUpNominal > 0) {
                    $lines[] = ['account_id' => $depositId, 'debit' => 0, 'credit' => $sumPPOBTopUpNominal, 'unit' => 'ATK'];
                }
                if ($sumCustomerPaymentNominal > 0) {
                    $customerReceivableId = Account::where('code', '1101')->value('id');
                    if ($customerReceivableId) {
                        $lines[] = ['account_id' => $customerReceivableId, 'debit' => 0, 'credit' => $sumCustomerPaymentNominal, 'unit' => 'ATK'];
                    }
                }
                if ($hpp > 0 && $hppId && $inventoryId) {
                    $lines[] = ['account_id' => $hppId, 'debit' => $hpp, 'credit' => 0, 'unit' => 'ATK'];
                    $lines[] = ['account_id' => $inventoryId, 'debit' => 0, 'credit' => $hpp, 'unit' => 'ATK'];
                }
                
                $totalDebit = array_sum(array_column($lines, 'debit'));
                $totalCredit = array_sum(array_column($lines, 'credit'));
                \Illuminate\Support\Facades\Log::info('Journal lines:', [
                    'lines' => $lines,
                    'totalDebit' => $totalDebit,
                    'totalCredit' => $totalCredit
                ]);
                
                $poster = app(AccountingPoster::class);
                $poster->post(
                    'ATK-'.$transaction->transaction_number,
                    now()->toDateString(),
                    'ATK POS',
                    $lines,
                    null,
                    'atk_transaction',
                    $transaction->id
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->id,
                'message' => 'Transaction successful',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

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
        $transaction->load('items');

        return view('atk.transactions.show', compact('transaction'));
    }

    public function destroy(AtkTransaction $transaction)
    {
        if (! Auth::user()->hasPermission('atk.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $transaction->load(['items.product']);

        DB::transaction(function () use ($transaction) {
            $sumBankNominal = 0;
            $totalCashIn = 0;
            $totalCashOut = 0;
            $cashOutFloatTransactions = [];

            foreach ($transaction->items as $item) {
                $product = $item->product;
                $itemType = $item->item_type ?? ($product ? (strtoupper($product->category ?? '') === 'JASA TRANSFER BANK' ? 'bank' : (strtoupper($product->category ?? '') === 'JASA POTOCOPY' ? 'service' : 'product')) : 'product');
                $category = $product ? strtoupper($product->category ?? '') : '';
                $isService = $category === 'JASA POTOCOPY';
                $isBank = $category === 'JASA TRANSFER BANK';

                if (! $isService && ! $isBank && $product) {
                    $product->increment('stock', $item->quantity);
                }

                if ($isBank) {
                    $sumBankNominal += (float) ($item->nominal_transaksi ?? 0);
                }

                // Calculate totalCashIn and totalCashOut just like in store method
                if ($itemType === 'customer_payment') {
                    $totalCashIn += (float) ($item->nominal_transaksi ?? 0);
                } elseif ($itemType === 'cash_out') {
                    $totalCashOut += (float) ($item->nominal_transaksi ?? 0);
                    $cashOutFloatTransactions[] = [
                        'float_account_id' => $transaction->atk_float_account_id,
                        'amount' => (float) ($item->nominal_transaksi ?? 0) + (float) ($item->fee ?? 0),
                    ];
                } elseif ($itemType === 'top_up') {
                    $totalCashIn += (float) ($item->nominal_transaksi ?? 0) + (float) ($item->fee ?? 0);
                } elseif ($itemType === 'ppob') {
                    $totalCashIn += (float) ($item->nominal_transaksi ?? 0) + (float) ($item->fee ?? 0);
                } elseif ($itemType === 'bank') {
                    $totalCashIn += (float) ($item->nominal_transaksi ?? 0) + (float) ($item->fee ?? 0);
                } elseif ($itemType === 'service' || $itemType === 'product') {
                    $totalCashIn += (float) ($item->subtotal ?? 0);
                }
            }

            // Only update cash if payment method wasn't hutang
            if ($transaction->payment_method !== 'hutang') {
                $cash = Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);
                $netCashChange = $totalCashIn - $totalCashOut;
                $balanceBefore = $cash->balance;
                
                // Reverse the cash change: if original was +X, now we do -X
                $cash->balance = (float) $cash->balance - $netCashChange;
                $cash->save();

                // Create AtkCashMovement records for the reversal
                if ($totalCashIn > 0) {
                    \App\Models\AtkCashMovement::create([
                        'movement_type' => 'adjustment',
                        'amount' => $totalCashIn,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceBefore - $netCashChange,
                        'description' => 'Pembatalan Transaksi POS - Masuk - ' . $transaction->transaction_number,
                        'created_by' => Auth::id(),
                    ]);
                }
                if ($totalCashOut > 0) {
                    \App\Models\AtkCashMovement::create([
                        'movement_type' => 'adjustment',
                        'amount' => $totalCashOut,
                        'balance_before' => $balanceBefore - $totalCashIn,
                        'balance_after' => $balanceBefore - $netCashChange,
                        'description' => 'Pembatalan Transaksi POS - Keluar - ' . $transaction->transaction_number,
                        'created_by' => Auth::id(),
                    ]);
                }

                // Reverse float account changes from cash_out
                foreach ($cashOutFloatTransactions as $coFt) {
                    if ($coFt['float_account_id']) {
                        $floatAccount = \App\Models\AtkFloatAccount::find($coFt['float_account_id']);
                        if ($floatAccount) {
                            $balanceBeforeFloat = $floatAccount->current_balance;
                            $floatAccount->current_balance -= $coFt['amount'];
                            $floatAccount->save();

                            \App\Models\AtkFloatTransaction::create([
                                'atk_float_account_id' => $floatAccount->id,
                                'transaction_type' => 'withdrawal',
                                'amount' => $coFt['amount'],
                                'balance_before' => $balanceBeforeFloat,
                                'balance_after' => $floatAccount->current_balance,
                                'description' => 'Pembatalan Cash Out - ' . $transaction->transaction_number,
                                'reference_type' => 'atk_transaction',
                                'reference_id' => $transaction->id,
                                'created_by' => Auth::id(),
                            ]);
                        }
                    }
                }
            }

            if ($sumBankNominal > 0) {
                $deposit = AgentDeposit::firstOrCreate(['name' => 'Deposit Agen Bank'], ['balance' => 0]);
                $deposit->balance = (float) $deposit->balance + (float) $sumBankNominal;
                $deposit->save();
            }

            $journals = Journal::where('source_type', 'atk_transaction')
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
        if (! Auth::user()->hasPermission('atk.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:atk_transactions,id',
        ]);

        $transactions = AtkTransaction::with(['items.product'])
            ->whereIn('id', $validated['ids'])
            ->get();

        DB::transaction(function () use ($transactions) {
            foreach ($transactions as $transaction) {
                $sumBankNominal = 0;
                $totalCashIn = 0;
                $totalCashOut = 0;
                $cashOutFloatTransactions = [];

                foreach ($transaction->items as $item) {
                    $product = $item->product;
                    $itemType = $item->item_type ?? ($product ? (strtoupper($product->category ?? '') === 'JASA TRANSFER BANK' ? 'bank' : (strtoupper($product->category ?? '') === 'JASA POTOCOPY' ? 'service' : 'product')) : 'product');
                    $category = $product ? strtoupper($product->category ?? '') : '';
                    $isService = $category === 'JASA POTOCOPY';
                    $isBank = $category === 'JASA TRANSFER BANK';

                    if (! $isService && ! $isBank && $product) {
                        $product->increment('stock', $item->quantity);
                    }

                    if ($isBank) {
                        $sumBankNominal += (float) ($item->nominal_transaksi ?? 0);
                    }

                    // Calculate totalCashIn and totalCashOut just like in store method
                    if ($itemType === 'customer_payment') {
                        $totalCashIn += (float) ($item->nominal_transaksi ?? 0);
                    } elseif ($itemType === 'cash_out') {
                        $totalCashOut += (float) ($item->nominal_transaksi ?? 0);
                        $cashOutFloatTransactions[] = [
                            'float_account_id' => $transaction->atk_float_account_id,
                            'amount' => (float) ($item->nominal_transaksi ?? 0) + (float) ($item->fee ?? 0),
                        ];
                    } elseif ($itemType === 'top_up') {
                        $totalCashIn += (float) ($item->nominal_transaksi ?? 0) + (float) ($item->fee ?? 0);
                    } elseif ($itemType === 'ppob') {
                        $totalCashIn += (float) ($item->nominal_transaksi ?? 0) + (float) ($item->fee ?? 0);
                    } elseif ($itemType === 'bank') {
                        $totalCashIn += (float) ($item->nominal_transaksi ?? 0) + (float) ($item->fee ?? 0);
                    } elseif ($itemType === 'service' || $itemType === 'product') {
                        $totalCashIn += (float) ($item->subtotal ?? 0);
                    }
                }

                // Only update cash if payment method wasn't hutang
                if ($transaction->payment_method !== 'hutang') {
                    $cash = Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);
                    $netCashChange = $totalCashIn - $totalCashOut;
                    $balanceBefore = $cash->balance;
                    
                    // Reverse the cash change: if original was +X, now we do -X
                    $cash->balance = (float) $cash->balance - $netCashChange;
                    $cash->save();

                    // Create AtkCashMovement records for the reversal
                    if ($totalCashIn > 0) {
                        \App\Models\AtkCashMovement::create([
                            'movement_type' => 'adjustment',
                            'amount' => $totalCashIn,
                            'balance_before' => $balanceBefore,
                            'balance_after' => $balanceBefore - $netCashChange,
                            'description' => 'Pembatalan Transaksi POS - Masuk - ' . $transaction->transaction_number,
                            'created_by' => Auth::id(),
                        ]);
                    }
                    if ($totalCashOut > 0) {
                        \App\Models\AtkCashMovement::create([
                            'movement_type' => 'adjustment',
                            'amount' => $totalCashOut,
                            'balance_before' => $balanceBefore - $totalCashIn,
                            'balance_after' => $balanceBefore - $netCashChange,
                            'description' => 'Pembatalan Transaksi POS - Keluar - ' . $transaction->transaction_number,
                            'created_by' => Auth::id(),
                        ]);
                    }

                    // Reverse float account changes from cash_out
                    foreach ($cashOutFloatTransactions as $coFt) {
                        if ($coFt['float_account_id']) {
                            $floatAccount = \App\Models\AtkFloatAccount::find($coFt['float_account_id']);
                            if ($floatAccount) {
                                $balanceBeforeFloat = $floatAccount->current_balance;
                                $floatAccount->current_balance -= $coFt['amount'];
                                $floatAccount->save();

                                \App\Models\AtkFloatTransaction::create([
                                    'atk_float_account_id' => $floatAccount->id,
                                    'transaction_type' => 'withdrawal',
                                    'amount' => $coFt['amount'],
                                    'balance_before' => $balanceBeforeFloat,
                                    'balance_after' => $floatAccount->current_balance,
                                    'description' => 'Pembatalan Cash Out - ' . $transaction->transaction_number,
                                    'reference_type' => 'atk_transaction',
                                    'reference_id' => $transaction->id,
                                    'created_by' => Auth::id(),
                                ]);
                            }
                        }
                    }
                }

                if ($sumBankNominal > 0) {
                    $deposit = AgentDeposit::firstOrCreate(['name' => 'Deposit Agen Bank'], ['balance' => 0]);
                    $deposit->balance = (float) $deposit->balance + (float) $sumBankNominal;
                    $deposit->save();
                }

                $journals = Journal::where('source_type', 'atk_transaction')
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

    public function receipt(AtkTransaction $transaction)
    {
        $transaction->load('items');

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

            $writer->addRow(Row::fromValues(['Date', 'Transaction Number', 'Customer', 'Items', 'Total Amount', 'Payment Method']));

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
