<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Journal;
use App\Models\Setting;
use App\Models\TechnicianAttendance;
use App\Models\WashCustomer;
use App\Models\WashService;
use App\Models\WashTransaction;
use App\Models\WashTransactionItem;
use App\Services\AccountingPoster;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class WashTransactionController extends Controller
{
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
        $todayAttendance = TechnicianAttendance::where('user_id', Auth::id())
            ->whereDate('clock_in', today())
            ->first();

        $dailySales = WashTransaction::whereDate('created_at', $today)->sum('total_amount');
        $monthlySales = WashTransaction::where('created_at', 'like', "$month%")->sum('total_amount');
        $transactionCount = WashTransaction::whereDate('created_at', $today)->count();

        // Top selling services
        $topServices = WashTransactionItem::select('service_name', DB::raw('sum(quantity) as total_qty'))
            ->groupBy('service_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return view('wash.dashboard', compact('dailySales', 'monthlySales', 'transactionCount', 'topServices', 'todayAttendance'));
    }

    public function pos()
    {
        $services = WashService::where('is_active', true)->orderBy('vehicle_type')->orderBy('name')->get();
        $brands = $this->brands;
        $employees = \App\Models\WashEmployee::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('wash.pos', compact('services', 'brands', 'employees'));
    }

    public function checkCustomer(Request $request)
    {
        $phone = $request->query('phone');
        $vehiclePlate = $request->query('vehicle_plate');
        $customerName = $request->query('customer_name');
        $customer = WashCustomer::where('phone', $phone)->first();
        [$loyaltyType, $loyaltyValue] = $this->resolveLoyaltyIdentifier($vehiclePlate, $customerName ?: ($customer->name ?? null));
        $visitCount = 0;
        if ($loyaltyType && $loyaltyValue) {
            $visitCount = $this->buildLoyaltyQuery($loyaltyType, $loyaltyValue)->count();
        } elseif ($customer) {
            $visitCount = (int) $customer->visit_count;
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
                'free_wash_eligibility' => (int) ($customer->free_wash_eligibility ?? 0),
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
            'payment_method' => 'required|string',
            'cash_amount' => 'nullable|numeric',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'vehicle_plate' => 'nullable|string',
            'vehicle_brand' => 'nullable|string',
            'use_voucher' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

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

            foreach ($request->items as $itemData) {
                $service = WashService::find($itemData['id']);

                $price = $service->price;
                $subtotal = $price * $itemData['quantity'];
                $total += $subtotal;

                $items[] = [
                    'wash_service_id' => $service->id,
                    'service_name' => $service->name,
                    'price' => $price,
                    'quantity' => $itemData['quantity'],
                    'subtotal' => $subtotal,
                    'employee_id' => $itemData['employee_id'] ?? null,
                ];

            }

            $discountAmount = 0;
            $discountType = null;
            [$loyaltyType, $loyaltyValue] = $this->resolveLoyaltyIdentifier(
                $request->vehicle_plate,
                $request->customer_name ?: ($customer->name ?? null)
            );
            $visitCountBefore = 0;
            $isTenthVisit = false;
            if ($loyaltyType && $loyaltyValue) {
                $visitCountBefore = $this->buildLoyaltyQuery($loyaltyType, $loyaltyValue)->lockForUpdate()->count();
                $isTenthVisit = (($visitCountBefore + 1) % 10) === 0;
            }

            if ($customer && $request->use_voucher && $customer->free_wash_eligibility > 0) {
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
                if ($nextVisitCount % 10 === 0 && $discountType !== 'loyalty') {
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

            // Generate Queue Number (Reset daily)
            $today = now()->format('Y-m-d');
            $lastQueue = WashTransaction::whereDate('created_at', $today)->max('queue_number');
            $queueNumber = ($lastQueue ?? 0) + 1;

            $transaction = WashTransaction::create([
                'user_id' => Auth::id(),
                'wash_customer_id' => $customer ? $customer->id : null,
                'transaction_number' => 'WASH-'.time(),
                'queue_number' => $queueNumber,
                'total_amount' => $finalTotal,
                'discount_amount' => $discountAmount,
                'payment_method' => $request->payment_method,
                'cash_amount' => $request->cash_amount,
                'change_amount' => $request->cash_amount ? ($request->cash_amount - $finalTotal) : 0,
                'customer_name' => $request->customer_name ?? ($customer ? $customer->name : null),
                'vehicle_plate' => $request->vehicle_plate,
                'vehicle_brand' => $request->vehicle_brand,
                'notes' => $discountNote,
                'status' => 'lunas',
            ]);

            foreach ($items as $item) {
                $transaction->items()->create($item);
            }

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

            DB::commit();

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
        $query = WashTransaction::with('user');

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                $request->start_date.' 00:00:00',
                $request->end_date.' 23:59:59',
            ]);
        }

        $transactions = $query->latest()->paginate(10);

        return view('wash.transactions.index', compact('transactions'));
    }

    public function show(WashTransaction $transaction)
    {
        return view('wash.transactions.show', compact('transaction'));
    }

    public function destroy(WashTransaction $transaction)
    {
        if (! Auth::user()->hasRole('admin')) {
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

    public function exportPdf(Request $request)
    {
        $query = WashTransaction::with('user', 'items');

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                $request->start_date.' 00:00:00',
                $request->end_date.' 23:59:59',
            ]);
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

        return view('wash.transactions.receipt', compact('transaction'));
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
            ];
        })->toArray();
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
            'total' => number_format($transaction->total_amount, 0, ',', '.'),
            'metode_bayar' => strtoupper($transaction->payment_method),
            'status' => 'LUNAS',
            'items' => $items,
            'receipt_url' => $link,
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

    private function resolveLoyaltyIdentifier(?string $vehiclePlate, ?string $customerName): array
    {
        $plate = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $vehiclePlate));
        if ($plate !== '') {
            return ['plate', $plate];
        }

        $name = strtoupper(trim((string) $customerName));
        if ($name !== '') {
            return ['name', $name];
        }

        return [null, null];
    }

    private function buildLoyaltyQuery(string $type, string $value)
    {
        $query = WashTransaction::query();
        if ($type === 'plate') {
            $query->whereRaw(
                "UPPER(REPLACE(REPLACE(COALESCE(vehicle_plate, ''), ' ', ''), '-', '')) = ?",
                [$value]
            );
        } else {
            $query->whereRaw(
                "UPPER(TRIM(COALESCE(customer_name, ''))) = ?",
                [$value]
            );
        }

        return $query;
    }
}
