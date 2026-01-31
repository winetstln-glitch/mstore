<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AtkTransaction;
use App\Models\WashTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function atk(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $query = AtkTransaction::with(['items.product', 'user'])
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);

        // Calculate Summary before pagination
        $totalIncome = $query->sum('total_amount');
        $transactionCount = $query->count();
        
        // Get Daily Data for Chart
        $dailyIncome = AtkTransaction::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $transactions = $query->latest()->paginate(20);

        return view('reports.atk', compact('transactions', 'totalIncome', 'transactionCount', 'dailyIncome', 'startDate', 'endDate'));
    }

    public function wash(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $query = WashTransaction::with(['items.service', 'employee'])
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);

        $totalIncome = $query->sum('total_amount');
        $transactionCount = $query->count();
        
        $dailyIncome = WashTransaction::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $transactions = $query->latest()->paginate(20);

        return view('reports.wash', compact('transactions', 'totalIncome', 'transactionCount', 'dailyIncome', 'startDate', 'endDate'));
    }
}
