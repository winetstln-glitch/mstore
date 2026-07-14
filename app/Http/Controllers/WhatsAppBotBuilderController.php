<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WhatsAppBotBuilderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:chat.manage'),
        ];
    }

    public function index()
    {
        $menus = WhatsAppMenu::orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('whatsapp.builder', compact('menus'));
    }

    public function create()
    {
        return view('whatsapp.builder-create');
    }

    public function store(Request $request)
    {
        $keyword = preg_replace('/\s+/', ' ', trim(Str::lower((string) $request->input('keyword', ''))));
        $type = (string) $request->input('type', 'text');
        $request->merge([
            'keyword' => $keyword,
            'type' => $type,
        ]);

        $request->validate([
            'keyword' => ['required', 'string', 'max:80', 'unique:whatsapp_menus,keyword'],
            'type' => ['required', 'in:text,image,document,button,list'],
            'response_text' => [
                Rule::requiredIf(in_array($type, ['text', 'button', 'list'], true)),
                'nullable',
                'string',
                'max:4000',
            ],
            'file' => [
                Rule::requiredIf(in_array($type, ['image', 'document'], true)),
                'nullable',
                'file',
                'max:10240',
                function ($attribute, $value, $fail) use ($type) {
                    if (!in_array($type, ['image', 'document'], true)) {
                        return;
                    }
                    if (!$value) {
                        return;
                    }
                    $mime = (string) $value->getMimeType();
                    if ($type === 'image') {
                        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                        if (!in_array($mime, $allowed, true)) {
                            $fail('File gambar tidak valid. Gunakan JPG, PNG, WEBP, atau GIF.');
                        }
                        return;
                    }
                    $allowed = [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ];
                    if (!in_array($mime, $allowed, true)) {
                        $fail('File dokumen tidak valid. Gunakan PDF, DOC/DOCX, atau XLS/XLSX.');
                    }
                }
            ],
            'priority' => ['nullable', 'integer', 'min:0'],
            'enable_fuzzy_match' => ['nullable', 'boolean'],
        ]);

        $filePath = null;
        $fileType = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('whatsapp/media', 'public');
            $fileType = $file->getMimeType();
        }

        WhatsAppMenu::create([
            'keyword' => $request->keyword,
            'type' => $request->type,
            'response_text' => $request->response_text,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'priority' => $request->priority ?? 0,
            'enable_fuzzy_match' => $request->boolean('enable_fuzzy_match', true),
            'is_active' => true,
        ]);

        return redirect()->route('whatsapp.builder.index')
            ->with('success', 'Menu WhatsApp berhasil dibuat!');
    }

    public function edit(WhatsAppMenu $menu)
    {
        return view('whatsapp.builder-edit', compact('menu'));
    }

    public function update(Request $request, WhatsAppMenu $menu)
    {
        $keyword = preg_replace('/\s+/', ' ', trim(Str::lower((string) $request->input('keyword', ''))));
        $type = (string) $request->input('type', $menu->type ?? 'text');
        $request->merge([
            'keyword' => $keyword,
            'type' => $type,
        ]);

        $request->validate([
            'keyword' => ['required', 'string', 'max:80', 'unique:whatsapp_menus,keyword,' . $menu->id],
            'type' => ['required', 'in:text,image,document,button,list'],
            'response_text' => [
                Rule::requiredIf(in_array($type, ['text', 'button', 'list'], true)),
                'nullable',
                'string',
                'max:4000',
            ],
            'file' => [
                Rule::requiredIf(in_array($type, ['image', 'document'], true) && empty($menu->file_path)),
                'nullable',
                'file',
                'max:10240',
                function ($attribute, $value, $fail) use ($type) {
                    if (!in_array($type, ['image', 'document'], true)) {
                        return;
                    }
                    if (!$value) {
                        return;
                    }
                    $mime = (string) $value->getMimeType();
                    if ($type === 'image') {
                        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                        if (!in_array($mime, $allowed, true)) {
                            $fail('File gambar tidak valid. Gunakan JPG, PNG, WEBP, atau GIF.');
                        }
                        return;
                    }
                    $allowed = [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ];
                    if (!in_array($mime, $allowed, true)) {
                        $fail('File dokumen tidak valid. Gunakan PDF, DOC/DOCX, atau XLS/XLSX.');
                    }
                }
            ],
            'priority' => ['nullable', 'integer', 'min:0'],
            'enable_fuzzy_match' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $filePath = $menu->file_path;
        $fileType = $menu->file_type;

        if (!in_array($type, ['image', 'document'], true)) {
            if ($menu->file_path && Storage::disk('public')->exists($menu->file_path)) {
                Storage::disk('public')->delete($menu->file_path);
            }
            $filePath = null;
            $fileType = null;
        }

        if ($request->hasFile('file')) {
            if ($menu->file_path && Storage::disk('public')->exists($menu->file_path)) {
                Storage::disk('public')->delete($menu->file_path);
            }

            $file = $request->file('file');
            $filePath = $file->store('whatsapp/media', 'public');
            $fileType = $file->getMimeType();
        }

        $menu->update([
            'keyword' => $request->keyword,
            'type' => $request->type,
            'response_text' => $request->response_text,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'priority' => $request->priority ?? 0,
            'enable_fuzzy_match' => $request->boolean('enable_fuzzy_match', true),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('whatsapp.builder.index')
            ->with('success', 'Menu WhatsApp berhasil diperbarui!');
    }

    public function destroy(WhatsAppMenu $menu)
    {
        if ($menu->file_path && Storage::disk('public')->exists($menu->file_path)) {
            Storage::disk('public')->delete($menu->file_path);
        }

        $menu->delete();

        return redirect()->route('whatsapp.builder.index')
            ->with('success', 'Menu WhatsApp berhasil dihapus!');
    }

    public function importTemplates()
    {
        if (app()->environment('production')) {
            abort(403);
        }

        // Re-run the seeder to import all templates
        \Artisan::call('db:seed', [
            '--class' => 'WhatsAppMenuSeeder',
        ]);

        return redirect()->route('whatsapp.builder.index')
            ->with('success', 'Semua template berhasil diimpor!');
    }
}
