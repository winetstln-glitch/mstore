<?php

namespace App\Http\Controllers;

use App\Models\KasbonInstallment;
use App\Models\KasbonLoan;
use App\Models\Role;
use App\Models\SalaryAdjustment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalaryAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::user()->hasAnyRole(['admin', 'staf-keuangan', 'staf keuangan', 'finance', Role::HRD_MANAGER, 'hrd manager', 'hrd', 'manager hrd', Role::DIREKTUR, 'direktur', 'owner', 'owner pendiri'])) {
            abort(403, 'Unauthorized');
        }

        // Ambil data kasbon
        $kasbonQuery = SalaryAdjustment::with('user')->where('type', 'kasbon');
        // Ambil data bonus
        $bonusQuery = SalaryAdjustment::with('user')->where('type', 'bonus');

        if ($request->filled('user_id')) {
            $kasbonQuery->where('user_id', $request->user_id);
            $bonusQuery->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $kasbonQuery->where('status', $request->status);
            $bonusQuery->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $kasbonQuery->whereBetween('date', [$request->start_date, $request->end_date]);
            $bonusQuery->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $kasbonAdjustments = $kasbonQuery->latest('date')->paginate(15, ['*'], 'kasbon_page')->withQueryString();
        $bonusAdjustments = $bonusQuery->latest('date')->paginate(15, ['*'], 'bonus_page')->withQueryString();

        $loanQuery = KasbonLoan::query()
            ->with(['user', 'installments' => function ($q) {
                $q->latest('date');
            }])
            ->withSum('installments', 'amount')
            ->latest('start_date');
        
        if ($request->filled('user_id')) {
            $loanQuery->where('user_id', $request->user_id);
        }

        $loans = $loanQuery->get();

        $users = User::whereHas('role', function ($q) {
            $q->whereNotIn('name', ['Direktur', 'Koordinator']);
        })->where('is_active', true)
          ->with('role')
          ->orderBy('name')
          ->get();

        // Recap per teknisi
        $recap = $users->map(function ($user) {
            $totalKasbonBiasa = SalaryAdjustment::where('user_id', $user->id)
                ->where('type', 'kasbon')
                ->where('status', 'pending')
                ->sum('amount');
            
            $totalPinjamanAktif = KasbonLoan::where('user_id', $user->id)
                ->where('status', 'active')
                ->sum('principal_amount');
            
            $totalCicilan = KasbonInstallment::whereHas('kasbonLoan', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->sum('amount');
            
            $sisaPinjaman = KasbonLoan::where('user_id', $user->id)
                ->where('status', 'active')
                ->get()
                ->sum(fn($loan) => $loan->remaining);

            return [
                'user' => $user,
                'total_kasbon_biasa' => (float) $totalKasbonBiasa,
                'total_pinjaman_aktif' => (float) $totalPinjamanAktif,
                'total_cicilan' => (float) $totalCicilan,
                'sisa_pinjaman' => (float) $sisaPinjaman,
            ];
        });

        return view('technicians.kasbon.index', compact('kasbonAdjustments', 'bonusAdjustments', 'loans', 'users', 'recap'));
    }

    public function store(Request $request)
    {
        if (! Auth::user()->hasAnyRole(['admin', 'staf-keuangan', 'staf keuangan', 'finance', Role::HRD_MANAGER, 'hrd manager', 'hrd', 'manager hrd', Role::DIREKTUR, 'direktur', 'owner', 'owner pendiri'])) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:bonus,kasbon',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        SalaryAdjustment::create($request->all());

        return back()->with('success', __(ucfirst($request->type).' added successfully.'));
    }

    public function update(Request $request, SalaryAdjustment $salaryAdjustment)
    {
        if (! Auth::user()->hasAnyRole(['admin', 'staf-keuangan', 'staf keuangan', 'finance', Role::HRD_MANAGER, 'hrd manager', 'hrd', 'manager hrd', Role::DIREKTUR, 'direktur', 'owner', 'owner pendiri'])) {
            abort(403, 'Unauthorized');
        }

        if ($salaryAdjustment->status === 'processed') {
            return back()->with('error', __('Cannot edit processed adjustment.'));
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $salaryAdjustment->update($request->all());

        return back()->with('success', __('Adjustment updated successfully.'));
    }

    public function destroy(SalaryAdjustment $salaryAdjustment)
    {
        if (! Auth::user()->hasAnyRole(['admin', 'staf-keuangan', 'staf keuangan', 'finance', Role::HRD_MANAGER, 'hrd manager', 'hrd', 'manager hrd', Role::DIREKTUR, 'direktur', 'owner', 'owner pendiri'])) {
            abort(403, 'Unauthorized');
        }

        if ($salaryAdjustment->status === 'processed') {
            return back()->with('error', __('Cannot delete processed adjustment.'));
        }

        $salaryAdjustment->delete();

        return back()->with('success', __('Adjustment deleted successfully.'));
    }
}
