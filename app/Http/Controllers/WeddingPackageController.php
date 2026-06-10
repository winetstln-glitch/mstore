<?php

namespace App\Http\Controllers;

use App\Models\WeddingPackage;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WeddingPackageController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:wedding.view', only: ['index']),
            new Middleware('permission:wedding.manage', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function index()
    {
        $packages = WeddingPackage::query()->latest()->paginate(20);
        return view('wedding.packages.index', compact('packages'));
    }

    public function create()
    {
        return view('wedding.packages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'facilities_text' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['facilities'] = array_values(array_filter(array_map('trim', preg_split("/\r\n|\r|\n/", (string) ($validated['facilities_text'] ?? '')))));
        unset($validated['facilities_text']);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('wedding/packages', 'public');
        }

        $package = WeddingPackage::create($validated);
        $this->auditLogService->logAction('wedding.package.created', $package, [], $package->toArray());

        return redirect()->route('wedding.packages.index')->with('success', 'Paket berhasil dibuat.');
    }

    public function edit(WeddingPackage $package)
    {
        return view('wedding.packages.edit', compact('package'));
    }

    public function update(Request $request, WeddingPackage $package)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'facilities_text' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['facilities'] = array_values(array_filter(array_map('trim', preg_split("/\r\n|\r|\n/", (string) ($validated['facilities_text'] ?? '')))));
        unset($validated['facilities_text']);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('wedding/packages', 'public');
        }

        $old = $package->toArray();
        $package->update($validated);
        $this->auditLogService->logAction('wedding.package.updated', $package, $old, $package->toArray());

        return redirect()->route('wedding.packages.index')->with('success', 'Paket berhasil diperbarui.');
    }

    public function destroy(WeddingPackage $package)
    {
        $old = $package->toArray();
        $package->delete();
        $this->auditLogService->logAction('wedding.package.deleted', $package, $old, []);

        return redirect()->route('wedding.packages.index')->with('success', 'Paket berhasil dihapus.');
    }
}
