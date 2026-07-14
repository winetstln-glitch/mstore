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
        $start = $request->get('start_date');
        $end = $request->get('end_date');
        
        // Set defaults if null or empty
        if (empty($start)) {
            $start = now()->subDays(7)->toDateString();
        }
        if (empty($end)) {
            $end = now()->toDateString();
        }

        $accounts = AtkFloatAccount::where('status', 'active')->get();

        $selectedAccount = null;
        $startBalance = 0;
        $totalIn = 0;
        $totalOut = 0;
        $endBalance = 0;
        $transactions = [];

        if ($accountId) {
            $startDate = $start;
            $endDate = $end;
            $selectedAccount = AtkFloatAccount::with(['transactions' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->whereNull('reversed_at')
                    ->orderBy('created_at', 'asc');
            }])->find($accountId);

            if ($selectedAccount) {
                $transactions = $selectedAccount->transactions;

                // Calculate start balance
                $earliest = $selectedAccount->transactions()
                    ->where('created_at', '<', $startDate . ' 00:00:00')
                    ->whereNull('reversed_at')
                    ->orderBy('created_at', 'desc')
                    ->first();
                $startBalance = $earliest->balance_after ?? 0;

                // Calculate total in and out
                $totalIn = $transactions->whereIn('transaction_type', ['deposit', 'transfer_in', 'adjustment'])->sum('amount');
                $totalOut = $transactions->whereIn('transaction_type', ['withdrawal', 'transfer_out', 'ppob', 'topup'])->sum('amount');
                $endBalance = $startBalance + $totalIn - $totalOut;

                // Calculate running balance
                $runningBalance = $startBalance;
                foreach ($transactions as $transaction) {
                    $isIncoming = in_array($transaction->transaction_type, ['deposit', 'transfer_in', 'adjustment']);
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
        $accountId = $request->get('account_id');
        $start = $request->get('start_date');
        $end = $request->get('end_date');

        if (empty($start)) {
            $start = now()->subDays(7)->toDateString();
        }
        if (empty($end)) {
            $end = now()->toDateString();
        }

        $accounts = \App\Models\AtkFloatAccount::where('status', 'active')->get();

        $selectedAccount = null;
        $startBalance = 0;
        $totalIn = 0;
        $totalOut = 0;
        $endBalance = 0;
        $transactions = [];

        if ($accountId) {
            $startDate = $start;
            $endDate = $end;
            $selectedAccount = \App\Models\AtkFloatAccount::with(['transactions' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->whereNull('reversed_at')
                    ->orderBy('created_at', 'asc');
            }])->find($accountId);

            if ($selectedAccount) {
                $transactions = $selectedAccount->transactions;

                $earliest = $selectedAccount->transactions()
                    ->where('created_at', '<', $startDate . ' 00:00:00')
                    ->whereNull('reversed_at')
                    ->orderBy('created_at', 'desc')
                    ->first();
                $startBalance = $earliest->balance_after ?? 0;

                $totalIn = $transactions->whereIn('transaction_type', ['deposit', 'transfer_in', 'adjustment'])->sum('amount');
                $totalOut = $transactions->whereIn('transaction_type', ['withdrawal', 'transfer_out', 'ppob', 'topup'])->sum('amount');
                $endBalance = $startBalance + $totalIn - $totalOut;

                $runningBalance = $startBalance;
                foreach ($transactions as $transaction) {
                    $isIncoming = in_array($transaction->transaction_type, ['deposit', 'transfer_in', 'adjustment']);
                    if ($isIncoming) {
                        $runningBalance += $transaction->amount;
                    } else {
                        $runningBalance -= $transaction->amount;
                    }
                    $transaction->running_balance = $runningBalance;
                }
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('atk.reports.pdf-float', compact('accounts', 'selectedAccount', 'start', 'end', 'startBalance', 'totalIn', 'totalOut', 'endBalance', 'transactions'))->setPaper('a4', 'portrait');
        return $pdf->download('laporan-float-atk.pdf');
    }

    public function excel(Request $request)
    {
        return response()->streamDownload(function () use ($request) {
            $accountId = $request->get('account_id');
            $start = $request->get('start_date');
            $end = $request->get('end_date');

            if (empty($start)) {
                $start = now()->subDays(7)->toDateString();
            }
            if (empty($end)) {
                $end = now()->toDateString();
            }

            $selectedAccount = null;
            $startBalance = 0;
            $totalIn = 0;
            $totalOut = 0;
            $endBalance = 0;
            $transactions = [];

            if ($accountId) {
                $startDate = $start;
                $endDate = $end;
                $selectedAccount = \App\Models\AtkFloatAccount::with(['transactions' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->whereNull('reversed_at')
                        ->orderBy('created_at', 'asc');
                }])->find($accountId);

                if ($selectedAccount) {
                    $transactions = $selectedAccount->transactions;

                    $earliest = $selectedAccount->transactions()
                        ->where('created_at', '<', $startDate . ' 00:00:00')
                        ->whereNull('reversed_at')
                        ->orderBy('created_at', 'desc')
                        ->first();
                    $startBalance = $earliest->balance_after ?? 0;

                    $totalIn = $transactions->whereIn('transaction_type', ['deposit', 'transfer_in', 'adjustment'])->sum('amount');
                    $totalOut = $transactions->whereIn('transaction_type', ['withdrawal', 'transfer_out', 'ppob', 'topup'])->sum('amount');
                    $endBalance = $startBalance + $totalIn - $totalOut;

                    $runningBalance = $startBalance;
                    foreach ($transactions as $transaction) {
                        $isIncoming = in_array($transaction->transaction_type, ['deposit', 'transfer_in', 'adjustment']);
                        if ($isIncoming) {
                            $runningBalance += $transaction->amount;
                        } else {
                            $runningBalance -= $transaction->amount;
                        }
                        $transaction->running_balance = $runningBalance;
                    }
                }
            }

            $writer = new \OpenSpout\Writer\XLSX\Writer();
            $writer->openToFile('php://output');

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Laporan Float Account']));
            if ($selectedAccount) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Akun', $selectedAccount->name]));
            }
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Periode', $start, 'sampai', $end]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));

            if ($selectedAccount) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Saldo Awal', $startBalance]));
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Total Masuk', $totalIn]));
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Total Keluar', $totalOut]));
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Saldo Akhir', $endBalance]));
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));

                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['No', 'Tanggal', 'Referensi', 'Keterangan', 'Debit', 'Kredit', 'Saldo Berjalan']));
                foreach ($transactions as $index => $transaction) {
                    $isIncoming = in_array($transaction->transaction_type, ['deposit', 'transfer_in', 'adjustment']);
                    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                        $index + 1,
                        $transaction->created_at->format('Y-m-d H:i'),
                        $transaction->transaction_type,
                        $transaction->description,
                        $isIncoming ? $transaction->amount : 0,
                        !$isIncoming ? $transaction->amount : 0,
                        $transaction->running_balance
                    ]));
                }
            } else {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Pilih akun float terlebih dahulu']));
            }
            $writer->close();
        }, 'laporan-float-atk.xlsx');
    }
}
