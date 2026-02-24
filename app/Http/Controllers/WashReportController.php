<?php

namespace App\Http\Controllers;

use App\Models\WashTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WashReportController extends Controller
{
    private function buildData(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        $month = $request->input('month', now()->format('Y-m'));

        $dailyIncome = WashTransaction::whereDate('created_at', $date)->sum('total_amount');
        $dailyExpense = \App\Models\Transaction::where('type', 'expense')
            ->where('category', 'Pengeluaran Pengurus')
            ->where('reference_number', 'like', 'WASH-EXP-%')
            ->whereDate('transaction_date', $date)->sum('amount');

        $monthlyIncome = WashTransaction::where('created_at', 'like', "$month%")->sum('total_amount');
        $monthlyExpense = \App\Models\Transaction::where('type', 'expense')
            ->where('category', 'Pengeluaran Pengurus')
            ->where('reference_number', 'like', 'WASH-EXP-%')
            ->whereMonth('transaction_date', substr($month, 5, 2))
            ->whereYear('transaction_date', substr($month, 0, 4))->sum('amount');

        $dailyIncomeRows = WashTransaction::whereDate('created_at', $date)
            ->select(['id', 'transaction_number', 'total_amount', 'payment_method', 'created_at'])
            ->orderByDesc('created_at')->get();
        $dailyExpenseRows = \App\Models\Transaction::where('type', 'expense')
            ->where('category', 'Pengeluaran Pengurus')
            ->where('reference_number', 'like', 'WASH-EXP-%')
            ->whereDate('transaction_date', $date)
            ->select(['id', 'description', 'amount', 'transaction_date'])
            ->orderByDesc('transaction_date')->get();

        $monthlyDailyIncome = WashTransaction::where('created_at', 'like', "$month%")
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(total_amount) as total'))
            ->groupBy(DB::raw('DATE(created_at)'))->orderBy('d', 'asc')->get();
        $monthlyDailyExpense = \App\Models\Transaction::where('type', 'expense')
            ->where('category', 'Pengeluaran Pengurus')
            ->where('reference_number', 'like', 'WASH-EXP-%')
            ->whereMonth('transaction_date', substr($month, 5, 2))
            ->whereYear('transaction_date', substr($month, 0, 4))
            ->select(DB::raw('DATE(transaction_date) as d'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('DATE(transaction_date)'))->orderBy('d', 'asc')->get();

        $dailyByService = DB::table('wash_transaction_items as i')
            ->join('wash_transactions as t', 't.id', '=', 'i.wash_transaction_id')
            ->whereDate('t.created_at', $date)
            ->select('i.service_name', DB::raw('SUM(i.subtotal) as amount'))
            ->groupBy('i.service_name')->orderByDesc('amount')->get();
        $dailyByPayment = WashTransaction::whereDate('created_at', $date)
            ->select('payment_method', DB::raw('SUM(total_amount) as amount'))
            ->groupBy('payment_method')->orderByDesc('amount')->get();

        $monthlyByService = DB::table('wash_transaction_items as i')
            ->join('wash_transactions as t', 't.id', '=', 'i.wash_transaction_id')
            ->where('t.created_at', 'like', "$month%")
            ->select('i.service_name', DB::raw('SUM(i.subtotal) as amount'))
            ->groupBy('i.service_name')->orderByDesc('amount')->get();
        $monthlyByPayment = WashTransaction::where('created_at', 'like', "$month%")
            ->select('payment_method', DB::raw('SUM(total_amount) as amount'))
            ->groupBy('payment_method')->orderByDesc('amount')->get();

        return compact(
            'date', 'month',
            'dailyIncome', 'dailyExpense', 'monthlyIncome', 'monthlyExpense',
            'dailyIncomeRows', 'dailyExpenseRows',
            'monthlyDailyIncome', 'monthlyDailyExpense',
            'dailyByService', 'dailyByPayment', 'monthlyByService', 'monthlyByPayment'
        );
    }

    public function index(Request $request)
    {
        $data = $this->buildData($request);

        return view('wash.reports.index', $data);
    }

    public function pdf(Request $request)
    {
        $data = $this->buildData($request);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('wash.reports.pdf', $data)->setPaper('a4', 'portrait');

        return $pdf->download('laporan_wash.pdf');
    }

    public function excel(Request $request)
    {
        $data = $this->buildData($request);

        return response()->streamDownload(function () use ($data) {
            $writer = new \OpenSpout\Writer\XLSX\Writer;
            $writer->openToFile('php://output');
            $sheet = function ($title) {};

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Laporan Wash']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Tanggal', $data['date'], 'Bulan', $data['month']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Ringkasan Harian']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Pemasukan', $data['dailyIncome'], 'Pengeluaran', $data['dailyExpense'], 'Laba', $data['dailyIncome'] - $data['dailyExpense']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Ringkasan Bulanan']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Pemasukan', $data['monthlyIncome'], 'Pengeluaran', $data['monthlyExpense'], 'Laba', $data['monthlyIncome'] - $data['monthlyExpense']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Rincian Pemasukan Harian']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Waktu', 'No Trx', 'Metode', 'Total']));
            foreach ($data['dailyIncomeRows'] as $r) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([$r->created_at->format('H:i'), $r->transaction_number, strtoupper($r->payment_method), $r->total_amount]));
            }
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Rincian Pengeluaran Harian']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Tanggal', 'Deskripsi', 'Nominal']));
            foreach ($data['dailyExpenseRows'] as $r) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([\Carbon\Carbon::parse($r->transaction_date)->format('Y-m-d'), $r->description, $r->amount]));
            }
            $writer->close();
        }, 'laporan_wash.xlsx');
    }
}
