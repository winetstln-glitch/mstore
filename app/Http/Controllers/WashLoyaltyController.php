<?php

namespace App\Http\Controllers;

use App\Models\WashLoyaltyCounter;
use App\Models\WashRewardRedemption;
use App\Models\WashRewardVoucher;
use App\Services\Wash\WashLoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WashLoyaltyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:wash.loyalty.view', only: ['index']),
            new Middleware('permission:wash.reward.view', only: ['vouchers', 'redemptions', 'report']),
        ];
    }

    public function index(Request $request, WashLoyaltyService $loyalty)
    {
        $q = trim((string) $request->get('q', ''));

        $counters = WashLoyaltyCounter::query()
            ->with('customer')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('vehicle_plate', 'like', '%'.strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $q)).'%')
                    ->orWhereHas('customer', function ($customerQuery) use ($q) {
                        $customerQuery->where('name', 'like', '%'.$q.'%')
                            ->orWhere('phone', 'like', '%'.$q.'%');
                    });
            })
            ->orderByDesc('last_paid_at')
            ->paginate(20)
            ->appends($request->query());

        $target = $loyalty->target();
        $voucherCounts = WashRewardVoucher::query()
            ->selectRaw('vehicle_plate, COUNT(*) as cnt')
            ->where('status', 'available')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->groupBy('vehicle_plate')
            ->pluck('cnt', 'vehicle_plate');

        return view('wash.loyalty.index', compact('counters', 'target', 'voucherCounts', 'q'));
    }

    public function vouchers(Request $request)
    {
        $status = trim((string) $request->get('status', ''));
        $q = trim((string) $request->get('q', ''));

        $vouchers = WashRewardVoucher::query()
            ->with('customer')
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where('code', 'like', '%'.strtoupper($q).'%')
                    ->orWhere('vehicle_plate', 'like', '%'.strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $q)).'%')
                    ->orWhereHas('customer', function ($customerQuery) use ($q) {
                        $customerQuery->where('name', 'like', '%'.$q.'%')
                            ->orWhere('phone', 'like', '%'.$q.'%');
                    });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        return view('wash.loyalty.vouchers', compact('vouchers', 'status', 'q'));
    }

    public function redemptions(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $redemptions = WashRewardRedemption::query()
            ->with(['voucher', 'transaction', 'redeemedBy'])
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('voucher', function ($voucherQuery) use ($q) {
                    $voucherQuery->where('code', 'like', '%'.strtoupper($q).'%')
                        ->orWhere('vehicle_plate', 'like', '%'.strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $q)).'%');
                })->orWhereHas('transaction', function ($trxQuery) use ($q) {
                    $trxQuery->where('transaction_number', 'like', '%'.$q.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        return view('wash.loyalty.redemptions', compact('redemptions', 'q'));
    }

    public function report(Request $request)
    {
        $range = trim((string) $request->get('range', '30'));
        $days = max(1, min(365, (int) $range));
        $from = now()->subDays($days)->startOfDay();

        $totalCounters = WashLoyaltyCounter::query()->count();
        $activeVouchers = WashRewardVoucher::query()
            ->where('status', 'available')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();
        $usedVouchers = WashRewardVoucher::query()->where('status', 'used')->count();
        $expiredVouchers = WashRewardVoucher::query()
            ->where(function ($q) {
                $q->where('status', 'expired')
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'available')->whereNotNull('expires_at')->where('expires_at', '<=', now());
                    });
            })
            ->count();

        $issuedInRange = WashRewardVoucher::query()->where('issued_at', '>=', $from)->count();
        $redeemedInRange = WashRewardRedemption::query()->where('redeemed_at', '>=', $from)->count();

        $topLoyal = WashLoyaltyCounter::query()
            ->with('customer')
            ->orderByDesc('lifetime_paid_count')
            ->limit(10)
            ->get();

        return view('wash.loyalty.report', compact(
            'days',
            'from',
            'totalCounters',
            'activeVouchers',
            'usedVouchers',
            'expiredVouchers',
            'issuedInRange',
            'redeemedInRange',
            'topLoyal'
        ));
    }
}
