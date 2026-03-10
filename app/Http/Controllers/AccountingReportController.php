<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class AccountingReportController extends Controller
{
    public function trialBalance(Request $request)
    {
        $start = $request->input('start_date');
        $end = $request->input('end_date');

        $query = JournalEntry::query()
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->select('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type', DB::raw('SUM(journal_entries.debit) as debit'), DB::raw('SUM(journal_entries.credit) as credit'))
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type');

        if ($start) {
            $query->whereDate('journals.date', '>=', $start);
        }
        if ($end) {
            $query->whereDate('journals.date', '<=', $end);
        }

        $rows = $query->orderBy('accounts.code')->get();
        $totalDebit = $rows->sum('debit');
        $totalCredit = $rows->sum('credit');

        return view('accounting.trial_balance', compact('rows', 'totalDebit', 'totalCredit', 'start', 'end'));
    }

    public function incomeStatement(Request $request)
    {
        $start = $request->input('start_date');
        $end = $request->input('end_date');

        $base = JournalEntry::query()
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id');
        if ($start) {
            $base->whereDate('journals.date', '>=', $start);
        }
        if ($end) {
            $base->whereDate('journals.date', '<=', $end);
        }

        $revenues = (clone $base)
            ->where('accounts.type', 'revenue')
            ->select('accounts.code', 'accounts.name', DB::raw('SUM(journal_entries.credit - journal_entries.debit) as amount'))
            ->groupBy('accounts.code', 'accounts.name')
            ->orderBy('accounts.code')
            ->get();

        $expenses = (clone $base)
            ->where('accounts.type', 'expense')
            ->select('accounts.code', 'accounts.name', DB::raw('SUM(journal_entries.debit - journal_entries.credit) as amount'))
            ->groupBy('accounts.code', 'accounts.name')
            ->orderBy('accounts.code')
            ->get();

        $totalRevenue = $revenues->sum('amount');
        $totalExpense = $expenses->sum('amount');
        $netIncome = $totalRevenue - $totalExpense;

        return view('accounting.income_statement', compact('revenues', 'expenses', 'totalRevenue', 'totalExpense', 'netIncome', 'start', 'end'));
    }

    public function balanceSheet(Request $request)
    {
        $end = $request->input('end_date');
        $start = $request->input('start_date');

        $base = JournalEntry::query()
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id');
        if ($start) {
            $base->whereDate('journals.date', '>=', $start);
        }
        if ($end) {
            $base->whereDate('journals.date', '<=', $end);
        }

        $assets = (clone $base)
            ->where('accounts.type', 'asset')
            ->select('accounts.code', 'accounts.name', DB::raw('SUM(journal_entries.debit - journal_entries.credit) as amount'))
            ->groupBy('accounts.code', 'accounts.name')
            ->orderBy('accounts.code')
            ->get();

        $liabilities = (clone $base)
            ->where('accounts.type', 'liability')
            ->select('accounts.code', 'accounts.name', DB::raw('SUM(journal_entries.credit - journal_entries.debit) as amount'))
            ->groupBy('accounts.code', 'accounts.name')
            ->orderBy('accounts.code')
            ->get();

        $equity = (clone $base)
            ->where('accounts.type', 'equity')
            ->select('accounts.code', 'accounts.name', DB::raw('SUM(journal_entries.credit - journal_entries.debit) as amount'))
            ->groupBy('accounts.code', 'accounts.name')
            ->orderBy('accounts.code')
            ->get();

        $plBase = JournalEntry::query()
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id');
        if ($start) {
            $plBase->whereDate('journals.date', '>=', $start);
        }
        if ($end) {
            $plBase->whereDate('journals.date', '<=', $end);
        }
        $rev = (clone $plBase)->where('accounts.type', 'revenue')->select(DB::raw('SUM(journal_entries.credit - journal_entries.debit) as v'))->value('v') ?? 0;
        $exp = (clone $plBase)->where('accounts.type', 'expense')->select(DB::raw('SUM(journal_entries.debit - journal_entries.credit) as v'))->value('v') ?? 0;
        $netIncome = (float) $rev - (float) $exp;

        $totalAssets = $assets->sum('amount');
        $totalLiabilities = $liabilities->sum('amount');
        $totalEquity = $equity->sum('amount') + $netIncome;
        $rhs = $totalLiabilities + $totalEquity;

        return view('accounting.balance_sheet', compact('assets', 'liabilities', 'equity', 'netIncome', 'totalAssets', 'totalLiabilities', 'totalEquity', 'rhs', 'start', 'end'));
    }

    public function ledger(Request $request)
    {
        $accountId = $request->input('account_id');
        $start = $request->input('start_date');
        $end = $request->input('end_date');

        $this->ensureDefaultAccounts();
        $accounts = Account::orderBy('code')->get(['id', 'code', 'name']);
        $entries = collect();
        $selected = null;
        if ($accountId) {
            $selected = Account::find($accountId);
            $q = JournalEntry::query()
                ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
                ->where('journal_entries.account_id', $accountId)
                ->select('journals.date', 'journals.journal_no', 'journal_entries.debit', 'journal_entries.credit', 'journal_entries.memo')
                ->orderBy('journals.date')->orderBy('journal_entries.id');
            if ($start) {
                $q->whereDate('journals.date', '>=', $start);
            }
            if ($end) {
                $q->whereDate('journals.date', '<=', $end);
            }
            $entries = $q->get();
        }

        return view('accounting.ledger', compact('accounts', 'entries', 'selected', 'start', 'end'));
    }

    public function exportTrialBalancePdf(Request $request)
    {
        $request->merge(['start_date' => $request->start_date, 'end_date' => $request->end_date]);
        $start = $request->start_date;
        $end = $request->end_date;
        $q = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->select('accounts.code', 'accounts.name', 'accounts.type', DB::raw('SUM(journal_entries.debit) as debit'), DB::raw('SUM(journal_entries.credit) as credit'))
            ->groupBy('accounts.code', 'accounts.name', 'accounts.type');
        if ($start) {
            $q->whereDate('journals.date', '>=', $start);
        }
        if ($end) {
            $q->whereDate('journals.date', '<=', $end);
        }
        $rows = $q->orderBy('accounts.code')->get();
        $totalDebit = $rows->sum('debit');
        $totalCredit = $rows->sum('credit');
        $pdf = Pdf::loadView('accounting.pdf.trial_balance', compact('rows', 'totalDebit', 'totalCredit', 'start', 'end'));

        return $pdf->download('trial_balance.pdf');
    }

    public function exportTrialBalanceExcel(Request $request)
    {
        $start = $request->start_date;
        $end = $request->end_date;
        $q = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->select('accounts.code', 'accounts.name', DB::raw('SUM(journal_entries.debit) as debit'), DB::raw('SUM(journal_entries.credit) as credit'))
            ->groupBy('accounts.code', 'accounts.name');
        if ($start) {
            $q->whereDate('journals.date', '>=', $start);
        }
        if ($end) {
            $q->whereDate('journals.date', '<=', $end);
        }
        $rows = $q->orderBy('accounts.code')->get();

        return response()->streamDownload(function () use ($rows) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(['Kode', 'Nama Akun', 'Debit', 'Kredit']));
            foreach ($rows as $r) {
                $writer->addRow(Row::fromValues([$r->code, $r->name, $r->debit, $r->credit]));
            }
            $writer->close();
        }, 'trial_balance.xlsx');
    }

    public function exportIncomeStatementPdf(Request $request)
    {
        $start = $request->start_date;
        $end = $request->end_date;
        $base = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id');
        if ($start) {
            $base->whereDate('journals.date', '>=', $start);
        }
        if ($end) {
            $base->whereDate('journals.date', '<=', $end);
        }
        $revenues = (clone $base)->where('accounts.type', 'revenue')->select('accounts.code', 'accounts.name', DB::raw('SUM(journal_entries.credit - journal_entries.debit) as amount'))->groupBy('accounts.code', 'accounts.name')->orderBy('accounts.code')->get();
        $expenses = (clone $base)->where('accounts.type', 'expense')->select('accounts.code', 'accounts.name', DB::raw('SUM(journal_entries.debit - journal_entries.credit) as amount'))->groupBy('accounts.code', 'accounts.name')->orderBy('accounts.code')->get();
        $totalRevenue = $revenues->sum('amount');
        $totalExpense = $expenses->sum('amount');
        $netIncome = $totalRevenue - $totalExpense;
        $pdf = Pdf::loadView('accounting.pdf.income_statement', compact('revenues', 'expenses', 'totalRevenue', 'totalExpense', 'netIncome', 'start', 'end'));

        return $pdf->download('income_statement.pdf');
    }

    public function exportIncomeStatementExcel(Request $request)
    {
        $start = $request->start_date;
        $end = $request->end_date;
        $base = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id');
        if ($start) {
            $base->whereDate('journals.date', '>=', $start);
        }
        if ($end) {
            $base->whereDate('journals.date', '<=', $end);
        }
        $revenues = (clone $base)->where('accounts.type', 'revenue')->select('accounts.code', 'accounts.name', DB::raw('SUM(journal_entries.credit - journal_entries.debit) as amount'))->groupBy('accounts.code', 'accounts.name')->orderBy('accounts.code')->get();
        $expenses = (clone $base)->where('accounts.type', 'expense')->select('accounts.code', 'accounts.name', DB::raw('SUM(journal_entries.debit - journal_entries.credit) as amount'))->groupBy('accounts.code', 'accounts.name')->orderBy('accounts.code')->get();
        $netIncome = $revenues->sum('amount') - $expenses->sum('amount');

        return response()->streamDownload(function () use ($revenues, $expenses, $netIncome) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(['Laporan Laba Rugi']));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Pendapatan']));
            $writer->addRow(Row::fromValues(['Kode', 'Nama', 'Jumlah']));
            foreach ($revenues as $r) {
                $writer->addRow(Row::fromValues([$r->code, $r->name, $r->amount]));
            }
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Beban']));
            $writer->addRow(Row::fromValues(['Kode', 'Nama', 'Jumlah']));
            foreach ($expenses as $e) {
                $writer->addRow(Row::fromValues([$e->code, $e->name, $e->amount]));
            }
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Laba Bersih', $netIncome]));
            $writer->close();
        }, 'income_statement.xlsx');
    }

    public function exportBalanceSheetPdf(Request $request)
    {
        $start = $request->start_date;
        $end = $request->end_date;
        $base = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id');
        if ($start) {
            $base->whereDate('journals.date', '>=', $start);
        }
        if ($end) {
            $base->whereDate('journals.date', '<=', $end);
        }
        $assets = (clone $base)->where('accounts.type', 'asset')->select('accounts.code', 'accounts.name', DB::raw('SUM(journal_entries.debit - journal_entries.credit) as amount'))->groupBy('accounts.code', 'accounts.name')->orderBy('accounts.code')->get();
        $liabilities = (clone $base)->where('accounts.type', 'liability')->select('accounts.code', 'accounts.name', DB::raw('SUM(journal_entries.credit - journal_entries.debit) as amount'))->groupBy('accounts.code', 'accounts.name')->orderBy('accounts.code')->get();
        $equity = (clone $base)->where('accounts.type', 'equity')->select('accounts.code', 'accounts.name', DB::raw('SUM(journal_entries.credit - journal_entries.debit) as amount'))->groupBy('accounts.code', 'accounts.name')->orderBy('accounts.code')->get();
        $rev = (clone $base)->where('accounts.type', 'revenue')->select(DB::raw('SUM(journal_entries.credit - journal_entries.debit) as v'))->value('v') ?? 0;
        $exp = (clone $base)->where('accounts.type', 'expense')->select(DB::raw('SUM(journal_entries.debit - journal_entries.credit) as v'))->value('v') ?? 0;
        $netIncome = (float) $rev - (float) $exp;
        $totalAssets = $assets->sum('amount');
        $totalLiabilities = $liabilities->sum('amount');
        $totalEquity = $equity->sum('amount') + $netIncome;
        $rhs = $totalLiabilities + $totalEquity;
        $pdf = Pdf::loadView('accounting.pdf.balance_sheet', compact('assets', 'liabilities', 'equity', 'netIncome', 'totalAssets', 'totalLiabilities', 'totalEquity', 'rhs', 'start', 'end'));

        return $pdf->download('balance_sheet.pdf');
    }

    public function exportBalanceSheetExcel(Request $request)
    {
        $start = $request->start_date;
        $end = $request->end_date;
        $base = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id');
        if ($start) {
            $base->whereDate('journals.date', '>=', $start);
        }
        if ($end) {
            $base->whereDate('journals.date', '<=', $end);
        }
        $assets = (clone $base)->where('accounts.type', 'asset')->select('accounts.code', 'accounts.name', DB::raw('SUM(journal_entries.debit - journal_entries.credit) as amount'))->groupBy('accounts.code', 'accounts.name')->orderBy('accounts.code')->get();
        $liabilities = (clone $base)->where('accounts.type', 'liability')->select('accounts.code', 'accounts.name', DB::raw('SUM(journal_entries.credit - journal_entries.debit) as amount'))->groupBy('accounts.code', 'accounts.name')->orderBy('accounts.code')->get();
        $equity = (clone $base)->where('accounts.type', 'equity')->select('accounts.code', 'accounts.name', DB::raw('SUM(journal_entries.credit - journal_entries.debit) as amount'))->groupBy('accounts.code', 'accounts.name')->orderBy('accounts.code')->get();
        $rev = (clone $base)->where('accounts.type', 'revenue')->select(DB::raw('SUM(journal_entries.credit - journal_entries.debit) as v'))->value('v') ?? 0;
        $exp = (clone $base)->where('accounts.type', 'expense')->select(DB::raw('SUM(journal_entries.debit - journal_entries.credit) as v'))->value('v') ?? 0;
        $netIncome = (float) $rev - (float) $exp;
        $totalAssets = $assets->sum('amount');
        $totalLiabilities = $liabilities->sum('amount');
        $totalEquity = $equity->sum('amount') + $netIncome;
        $rhs = $totalLiabilities + $totalEquity;

        return response()->streamDownload(function () use ($assets, $liabilities, $equity, $netIncome, $totalAssets, $totalLiabilities, $totalEquity, $rhs) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(['Neraca']));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Aset']));
            $writer->addRow(Row::fromValues(['Kode', 'Nama', 'Jumlah']));
            foreach ($assets as $a) {
                $writer->addRow(Row::fromValues([$a->code, $a->name, $a->amount]));
            }
            $writer->addRow(Row::fromValues(['Total Aset', $totalAssets]));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Kewajiban']));
            $writer->addRow(Row::fromValues(['Kode', 'Nama', 'Jumlah']));
            foreach ($liabilities as $l) {
                $writer->addRow(Row::fromValues([$l->code, $l->name, $l->amount]));
            }
            $writer->addRow(Row::fromValues(['Total Kewajiban', $totalLiabilities]));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Ekuitas']));
            $writer->addRow(Row::fromValues(['Kode', 'Nama', 'Jumlah']));
            foreach ($equity as $e) {
                $writer->addRow(Row::fromValues([$e->code, $e->name, $e->amount]));
            }
            $writer->addRow(Row::fromValues(['Laba Berjalan', $netIncome]));
            $writer->addRow(Row::fromValues(['Total Ekuitas', $totalEquity]));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Aset', $totalAssets]));
            $writer->addRow(Row::fromValues(['Kewajiban + Ekuitas', $rhs]));
            $writer->close();
        }, 'balance_sheet.xlsx');
    }

    public function exportLedgerPdf(Request $request)
    {
        $accountId = $request->account_id;
        $start = $request->start_date;
        $end = $request->end_date;
        $selected = $accountId ? Account::find($accountId) : null;
        $entries = collect();
        if ($accountId) {
            $q = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
                ->where('journal_entries.account_id', $accountId)
                ->select('journals.date', 'journals.journal_no', 'journal_entries.debit', 'journal_entries.credit', 'journal_entries.memo')
                ->orderBy('journals.date')->orderBy('journal_entries.id');
            if ($start) {
                $q->whereDate('journals.date', '>=', $start);
            }
            if ($end) {
                $q->whereDate('journals.date', '<=', $end);
            }
            $entries = $q->get();
        }
        $pdf = Pdf::loadView('accounting.pdf.ledger', compact('selected', 'entries', 'start', 'end'));

        return $pdf->download('ledger.pdf');
    }

    public function exportLedgerExcel(Request $request)
    {
        $accountId = $request->account_id;
        $start = $request->start_date;
        $end = $request->end_date;
        $selected = $accountId ? Account::find($accountId) : null;
        $entries = collect();
        if ($accountId) {
            $q = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
                ->where('journal_entries.account_id', $accountId)
                ->select('journals.date', 'journals.journal_no', 'journal_entries.debit', 'journal_entries.credit', 'journal_entries.memo')
                ->orderBy('journals.date')->orderBy('journal_entries.id');
            if ($start) {
                $q->whereDate('journals.date', '>=', $start);
            }
            if ($end) {
                $q->whereDate('journals.date', '<=', $end);
            }
            $entries = $q->get();
        }

        return response()->streamDownload(function () use ($selected, $entries) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(['Buku Besar']));
            if ($selected) {
                $writer->addRow(Row::fromValues([$selected->code.' - '.$selected->name]));
            }
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Tanggal', 'No. Jurnal', 'Debit', 'Kredit', 'Keterangan']));
            foreach ($entries as $e) {
                $writer->addRow(Row::fromValues([$e->date, $e->journal_no, $e->debit, $e->credit, $e->memo]));
            }
            $writer->close();
        }, 'ledger.xlsx');
    }

    public function cashFlow(Request $request)
    {
        $start = $request->input('start_date');
        $end = $request->input('end_date') ?: now()->toDateString();
        $cashAccounts = Account::whereIn('code', ['1001', '1002'])->pluck('id')->all();

        $journalIds = DB::table('journal_entries')
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->when($start, fn ($q) => $q->whereDate('journals.date', '>=', $start))
            ->whereDate('journals.date', '<=', $end)
            ->whereIn('journal_entries.account_id', $cashAccounts)
            ->distinct()->pluck('journals.id')->all();

        $cashInByType = ['revenue' => 0, 'expense' => 0, 'asset' => 0, 'liability' => 0, 'equity' => 0];
        $cashOutByType = ['revenue' => 0, 'expense' => 0, 'asset' => 0, 'liability' => 0, 'equity' => 0];

        foreach ($journalIds as $jid) {
            $creditByType = DB::table('journal_entries')
                ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
                ->where('journal_entries.journal_id', $jid)
                ->whereNotIn('journal_entries.account_id', $cashAccounts)
                ->select('accounts.type', DB::raw('SUM(journal_entries.credit) as c'))
                ->groupBy('accounts.type')->get();
            foreach ($creditByType as $row) {
                if (isset($cashInByType[$row->type])) {
                    $cashInByType[$row->type] += (float) $row->c;
                }
            }

            $debitByType = DB::table('journal_entries')
                ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
                ->where('journal_entries.journal_id', $jid)
                ->whereNotIn('journal_entries.account_id', $cashAccounts)
                ->select('accounts.type', DB::raw('SUM(journal_entries.debit) as d'))
                ->groupBy('accounts.type')->get();
            foreach ($debitByType as $row) {
                if (isset($cashOutByType[$row->type])) {
                    $cashOutByType[$row->type] += (float) $row->d;
                }
            }
        }

        $operatingIn = $cashInByType['revenue'];
        $operatingOut = $cashOutByType['expense'];
        $investingIn = $cashInByType['asset'];
        $investingOut = $cashOutByType['asset'];
        $financingIn = $cashInByType['liability'] + $cashInByType['equity'];
        $financingOut = $cashOutByType['liability'] + $cashOutByType['equity'];

        $netOperating = $operatingIn - $operatingOut;
        $netInvesting = $investingIn - $investingOut;
        $netFinancing = $financingIn - $financingOut;
        $netChange = $netOperating + $netInvesting + $netFinancing;

        $closingCash = DB::table('journal_entries')
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->whereDate('journals.date', '<=', $end)
            ->whereIn('journal_entries.account_id', $cashAccounts)
            ->select(DB::raw('SUM(journal_entries.debit - journal_entries.credit) as bal'))->value('bal') ?? 0;
        $openingCash = null;
        if ($start) {
            $openingCash = DB::table('journal_entries')
                ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
                ->whereDate('journals.date', '<', $start)
                ->whereIn('journal_entries.account_id', $cashAccounts)
                ->select(DB::raw('SUM(journal_entries.debit - journal_entries.credit) as bal'))->value('bal') ?? 0;
        }

        return view('accounting.cash_flow', compact(
            'start', 'end',
            'operatingIn', 'operatingOut', 'investingIn', 'investingOut', 'financingIn', 'financingOut',
            'netOperating', 'netInvesting', 'netFinancing', 'netChange', 'openingCash', 'closingCash'
        ));
    }

    public function exportCashFlowPdf(Request $request)
    {
        $data = $this->prepareCashFlowData($request);
        $pdf = Pdf::loadView('accounting.pdf.cash_flow', $data);

        return $pdf->download('cash_flow.pdf');
    }

    public function exportCashFlowExcel(Request $request)
    {
        $data = $this->prepareCashFlowData($request);

        return response()->streamDownload(function () use ($data) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(['Laporan Arus Kas (Metode Langsung)']));
            $writer->addRow(Row::fromValues(['Periode', $data['start'] ?? '-', $data['end'] ?? '-']));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Arus Kas dari Aktivitas Operasi']));
            $writer->addRow(Row::fromValues(['Penerimaan dari pelanggan', $data['operatingIn']]));
            $writer->addRow(Row::fromValues(['Pembayaran beban', -$data['operatingOut']]));
            $writer->addRow(Row::fromValues(['Netto Operasi', $data['netOperating']]));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Arus Kas dari Aktivitas Investasi']));
            $writer->addRow(Row::fromValues(['Penerimaan penjualan aset', $data['investingIn']]));
            $writer->addRow(Row::fromValues(['Pembelian aset', -$data['investingOut']]));
            $writer->addRow(Row::fromValues(['Netto Investasi', $data['netInvesting']]));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Arus Kas dari Aktivitas Pendanaan']));
            $writer->addRow(Row::fromValues(['Penerimaan pendanaan', $data['financingIn']]));
            $writer->addRow(Row::fromValues(['Pengembalian/Distribusi', -$data['financingOut']]));
            $writer->addRow(Row::fromValues(['Netto Pendanaan', $data['netFinancing']]));
            $writer->addRow(Row::fromValues([]));
            if (! is_null($data['openingCash'])) {
                $writer->addRow(Row::fromValues(['Saldo Awal Kas', $data['openingCash']]));
            }
            $writer->addRow(Row::fromValues(['Kenaikan (Penurunan) Kas', $data['netChange']]));
            $writer->addRow(Row::fromValues(['Saldo Akhir Kas', $data['closingCash']]));
            $writer->close();
        }, 'cash_flow.xlsx');
    }

    private function prepareCashFlowData(Request $request): array
    {
        $start = $request->input('start_date');
        $end = $request->input('end_date') ?: now()->toDateString();
        $cashAccounts = Account::whereIn('code', ['1001', '1002'])->pluck('id')->all();
        $journalIds = DB::table('journal_entries')
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->when($start, fn ($q) => $q->whereDate('journals.date', '>=', $start))
            ->whereDate('journals.date', '<=', $end)
            ->whereIn('journal_entries.account_id', $cashAccounts)
            ->distinct()->pluck('journals.id')->all();
        $cashInByType = ['revenue' => 0, 'expense' => 0, 'asset' => 0, 'liability' => 0, 'equity' => 0];
        $cashOutByType = ['revenue' => 0, 'expense' => 0, 'asset' => 0, 'liability' => 0, 'equity' => 0];
        foreach ($journalIds as $jid) {
            $creditByType = DB::table('journal_entries')
                ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
                ->where('journal_entries.journal_id', $jid)
                ->whereNotIn('journal_entries.account_id', $cashAccounts)
                ->select('accounts.type', DB::raw('SUM(journal_entries.credit) as c'))
                ->groupBy('accounts.type')->get();
            foreach ($creditByType as $row) {
                if (isset($cashInByType[$row->type])) {
                    $cashInByType[$row->type] += (float) $row->c;
                }
            }
            $debitByType = DB::table('journal_entries')
                ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
                ->where('journal_entries.journal_id', $jid)
                ->whereNotIn('journal_entries.account_id', $cashAccounts)
                ->select('accounts.type', DB::raw('SUM(journal_entries.debit) as d'))
                ->groupBy('accounts.type')->get();
            foreach ($debitByType as $row) {
                if (isset($cashOutByType[$row->type])) {
                    $cashOutByType[$row->type] += (float) $row->d;
                }
            }
        }
        $operatingIn = $cashInByType['revenue'];
        $operatingOut = $cashOutByType['expense'];
        $investingIn = $cashInByType['asset'];
        $investingOut = $cashOutByType['asset'];
        $financingIn = $cashInByType['liability'] + $cashInByType['equity'];
        $financingOut = $cashOutByType['liability'] + $cashOutByType['equity'];
        $netOperating = $operatingIn - $operatingOut;
        $netInvesting = $investingIn - $investingOut;
        $netFinancing = $financingIn - $financingOut;
        $netChange = $netOperating + $netInvesting + $netFinancing;
        $closingCash = DB::table('journal_entries')
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->whereDate('journals.date', '<=', $end)
            ->whereIn('journal_entries.account_id', $cashAccounts)
            ->select(DB::raw('SUM(journal_entries.debit - journal_entries.credit) as bal'))->value('bal') ?? 0;
        $openingCash = null;
        if ($start) {
            $openingCash = DB::table('journal_entries')
                ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
                ->whereDate('journals.date', '<', $start)
                ->whereIn('journal_entries.account_id', $cashAccounts)
                ->select(DB::raw('SUM(journal_entries.debit - journal_entries.credit) as bal'))->value('bal') ?? 0;
        }

        return compact(
            'start', 'end',
            'operatingIn', 'operatingOut', 'investingIn', 'investingOut', 'financingIn', 'financingOut',
            'netOperating', 'netInvesting', 'netFinancing', 'netChange', 'openingCash', 'closingCash'
        );
    }

    private function ensureDefaultAccounts(): void
    {
        if (Account::query()->exists()) {
            return;
        }

        foreach ($this->defaultChartOfAccounts() as [$code, $name, $type]) {
            Account::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'type' => $type]
            );
        }
    }

    private function defaultChartOfAccounts(): array
    {
        return [
            ['1001', 'Kas', 'asset'],
            ['1002', 'Bank', 'asset'],
            ['1101', 'Piutang Usaha', 'asset'],
            ['1201', 'Persediaan ATK', 'asset'],
            ['1301', 'Peralatan Jaringan', 'asset'],
            ['1302', 'Kendaraan', 'asset'],
            ['1401', 'Deposit Agen Bank', 'asset'],
            ['2001', 'Hutang Supplier', 'liability'],
            ['2002', 'Hutang Gaji', 'liability'],
            ['2101', 'Pendapatan Diterima Dimuka', 'liability'],
            ['3001', 'Modal', 'equity'],
            ['3101', 'Laba Ditahan', 'equity'],
            ['3201', 'Laba Berjalan', 'equity'],
            ['4001', 'Pendapatan ISP', 'revenue'],
            ['4002', 'Pendapatan Instalasi', 'revenue'],
            ['4003', 'Pendapatan ATK', 'revenue'],
            ['4004', 'Pendapatan Jasa Transfer Bank', 'revenue'],
            ['4005', 'Pendapatan Car Wash', 'revenue'],
            ['5001', 'HPP ATK', 'expense'],
            ['6001', 'Beban Bandwidth', 'expense'],
            ['6002', 'Beban Listrik', 'expense'],
            ['6003', 'Beban Gaji', 'expense'],
            ['6004', 'Beban ATK Internal', 'expense'],
            ['6005', 'Beban Maintenance', 'expense'],
            ['6006', 'Beban Transport', 'expense'],
            ['6007', 'Beban Bank/Payment', 'expense'],
        ];
    }
}
