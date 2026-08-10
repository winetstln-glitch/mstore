<?php

namespace App\Http\Controllers;

use App\Models\Bandwidth;
use Illuminate\Http\Request;

class BandwidthController extends Controller
{
    /**
     * Tampilkan daftar bandwidth yang tersedia.
     */
    public function index()
    {
        $bandwidths = Bandwidth::orderBy('name')->get();

        if (request()->expectsJson()) {
            return response()->json($bandwidths);
        }

        return view('bandwidth.index', compact('bandwidths'));
    }

    /**
     * Simpan bandwidth baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:100|unique:bandwidths,name',
            'rate'  => 'nullable|string|max:50',
        ]);

        $bandwidth = Bandwidth::create($validated);

        return response()->json(['success' => true, 'data' => $bandwidth], 201);
    }

    /**
     * Hapus bandwidth.
     */
    public function destroy(Bandwidth $bandwidth)
    {
        $bandwidth->delete();

        return response()->json(['success' => true]);
    }
}
