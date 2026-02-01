<?php

namespace App\Http\Controllers;

use App\Models\AtkTransaction;
use App\Models\AtkTransactionItem;
use App\Models\AtkProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AtkTransactionController extends Controller
{
    public function dashboard()
    {
        $today = now()->format('Y-m-d');
        $month = now()->format('Y-m');

        $dailySales = AtkTransaction::whereDate('created_at', $today)->sum('total_amount');
        $monthlySales = AtkTransaction::where('created_at', 'like', "$month%")->sum('total_amount');
        $transactionCount = AtkTransaction::whereDate('created_at', $today)->count();
        
        // Top selling products
        $topProducts = AtkTransactionItem::select('product_name', DB::raw('sum(quantity) as total_qty'))
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return view('atk.dashboard', compact('dailySales', 'monthlySales', 'transactionCount', 'topProducts'));
    }

    public function pos()
    {
        $products = AtkProduct::where('stock', '>', 0)->get();
        return view('atk.pos', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:atk_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'cash_amount' => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

            $total = 0;
            $items = [];

            foreach ($request->items as $itemData) {
                $product = AtkProduct::lockForUpdate()->find($itemData['id']);
                
                if ($product->stock < $itemData['quantity']) {
                    throw new \Exception("Stock for {$product->name} is insufficient.");
                }

                $price = $product->price; // Or calculate tiered price
                $subtotal = $price * $itemData['quantity'];
                $total += $subtotal;

                $product->decrement('stock', $itemData['quantity']);

                $items[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $price,
                    'quantity' => $itemData['quantity'],
                    'subtotal' => $subtotal,
                ];
            }

            $transaction = AtkTransaction::create([
                'user_id' => Auth::id(),
                'transaction_number' => 'TRX-' . time(),
                'total_amount' => $total,
                'payment_method' => $request->payment_method,
                'cash_amount' => $request->cash_amount,
                'change_amount' => $request->cash_amount ? ($request->cash_amount - $total) : 0,
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
        $transactions = AtkTransaction::with('user')->latest()->paginate(10);
        return view('atk.transactions.index', compact('transactions'));
    }

    public function show(AtkTransaction $transaction)
    {
        $transaction->load('items');
        return view('atk.transactions.show', compact('transaction'));
    }

    public function receipt(AtkTransaction $transaction)
    {
        $transaction->load('items');
        return view('atk.transactions.receipt', compact('transaction'));
    }
}
