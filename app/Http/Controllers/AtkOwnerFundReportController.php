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

        // Normalize dates
        if (is_string($start)) {
            $start = \Illuminate\Support\Carbon::parse($start);
        }
        if (is_string($end)) {
            $end = \Illuminate\Support\Carbon::parse($end);
        }

        $query = OwnerFund::orderBy('created_at', 'desc')->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        if ($status) {
            $query->where('status', $status);
        }

        $funds = $query->paginate(20);

        // Calculate current balance based on the latest transaction before or during the end date
        $latestTransaction = OwnerFund::whereDate('created_at', '<=', $end)->orderBy('created_at', 'desc')->first();
        $currentBalance = $latestTransaction->balance ?? 0;

        $incoming = $funds->where('type', 'loan')->sum('amount');
        $outgoing = $funds->where('type', 'repayment')->sum('amount');

        return view('atk.reports.owner-funds', compact('funds', 'currentBalance', 'incoming', 'outgoing', 'start', 'end', 'status'));
    }

    public function pdf(Request $request)
    {
        $start = $request->get('start_date', now()->subDays(30));
        $end = $request->get('end_date', now());
        $status = $request->get('status');

        if (is_string($start)) {
            $start = \Illuminate\Support\Carbon::parse($start);
        }
        if (is_string($end)) {
            $end = \Illuminate\Support\Carbon::parse($end);
        }

        $query = OwnerFund::orderBy('created_at', 'desc')->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        if ($status) {
            $query->where('status', $status);
        }

        $funds = $query->get(); // Use get() instead of paginate() for PDF

        $latestTransaction = OwnerFund::whereDate('created_at', '<=', $end)->orderBy('created_at', 'desc')->first();
        $currentBalance = $latestTransaction->balance ?? 0;

        $incoming = $funds->where('type', 'loan')->sum('amount');
        $outgoing = $funds->where('type', 'repayment')->sum('amount');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('atk.reports.pdf-owner-funds', compact('funds', 'currentBalance', 'incoming', 'outgoing', 'start', 'end', 'status'))->setPaper('a4', 'portrait');
        return $pdf->download('laporan-dana-talangan-atk.pdf');
    }

    public function excel(Request $request)
    {
        return response()->streamDownload(function () use ($request) {
            $start = $request->get('start_date', now()->subDays(30));
            $end = $request->get('end_date', now());
            $status = $request->get('status');

            if (is_string($start)) {
                $start = \Illuminate\Support\Carbon::parse($start);
            }
            if (is_string($end)) {
                $end = \Illuminate\Support\Carbon::parse($end);
            }

            $query = OwnerFund::orderBy('created_at', 'desc')->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
            if ($status) {
                $query->where('status', $status);
            }

            $funds = $query->get();

            $latestTransaction = OwnerFund::whereDate('created_at', '<=', $end)->orderBy('created_at', 'desc')->first();
            $currentBalance = $latestTransaction->balance ?? 0;

            $incoming = $funds->where('type', 'loan')->sum('amount');
            $outgoing = $funds->where('type', 'repayment')->sum('amount');

            $writer = new \OpenSpout\Writer\XLSX\Writer();
            $writer->openToFile('php://output');

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Laporan Dana Talangan']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Periode', ($start instanceof \Illuminate\Support\Carbon ? $start->format('Y-m-d') : $start), 'sampai', ($end instanceof \Illuminate\Support\Carbon ? $end->format('Y-m-d') : $end)]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Dana Masuk', $incoming]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Pengembalian', $outgoing]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Saldo Aktif', $currentBalance]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['No', 'Tanggal', 'Kode', 'Jenis', 'Jumlah', 'Saldo', 'Keterangan']));
            foreach ($funds as $index => $fund) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $index + 1,
                    $fund->transaction_date->format('Y-m-d'),
                    $fund->transaction_code,
                    $fund->type,
                    $fund->amount,
                    $fund->balance,
                    $fund->description
                ]));
            }
            $writer->close();
        }, 'laporan-dana-talangan-atk.xlsx');
    }
}
