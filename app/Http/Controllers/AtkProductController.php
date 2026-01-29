<?php

namespace App\Http\Controllers;

use App\Models\AtkProduct;
use App\Models\AtkTransaction;
use App\Models\AtkTransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AtkProductController extends Controller
{
    public function index()
    {
        $products = AtkProduct::latest()->paginate(10);
        return view('atk.products.index', compact('products'));
    }

    public function restock(Request $request, AtkProduct $product)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Create Transaction Record for Stock In
            $transaction = AtkTransaction::create([
                'invoice_number' => 'IN-ATK-' . date('YmdHis') . '-' . Str::random(4),
                'user_id' => auth()->id(),
                'customer_name' => 'Supplier', // Or make this input
                'total_amount' => 0, // Cost tracking optional for now
                'amount_paid' => 0,
                'payment_method' => 'cash',
                'type' => 'in',
                'notes' => $request->note ?? 'Restock Manual',
            ]);

            // Create Transaction Item
            AtkTransactionItem::create([
                'atk_transaction_id' => $transaction->id,
                'atk_product_id' => $product->id,
                'quantity' => $request->quantity,
                'price' => $product->buy_price, // Use current buy price as cost
                'subtotal' => $product->buy_price * $request->quantity,
            ]);

            // Update Stock
            $product->increment('stock', $request->quantity);

            DB::commit();

            return redirect()->route('atk.products.index')->with('success', 'Stok berhasil ditambahkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error adding stock: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:atk_products',
            'name' => 'required',
            'stock' => 'required|integer|min:0',
            'buy_price' => 'required|numeric|min:0',
            'sell_price_retail' => 'required|numeric|min:0',
            'sell_price_wholesale' => 'required|numeric|min:0',
            'unit' => 'required',
        ]);

        AtkProduct::create($request->all());

        return redirect()->route('atk.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, AtkProduct $product)
    {
        $request->validate([
            'code' => 'required|unique:atk_products,code,' . $product->id,
            'name' => 'required',
            'stock' => 'required|integer|min:0',
            'buy_price' => 'required|numeric|min:0',
            'sell_price_retail' => 'required|numeric|min:0',
            'sell_price_wholesale' => 'required|numeric|min:0',
            'unit' => 'required',
        ]);

        $product->update($request->all());

        return redirect()->route('atk.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(AtkProduct $product)
    {
        $product->delete();
        return redirect()->route('atk.products.index')->with('success', 'Produk berhasil dihapus.');
    }
}
