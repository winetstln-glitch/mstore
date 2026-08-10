<?php

namespace App\Http\Controllers;

use App\Models\WashCommissionEarning;
use App\Models\WashLoyaltyCounter;
use App\Models\WashMember;
use App\Models\WashMemberLevel;
use App\Models\WashRewardRedemption;
use App\Models\WashTransaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WashReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:wash.report'),
        ];
    }

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

        $dailyCommission = 0;
        $monthlyCommission = 0;
        $dailyCommissionDetail = collect();
        $monthlyDailyCommissionMap = collect();
        try {
            if (Schema::hasTable('wash_commission_earnings')) {
                $dailyCommBaseQ = WashCommissionEarning::query()
                    ->join('wash_transactions as t', 't.id', '=', 'wash_commission_earnings.wash_transaction_id')
                    ->whereIn('wash_commission_earnings.status', [WashCommissionEarning::STATUS_EARNED, WashCommissionEarning::STATUS_PAID])
                    ->whereBetween('t.created_at', [
                        $startDate.' 00:00:00',
                        $endDate.' 23:59:59',
                    ]);
                if ($normalizedVehiclePlate !== '') {
                    $dailyCommBaseQ->whereRaw(
                        "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(t.vehicle_plate, ''), ' ', ''), '-', ''), '.', ''), '/', '')) = ?",
                        [$normalizedVehiclePlate]
                    );
                }
                $dailyCommission = (int) (clone $dailyCommBaseQ)->sum('wash_commission_earnings.total_earned');
                $dailyCommissionDetail = (clone $dailyCommBaseQ)
                    ->join('wash_employees as emp', 'emp.id', '=', 'wash_commission_earnings.wash_employee_id')
                    ->join('wash_transaction_items as wti', 'wti.id', '=', 'wash_commission_earnings.wash_transaction_item_id')
                    ->selectRaw('emp.id as employee_id, emp.name, wti.service_name, wash_commission_earnings.rate_per_unit, count(*) as item_count, sum(wash_commission_earnings.total_earned) as total_commission')
                    ->groupBy(['emp.id', 'emp.name', 'wti.service_name', 'wash_commission_earnings.rate_per_unit'])
                    ->orderBy('emp.name')
                    ->orderByDesc('total_commission')
                    ->get();

                $monthlyCommBaseQ = WashCommissionEarning::query()
                    ->join('wash_transactions as t', 't.id', '=', 'wash_commission_earnings.wash_transaction_id')
                    ->whereIn('wash_commission_earnings.status', [WashCommissionEarning::STATUS_EARNED, WashCommissionEarning::STATUS_PAID])
                    ->where('t.created_at', 'like', "$month%");
                if ($normalizedVehiclePlate !== '') {
                    $monthlyCommBaseQ->whereRaw(
                        "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(t.vehicle_plate, ''), ' ', ''), '-', ''), '.', ''), '/', '')) = ?",
                        [$normalizedVehiclePlate]
                    );
                }
                $monthlyCommission = (int) (clone $monthlyCommBaseQ)->sum('wash_commission_earnings.total_earned');
                $monthlyDailyCommissionMap = (clone $monthlyCommBaseQ)
                    ->select(DB::raw('DATE(t.created_at) as d'), DB::raw('SUM(wash_commission_earnings.total_earned) as total'))
                    ->groupBy(DB::raw('DATE(t.created_at)'))
                    ->get()
                    ->keyBy('d');
            }
        } catch (\Throwable) {}

        $dailyWashNetProfit = $dailyWashIncome - $dailyWashExpense - $dailyCommission;
        $dailyTotalNetProfit = $dailyIncome - $dailyExpense - $dailyCommission;
        $monthlyWashNetProfit = $monthlyWashIncome - $monthlyWashExpense - $monthlyCommission;
        $monthlyTotalNetProfit = $monthlyIncome - $monthlyExpense - $monthlyCommission;

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

        $memberActiveCount = WashMember::query()->where('status', 'active')->count();
        $memberNewDailyCount = WashMember::query()
            ->whereBetween('joined_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->count();
        $memberNewMonthlyCount = WashMember::query()
            ->where('joined_at', 'like', "$month%")
            ->count();
        $topMembers = WashMember::query()
            ->with('level')
            ->orderByDesc('total_spending')
            ->orderByDesc('total_transactions')
            ->limit(10)
            ->get();
        $levelDistribution = WashMemberLevel::query()
            ->where('is_active', true)
            ->withCount('members')
            ->orderBy('min_transactions')
            ->get();

        $dailyRewardRedemptionsQuery = WashRewardRedemption::query()
            ->with(['voucher.member.level', 'voucher.customer'])
            ->whereBetween('redeemed_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->orderByDesc('redeemed_at');
        if ($normalizedVehiclePlate !== '') {
            $dailyRewardRedemptionsQuery->whereHas('voucher', function ($query) use ($normalizedVehiclePlate) {
                $query->whereRaw(
                    "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(vehicle_plate, ''), ' ', ''), '-', ''), '.', ''), '/', '')) = ?",
                    [$normalizedVehiclePlate]
                );
            });
        }
        $dailyRewardRedemptions = $dailyRewardRedemptionsQuery->limit(20)->get();
        $dailyRewardRedemptionCount = (clone $dailyRewardRedemptionsQuery)->count();

        $monthlyRewardRedemptionsQuery = WashRewardRedemption::query()
            ->with(['voucher.member.level', 'voucher.customer'])
            ->where('redeemed_at', 'like', "$month%")
            ->orderByDesc('redeemed_at');
        if ($normalizedVehiclePlate !== '') {
            $monthlyRewardRedemptionsQuery->whereHas('voucher', function ($query) use ($normalizedVehiclePlate) {
                $query->whereRaw(
                    "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(vehicle_plate, ''), ' ', ''), '-', ''), '.', ''), '/', '')) = ?",
                    [$normalizedVehiclePlate]
                );
            });
        }
        $monthlyRewardRedemptions = $monthlyRewardRedemptionsQuery->limit(20)->get();
        $monthlyRewardRedemptionCount = (clone $monthlyRewardRedemptionsQuery)->count();

        $loyaltyProgressRowsQuery = WashLoyaltyCounter::query()
            ->with(['member.level', 'customer'])
            ->orderByDesc('last_paid_at')
            ->orderByDesc('lifetime_paid_count');
        if ($normalizedVehiclePlate !== '') {
            $loyaltyProgressRowsQuery->whereRaw(
                "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(vehicle_plate, ''), ' ', ''), '-', ''), '.', ''), '/', '')) = ?",
                [$normalizedVehiclePlate]
            );
        }
        $loyaltyProgressRows = $loyaltyProgressRowsQuery->limit(20)->get()->map(function (WashLoyaltyCounter $counter) {
            $progress = (int) ($counter->cycle_paid_count ?? 0);
            $target = 10;

            return (object) [
                'member_name' => $counter->member?->name ?? $counter->customer?->name ?? '-',
                'member_number' => $counter->member?->member_number ?? '-',
                'level_name' => $counter->member?->level?->name ?? 'Bronze Member',
                'vehicle_plate' => $counter->vehicle_plate,
                'progress' => $progress,
                'target' => $target,
                'remaining' => max($target - $progress, 0),
                'lifetime_paid_count' => (int) ($counter->lifetime_paid_count ?? 0),
                'last_paid_at' => $counter->last_paid_at,
            ];
        });

        $dailyCommissionItemCount = 0;
        $dailyCommissionEmpCount = collect($dailyCommissionDetail)->pluck('employee_id')->unique()->count();
        foreach ($dailyCommissionDetail as $d) {
            $dailyCommissionItemCount += (int) $d->item_count;
        }
        $dailyLabaKotor = $dailyIncome - $dailyExpense;
        $dailyTrxCount = $dailyIncomeRows->count();
        $dailyExpCount = $dailyExpenseRows->count();

        $dailyCash = (float) (collect($dailyByPayment)->firstWhere('payment_method', 'cash')->amount ?? 0);
        $dailyQris = (float) (collect($dailyByPayment)->firstWhere('payment_method', 'qris')->amount ?? 0);
        $dailyTransfer = (float) (collect($dailyByPayment)->firstWhere('payment_method', 'transfer')->amount ?? 0);
        $dailySetoranCash = $dailyCash - (float) $dailyExpense;
        $dailySetoranCashBersih = $dailyCash - (float) $dailyExpense - (float) $dailyCommission;
        $loyaltyBonusCount = $dailyIncomeRows->filter(fn ($r) => str_starts_with(strtolower(trim((string) ($r->notes ?? ''))), 'bonus_cuci'))->count();
        $dailySvcTotal = (float) $dailyByService->sum('amount');
        $dailySvcDiff = (float) $dailyIncome - $dailySvcTotal;
        $dailyDiscountTotal = (float) $dailyIncomeRows->sum('discount_amount');

        $monthlyLabaKotor = $monthlyIncome - $monthlyExpense;

        $monthlyCash = (float) (collect($monthlyByPayment)->firstWhere('payment_method', 'cash')->amount ?? 0);
        $monthlyQris = (float) (collect($monthlyByPayment)->firstWhere('payment_method', 'qris')->amount ?? 0);
        $monthlyTransfer = (float) (collect($monthlyByPayment)->firstWhere('payment_method', 'transfer')->amount ?? 0);
        $monthlySetoranCash = $monthlyCash - (float) $monthlyExpense;
        $monthlySetoranCashBersih = $monthlyCash - (float) $monthlyExpense - (float) $monthlyCommission;
        $monthlySvcTotal = (float) $monthlyByService->sum('amount');
        $monthlySvcDiff = (float) $monthlyIncome - $monthlySvcTotal;

        return compact(
            'startDate', 'endDate', 'month',
            'vehiclePlate', 'knownVehiclePlates',
            'dailyIncome', 'dailyExpense', 'monthlyIncome', 'monthlyExpense',
            'dailyCaffeInitialCapital', 'dailyCaffeRevenue', 'monthlyCaffeInitialCapital', 'monthlyCaffeRevenue',
            'dailyWashIncome', 'dailyWashExpense', 'monthlyWashIncome', 'monthlyWashExpense',
            'dailyCommission', 'monthlyCommission',
            'dailyCommissionDetail', 'monthlyDailyCommissionMap',
            'dailyWashNetProfit', 'dailyTotalNetProfit',
            'monthlyWashNetProfit', 'monthlyTotalNetProfit',
            'dailyIncomeRows', 'dailyExpenseRows',
            'monthlyDailyIncome', 'monthlyDailyExpense',
            'dailyByService', 'dailyByPayment', 'monthlyByService', 'monthlyByPayment',
            'memberActiveCount', 'memberNewDailyCount', 'memberNewMonthlyCount',
            'topMembers', 'levelDistribution',
            'dailyRewardRedemptions', 'dailyRewardRedemptionCount',
            'monthlyRewardRedemptions', 'monthlyRewardRedemptionCount',
            'loyaltyProgressRows',
            'dailyCommissionItemCount', 'dailyCommissionEmpCount',
            'dailyLabaKotor', 'dailyTrxCount', 'dailyExpCount',
            'dailyCash', 'dailyQris', 'dailyTransfer',
            'dailySetoranCash', 'dailySetoranCashBersih',
            'loyaltyBonusCount', 'dailySvcTotal', 'dailySvcDiff', 'dailyDiscountTotal',
            'monthlyLabaKotor',
            'monthlyCash', 'monthlyQris', 'monthlyTransfer',
            'monthlySetoranCash', 'monthlySetoranCashBersih',
            'monthlySvcTotal', 'monthlySvcDiff'
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
        $transactions = WashTransaction::query()
            ->whereNotNull('vehicle_plate')
            ->whereRaw("TRIM(COALESCE(vehicle_plate, '')) <> ''")
            ->select('vehicle_plate', 'vehicle_brand')
            ->orderByDesc('created_at')
            ->get();

        $unique = [];
        foreach ($transactions as $transaction) {
            $raw = trim((string) $transaction->vehicle_plate);
            $normalized = $this->normalizePlate($raw);
            if ($normalized === '' || isset($unique[$normalized])) {
                continue;
            }
            $unique[$normalized] = [
                'plate' => $raw,
                'brand' => trim((string) ($transaction->vehicle_brand ?? '')),
            ];
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
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Pemasukan', $data['dailyIncome'], 'Pengeluaran', $data['dailyExpense'], 'Laba Kotor', $data['dailyIncome'] - $data['dailyExpense']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Potongan Komisi Operator', -$data['dailyCommission'], 'Laba Bersih (Setelah Komisi)', $data['dailyTotalNetProfit']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Caffe - Modal Awal', $data['dailyCaffeInitialCapital'], 'Caffe - Pendapatan', $data['dailyCaffeRevenue'], 'Caffe - Selisih', $data['dailyCaffeRevenue'] - $data['dailyCaffeInitialCapital']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Ringkasan Bulanan']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Pemasukan', $data['monthlyIncome'], 'Pengeluaran', $data['monthlyExpense'], 'Laba Kotor', $data['monthlyIncome'] - $data['monthlyExpense']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Potongan Komisi Operator', -$data['monthlyCommission'], 'Laba Bersih (Setelah Komisi)', $data['monthlyTotalNetProfit']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Caffe - Modal Awal', $data['monthlyCaffeInitialCapital'], 'Caffe - Pendapatan', $data['monthlyCaffeRevenue'], 'Caffe - Selisih', $data['monthlyCaffeRevenue'] - $data['monthlyCaffeInitialCapital']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            if ($data['dailyCommissionDetail']->count() > 0) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Rincian Komisi Karyawan (Harian)']));
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['No', 'Nama Karyawan', 'Jumlah Item', 'Total Komisi']));
                foreach ($data['dailyCommissionDetail'] as $idx => $d) {
                    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([$idx + 1, $d->name, (int)$d->item_count, (int)$d->total_commission]));
                }
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['', '', 'TOTAL POTONGAN KOMISI', -$data['dailyCommission']]));
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            }
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
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Membership & Loyalty']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                'Member Aktif', $data['memberActiveCount'],
                'Member Baru (Harian)', $data['memberNewDailyCount'],
                'Member Baru (Bulanan)', $data['memberNewMonthlyCount'],
            ]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                'Reward Redemption (Harian)', $data['dailyRewardRedemptionCount'],
                'Reward Redemption (Bulanan)', $data['monthlyRewardRedemptionCount'],
            ]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Distribusi Level']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Level', 'Member', 'Diskon', 'Priority Rank']));
            foreach ($data['levelDistribution'] as $level) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $level->name,
                    $level->members_count,
                    $level->discount_percent,
                    $level->priority_rank,
                ]));
            }
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Top Member']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Member', 'No Member', 'Level', 'Total Transaksi', 'Total Kunjungan', 'Total Spending']));
            foreach ($data['topMembers'] as $member) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $member->name,
                    $member->member_number,
                    $member->level?->name ?? 'Bronze Member',
                    $member->total_transactions,
                    $member->total_visits,
                    $member->total_spending,
                ]));
            }
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Loyalty Progress']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Member', 'No Member', 'Level', 'Plat', 'Progress', 'Sisa', 'Lifetime Paid', 'Transaksi Terakhir']));
            foreach ($data['loyaltyProgressRows'] as $row) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $row->member_name,
                    $row->member_number,
                    $row->level_name,
                    $row->vehicle_plate,
                    $row->progress.'/'.$row->target,
                    $row->remaining,
                    $row->lifetime_paid_count,
                    $row->last_paid_at?->format('Y-m-d H:i') ?? '-',
                ]));
            }
            $writer->close();
        }, 'laporan_wash.xlsx');
    }
}
