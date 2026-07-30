<?php

namespace App\Http\Controllers;

use App\Models\HotspotBanner;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class HotspotBannerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('permission:hotspot.view', only: ['index']),
            new Middleware('permission:hotspot.create', only: ['create', 'store']),
            new Middleware('permission:hotspot.edit', only: ['edit', 'update', 'toggle']),
            new Middleware('permission:hotspot.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $pageTarget = trim((string) $request->query('page_target', ''));
        $status = trim((string) $request->query('status', ''));

        $banners = HotspotBanner::query()
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%' . $search . '%')
                ->orWhere('subtitle', 'like', '%' . $search . '%'))
            ->when($pageTarget !== '', fn ($q) => $q->where('page_target', $pageTarget))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('hotspot.banners.index', compact('banners', 'search', 'pageTarget', 'status'));
    }

    public function create()
    {
        $pageTargets = $this->pageTargets();
        return view('hotspot.banners.create', compact('pageTargets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => ['required', 'image', 'max:5120'],
            'mobile_image' => ['nullable', 'image', 'max:5120'],
            'cta_text' => ['nullable', 'string', 'max:80'],
            'url_cta' => ['nullable', 'string', 'max:500', 'url'],
            'open_new_tab' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active' => ['boolean'],
            'page_target' => ['required', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $imagePath = $request->file('image')->store('hotspot-banners', 'public');

        $mobileImagePath = null;
        if ($request->hasFile('mobile_image')) {
            $mobileImagePath = $request->file('mobile_image')->store('hotspot-banners', 'public');
        }

        HotspotBanner::create([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'image_path' => $imagePath,
            'mobile_image_path' => $mobileImagePath,
            'cta_text' => $validated['cta_text'] ?? null,
            'url_cta' => $validated['url_cta'] ?? null,
            'open_new_tab' => (bool) ($validated['open_new_tab'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'page_target' => $validated['page_target'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('hotspot.banners.index')
            ->with('success', 'Banner hotspot berhasil dibuat.');
    }

    public function edit(HotspotBanner $banner)
    {
        $pageTargets = $this->pageTargets();
        return view('hotspot.banners.edit', compact('banner', 'pageTargets'));
    }

    public function update(Request $request, HotspotBanner $banner)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:5120'],
            'mobile_image' => ['nullable', 'image', 'max:5120'],
            'cta_text' => ['nullable', 'string', 'max:80'],
            'url_cta' => ['nullable', 'string', 'max:500', 'url'],
            'open_new_tab' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active' => ['boolean'],
            'page_target' => ['required', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $data = [
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'cta_text' => $validated['cta_text'] ?? null,
            'url_cta' => $validated['url_cta'] ?? null,
            'open_new_tab' => (bool) ($validated['open_new_tab'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'page_target' => $validated['page_target'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'updated_by' => auth()->id(),
        ];

        if ($request->hasFile('image')) {
            if ($banner->image_path && Storage::disk('public')->exists($banner->image_path)) {
                Storage::disk('public')->delete($banner->image_path);
            }
            $data['image_path'] = $request->file('image')->store('hotspot-banners', 'public');
        }

        if ($request->hasFile('mobile_image')) {
            if ($banner->mobile_image_path && Storage::disk('public')->exists($banner->mobile_image_path)) {
                Storage::disk('public')->delete($banner->mobile_image_path);
            }
            $data['mobile_image_path'] = $request->file('mobile_image')->store('hotspot-banners', 'public');
        }

        $banner->update($data);

        return redirect()->route('hotspot.banners.index')
            ->with('success', 'Banner hotspot berhasil diperbarui.');
    }

    public function destroy(HotspotBanner $banner)
    {
        if ($banner->image_path && Storage::disk('public')->exists($banner->image_path)) {
            Storage::disk('public')->delete($banner->image_path);
        }
        if ($banner->mobile_image_path && Storage::disk('public')->exists($banner->mobile_image_path)) {
            Storage::disk('public')->delete($banner->mobile_image_path);
        }

        $banner->delete();

        return redirect()->route('hotspot.banners.index')
            ->with('success', 'Banner hotspot berhasil dihapus.');
    }

    public function toggle(HotspotBanner $banner)
    {
        $banner->is_active = !$banner->is_active;
        $banner->updated_by = auth()->id();
        $banner->save();

        return back()->with('success', 'Status banner berhasil diubah.');
    }

    protected function pageTargets(): array
    {
        return [
            'all' => 'Semua Halaman',
            'login' => 'Halaman Login',
            'landing' => 'Halaman Landing',
            'voucher' => 'Halaman Voucher',
            'member' => 'Halaman Member',
            'residential' => 'Halaman Rumahan',
        ];
    }
}
