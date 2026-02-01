<?php

namespace App\Http\Controllers;

use App\Models\WashTransaction;
use App\Models\WashTransactionItem;
use App\Models\WashService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WashTransactionController extends Controller
{
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
        return view('wash.pos', compact('services'));
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
            'vehicle_plate' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

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
                ];
            }

            $transaction = WashTransaction::create([
                'user_id' => Auth::id(),
                'transaction_number' => 'WASH-' . time(),
                'total_amount' => $total,
                'payment_method' => $request->payment_method,
                'cash_amount' => $request->cash_amount,
                'change_amount' => $request->cash_amount ? ($request->cash_amount - $total) : 0,
                'customer_name' => $request->customer_name,
                'vehicle_plate' => $request->vehicle_plate,
            ]);

            foreach ($items as $item) {
                $transaction->items()->create($item);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->id,
                'message' => 'Transaction successful'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function index()
    {
        $transactions = WashTransaction::with('user')->latest()->paginate(10);
        return view('wash.transactions.index', compact('transactions'));
    }

    public function show(WashTransaction $transaction)
    {
        $transaction->load('items');
        return view('wash.transactions.show', compact('transaction'));
    }
}
