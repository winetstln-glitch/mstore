<?php

namespace App\Http\Controllers;

use App\Models\SalaryAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalaryAdjustmentController extends Controller
{
    public function store(Request $request)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
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

        return back()->with('success', __(ucfirst($request->type) . ' added successfully.'));
    }

    public function destroy(SalaryAdjustment $salaryAdjustment)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized');
        }

        if ($salaryAdjustment->status === 'processed') {
            return back()->with('error', __('Cannot delete processed adjustment.'));
        }

        $salaryAdjustment->delete();

        return back()->with('success', __('Adjustment deleted successfully.'));
    }
}
