<?php

namespace App\Http\Controllers;

use App\Models\WashShift;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WashShiftController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:wash.shift.view', only: ['index', 'show']),
            new Middleware('permission:wash.shift.manage', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function index()
    {
        $shifts = WashShift::orderBy('name')->paginate(20);
        return view('wash.shifts.index', compact('shifts'));
    }

    public function create()
    {
        return view('wash.shifts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'description' => 'nullable|string',
        ]);

        WashShift::create([
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('wash.shifts.index')->with('success', 'Shift berhasil ditambahkan!');
    }

    public function show(WashShift $shift)
    {
        $sessions = $shift->sessions()->latest('opened_at')->paginate(20);
        return view('wash.shifts.show', compact('shift', 'sessions'));
    }

    public function edit(WashShift $shift)
    {
        return view('wash.shifts.edit', compact('shift'));
    }

    public function update(Request $request, WashShift $shift)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'description' => 'nullable|string',
        ]);

        $shift->update([
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('wash.shifts.index')->with('success', 'Shift berhasil diupdate!');
    }

    public function destroy(WashShift $shift)
    {
        $shift->delete();
        return redirect()->route('wash.shifts.index')->with('success', 'Shift berhasil dihapus!');
    }
}
