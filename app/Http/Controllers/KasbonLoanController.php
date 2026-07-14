<?php

namespace App\Http\Controllers;

use App\Models\KasbonInstallment;
use App\Models\KasbonLoan;
use App\Models\Role;
use App\Models\SalaryAdjustment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KasbonLoanController extends Controller
{
    public function store(Request $request)
    {
        if (! Auth::user()->hasAnyRole(['admin', 'staf-keuangan', 'staf keuangan', 'finance', Role::HRD_MANAGER, 'hrd manager', 'hrd', 'manager hrd', Role::DIREKTUR, 'direktur', 'owner', 'owner pendiri'])) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'principal_amount' => 'required|numeric|min:1',
            'start_date' => 'required|date',
            'tenor_months' => 'nullable|integer|min:1',
            'monthly_installment' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        KasbonLoan::create([
            'user_id' => $request->user_id,
            'principal_amount' => $request->principal_amount,
            'start_date' => $request->start_date,
            'tenor_months' => $request->tenor_months,
            'monthly_installment' => $request->monthly_installment,
            'description' => $request->description,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', __('Kasbon angsuran added successfully.'));
    }

    public function update(Request $request, KasbonLoan $kasbonLoan)
    {
        if (! Auth::user()->hasAnyRole(['admin', 'staf-keuangan', 'staf keuangan', 'finance', Role::HRD_MANAGER, 'hrd manager', 'hrd', 'manager hrd', Role::DIREKTUR, 'direktur', 'owner', 'owner pendiri'])) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'principal_amount' => 'required|numeric|min:1',
            'start_date' => 'required|date',
            'tenor_months' => 'nullable|integer|min:1',
            'monthly_installment' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $kasbonLoan->update([
            'principal_amount' => $request->principal_amount,
            'start_date' => $request->start_date,
            'tenor_months' => $request->tenor_months,
            'monthly_installment' => $request->monthly_installment,
            'description' => $request->description,
        ]);
        $kasbonLoan->checkAndUpdateStatus();

        return back()->with('success', __('Kasbon angsuran updated successfully.'));
    }

    public function destroy(KasbonLoan $kasbonLoan)
    {
        if (! Auth::user()->hasAnyRole(['admin', 'staf-keuangan', 'staf keuangan', 'finance', Role::HRD_MANAGER, 'hrd manager', 'hrd', 'manager hrd', Role::DIREKTUR, 'direktur', 'owner', 'owner pendiri'])) {
            abort(403, 'Unauthorized');
        }

        $kasbonLoan->delete();

        return back()->with('success', __('Kasbon angsuran deleted successfully.'));
    }

    public function storeInstallment(Request $request, KasbonLoan $kasbonLoan)
    {
        if (! Auth::user()->hasAnyRole(['admin', 'staf-keuangan', 'staf keuangan', 'finance', Role::HRD_MANAGER, 'hrd manager', 'hrd', 'manager hrd', Role::DIREKTUR, 'direktur', 'owner', 'owner pendiri'])) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'potong_gaji' => 'nullable|boolean',
        ]);

        $installment = $kasbonLoan->installments()->create([
            'amount' => $request->amount,
            'date' => $request->date,
            'description' => $request->description,
            'created_by' => Auth::id(),
        ]);

        if ($request->potong_gaji) {
            $salaryAdjustment = SalaryAdjustment::create([
                'user_id' => $kasbonLoan->user_id,
                'type' => 'kasbon',
                'amount' => $request->amount,
                'date' => $request->date,
                'description' => 'Cicilan kasbon angsuran #' . $kasbonLoan->id . ': ' . ($request->description ?? '-'),
            ]);
            $installment->update(['salary_adjustment_id' => $salaryAdjustment->id]);
        }

        $kasbonLoan->checkAndUpdateStatus();

        return back()->with('success', __('Cicilan added successfully.'));
    }

    public function destroyInstallment(KasbonLoan $kasbonLoan, KasbonInstallment $installment)
    {
        if (! Auth::user()->hasAnyRole(['admin', 'staf-keuangan', 'staf keuangan', 'finance', Role::HRD_MANAGER, 'hrd manager', 'hrd', 'manager hrd', Role::DIREKTUR, 'direktur', 'owner', 'owner pendiri'])) {
            abort(403, 'Unauthorized');
        }

        if ($installment->salary_adjustment_id) {
            $salaryAdjustment = SalaryAdjustment::find($installment->salary_adjustment_id);
            if ($salaryAdjustment && $salaryAdjustment->status !== 'processed') {
                $salaryAdjustment->delete();
            }
        }

        $installment->delete();
        $kasbonLoan->checkAndUpdateStatus();

        return back()->with('success', __('Cicilan deleted successfully.'));
    }
}
