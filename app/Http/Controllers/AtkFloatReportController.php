<?php

namespace App\Http\Controllers;

use App\Models\AtkFloatAccount;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AtkFloatReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:atk.report', only: ['index', 'pdf', 'excel']),
        ];
    }

    public function index(Request $request)
    {
        $accountId = $request->get('account_id');
        $start = $request->get('start_date', now()->subDays(7));
        $end = $request->get('end_date', now());

        $accounts = AtkFloatAccount::where('status', 'active')->get();

        $selectedAccount = null;
        $startBalance = 0;
        $totalIn = 0;
        $totalOut = 0;
        $endBalance = 0;
        $transactions = [];

        if ($accountId) {
            $selectedAccount = AtkFloatAccount::with(['transactions' => function ($q) use ($start, $end) {
                $q->whereDate('created_at', '>=', $start)
                    ->whereDate('created_at', '<=', $end)
                    ->orderBy('created_at', 'asc');
            }])->find($accountId);

            if ($selectedAccount) {
                $transactions = $selectedAccount->transactions;

                // Calculate start balance
                $earliest = $selectedAccount->transactions()
                    ->whereDate('created_at', '<', $start)
                    ->orderBy('created_at', 'desc')
                    ->first();
                $startBalance = $earliest->balance_after ?? 0;

                // Calculate total in and out
                $totalIn = $transactions->whereIn('transaction_type', ['deposit', 'topup', 'transfer_in'])->sum('amount');
                $totalOut = $transactions->whereIn('transaction_type', ['withdrawal', 'transfer_out', 'ppob'])->sum('amount');
                $endBalance = $startBalance + $totalIn - $totalOut;

                // Calculate running balance
                $runningBalance = $startBalance;
                foreach ($transactions as $transaction) {
                    $isIncoming = in_array($transaction->transaction_type, ['deposit', 'topup', 'transfer_in']);
                    if ($isIncoming) {
                        $runningBalance += $transaction->amount;
                    } else {
                        $runningBalance -= $transaction->amount;
                    }
                    $transaction->running_balance = $runningBalance;
                }
            }
        }

        return view('atk.reports.float', compact('accounts', 'selectedAccount', 'start', 'end', 'startBalance', 'totalIn', 'totalOut', 'endBalance', 'transactions'));
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
