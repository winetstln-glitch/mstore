<?php

namespace App\Http\Controllers;

use App\Models\CctvPackage;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CctvPackageController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:cctv.view', only: ['index']),
            new Middleware('permission:cctv.manage', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function index()
    {
        $packages = CctvPackage::query()->latest()->paginate(20);
        return view('cctv.packages.index', compact('packages'));
    }

    public function create()
    {
        return view('cctv.packages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'camera_count' => ['nullable', 'integer', 'min:0'],
            'dvr_nvr' => ['nullable', 'string', 'max:255'],
            'hdd' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'warranty_months' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $package = CctvPackage::create($validated);
        $this->auditLogService->logAction('cctv.package.created', $package, [], $package->toArray());

        return redirect()->route('cctv.packages.index')->with('success', 'Paket berhasil dibuat.');
    }

    public function edit(CctvPackage $package)
    {
        return view('cctv.packages.edit', compact('package'));
    }

    public function update(Request $request, CctvPackage $package)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'camera_count' => ['nullable', 'integer', 'min:0'],
            'dvr_nvr' => ['nullable', 'string', 'max:255'],
            'hdd' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'warranty_months' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $old = $package->toArray();
        $package->update($validated);
        $this->auditLogService->logAction('cctv.package.updated', $package, $old, $package->toArray());

        return redirect()->route('cctv.packages.index')->with('success', 'Paket berhasil diperbarui.');
    }

    public function destroy(CctvPackage $package)
    {
        $old = $package->toArray();
        $package->delete();
        $this->auditLogService->logAction('cctv.package.deleted', $package, $old, []);

        return redirect()->route('cctv.packages.index')->with('success', 'Paket berhasil dihapus.');
    }
}

