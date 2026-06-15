<?php

namespace App\Http\Controllers;

use App\Models\AtkCashMovement;
use App\Models\Cash;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;

class AtkCashReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:atk.report', only: ['index', 'pdf', 'excel']),
        ];
    }

    public function index(Request $request)
    {
        $start = $request->get('start_date', Carbon::today()->subDays(7));
        $end = $request->get('end_date', Carbon::today());
        $period = $request->get('period', 'custom');

        if ($period === 'daily') {
            $start = Carbon::today();
            $end = Carbon::today();
        } elseif ($period === 'weekly') {
            $start = Carbon::today()->startOfWeek();
            $end = Carbon::today()->endOfWeek();
        } elseif ($period === 'monthly') {
            $start = Carbon::today()->startOfMonth();
            $end = Carbon::today()->endOfMonth();
        }

        $cash = Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);

        $incoming = AtkCashMovement::whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->whereIn('movement_type', ['sale', 'service', 'topup', 'ppob', 'owner_loan', 'adjustment_in'])
            ->sum('amount');

        $outgoing = AtkCashMovement::whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->whereIn('movement_type', ['expense', 'transfer', 'withdrawal', 'owner_repayment', 'adjustment_out'])
            ->sum('amount');

        $startBalance = 0;
        $earliest = AtkCashMovement::whereDate('created_at', '<', $start)->orderBy('created_at', 'desc')->first();
        if ($earliest) {
            $startBalance = $earliest->balance_after;
        }

        // Get detail movements with running balance
        $movements = AtkCashMovement::whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->orderBy('created_at', 'asc')
            ->get();

        $endBalance = $startBalance;
        foreach ($movements as $movement) {
            $isIncoming = in_array($movement->movement_type, ['sale', 'service', 'topup', 'ppob', 'owner_loan', 'adjustment_in']);
            if ($isIncoming) {
                $endBalance += $movement->amount;
            } else {
                $endBalance -= $movement->amount;
            }
            $movement->running_balance = $endBalance;
        }

        return view('atk.reports.cash', compact('start', 'end', 'period', 'cash', 'startBalance', 'incoming', 'outgoing', 'endBalance', 'movements'));
    }

    public function pdf(Request $request)
    {
        return redirect()->back();
    }

    public function excel(Request $request)
    {
        return redirect()->back();
    }
}
