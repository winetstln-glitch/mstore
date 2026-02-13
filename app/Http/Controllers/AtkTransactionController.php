<?php

namespace App\Http\Controllers;

use App\Models\AtkTransaction;
use App\Models\AtkTransactionItem;
use App\Models\AtkProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Cash;
use App\Models\AgentDeposit;

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
        $products = AtkProduct::where('category', 'ATK')->where('stock', '>', 0)->get();
        $services = AtkProduct::where('category', 'JASA POTOCOPY')->get();
        $bankServices = AtkProduct::where('category', 'JASA TRANSFER BANK')->get();
        return view('atk.pos', compact('products', 'services', 'bankServices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:atk_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.nominal_transaksi' => 'nullable|numeric|min:0',
            'items.*.fee' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string',
            'cash_amount' => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

            $total = 0;
            $items = [];

            $sumBankNominal = 0;
            foreach ($request->items as $itemData) {
                $product = AtkProduct::lockForUpdate()->find($itemData['id']);
                
                $isService = strtoupper($product->category ?? '') === 'JASA POTOCOPY';
                $isBank = strtoupper($product->category ?? '') === 'JASA TRANSFER BANK';
                if (!$isService) {
                    if ($product->stock < $itemData['quantity']) {
                        throw new \Exception("Stock for {$product->name} is insufficient.");
                    }
                }

                if ($isBank) {
                    $nominal = (float)($itemData['nominal_transaksi'] ?? 0);
                    $fee = (float)($itemData['fee'] ?? 0);
                    $sumBankNominal += $nominal;
                    $subtotal = $fee;
                    $total += ($nominal + $fee);
                    $price = $fee;
                } else {
                    $price = $product->price;
                    $subtotal = $price * $itemData['quantity'];
                    $total += $subtotal;
                }

                if (!$isService) {
                    $product->decrement('stock', $itemData['quantity']);
                }

                $items[] = [
                    'product_id' => $product->id,
                    'atk_product_id' => $product->id, // Added for compatibility
                    'product_name' => $product->name,
                    'price' => $price,
                    'quantity' => $itemData['quantity'],
                    'subtotal' => $subtotal,
                    'nominal_transaksi' => $isBank ? $nominal : null,
                    'fee' => $isBank ? $fee : null,
                ];
            }

            $transaction = AtkTransaction::create([
                    'user_id' => Auth::id(),
                    'transaction_number' => 'TRX-' . time(),
                    'invoice_number' => 'INV-' . time(), // Added to satisfy legacy constraint
                    'total_amount' => $total,
                    'payment_method' => $request->payment_method,
                    'cash_amount' => $request->cash_amount,
                    'change_amount' => $request->cash_amount ? ($request->cash_amount - $total) : 0,
                ]);

            foreach ($items as $item) {
                $transaction->items()->create($item);
            }

            // Update default cash balance (Kas Utama)
            $cash = Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);
            $cash->balance = (float)$cash->balance + (float)$total;
            $cash->save();

            // Reduce Agent Deposit by nominal transfer sum
            if ($sumBankNominal > 0) {
                $deposit = AgentDeposit::firstOrCreate(['name' => 'Deposit Agen Bank'], ['balance' => 0]);
                $deposit->balance = (float)$deposit->balance - (float)$sumBankNominal;
                $deposit->save();
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

    public function index(Request $request)
    {
        $categories = ['ATK', 'JASA POTOCOPY', 'JASA TRANSFER BANK'];
        $query = AtkTransaction::with(['user', 'items.product']);

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        if ($request->filled('category')) {
            $query->whereHas('items.product', function($q) use ($request) {
                $q->where('category', $request->get('category'));
            });
        }

        $transactions = $query->latest()->paginate(15)->appends($request->query());

        // Total revenue for current filter
        $sumQuery = AtkTransaction::query();
        if ($request->start_date && $request->end_date) {
            $sumQuery->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }
        if ($request->filled('category')) {
            $sumQuery->whereHas('items.product', function($q) use ($request) {
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
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        if ($request->filled('category')) {
            $query->whereHas('items.product', function($q) use ($request) {
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
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        if ($request->filled('category')) {
            $query->whereHas('items.product', function($q) use ($request) {
                $q->where('category', $request->get('category'));
            });
        }

        $transactions = $query->latest()->get();
        
        return response()->streamDownload(function () use ($transactions) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues(['Date', 'Transaction Number', 'Customer', 'Items', 'Total Amount', 'Payment Method']));

            foreach ($transactions as $trx) {
                $items = $trx->items->map(function($item) {
                    return $item->product_name . ' (' . $item->quantity . ')';
                })->implode(', ');

                $writer->addRow(Row::fromValues([
                    $trx->created_at->format('Y-m-d H:i'),
                    $trx->transaction_number,
                    $trx->user->name ?? 'Guest',
                    $items,
                    $trx->total_amount,
                    $trx->payment_method
                ]));
            }

            $writer->close();
        }, 'atk_transactions.xlsx');
    }
}
