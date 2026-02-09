<?php

namespace App\Http\Controllers;

use App\Models\AtkProduct;
use Illuminate\Http\Request;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Common\Entity\Row;

class AtkProductController extends Controller
{
    public function export()
    {
        $products = AtkProduct::all();
        
        return response()->streamDownload(function () use ($products) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues(['Code', 'Name', 'Category', 'Price', 'Cost Price', 'Stock', 'Unit', 'Description']));

            foreach ($products as $product) {
                $writer->addRow(Row::fromValues([
                    $product->code,
                    $product->name,
                    $product->category,
                    $product->price,
                    $product->cost_price,
                    $product->stock,
                    $product->unit,
                    $product->description,
                ]));
            }

            $writer->close();
        }, 'atk_products.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');
        $reader = new Reader();
        $reader->open($file->getRealPath());

        $count = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $index => $row) {
                if ($index === 1) continue; // Skip header

                $cells = $row->getCells();
                if (empty($cells)) continue;

                $code = $cells[0]->getValue() ?? null;
                if (!$code) continue;

                AtkProduct::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $cells[1]->getValue() ?? 'Unknown',
                        'category' => $cells[2]->getValue() ?? null,
                        'price' => (float) ($cells[3]->getValue() ?? 0),
                        'cost_price' => (float) ($cells[4]->getValue() ?? 0),
                        'stock' => (int) ($cells[5]->getValue() ?? 0),
                        'unit' => $cells[6]->getValue() ?? 'pcs',
                        'description' => $cells[7]->getValue() ?? null,
                    ]
                );
                $count++;
            }
        }

        $reader->close();

        return redirect()->route('atk.products.index')->with('success', __("$count products imported successfully."));
    }

    public function index()
    {
        $products = AtkProduct::latest()->paginate(10);
        return view('atk.products.index', compact('products'));
    }

    public function create()
    {
        return view('atk.products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:atk_products,code',
            'category' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'unit' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('atk-products', 'public');
        }

        AtkProduct::create($validated);

        return redirect()->route('atk.products.index')->with('success', __('Product created successfully.'));
    }

    public function edit(AtkProduct $product)
    {
        return view('atk.products.edit', compact('product'));
    }

    public function update(Request $request, AtkProduct $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:atk_products,code,' . $product->id,
            'category' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'unit' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('atk-products', 'public');
        }

        $product->update($validated);

        return redirect()->route('atk.products.index')->with('success', __('Product updated successfully.'));
    }

    public function destroy(AtkProduct $product)
    {
        if ($product->image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image);
        }
        $product->delete();
        return redirect()->route('atk.products.index')->with('success', __('Product deleted successfully.'));
    }
}
