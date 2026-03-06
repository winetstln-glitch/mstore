<?php

namespace App\Http\Controllers;

use App\Models\AtkProduct;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

class AtkProductController extends Controller
{
    public function barcodes(Request $request)
    {
        $query = AtkProduct::query();
        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }
        $products = $query->orderBy('name')->get();

        return view('atk.products.barcodes', compact('products'));
    }

    public function barcodesPdf(Request $request)
    {
        $query = AtkProduct::query();

        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }

        $products = $query->orderBy('name')->get();

        $htmlGen = new \Picqer\Barcode\BarcodeGeneratorHTML;
        $barcodes = [];

        foreach ($products as $product) {
            $code = $product->code ?: ('ITEM_'.substr(uniqid('', true), -6));
            $barcodes[$product->id] = $htmlGen->getBarcode(
                $code,
                $htmlGen::TYPE_CODE_128,
                2,
                60
            );
        }

        // 🔹 PILIH UKURAN KERTAS
        $paper = $request->get('paper');
        $paper = in_array($paper, ['a4', 'legal', 'letter']) ? $paper : 'a4';
        $mode = $request->get('mode', 'preview'); // preview | download | print

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'atk.products.barcodes_pdf',
            compact('products', 'barcodes')
        )->setPaper($paper, 'portrait');

        if ($mode === 'download') {
            return $pdf->download('atk_barcodes.pdf');
        }

        if ($mode === 'print') {
            return response(
                $pdf->output(),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="atk_barcodes.pdf"',
                ]
            );
        }

        // default preview
        return $pdf->stream('atk_barcodes.pdf');
    }

    public function barcodesPreview(Request $request)
    {
        $query = AtkProduct::query();
        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }
        $products = $query->orderBy('name')->get();
        $htmlGen = new \Picqer\Barcode\BarcodeGeneratorHTML;
        $barcodes = [];
        foreach ($products as $product) {
            $code = $product->code ?: ('ITEM_'.substr(uniqid('', true), -6));
            $barcodes[$product->id] = $htmlGen->getBarcode($code, $htmlGen::TYPE_CODE_128, 1.5, 60);
        }
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('atk.products.barcodes_pdf', compact('products', 'barcodes'))->setPaper('a4', 'portrait');

        return $pdf->stream('atk_barcodes.pdf');
    }

    public function export()
    {
        $products = AtkProduct::all();

        return response()->streamDownload(function () use ($products) {
            $writer = new Writer;
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
            'file' => 'required|file|mimes:xlsx',
        ]);

        try {
            $file = $request->file('file');
            $reader = new Reader;
            $reader->open($file->getRealPath());

            $count = 0;
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $index => $row) {
                    if ($index === 1) {
                        continue;
                    } // Skip header

                    $cells = $row->getCells();
                    if (empty($cells)) {
                        continue;
                    }

                    // Detect format:
                    // Format A: Code, Name, Category, Price, Cost Price, Stock, Unit, Description
                    // Format B (minimal): Name, Category, Price, Cost Price, Stock, Unit, Description
                    $first = trim((string) ($cells[0]->getValue() ?? ''));
                    $second = isset($cells[1]) ? trim((string) ($cells[1]->getValue() ?? '')) : '';

                    $isFormatA = $second !== ''; // assume second cell holds Name when present

                    if ($isFormatA) {
                        $code = $first;
                        $name = $second;
                        $category = isset($cells[2]) ? trim((string) ($cells[2]->getValue() ?? '')) : '';
                        $price = isset($cells[3]) ? (float) ($cells[3]->getValue() ?? 0) : 0;
                        $costPrice = isset($cells[4]) ? (float) ($cells[4]->getValue() ?? 0) : 0;
                        $stock = isset($cells[5]) ? (int) ($cells[5]->getValue() ?? 0) : 0;
                        $unit = isset($cells[6]) ? trim((string) ($cells[6]->getValue() ?? 'pcs')) : 'pcs';
                        $description = isset($cells[7]) ? trim((string) ($cells[7]->getValue() ?? '')) : '';
                    } else {
                        $code = '';
                        $name = $first;
                        $category = isset($cells[1]) ? trim((string) ($cells[1]->getValue() ?? '')) : '';
                        $price = isset($cells[2]) ? (float) ($cells[2]->getValue() ?? 0) : 0;
                        $costPrice = isset($cells[3]) ? (float) ($cells[3]->getValue() ?? 0) : 0;
                        $stock = isset($cells[4]) ? (int) ($cells[4]->getValue() ?? 0) : 0;
                        $unit = isset($cells[5]) ? trim((string) ($cells[5]->getValue() ?? 'pcs')) : 'pcs';
                        $description = isset($cells[6]) ? trim((string) ($cells[6]->getValue() ?? '')) : '';
                    }

                    // Minimal requirement: name
                    if ($name === '') {
                        continue;
                    }

                    // Generate code if missing
                    if ($code === '') {
                        $base = strtoupper(\Illuminate\Support\Str::slug($name, '_'));
                        $suffix = substr(uniqid('', true), -6);
                        $code = $base ? ($base.'_'.$suffix) : ('ITEM_'.$suffix);
                        while (\App\Models\AtkProduct::where('code', $code)->exists()) {
                            $suffix = substr(uniqid('', true), -6);
                            $code = $base ? ($base.'_'.$suffix) : ('ITEM_'.$suffix);
                        }
                    }

                    AtkProduct::updateOrCreate(
                        ['code' => $code],
                        [
                            'name' => $name ?: 'Unknown',
                            'category' => $category ?: null,
                            'price' => $price,
                            'cost_price' => $costPrice,
                            'stock' => $stock,
                            'unit' => $unit ?: 'pcs',
                            'description' => $description ?: null,
                        ]
                    );
                    $count++;
                }
            }

            $reader->close();

            return redirect()->route('atk.products.index')->with('success', __("$count products imported successfully."));
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => __('Import failed. Please ensure the file is .xlsx and follows the template (Code, Name, Category, Price, Cost Price, Stock, Unit, Description).')]);
        }
    }

    public function index(Request $request)
    {
        $categories = ['ATK', 'JASA POTOCOPY', 'JASA TRANSFER BANK'];
        $query = AtkProduct::query();
        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }
        $per = $request->get('per_page', '10');
        if ($per === 'all') {
            $perPage = max(1, (int) $query->count());
        } else {
            $perPage = (int) $per;
            if (! in_array($perPage, [10, 50, 100], true)) {
                $perPage = 10;
            }
        }
        $products = $query->latest()->paginate($perPage)->appends($request->query());

        return view('atk.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        return view('atk.products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:atk_products,code',
            'category' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'unit' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if (empty($validated['code'])) {
            $base = strtoupper(\Illuminate\Support\Str::slug($validated['name'], '_'));
            $suffix = substr(uniqid('', true), -6);
            $code = $base ? ($base.'_'.$suffix) : ('ITEM_'.$suffix);
            // ensure unique
            while (\App\Models\AtkProduct::where('code', $code)->exists()) {
                $suffix = substr(uniqid('', true), -6);
                $code = $base ? ($base.'_'.$suffix) : ('ITEM_'.$suffix);
            }
            $validated['code'] = $code;
        }
        if (empty($validated['unit'])) {
            $validated['unit'] = 'pcs';
        }

        $validated['price'] = $validated['price'] ?? 0;
        $validated['cost_price'] = $validated['cost_price'] ?? 0;
        $validated['stock'] = $validated['stock'] ?? 0;

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

    public function show(AtkProduct $product)
    {
        return redirect()->route('atk.products.edit', $product);
    }

    public function update(Request $request, AtkProduct $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:atk_products,code,'.$product->id,
            'category' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'unit' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if (array_key_exists('code', $validated) && empty($validated['code'])) {
            // keep existing code if user cleared it
            $validated['code'] = $product->code;
        }
        $validated['price'] = $validated['price'] ?? $product->price ?? 0;
        $validated['cost_price'] = $validated['cost_price'] ?? $product->cost_price ?? 0;
        $validated['stock'] = $validated['stock'] ?? $product->stock ?? 0;
        if (empty($validated['unit'])) {
            $validated['unit'] = 'pcs';
        }

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

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return redirect()
                ->route('atk.products.index')
                ->withErrors(['ids' => __('No products selected.')]);
        }

        \DB::transaction(function () use ($ids) {

            // Ambil hanya kolom yang diperlukan
            $products = AtkProduct::whereIn('id', $ids)
                ->select('id', 'image')
                ->get();

            // Kumpulkan file image
            $images = $products
                ->pluck('image')
                ->filter()
                ->toArray();

            if (! empty($images)) {
                \Storage::disk('public')->delete($images);
            }

            // Single query delete
            AtkProduct::whereIn('id', $ids)->delete();
        });

        return redirect()
            ->route('atk.products.index')
            ->with('success', __('Selected products deleted successfully.'));
    }
}
