<?php

namespace App\Http\Controllers;

use App\Models\WashDailyClosing;
use App\Models\WashTransaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class WashDailyClosingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:wash.closing.view', only: ['index', 'show']),
            new Middleware('permission:wash.closing.create', only: ['create', 'store']),
            new Middleware('permission:wash.closing.approve', only: ['approve']),
        ];
    }

    public function index()
    {
        $closings = WashDailyClosing::with('closedBy', 'approvedBy')
            ->latest('closing_date')
            ->paginate(20);
        return view('wash.daily-closings.index', compact('closings'));
    }

    public function create()
    {
        $today = now()->format('Y-m-d');
        $existingClosing = WashDailyClosing::where('closing_date', $today)->first();
        if ($existingClosing) {
            return redirect()->route('wash.daily-closings.show', $existingClosing)->with('error', 'Penutupan hari ini sudah dibuat!');
        }
        return view('wash.daily-closings.create', compact('today'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'closing_date' => 'required|date|unique:wash_daily_closings,closing_date',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $date = $request->closing_date;
            $washTransactions = WashTransaction::whereDate('transaction_date', $date)->get();
            
            $washRevenue = $washTransactions->whereNotIn('vehicle_type', ['coffee', 'caffe'])->sum('total_amount');
            $caffeRevenue = $washTransactions->whereIn('vehicle_type', ['coffee', 'caffe'])->sum('total_amount');
            
            $totalExpenses = DB::table('transactions')
                ->where('type', 'expense')
                ->where('reference_number', 'like', 'WASH-EXP-%')
                ->whereDate('transaction_date', $date)
                ->sum('amount');
            
            $totalMemberTransactions = $washTransactions->whereNotNull('wash_member_id')->count();
            $totalNonMemberTransactions = $washTransactions->whereNull('wash_member_id')->count();
            
            $grossProfit = $washRevenue + $caffeRevenue - $totalExpenses;
            $netProfit = $grossProfit;

            WashDailyClosing::create([
                'closing_date' => $date,
                'wash_revenue' => $washRevenue,
                'caffe_revenue' => $caffeRevenue,
                'total_expenses' => $totalExpenses,
                'gross_profit' => $grossProfit,
                'net_profit' => $netProfit,
                'total_member_transactions' => $totalMemberTransactions,
                'total_non_member_transactions' => $totalNonMemberTransactions,
                'closed_by' => auth()->id(),
                'notes' => $request->notes,
                'status' => 'draft',
            ]);
        });

        return redirect()->route('wash.daily-closings.index')->with('success', 'Penutupan harian berhasil dibuat!');
    }

    public function show(WashDailyClosing $closing)
    {
        $closing->load('closedBy', 'approvedBy');
        return view('wash.daily-closings.show', compact('closing'));
    }

    public function approve(WashDailyClosing $closing)
    {
        if ($closing->status !== 'draft') {
            return redirect()->route('wash.daily-closings.index')->with('error', 'Penutupan ini tidak dapat diubah!');
        }

        $closing->update([
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'status' => 'approved',
        ]);

        return redirect()->route('wash.daily-closings.index')->with('success', 'Penutupan harian berhasil disetujui!');
    }
}
