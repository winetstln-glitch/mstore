<?php

namespace App\Http\Controllers;

use App\Models\AtkCashMovement;
use App\Models\Cash;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;

class AtkCashReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:atk.report', only: ['index', 'pdf', 'excel']),
        ];
    }

    public function index(Request $request)
    {
        $start = $request->get('start_date', Carbon::today()->subDays(7));
        $end = $request->get('end_date', Carbon::today());
        $period = $request->get('period', 'custom');

        if ($period === 'daily') {
            $start = Carbon::today();
            $end = Carbon::today();
        } elseif ($period === 'weekly') {
            $start = Carbon::today()->startOfWeek();
            $end = Carbon::today()->endOfWeek();
        } elseif ($period === 'monthly') {
            $start = Carbon::today()->startOfMonth();
            $end = Carbon::today()->endOfMonth();
        }

        $cash = Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);

        // Use direction column and exclude reversed movements
        $incoming = AtkCashMovement::whereDate('occurred_at', '>=', $start)
            ->whereDate('occurred_at', '<=', $end)
            ->where('direction', 'in')
            ->whereNull('reversed_at')
            ->sum('amount');

        $outgoing = AtkCashMovement::whereDate('occurred_at', '>=', $start)
            ->whereDate('occurred_at', '<=', $end)
            ->where('direction', 'out')
            ->whereNull('reversed_at')
            ->sum('amount');

        $startBalance = 0;
        $earliest = AtkCashMovement::whereDate('occurred_at', '<', $start)
            ->whereNull('reversed_at')
            ->orderBy('occurred_at', 'desc')
            ->first();
        if ($earliest) {
            $startBalance = $earliest->balance_after;
        }

        // Get detail movements with running balance, exclude reversed
        $movements = AtkCashMovement::whereDate('occurred_at', '>=', $start)
            ->whereDate('occurred_at', '<=', $end)
            ->whereNull('reversed_at')
            ->orderBy('occurred_at', 'asc')
            ->get();

        $endBalance = $startBalance;
        foreach ($movements as $movement) {
            $isIncoming = $movement->direction === 'in';
            if ($isIncoming) {
                $endBalance += $movement->amount;
            } else {
                $endBalance -= $movement->amount;
            }
            $movement->running_balance = $endBalance;
        }

        return view('atk.reports.cash', compact('start', 'end', 'period', 'cash', 'startBalance', 'incoming', 'outgoing', 'endBalance', 'movements'));
    }

    public function pdf(Request $request)
    {
        // Reuse the same data logic from index method
        $start = $request->get('start_date', \Illuminate\Support\Carbon::today()->subDays(7));
        $end = $request->get('end_date', \Illuminate\Support\Carbon::today());
        $period = $request->get('period', 'custom');

        if ($period === 'daily') {
            $start = \Illuminate\Support\Carbon::today();
            $end = \Illuminate\Support\Carbon::today();
        } elseif ($period === 'weekly') {
            $start = \Illuminate\Support\Carbon::today()->startOfWeek();
            $end = \Illuminate\Support\Carbon::today()->endOfWeek();
        } elseif ($period === 'monthly') {
            $start = \Illuminate\Support\Carbon::today()->startOfMonth();
            $end = \Illuminate\Support\Carbon::today()->endOfMonth();
        }

        $cash = \App\Models\Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);

        // Use direction column and exclude reversed movements
        $incoming = \App\Models\AtkCashMovement::whereDate('occurred_at', '>=', $start)
            ->whereDate('occurred_at', '<=', $end)
            ->where('direction', 'in')
            ->whereNull('reversed_at')
            ->sum('amount');

        $outgoing = \App\Models\AtkCashMovement::whereDate('occurred_at', '>=', $start)
            ->whereDate('occurred_at', '<=', $end)
            ->where('direction', 'out')
            ->whereNull('reversed_at')
            ->sum('amount');

        $startBalance = 0;
        $earliest = \App\Models\AtkCashMovement::whereDate('occurred_at', '<', $start)
            ->whereNull('reversed_at')
            ->orderBy('occurred_at', 'desc')
            ->first();
        if ($earliest) {
            $startBalance = $earliest->balance_after;
        }

        $movements = \App\Models\AtkCashMovement::whereDate('occurred_at', '>=', $start)
            ->whereDate('occurred_at', '<=', $end)
            ->whereNull('reversed_at')
            ->orderBy('occurred_at', 'asc')
            ->get();

        $endBalance = $startBalance;
        foreach ($movements as $movement) {
            $isIncoming = $movement->direction === 'in';
            if ($isIncoming) {
                $endBalance += $movement->amount;
            } else {
                $endBalance -= $movement->amount;
            }
            $movement->running_balance = $endBalance;
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('atk.reports.pdf-cash', compact('start', 'end', 'period', 'cash', 'startBalance', 'incoming', 'outgoing', 'endBalance', 'movements'))->setPaper('a4', 'portrait');
        return $pdf->download('laporan-kas-atk.pdf');
    }

    public function excel(Request $request)
    {
        return response()->streamDownload(function () use ($request) {
            $start = $request->get('start_date', \Illuminate\Support\Carbon::today()->subDays(7));
            $end = $request->get('end_date', \Illuminate\Support\Carbon::today());
            $period = $request->get('period', 'custom');

            if ($period === 'daily') {
                $start = \Illuminate\Support\Carbon::today();
                $end = \Illuminate\Support\Carbon::today();
            } elseif ($period === 'weekly') {
                $start = \Illuminate\Support\Carbon::today()->startOfWeek();
                $end = \Illuminate\Support\Carbon::today()->endOfWeek();
            } elseif ($period === 'monthly') {
                $start = \Illuminate\Support\Carbon::today()->startOfMonth();
                $end = \Illuminate\Support\Carbon::today()->endOfMonth();
            }

            // Use direction column and exclude reversed movements
            $incoming = \App\Models\AtkCashMovement::whereDate('occurred_at', '>=', $start)
                ->whereDate('occurred_at', '<=', $end)
                ->where('direction', 'in')
                ->whereNull('reversed_at')
                ->sum('amount');

            $outgoing = \App\Models\AtkCashMovement::whereDate('occurred_at', '>=', $start)
                ->whereDate('occurred_at', '<=', $end)
                ->where('direction', 'out')
                ->whereNull('reversed_at')
                ->sum('amount');

            $startBalance = 0;
            $earliest = \App\Models\AtkCashMovement::whereDate('occurred_at', '<', $start)
                ->whereNull('reversed_at')
                ->orderBy('occurred_at', 'desc')
                ->first();
            if ($earliest) {
                $startBalance = $earliest->balance_after;
            }

            $movements = \App\Models\AtkCashMovement::whereDate('occurred_at', '>=', $start)
                ->whereDate('occurred_at', '<=', $end)
                ->whereNull('reversed_at')
                ->orderBy('occurred_at', 'asc')
                ->get();

            $endBalance = $startBalance;
            foreach ($movements as $movement) {
                $isIncoming = $movement->direction === 'in';
                if ($isIncoming) {
                    $endBalance += $movement->amount;
                } else {
                    $endBalance -= $movement->amount;
                }
                $movement->running_balance = $endBalance;
            }

            $writer = new \OpenSpout\Writer\XLSX\Writer();
            $writer->openToFile('php://output');

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Laporan Kas ATK']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Periode', ($start instanceof \Illuminate\Support\Carbon ? $start->format('Y-m-d') : $start), 'sampai', ($end instanceof \Illuminate\Support\Carbon ? $end->format('Y-m-d') : $end)]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Saldo Awal', $startBalance]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Kas Masuk', $incoming]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Kas Keluar', $outgoing]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Saldo Akhir', $endBalance]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['No', 'Tanggal', 'Kategori', 'Keterangan', 'Masuk', 'Keluar', 'Saldo Berjalan']));
            foreach ($movements as $index => $movement) {
                $isIncoming = in_array($movement->movement_type, ['sale', 'service', 'topup', 'ppob', 'owner_loan', 'adjustment_in']);
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $index + 1,
                    $movement->created_at->format('Y-m-d H:i'),
                    $movement->movement_type,
                    $movement->description,
                    $isIncoming ? $movement->amount : 0,
                    !$isIncoming ? $movement->amount : 0,
                    $movement->running_balance
                ]));
            }
            $writer->close();
        }, 'laporan-kas-atk.xlsx');
    }
}
