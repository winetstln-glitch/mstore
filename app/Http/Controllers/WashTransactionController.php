<?php

namespace App\Http\Controllers;

use App\Models\WashTransaction;
use App\Models\WashTransactionItem;
use App\Models\WashService;
use App\Models\WashCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use Barryvdh\DomPDF\Facade\Pdf;

class WashTransactionController extends Controller
{
    private $brands = [
        'Motor' => [
            'Honda', 'Yamaha', 'Suzuki', 'Kawasaki', 'Vespa', 'KTM', 'Harley Davidson', 'BMW Motorrad', 'Ducati', 'Triumph', 'Royal Enfield', 'TVS', 'Benelli', 'Sym', 'Kymco', 'Viar', 'Gesits', 'Volta', 'Alva', 'Polytron', 'Davigo', 'Smoot', 'Selis', 'United', 'Zero', 'Aprilia', 'Moto Guzzi', 'Husqvarna', 'Bajaj', 'Minerva', 'Happy', 'Kaisar', 'Nozomi'
        ],
        'Mobil' => [
            'Toyota', 'Honda', 'Daihatsu', 'Mitsubishi', 'Suzuki', 'Nissan', 'Mazda', 'Wuling', 'Hyundai', 'Kia', 'Isuzu', 'BMW', 'Mercedes-Benz', 'Audi', 'Volkswagen', 'Lexus', 'Land Rover', 'Jeep', 'Ford', 'Chevrolet', 'Peugeot', 'Renault', 'Chery', 'DFSK', 'MG', 'Subaru', 'Volvo', 'Mini', 'Porsche', 'Ferrari', 'Lamborghini', 'Jaguar', 'Maserati', 'McLaren', 'Aston Martin', 'Bentley', 'Rolls-Royce', 'Tesla', 'BYD', 'Neta', 'Citroen', 'Tata', 'Proton', 'Holden', 'Opel', 'Fiat', 'Alfa Romeo', 'Datsun', 'Hino', 'UD Trucks', 'Scania', 'Foton'
        ]
    ];

    public function dashboard()
    {
        $today = now()->format('Y-m-d');
        $month = now()->format('Y-m');

        $dailySales = WashTransaction::whereDate('created_at', $today)->sum('total_amount');
        $monthlySales = WashTransaction::where('created_at', 'like', "$month%")->sum('total_amount');
        $transactionCount = WashTransaction::whereDate('created_at', $today)->count();
        
        // Top selling services
        $topServices = WashTransactionItem::select('service_name', DB::raw('sum(quantity) as total_qty'))
            ->groupBy('service_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return view('wash.dashboard', compact('dailySales', 'monthlySales', 'transactionCount', 'topServices'));
    }

    public function pos()
    {
        $services = WashService::where('is_active', true)->orderBy('vehicle_type')->orderBy('name')->get();
        $brands = $this->brands;
        return view('wash.pos', compact('services', 'brands'));
    }

    public function checkCustomer(Request $request)
    {
        $phone = $request->query('phone');
        $customer = WashCustomer::where('phone', $phone)->first();

        if ($customer) {
            return response()->json([
                'found' => true,
                'name' => $customer->name,
                'visit_count' => $customer->visit_count,
                'free_wash_eligibility' => $customer->free_wash_eligibility
            ]);
        }

        return response()->json(['found' => false]);
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
                'service_name'    => $service->name,
                'price'           => $price,
                'quantity'        => $itemData['quantity'],
                'subtotal'        => $subtotal,
                'employee_id'     => Auth::id(),
                ];

            }

            // Handle Voucher
            $discountAmount = 0;
            if ($customer && $request->use_voucher && $customer->free_wash_eligibility > 0) {
                // Apply discount (assuming 1 free wash = value of first item or fixed amount? 
                // Let's assume it makes the transaction free or deducts the most expensive item?
                // Simplest: Deduct the price of the first item found)
                if (count($items) > 0) {
                    $discountAmount = $items[0]['price']; // Discount one wash
                    $customer->decrement('free_wash_eligibility');
                }
            }

            // Update visit count and eligibility
            if ($customer) {
                $customer->increment('visit_count');
                if ($customer->visit_count % 10 == 0) {
                    $customer->increment('free_wash_eligibility');
                }
            }

            $finalTotal = max(0, $total - $discountAmount);

            // Generate Queue Number (Reset daily)
            $today = now()->format('Y-m-d');
            $lastQueue = WashTransaction::whereDate('created_at', $today)->max('queue_number');
            $queueNumber = ($lastQueue ?? 0) + 1;

            $transaction = WashTransaction::create([
                'user_id' => Auth::id(),
                'wash_customer_id' => $customer ? $customer->id : null,
                'transaction_number' => 'WASH-' . time(),
                'queue_number' => $queueNumber,
                'total_amount' => $finalTotal,
                'discount_amount' => $discountAmount,
                'payment_method' => $request->payment_method,
                'cash_amount' => $request->cash_amount,
                'change_amount' => $request->cash_amount ? ($request->cash_amount - $finalTotal) : 0,
                'customer_name' => $request->customer_name ?? ($customer ? $customer->name : null),
                'vehicle_plate' => $request->vehicle_plate,
                'vehicle_brand' => $request->vehicle_brand,
            ]);

            foreach ($items as $item) {
                $transaction->items()->create($item);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->id,
                'queue_number' => $queueNumber,
                'message' => 'Transaction successful' . ($discountAmount > 0 ? ' (Voucher Applied)' : '')
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
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $transactions = $query->latest()->paginate(10);
        return view('wash.transactions.index', compact('transactions'));
    }

    public function show(WashTransaction $transaction)
    {
        return view('wash.transactions.show', compact('transaction'));
    }

    public function exportPdf(Request $request)
    {
        $query = WashTransaction::with('user', 'items');

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
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
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $transactions = $query->latest()->get();
        
        return response()->streamDownload(function () use ($transactions) {
            $writer = new Writer();
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
                    $trx->payment_method
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
}
