<?php

namespace App\Http\Controllers;

use App\Models\HotspotProfile;
use App\Models\Router;
use App\Models\WashMemberPackage;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WashMemberPackageController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:wash.package.view', only: ['index']),
            new Middleware('permission:wash.package.manage', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function index(Request $request)
    {
        $query = WashMemberPackage::query()->with(['hotspotProfile', 'router']);

        if ($request->filled('type')) {
            $t = $request->input('type');
            if (in_array($t, ['wash', 'wifi', 'both'], true)) {
                $query->where('type', $t);
            }
        }

        $packages = $query->orderBy('sort_order')->orderBy('id')->paginate(50);

        return view('wash.member-packages.index', compact('packages'));
    }

    public function create()
    {
        $routers = Router::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $hotspotProfiles = HotspotProfile::query()->active()->orderBy('name')->get(['id', 'name', 'price']);

        return view('wash.member-packages.create', compact('routers', 'hotspotProfiles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'code' => ['required', 'string', 'max:32', 'unique:wash_member_packages,code'],
            'type' => ['required', 'in:wash,wifi,both'],
            'network_type' => ['nullable', 'in:pppoe,hotspot'],
            'hotspot_profile_id' => ['nullable', 'exists:hotspot_profiles,id'],
            'pppoe_profile' => ['nullable', 'string', 'max:64'],
            'rate_limit_mbps' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'daily_wifi_minutes' => ['nullable', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:65535'],
            'benefits' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'router_id' => ['nullable', 'exists:routers,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['benefits'] = ! empty($validated['benefits'])
            ? array_values(array_filter($validated['benefits'], fn ($b) => ! empty($b)))
            : null;
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        $pkg = WashMemberPackage::create($validated);
        $this->auditLogService->logAction('wash.member_package.created', $pkg, [], $pkg->toArray());

        return redirect()->route('wash.member-packages.index')->with('success', 'Paket Member berhasil dibuat.');
    }

    public function edit(WashMemberPackage $package)
    {
        $routers = Router::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $hotspotProfiles = HotspotProfile::orderBy('name')->get(['id', 'name', 'price']);

        return view('wash.member-packages.edit', compact('package', 'routers', 'hotspotProfiles'));
    }

    public function update(Request $request, WashMemberPackage $package)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'code' => ['required', 'string', 'max:32', 'unique:wash_member_packages,code,'.$package->id],
            'type' => ['required', 'in:wash,wifi,both'],
            'network_type' => ['nullable', 'in:pppoe,hotspot'],
            'hotspot_profile_id' => ['nullable', 'exists:hotspot_profiles,id'],
            'pppoe_profile' => ['nullable', 'string', 'max:64'],
            'rate_limit_mbps' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'daily_wifi_minutes' => ['nullable', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:65535'],
            'benefits' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'router_id' => ['nullable', 'exists:routers,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['benefits'] = ! empty($validated['benefits'])
            ? array_values(array_filter($validated['benefits'], fn ($b) => ! empty($b)))
            : null;
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        $old = $package->toArray();
        $package->update($validated);
        $this->auditLogService->logAction('wash.member_package.updated', $package, $old, $package->toArray());

        return redirect()->route('wash.member-packages.index')->with('success', 'Paket Member berhasil diperbarui.');
    }

    public function destroy(WashMemberPackage $package)
    {
        $old = $package->toArray();
        $package->delete();
        $this->auditLogService->logAction('wash.member_package.deleted', $package, $old, []);

        return redirect()->route('wash.member-packages.index')->with('success', 'Paket Member berhasil dihapus.');
    }
}
