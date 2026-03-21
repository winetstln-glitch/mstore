<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PackageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:package.view', only: ['index']),
            new Middleware('permission:package.create', only: ['create', 'store']),
            new Middleware('permission:package.edit', only: ['edit', 'update']),
            new Middleware('permission:package.delete', only: ['destroy']),
        ];
    }

    public function index()
    {
        $packages = Package::orderBy('name')->get();

        return view('packages.index', compact('packages'));
    }

    public function create()
    {
        return view('packages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'package_type' => 'required|in:pppoe,hotspot',
            'price' => 'required|integer|min:0',
            'speed' => 'nullable|string|max:100',
            'devices_limit_mode' => 'required|in:limited,unlimited',
            'devices_limit' => 'nullable|integer|min:1|required_if:devices_limit_mode,limited',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['devices_limit'] = $validated['devices_limit_mode'] === 'limited'
            ? (int) ($validated['devices_limit'] ?? 0)
            : null;
        unset($validated['devices_limit_mode']);

        Package::create($validated);

        return redirect()->route('packages.index')->with('success', __('Package created successfully.'));
    }

    public function edit(Package $package)
    {
        return view('packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'package_type' => 'required|in:pppoe,hotspot',
            'price' => 'required|integer|min:0',
            'speed' => 'nullable|string|max:100',
            'devices_limit_mode' => 'required|in:limited,unlimited',
            'devices_limit' => 'nullable|integer|min:1|required_if:devices_limit_mode,limited',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['devices_limit'] = $validated['devices_limit_mode'] === 'limited'
            ? (int) ($validated['devices_limit'] ?? 0)
            : null;
        unset($validated['devices_limit_mode']);

        $package->update($validated);

        return redirect()->route('packages.index')->with('success', __('Package updated successfully.'));
    }

    public function destroy(Package $package)
    {
        $package->delete();

        return redirect()->route('packages.index')->with('success', __('Package deleted successfully.'));
    }
}
