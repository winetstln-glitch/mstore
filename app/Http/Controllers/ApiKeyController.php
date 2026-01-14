<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public function index()
    {
        $keys = ApiKey::latest()->paginate(10);
        return view('settings.apikeys.index', compact('keys'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        ApiKey::create([
            'name' => $request->name,
            'key' => Str::random(32),
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'API Key created successfully.');
    }

    public function destroy(ApiKey $apiKey)
    {
        $apiKey->delete();
        return redirect()->back()->with('success', 'API Key deleted successfully.');
    }

    public function toggle(ApiKey $apiKey)
    {
        $apiKey->update(['is_active' => !$apiKey->is_active]);
        return redirect()->back()->with('success', 'API Key status updated.');
    }
}
