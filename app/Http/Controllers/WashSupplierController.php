<?php

namespace App\Http\Controllers;

use App\Models\WashSupplier;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WashSupplierController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:wash.supplier.view', only: ['index', 'show']),
            new Middleware('permission:wash.supplier.manage', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function index()
    {
        $suppliers = WashSupplier::orderBy('name')->paginate(20);
        return view('wash.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('wash.suppliers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'pic' => 'nullable|string|max:255',
        ]);

        WashSupplier::create([
            'code' => 'SUP-' . str_pad(WashSupplier::max('id') + 1, 4, '0', STR_PAD_LEFT),
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'pic' => $request->pic,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('wash.suppliers.index')->with('success', 'Supplier berhasil ditambahkan!');
    }

    public function show(WashSupplier $supplier)
    {
        $stockItems = $supplier->stockItems()->orderBy('name')->get();
        return view('wash.suppliers.show', compact('supplier', 'stockItems'));
    }

    public function edit(WashSupplier $supplier)
    {
        return view('wash.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, WashSupplier $supplier)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'pic' => 'nullable|string|max:255',
        ]);

        $supplier->update([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'pic' => $request->pic,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('wash.suppliers.index')->with('success', 'Supplier berhasil diupdate!');
    }

    public function destroy(WashSupplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('wash.suppliers.index')->with('success', 'Supplier berhasil dihapus!');
    }
}
