<?php

namespace App\Http\Controllers;

use App\Models\OwnerFund;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AtkOwnerFundReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:atk.report', only: ['index', 'pdf', 'excel']),
        ];
    }

    public function index(Request $request)
    {
        $start = $request->get('start_date', now()->subDays(30));
        $end = $request->get('end_date', now());
        $status = $request->get('status');

        $query = OwnerFund::orderBy('created_at', 'desc')->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        if ($status) {
            $query->where('status', $status);
        }

        $funds = $query->paginate(20);
        $currentBalance = OwnerFund::orderBy('created_at', 'desc')->first()->balance ?? 0;

        $incoming = $funds->where('type', 'loan')->sum('amount');
        $outgoing = $funds->where('type', 'repayment')->sum('amount');

        return view('atk.reports.owner-funds', compact('funds', 'currentBalance', 'incoming', 'outgoing', 'start', 'end', 'status'));
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
