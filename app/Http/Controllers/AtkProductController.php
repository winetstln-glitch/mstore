<?php

namespace App\Http\Controllers;

use App\Models\AtkProduct;
use Illuminate\Http\Request;

class AtkProductController extends Controller
{
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
