<?php

namespace App\Http\Controllers;

use App\Models\AtkProduct;
use App\Models\AtkTransaction;
use App\Models\AtkTransactionItem;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Common\Entity\Row;

class AtkProductController extends Controller
{
    public function exportExcel()
    {
        $writer = new Writer();
        $filename = 'data-produk-atk-' . date('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $writer->openToFile('php://output');
        
        // Header
        $writer->addRow(Row::fromValues([
            'Kode', 'Nama Produk', 'Stok', 'Satuan', 
            'Harga Beli', 'Harga Jual Ecer', 'Harga Jual Grosir'
        ]));
        
        $products = AtkProduct::all();
        
        foreach ($products as $product) {
            $writer->addRow(Row::fromValues([
                $product->code,
                $product->name,
                $product->stock,
                $product->unit,
                $product->buy_price,
                $product->sell_price_retail,
                $product->sell_price_wholesale
            ]));
        }
        
        $writer->close();
        exit;
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);
        
        $file = $request->file('file');
        $reader = new Reader();
        $reader->open($file->getRealPath());
        
        $count = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $index => $row) {
                if ($index === 1) continue; // Skip header
                
                $cells = $row->getCells();
                $data = [];
                foreach ($cells as $cell) {
                    $data[] = $cell->getValue();
                }
                
                // Expected order: Code, Name, Stock, Unit, Buy, Retail, Wholesale
                if (count($data) >= 7) {
                    AtkProduct::updateOrCreate(
                        ['code' => $data[0]],
                        [
                            'name' => $data[1],
                            'stock' => (int)$data[2],
                            'unit' => $data[3],
                            'buy_price' => (float)$data[4],
                            'sell_price_retail' => (float)$data[5],
                            'sell_price_wholesale' => (float)$data[6],
                        ]
                    );
                    $count++;
                }
            }
        }
        
        $reader->close();
        
        return back()->with('success', "$count produk berhasil diimpor.");
    }

    public function index()
    {
        $products = AtkProduct::with('category')->latest()->paginate(10);
        $categories = Category::all();
        return view('atk.products.index', compact('products', 'categories'));
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'stock' => 'required|integer|min:0',
            'buy_price' => 'required|numeric|min:0',
            'sell_price_retail' => 'required|numeric|min:0',
            'sell_price_wholesale' => 'required|numeric|min:0',
            'unit' => 'required',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('atk-products', 'public');
        }

        AtkProduct::create($data);

        return redirect()->route('atk.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, AtkProduct $product)
    {
        $request->validate([
            'code' => 'required|unique:atk_products,code,' . $product->id,
            'name' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'stock' => 'required|integer|min:0',
            'buy_price' => 'required|numeric|min:0',
            'sell_price_retail' => 'required|numeric|min:0',
            'sell_price_wholesale' => 'required|numeric|min:0',
            'unit' => 'required',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            if ($product->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($product->image)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('atk-products', 'public');
        }

        $product->update($data);

        return redirect()->route('atk.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(AtkProduct $product)
    {
        $product->delete();
        return redirect()->route('atk.products.index')->with('success', 'Produk berhasil dihapus.');
    }
}
