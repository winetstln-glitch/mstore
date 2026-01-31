<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WashService;
use App\Models\WashTransaction;
use App\Models\WashTransactionItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;

class WashController extends Controller
{
    public function index(Request $request)
    {
        $query = WashTransaction::with(['user', 'items.service'])->latest();

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        } elseif ($request->filled('month')) {
            $query->whereMonth('created_at', date('m', strtotime($request->month)))
                  ->whereYear('created_at', date('Y', strtotime($request->month)));
        }

        $transactions = $query->paginate(10);
        return view('wash.index', compact('transactions'));
    }

    public function exportExcel(Request $request)
    {
        $query = WashTransaction::with(['user', 'items.service'])->latest();

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        } elseif ($request->filled('month')) {
            $query->whereMonth('created_at', date('m', strtotime($request->month)))
                  ->whereYear('created_at', date('Y', strtotime($request->month)));
        }

        $transactions = $query->get();

        $writer = new Writer();
        $writer->openToBrowser('Laporan_Wash_' . date('YmdHis') . '.xlsx');

        $writer->addRow(Row::fromValues([
            'Kode', 'Tanggal', 'Pelanggan', 'Plat Nomor', 'Total', 'Metode', 'Status', 'Layanan'
        ]));

        foreach ($transactions as $trx) {
            $services = $trx->items->map(function($item) {
                return $item->service->name . ' (x' . $item->quantity . ')';
            })->implode(', ');

            $writer->addRow(Row::fromValues([
                $trx->transaction_code,
                $trx->created_at->format('Y-m-d H:i:s'),
                $trx->customer_name,
                $trx->plate_number,
                $trx->total_amount,
                $trx->payment_method,
                $trx->status,
                $services
            ]));
        }

        $writer->close();
    }

    public function create()
    {
        $services = WashService::where('is_active', true)->with('category')->get();
        $categories = \App\Models\Category::all();
        // Only get users with wash.employee role
        $employees = \App\Models\User::whereHas('role', function($q) {
            $q->where('name', 'wash.employee');
        })->where('is_active', true)->get();
        
        return view('wash.pos', compact('services', 'employees', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:wash_services,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.employee_id' => 'nullable|exists:users,id', // Per item employee
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,transfer,qris',
            'customer_name' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
            'plate_number' => 'nullable|string',
            'employee_id' => 'nullable|exists:users,id', // Default/Main employee
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $itemsToCreate = [];

            foreach ($request->items as $itemData) {
                $service = WashService::find($itemData['id']);
                
                // Check stock for physical items
                if ($service->type === 'physical' && $service->stock < $itemData['qty']) {
                    throw new \Exception("Stok {$service->name} tidak mencukupi (Sisa: {$service->stock})");
                }

                $subtotal = $service->price * $itemData['qty'];
                $totalAmount += $subtotal;

                $itemsToCreate[] = [
                    'wash_service_id' => $service->id,
                    'price' => $service->price,
                    'quantity' => $itemData['qty'],
                    'subtotal' => $subtotal,
                    'employee_id' => $itemData['employee_id'] ?? $request->employee_id, // Use item employee or default
                ];
                
                // Deduct stock if physical
                if ($service->type === 'physical') {
                    $service->decrement('stock', $itemData['qty']);
                }
            }

            // Generate Transaction Code
            $date = now()->format('Ymd');
            $count = WashTransaction::whereDate('created_at', today())->count() + 1;
            $code = "WSH-{$date}-" . str_pad($count, 3, '0', STR_PAD_LEFT);

            $transaction = WashTransaction::create([
                'transaction_code' => $code,
                'customer_name' => $request->customer_name ?? 'Guest',
                'customer_id' => $request->customer_id,
                'plate_number' => $request->plate_number,
                'total_amount' => $totalAmount,
                'amount_paid' => $request->amount_paid,
                'payment_method' => $request->payment_method,
                'status' => 'completed', // Or 'pending' if queue
                'user_id' => auth()->id(),
                'employee_id' => $request->employee_id,
                'notes' => $request->notes,
            ]);

            foreach ($itemsToCreate as $item) {
                $item['wash_transaction_id'] = $transaction->id;
                WashTransactionItem::create($item);
            }
            
            // Loyalty Logic (Simple: 1 Point per transaction if registered customer)
            if ($request->customer_id) {
                $customer = \App\Models\Customer::find($request->customer_id);
                if ($customer) {
                    $points = 1; // Customize logic here
                    $customer->increment('loyalty_points', $points);
                    \App\Models\LoyaltyLog::create([
                        'customer_id' => $customer->id,
                        'wash_transaction_id' => $transaction->id,
                        'points' => $points,
                        'description' => "Poin dari transaksi {$code}"
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction successful',
                'redirect_url' => route('wash.receipt', $transaction->id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function receipt(WashTransaction $transaction)
    {
        return view('wash.receipt', compact('transaction'));
    }

    // Service Management
    public function services()
    {
        $services = WashService::with('category')->get();
        $categories = \App\Models\Category::all();
        return view('wash.services', compact('services', 'categories'));
    }

    public function storeService(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'vehicle_type' => 'required|in:car,motor',
            'price' => 'required|numeric|min:0',
            'type' => 'required|in:service,physical',
            'cost_price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $data = $request->all();
        if ($request->hasFile('image')) {
             $data['image'] = $request->file('image')->store('wash-services', 'public');
        }

        WashService::create($data);
        return back()->with('success', 'Service added successfully');
    }

    public function updateService(Request $request, WashService $service)
    {
        $request->validate([
            'name' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'vehicle_type' => 'required|in:car,motor',
            'price' => 'required|numeric|min:0',
            'type' => 'required|in:service,physical',
            'cost_price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $data = $request->all();
        if ($request->hasFile('image')) {
            if ($service->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($service->image)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($service->image);
            }
             $data['image'] = $request->file('image')->store('wash-services', 'public');
        }

        $service->update($data);
        return back()->with('success', 'Service updated successfully');
    }

    public function destroyService(WashService $service)
    {
        $service->delete();
        return back()->with('success', 'Service deleted successfully');
    }
}
