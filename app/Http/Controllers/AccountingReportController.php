<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingReportController extends Controller
{
    public function trialBalance(Request $request)
    {
        $start = $request->input('start_date');
        $end = $request->input('end_date');

        $query = JournalEntry::query()
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->select('accounts.id','accounts.code','accounts.name','accounts.type', DB::raw('SUM(journal_entries.debit) as debit'), DB::raw('SUM(journal_entries.credit) as credit'))
            ->groupBy('accounts.id','accounts.code','accounts.name','accounts.type');

        if ($start) {
            $query->whereDate('journal_entries.created_at', '>=', $start);
        }
        if ($end) {
            $query->whereDate('journal_entries.created_at', '<=', $end);
        }

        $rows = $query->orderBy('accounts.code')->get();
        $totalDebit = $rows->sum('debit');
        $totalCredit = $rows->sum('credit');

        return view('accounting.trial_balance', compact('rows','totalDebit','totalCredit','start','end'));
    }
}
