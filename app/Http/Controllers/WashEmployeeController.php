<?php

namespace App\Http\Controllers;

use App\Models\WashEmployee;
use App\Models\User;
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
        // 1. Create an empty instance so the form has a model to bind to
        $employee = new WashEmployee();
        
        // 2. Get users for the dropdown (like in the edit method)
        $users = User::all();

        // 3. Pass both variables to the view
        return view('wash.employees.create', compact('employee', 'users'));
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
        $users = User::all();
        return view('wash.employees.edit', compact('employee', 'users'));
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