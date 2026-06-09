<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\SalaryAdjustment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalaryAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::user()->hasRole(Role::ADMIN) && ! Auth::user()->hasRole(Role::FINANCE) && ! Auth::user()->hasRole(Role::HRD_MANAGER)) {
            abort(403, 'Unauthorized');
        }

        $query = SalaryAdjustment::with('user')->where('type', 'kasbon');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $adjustments = $query->latest('date')->paginate(15)->withQueryString();

        $users = User::whereHas('role', function ($q) {
            $q->whereIn('name', [Role::TECHNICIAN, Role::ADMIN]);
        })->where('is_active', true)
          ->with('role')
          ->orderBy('name')
          ->get();

        return view('technicians.kasbon.index', compact('adjustments', 'users'));
    }

    public function store(Request $request)
    {
        if (! Auth::user()->hasRole(Role::ADMIN) && ! Auth::user()->hasRole(Role::FINANCE)) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:bonus,kasbon',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        SalaryAdjustment::create($request->all());

        return back()->with('success', __(ucfirst($request->type).' added successfully.'));
    }

    public function destroy(SalaryAdjustment $salaryAdjustment)
    {
        if (! Auth::user()->hasRole(Role::ADMIN) && ! Auth::user()->hasRole(Role::FINANCE)) {
            abort(403, 'Unauthorized');
        }

        if ($salaryAdjustment->status === 'processed') {
            return back()->with('error', __('Cannot delete processed adjustment.'));
        }

        $salaryAdjustment->delete();

        return back()->with('success', __('Adjustment deleted successfully.'));
    }
}
