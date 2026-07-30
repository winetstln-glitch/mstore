<?php

namespace App\Http\Controllers;

use App\Models\HotspotProfile;
use App\Models\Router;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class HotspotProfileController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:hotspot.profile.view', only: ['index']),
            new Middleware('permission:hotspot.profile.manage', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function index(Request $request)
    {
        $query = HotspotProfile::query()->with(['router']);

        if ($request->filled('type')) {
            $type = $request->input('type');
            if ($type === 'voucher') {
                $query->where('package_type', 'voucher');
            } elseif (in_array($type, ['rumahan', 'residential', 'home'], true)) {
                $query->whereIn('package_type', ['residential', 'home', 'rumahan']);
            } elseif (in_array($type, ['member', 'membership'], true)) {
                $query->whereIn('package_type', ['member', 'membership']);
            } elseif ($type === 'pppoe') {
                $query->where('package_type', 'pppoe');
            }
        }

        $packages = $query->orderBy('sort_order')->orderBy('id')->paginate(50);
        $routers = Router::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('hotspot.profiles.index', compact('packages', 'routers'));
    }

    public function create()
    {
        $routers = Router::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('hotspot.profiles.create', compact('routers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'mikrotik_profile_name' => ['required', 'string', 'max:64'],
            'package_type' => ['required', 'in:voucher,member,membership,residential,home,rumahan,pppoe'],
            'rate_limit_mbps' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'shared_users' => ['nullable', 'integer', 'min:1'],
            'limit_uptime' => ['nullable', 'string', 'max:32'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'quota_mb' => ['nullable', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:65535'],
            'color_badge' => ['nullable', 'string', 'max:16'],
            'router_id' => ['nullable', 'exists:routers,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['shared_users'] = (int) ($validated['shared_users'] ?? 1);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        $pkg = HotspotProfile::create($validated);
        $this->auditLogService->logAction('hotspot.profile.created', $pkg, [], $pkg->toArray());

        return redirect()->route('hotspot.profiles.index')->with('success', 'Paket Hotspot berhasil dibuat.');
    }

    public function edit(HotspotProfile $profile)
    {
        $routers = Router::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $package = $profile;

        return view('hotspot.profiles.edit', compact('package', 'routers'));
    }

    public function update(Request $request, HotspotProfile $profile)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'mikrotik_profile_name' => ['required', 'string', 'max:64'],
            'package_type' => ['required', 'in:voucher,member,membership,residential,home,rumahan,pppoe'],
            'rate_limit_mbps' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'shared_users' => ['nullable', 'integer', 'min:1'],
            'limit_uptime' => ['nullable', 'string', 'max:32'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'quota_mb' => ['nullable', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:65535'],
            'color_badge' => ['nullable', 'string', 'max:16'],
            'router_id' => ['nullable', 'exists:routers,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['shared_users'] = (int) ($validated['shared_users'] ?? 1);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        $old = $profile->toArray();
        $profile->update($validated);
        $this->auditLogService->logAction('hotspot.profile.updated', $profile, $old, $profile->toArray());

        return redirect()->route('hotspot.profiles.index')->with('success', 'Paket Hotspot berhasil diperbarui.');
    }

    public function destroy(HotspotProfile $profile)
    {
        $old = $profile->toArray();
        $profile->delete();
        $this->auditLogService->logAction('hotspot.profile.deleted', $profile, $old, []);

        return redirect()->route('hotspot.profiles.index')->with('success', 'Paket Hotspot berhasil dihapus.');
    }
}
