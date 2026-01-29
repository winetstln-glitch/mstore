<?php

namespace App\Http\Controllers;

use App\Models\AtkProduct;
use App\Models\AtkTransaction;
use App\Models\AtkTransactionItem;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;

class AtkTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = AtkTransaction::with(['user', 'items.product'])->latest();

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
        return view('atk.transactions.index', compact('transactions'));
    }

    public function exportExcel(Request $request)
    {
        $query = AtkTransaction::with(['user', 'items.product'])->latest();

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
        $writer->openToBrowser('Laporan_ATK_' . date('YmdHis') . '.xlsx');

        $writer->addRow(Row::fromValues([
            'Invoice', 'Tanggal', 'Kasir', 'Pelanggan', 'Total', 'Metode', 'Detail Item'
        ]));

        foreach ($transactions as $trx) {
            $items = $trx->items->map(function($item) {
                return $item->product->name . ' (' . $item->quantity . ' x ' . $item->price . ')';
            })->implode(', ');

            $writer->addRow(Row::fromValues([
                $trx->invoice_number,
                $trx->created_at->format('Y-m-d H:i:s'),
                $trx->user->name ?? '-',
                $trx->customer_name,
                $trx->total_amount,
                $trx->payment_method,
                $items
            ]));
        }

        $writer->close();
    }

    public function create()
    {
        $products = AtkProduct::where('stock', '>', 0)->get();
        return view('atk.pos.index', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:atk_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price_type' => 'required|in:retail,wholesale',
            'payment_method' => 'required|string',
            'amount_paid' => 'numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            $itemsData = [];

            // Calculate total and prepare items
            foreach ($request->items as $item) {
                $product = AtkProduct::lockForUpdate()->find($item['id']);
                
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Stok tidak cukup untuk produk: " . $product->name);
                }

                $price = ($item['price_type'] === 'wholesale') ? $product->sell_price_wholesale : $product->sell_price_retail;
                $subtotal = $price * $item['quantity'];
                $totalAmount += $subtotal;

                $itemsData[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'subtotal' => $subtotal,
                ];
            }

            // Create Transaction
            $transaction = AtkTransaction::create([
                'invoice_number' => 'INV-ATK-' . date('YmdHis') . '-' . Str::random(4),
                'user_id' => auth()->id(),
                'customer_name' => $request->customer_name ?? 'Guest',
                'total_amount' => $totalAmount,
                'amount_paid' => $request->amount_paid ?? $totalAmount, // Capture amount paid
                'payment_method' => $request->payment_method,
                'type' => 'out', // Sale
                'notes' => $request->notes,
            ]);

            // Create Items and Update Stock
            foreach ($itemsData as $data) {
                AtkTransactionItem::create([
                    'atk_transaction_id' => $transaction->id,
                    'atk_product_id' => $data['product']->id,
                    'quantity' => $data['quantity'],
                    'price' => $data['price'],
                    'subtotal' => $data['subtotal'],
                ]);

                $data['product']->decrement('stock', $data['quantity']);
            }

            // Create Linked Finance Transaction (Income)
            Transaction::create([
                'user_id' => auth()->id(),
                'type' => 'income',
                'category' => 'ATK Revenue', // Consistent with P&L naming? P&L uses direct query, but this is for Finance List visibility
                'amount' => $totalAmount,
                'transaction_date' => $transaction->created_at,
                'description' => 'ATK Sale - ' . $transaction->invoice_number,
                'reference_number' => 'INV-ATK-' . $transaction->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil!',
                'transaction_id' => $transaction->id,
                'redirect' => route('atk.transactions.receipt', $transaction->id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(AtkTransaction $transaction)
    {
        $transaction->load(['items.product', 'user']);
        return view('atk.transactions.show', compact('transaction'));
    }

    public function receipt(AtkTransaction $transaction)
    {
        $transaction->load(['items.product', 'user']);
        return view('atk.transactions.receipt', compact('transaction'));
    }
    
    public function destroy(AtkTransaction $transaction)
    {
        try {
            DB::beginTransaction();
            
            // Delete linked Finance Transaction
            Transaction::where('reference_number', 'INV-ATK-' . $transaction->id)->delete();
            
            // Restore stock
            foreach ($transaction->items as $item) {
                 $item->product->increment('stock', $item->quantity);
            }
            
            // Delete items (if not cascaded by DB)
            $transaction->items()->delete();
            
            // Delete transaction
            $transaction->delete();
            
            DB::commit();
            
            return redirect()->route('atk.transactions.index')->with('success', 'Transaksi berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }

    public function dashboard() {
         // Simple Dashboard logic
         $totalSales = AtkTransaction::where('type', 'out')->sum('total_amount');
         $todaySales = AtkTransaction::where('type', 'out')->whereDate('created_at', today())->sum('total_amount');
         $lowStockProducts = AtkProduct::where('stock', '<', 10)->get();
         
         return view('atk.dashboard', compact('totalSales', 'todaySales', 'lowStockProducts'));
    }
}
