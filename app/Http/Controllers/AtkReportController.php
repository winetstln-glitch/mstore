<?php

namespace App\Http\Controllers;

use App\Models\AtkTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AtkReportController extends Controller
{
    private function buildData(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        $month = $request->input('month', now()->format('Y-m'));

        $dailyIncome = AtkTransaction::whereDate('created_at', $date)->sum('total_amount');
        $dailyExpense = \App\Models\Transaction::where('type', 'expense')
            ->where('category', 'Pengeluaran Pengurus')
            ->where('reference_number', 'like', 'ATK-EXP-%')
            ->whereDate('transaction_date', $date)->sum('amount');

        $monthlyIncome = AtkTransaction::where('created_at', 'like', "$month%")->sum('total_amount');
        $monthlyExpense = \App\Models\Transaction::where('type', 'expense')
            ->where('category', 'Pengeluaran Pengurus')
            ->where('reference_number', 'like', 'ATK-EXP-%')
            ->whereMonth('transaction_date', substr($month, 5, 2))
            ->whereYear('transaction_date', substr($month, 0, 4))->sum('amount');

        $dailyIncomeRows = AtkTransaction::whereDate('created_at', $date)
            ->select(['id','transaction_number','total_amount','payment_method','created_at'])
            ->orderByDesc('created_at')->get();
        $dailyExpenseRows = \App\Models\Transaction::where('type','expense')
            ->where('category','Pengeluaran Pengurus')
            ->where('reference_number','like','ATK-EXP-%')
            ->whereDate('transaction_date', $date)
            ->select(['id','description','amount','transaction_date'])
            ->orderByDesc('transaction_date')->get();

        $monthlyDailyIncome = AtkTransaction::where('created_at','like',"$month%")
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(total_amount) as total'))
            ->groupBy(DB::raw('DATE(created_at)'))->orderBy('d','asc')->get();
        $monthlyDailyExpense = \App\Models\Transaction::where('type','expense')
            ->where('category','Pengeluaran Pengurus')
            ->where('reference_number','like','ATK-EXP-%')
            ->whereMonth('transaction_date', substr($month, 5, 2))
            ->whereYear('transaction_date', substr($month, 0, 4))
            ->select(DB::raw('DATE(transaction_date) as d'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('DATE(transaction_date)'))->orderBy('d','asc')->get();

        $dailyByPayment = AtkTransaction::whereDate('created_at', $date)
            ->select('payment_method', DB::raw('SUM(total_amount) as amount'))
            ->groupBy('payment_method')->orderByDesc('amount')->get();
        $monthlyByPayment = AtkTransaction::where('created_at', 'like', "$month%")
            ->select('payment_method', DB::raw('SUM(total_amount) as amount'))
            ->groupBy('payment_method')->orderByDesc('amount')->get();

        return compact('date','month','dailyIncome','dailyExpense','monthlyIncome','monthlyExpense','dailyIncomeRows','dailyExpenseRows','monthlyDailyIncome','monthlyDailyExpense','dailyByPayment','monthlyByPayment');
    }

    public function index(Request $request)
    {
        $data = $this->buildData($request);
        return view('atk.reports.index', $data);
    }

    public function pdf(Request $request)
    {
        $data = $this->buildData($request);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('atk.reports.pdf', $data)->setPaper('a4', 'portrait');
        return $pdf->download('laporan_atk.pdf');
    }

    public function excel(Request $request)
    {
        $data = $this->buildData($request);
        return response()->streamDownload(function () use ($data) {
            $writer = new \OpenSpout\Writer\XLSX\Writer();
            $writer->openToFile('php://output');
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Laporan ATK']));
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
        }, 'laporan_atk.xlsx');
    }
}
