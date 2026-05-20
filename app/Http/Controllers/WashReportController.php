<?php

namespace App\Http\Controllers;

use App\Models\WashTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WashReportController extends Controller
{
    private function buildData(Request $request)
    {
        $startDate = (string) $request->input('start_date', $request->input('date', now()->format('Y-m-d')));
        $endDate = (string) $request->input('end_date', $request->input('date', $startDate));
        if ($startDate === '') {
            $startDate = now()->format('Y-m-d');
        }
        if ($endDate === '') {
            $endDate = $startDate;
        }
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        $month = $request->input('month', now()->format('Y-m'));
        $vehiclePlate = (string) $request->input('vehicle_plate', '');
        $normalizedVehiclePlate = $this->normalizePlate($vehiclePlate);
        $knownVehiclePlates = $this->getKnownVehiclePlates();

        $dailyIncomeQuery = WashTransaction::query()->whereBetween('created_at', [
            $startDate.' 00:00:00',
            $endDate.' 23:59:59',
        ]);
        $this->applyVehiclePlateFilter($dailyIncomeQuery, $normalizedVehiclePlate);
        $dailyIncome = $dailyIncomeQuery->sum('total_amount');
        $dailyExpense = \App\Models\Transaction::where('type', 'expense')
            ->where('category', 'Pengeluaran Pengurus')
            ->where('reference_number', 'like', 'WASH-EXP-%')
            ->whereBetween('transaction_date', [
                $startDate.' 00:00:00',
                $endDate.' 23:59:59',
            ])->sum('amount');

        $monthlyIncomeQuery = WashTransaction::query()->where('created_at', 'like', "$month%");
        $this->applyVehiclePlateFilter($monthlyIncomeQuery, $normalizedVehiclePlate);
        $monthlyIncome = $monthlyIncomeQuery->sum('total_amount');
        $monthlyExpense = \App\Models\Transaction::where('type', 'expense')
            ->where('category', 'Pengeluaran Pengurus')
            ->where('reference_number', 'like', 'WASH-EXP-%')
            ->whereMonth('transaction_date', substr($month, 5, 2))
            ->whereYear('transaction_date', substr($month, 0, 4))->sum('amount');
        $dailyCaffeInitialCapital = \App\Models\Transaction::where('type', 'expense')
            ->where('reference_number', 'like', 'WASH-EXP-%')
            ->where(function ($q) {
                $q->where('category', 'like', '%Kopi%')
                    ->orWhere('category', 'like', '%Caffe%')
                    ->orWhere('category', 'like', '%Warkop%');
            })
            ->whereBetween('transaction_date', [
                $startDate.' 00:00:00',
                $endDate.' 23:59:59',
            ])->sum('amount');
        $monthlyCaffeInitialCapital = \App\Models\Transaction::where('type', 'expense')
            ->where('reference_number', 'like', 'WASH-EXP-%')
            ->where(function ($q) {
                $q->where('category', 'like', '%Kopi%')
                    ->orWhere('category', 'like', '%Caffe%')
                    ->orWhere('category', 'like', '%Warkop%');
            })
            ->whereMonth('transaction_date', substr($month, 5, 2))
            ->whereYear('transaction_date', substr($month, 0, 4))
            ->sum('amount');
        $dailyCaffeRevenueQuery = DB::table('wash_transaction_items as i')
            ->join('wash_transactions as t', 't.id', '=', 'i.wash_transaction_id')
            ->leftJoin('wash_services as s', 's.id', '=', 'i.wash_service_id')
            ->whereBetween('t.created_at', [
                $startDate.' 00:00:00',
                $endDate.' 23:59:59',
            ])
            ->where(function ($q) {
                $q->where('s.vehicle_type', 'coffee')
                    ->orWhereRaw("LOWER(COALESCE(i.service_name, '')) like '%kopi%'")
                    ->orWhereRaw("LOWER(COALESCE(i.service_name, '')) like '%caffe%'")
                    ->orWhereRaw("LOWER(COALESCE(i.service_name, '')) like '%warkop%'");
            });
        if ($normalizedVehiclePlate !== '') {
            $dailyCaffeRevenueQuery->whereRaw(
                "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(t.vehicle_plate, ''), ' ', ''), '-', ''), '.', ''), '/', '')) = ?",
                [$normalizedVehiclePlate]
            );
        }
        $dailyCaffeRevenue = (float) $dailyCaffeRevenueQuery->sum('i.subtotal');
        $monthlyCaffeRevenueQuery = DB::table('wash_transaction_items as i')
            ->join('wash_transactions as t', 't.id', '=', 'i.wash_transaction_id')
            ->leftJoin('wash_services as s', 's.id', '=', 'i.wash_service_id')
            ->where('t.created_at', 'like', "$month%")
            ->where(function ($q) {
                $q->where('s.vehicle_type', 'coffee')
                    ->orWhereRaw("LOWER(COALESCE(i.service_name, '')) like '%kopi%'")
                    ->orWhereRaw("LOWER(COALESCE(i.service_name, '')) like '%caffe%'")
                    ->orWhereRaw("LOWER(COALESCE(i.service_name, '')) like '%warkop%'");
            });
        if ($normalizedVehiclePlate !== '') {
            $monthlyCaffeRevenueQuery->whereRaw(
                "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(t.vehicle_plate, ''), ' ', ''), '-', ''), '.', ''), '/', '')) = ?",
                [$normalizedVehiclePlate]
            );
        }
        $monthlyCaffeRevenue = (float) $monthlyCaffeRevenueQuery->sum('i.subtotal');
        $dailyWashIncome = $dailyIncome - $dailyCaffeRevenue;
        $dailyWashExpense = $dailyExpense - $dailyCaffeInitialCapital;
        $monthlyWashIncome = $monthlyIncome - $monthlyCaffeRevenue;
        $monthlyWashExpense = $monthlyExpense - $monthlyCaffeInitialCapital;

        $dailyIncomeRowsQuery = WashTransaction::query()
            ->with('user:id,name')
            ->whereBetween('created_at', [
                $startDate.' 00:00:00',
                $endDate.' 23:59:59',
            ])
            ->select(['id', 'transaction_number', 'total_amount', 'payment_method', 'vehicle_plate', 'created_at', 'user_id', 'queue_number', 'notes', 'discount_amount'])
            ->orderByDesc('created_at');
        $this->applyVehiclePlateFilter($dailyIncomeRowsQuery, $normalizedVehiclePlate);
        $dailyIncomeRows = $dailyIncomeRowsQuery->get();
        $dailyExpenseRows = \App\Models\Transaction::where('type', 'expense')
            ->where('category', 'Pengeluaran Pengurus')
            ->where('reference_number', 'like', 'WASH-EXP-%')
            ->whereBetween('transaction_date', [
                $startDate.' 00:00:00',
                $endDate.' 23:59:59',
            ])
            ->select(['id', 'description', 'amount', 'transaction_date'])
            ->orderByDesc('transaction_date')->get();

        $monthlyDailyIncomeQuery = WashTransaction::query()
            ->where('created_at', 'like', "$month%")
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(total_amount) as total'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('d', 'asc');
        $this->applyVehiclePlateFilter($monthlyDailyIncomeQuery, $normalizedVehiclePlate);
        $monthlyDailyIncome = $monthlyDailyIncomeQuery->get();
        $monthlyDailyExpense = \App\Models\Transaction::where('type', 'expense')
            ->where('category', 'Pengeluaran Pengurus')
            ->where('reference_number', 'like', 'WASH-EXP-%')
            ->whereMonth('transaction_date', substr($month, 5, 2))
            ->whereYear('transaction_date', substr($month, 0, 4))
            ->select(DB::raw('DATE(transaction_date) as d'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('DATE(transaction_date)'))->orderBy('d', 'asc')->get();

        $dailyByServiceQuery = DB::table('wash_transaction_items as i')
            ->join('wash_transactions as t', 't.id', '=', 'i.wash_transaction_id')
            ->whereBetween('t.created_at', [
                $startDate.' 00:00:00',
                $endDate.' 23:59:59',
            ])
            ->select('i.service_name', DB::raw('SUM(i.quantity) as total_qty'), DB::raw('SUM(i.subtotal) as amount'))
            ->groupBy('i.service_name')
            ->orderByDesc('amount');
        if ($normalizedVehiclePlate !== '') {
            $dailyByServiceQuery->whereRaw(
                "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(t.vehicle_plate, ''), ' ', ''), '-', ''), '.', ''), '/', '')) = ?",
                [$normalizedVehiclePlate]
            );
        }
        $dailyByService = $dailyByServiceQuery->get();

        $dailyByPaymentQuery = WashTransaction::query()
            ->whereBetween('created_at', [
                $startDate.' 00:00:00',
                $endDate.' 23:59:59',
            ])
            ->select('payment_method', DB::raw('SUM(total_amount) as amount'))
            ->groupBy('payment_method')
            ->orderByDesc('amount');
        $this->applyVehiclePlateFilter($dailyByPaymentQuery, $normalizedVehiclePlate);
        $dailyByPayment = $dailyByPaymentQuery->get();

        $monthlyByServiceQuery = DB::table('wash_transaction_items as i')
            ->join('wash_transactions as t', 't.id', '=', 'i.wash_transaction_id')
            ->where('t.created_at', 'like', "$month%")
            ->select('i.service_name', DB::raw('SUM(i.quantity) as total_qty'), DB::raw('SUM(i.subtotal) as amount'))
            ->groupBy('i.service_name')
            ->orderByDesc('amount');
        if ($normalizedVehiclePlate !== '') {
            $monthlyByServiceQuery->whereRaw(
                "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(t.vehicle_plate, ''), ' ', ''), '-', ''), '.', ''), '/', '')) = ?",
                [$normalizedVehiclePlate]
            );
        }
        $monthlyByService = $monthlyByServiceQuery->get();

        $monthlyByPaymentQuery = WashTransaction::query()
            ->where('created_at', 'like', "$month%")
            ->select('payment_method', DB::raw('SUM(total_amount) as amount'))
            ->groupBy('payment_method')
            ->orderByDesc('amount');
        $this->applyVehiclePlateFilter($monthlyByPaymentQuery, $normalizedVehiclePlate);
        $monthlyByPayment = $monthlyByPaymentQuery->get();

        return compact(
            'startDate', 'endDate', 'month',
            'vehiclePlate', 'knownVehiclePlates',
            'dailyIncome', 'dailyExpense', 'monthlyIncome', 'monthlyExpense',
            'dailyCaffeInitialCapital', 'dailyCaffeRevenue', 'monthlyCaffeInitialCapital', 'monthlyCaffeRevenue',
            'dailyWashIncome', 'dailyWashExpense', 'monthlyWashIncome', 'monthlyWashExpense',
            'dailyIncomeRows', 'dailyExpenseRows',
            'monthlyDailyIncome', 'monthlyDailyExpense',
            'dailyByService', 'dailyByPayment', 'monthlyByService', 'monthlyByPayment'
        );
    }

    private function applyVehiclePlateFilter($query, string $normalizedPlate): void
    {
        if ($normalizedPlate === '') {
            return;
        }
        $query->whereRaw(
            "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(vehicle_plate, ''), ' ', ''), '-', ''), '.', ''), '/', '')) = ?",
            [$normalizedPlate]
        );
    }

    private function normalizePlate(string $plate): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $plate));
    }

    private function getKnownVehiclePlates(): array
    {
        $plates = WashTransaction::query()
            ->whereNotNull('vehicle_plate')
            ->whereRaw("TRIM(COALESCE(vehicle_plate, '')) <> ''")
            ->orderByDesc('created_at')
            ->pluck('vehicle_plate')
            ->all();

        $unique = [];
        foreach ($plates as $plate) {
            $raw = trim((string) $plate);
            $normalized = $this->normalizePlate($raw);
            if ($normalized === '' || isset($unique[$normalized])) {
                continue;
            }
            $unique[$normalized] = $raw;
        }

        return array_values($unique);
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
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Rentang Harian', $data['startDate'].' s/d '.$data['endDate'], 'Bulan', $data['month']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Ringkasan Harian']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Pemasukan', $data['dailyIncome'], 'Pengeluaran', $data['dailyExpense'], 'Laba', $data['dailyIncome'] - $data['dailyExpense']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Caffe - Modal Awal', $data['dailyCaffeInitialCapital'], 'Caffe - Pendapatan', $data['dailyCaffeRevenue'], 'Caffe - Selisih', $data['dailyCaffeRevenue'] - $data['dailyCaffeInitialCapital']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Ringkasan Bulanan']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Pemasukan', $data['monthlyIncome'], 'Pengeluaran', $data['monthlyExpense'], 'Laba', $data['monthlyIncome'] - $data['monthlyExpense']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Caffe - Modal Awal', $data['monthlyCaffeInitialCapital'], 'Caffe - Pendapatan', $data['monthlyCaffeRevenue'], 'Caffe - Selisih', $data['monthlyCaffeRevenue'] - $data['monthlyCaffeInitialCapital']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Rincian Pemasukan Harian']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Tanggal', 'Waktu', 'No Antri', 'No Trx', 'Kasir', 'Metode', 'Total']));
            foreach ($data['dailyIncomeRows'] as $r) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $r->created_at->format('Y-m-d'),
                    $r->created_at->format('H:i'),
                    $r->queue_number ?? '-',
                    $r->transaction_number,
                    $r->user->name ?? '-',
                    strtoupper($r->payment_method),
                    $r->total_amount
                ]));
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
