<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyBranch;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CompanyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:company.view', only: ['index', 'show']),
            new Middleware('permission:company.manage', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function index()
    {
        $companies = Company::with('branches')->get();
        return view('company.index', compact('companies'));
    }

    public function create()
    {
        return view('company.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:companies,code',
            'tax_id' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'address' => 'nullable|string',
        ]);

        // Set default values
        $validated['currency'] = $validated['currency'] ?? 'IDR';
        $validated['country'] = $validated['country'] ?? 'ID';
        $validated['is_active'] = true;

        Company::create($validated);

        return redirect()->route('companies.index')->with('success', 'Perusahaan berhasil dibuat');
    }

    public function show(Company $company)
    {
        return view('company.show', compact('company'));
    }

    public function edit(Company $company)
    {
        return view('company.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:companies,code,'.$company->id,
            'tax_id' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        // Set is_active to false if not present
        $validated['is_active'] = $request->has('is_active');

        $company->update($validated);

        return redirect()->route('companies.index')->with('success', 'Perusahaan berhasil diperbarui');
    }

    public function destroy(Company $company)
    {
        $company->delete();
        return redirect()->route('companies.index')->with('success', 'Perusahaan berhasil dihapus');
    }
}