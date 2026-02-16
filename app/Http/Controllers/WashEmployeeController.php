<?php

namespace App\Http\Controllers;

use App\Models\WashEmployee;
use Illuminate\Http\Request;

class WashEmployeeController extends Controller
{
    public function index()
    {
        $employees = WashEmployee::all();
        return view('wash.employees.index', compact('employees'));
    }

    public function create()
    {
        return view('wash.employees.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        WashEmployee::create($request->all());
        return redirect()->route('wash.employees.index')->with('success', 'Employee created successfully.');
    }

    public function edit(WashEmployee $employee)
    {
        return view('wash.employees.edit', compact('employee'));
    }

    public function update(Request $request, WashEmployee $employee)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $employee->update($request->all());
        return redirect()->route('wash.employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(WashEmployee $employee)
    {
        $employee->delete();
        return redirect()->route('wash.employees.index')->with('success', 'Employee deleted successfully.');
    }
}
