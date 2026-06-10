<?php

namespace App\Http\Controllers;

use App\Models\WeddingGalleryItem;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WeddingGalleryController extends Controller implements HasMiddleware
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
        $items = WeddingGalleryItem::query()
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate(20);

        return view('wedding.gallery.index', compact('items'));
    }

    public function create()
    {
        return view('wedding.gallery.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $path = $request->file('image')->store('wedding/gallery', 'public');

        $item = WeddingGalleryItem::create([
            'image_path' => $path,
            'caption' => trim((string) ($validated['caption'] ?? '')) ?: null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->auditLogService->logAction('wedding.gallery.created', $item, [], $item->toArray());

        return redirect()->route('wedding.gallery.index')->with('success', 'Foto galeri berhasil ditambahkan.');
    }

    public function edit(WeddingGalleryItem $item)
    {
        return view('wedding.gallery.edit', compact('item'));
    }

    public function update(Request $request, WeddingGalleryItem $item)
    {
        $validated = $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $old = $item->toArray();

        $payload = [
            'caption' => trim((string) ($validated['caption'] ?? '')) ?: null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];

        if ($request->hasFile('image')) {
            $payload['image_path'] = $request->file('image')->store('wedding/gallery', 'public');
        }

        $item->update($payload);
        $this->auditLogService->logAction('wedding.gallery.updated', $item, $old, $item->toArray());

        return redirect()->route('wedding.gallery.index')->with('success', 'Foto galeri berhasil diperbarui.');
    }

    public function destroy(WeddingGalleryItem $item)
    {
        $old = $item->toArray();
        $item->delete();
        $this->auditLogService->logAction('wedding.gallery.deleted', $item, $old, []);

        return redirect()->route('wedding.gallery.index')->with('success', 'Foto galeri berhasil dihapus.');
    }
}

