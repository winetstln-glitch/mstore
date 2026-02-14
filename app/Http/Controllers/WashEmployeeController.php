<?php

namespace App\Http\Controllers;

use App\Models\WashEmployee;
use Illuminate\Http\Request;
use App\Models\User;

class WashEmployeeController extends Controller
{
    public function index()
    {
        $employees = WashEmployee::with('user')->get();
        return view('wash.employees.index', compact('employees'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();
        return view('wash.employees.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'user_id' => 'nullable|exists:users,id',
        ]);
        WashEmployee::create($request->only(['name','phone','status','user_id']));
        return redirect()->route('wash.employees.index')->with('success', 'Employee created successfully.');
    }

    public function edit(WashEmployee $employee)
    {
        $users = User::orderBy('name')->get();
        return view('wash.employees.edit', compact('employee','users'));
    }

    public function update(Request $request, WashEmployee $employee)
    {
        $request->validate([
            'name' => 'required',
            'user_id' => 'nullable|exists:users,id',
        ]);
        $employee->update($request->only(['name','phone','status','user_id']));
        return redirect()->route('wash.employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(WashEmployee $employee)
    {
        $employee->delete();
        return redirect()->route('wash.employees.index')->with('success', 'Employee deleted successfully.');
    }
}
