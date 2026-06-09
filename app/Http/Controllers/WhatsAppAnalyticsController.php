<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\Ticket;
use App\Models\VoucherPayment;
use App\Models\WhatsAppSession;
use App\Repositories\Contracts\WhatsAppAnalyticsEventRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WhatsAppAnalyticsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:whatsapp.analytics.view', only: ['index', 'data']),
        ];
    }

    public function index()
    {
        return view('whatsapp.analytics');
    }

    public function data(Request $request, WhatsAppAnalyticsEventRepositoryInterface $events): JsonResponse
    {
        $from = $this->parseDate($request->query('from'), now()->startOfMonth());
        $to = $this->parseDate($request->query('to'), now()->endOfDay());

        $incoming = $events->countBetween($from, $to, ['direction' => 'incoming']);
        $outgoing = $events->countBetween($from, $to, ['direction' => 'outgoing']);
        $aiUsage = $events->countBetween($from, $to, ['used_ai' => true]);
        $totalSessions = WhatsAppSession::query()->whereBetween('created_at', [$from, $to])->count();

        $ticketCreated = Ticket::query()->whereBetween('created_at', [$from, $to])->count();
        $qrisPayments = PaymentTransaction::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('payment_method', 'like', '%QRIS%')
            ->count();
        $voucherSold = VoucherPayment::query()->whereBetween('created_at', [$from, $to])->whereIn('status', ['paid', 'success'])->count();

        $topIntents = $events->topIntents($from, $to, 5)->map(function ($row) {
            return [
                'intent' => $row->intent,
                'total' => (int) $row->total,
            ];
        })->values();

        $fallback = $events->countBetween($from, $to, ['is_fallback' => true]);
        $aiResolution = $aiUsage > 0 ? (int) round((($aiUsage - $fallback) / $aiUsage) * 100) : 0;
        $aiEscalation = $aiUsage > 0 ? (int) round((Ticket::query()->whereBetween('created_at', [$from, $to])->count() / max(1, $aiUsage)) * 100) : 0;

        return response()->json([
            'ok' => true,
            'range' => [
                'from' => $from->toDateTimeString(),
                'to' => $to->toDateTimeString(),
            ],
            'summary' => [
                'incoming' => $incoming,
                'outgoing' => $outgoing,
                'sessions' => $totalSessions,
                'ai_usage' => $aiUsage,
                'ticket_created' => $ticketCreated,
                'qris_payment' => $qrisPayments,
                'voucher_sold' => $voucherSold,
            ],
            'intent_analytics' => $topIntents,
            'ai_analytics' => [
                'resolution_rate' => $aiResolution,
                'escalation_rate' => $aiEscalation,
                'fallback_rate' => $aiUsage > 0 ? (int) round(($fallback / $aiUsage) * 100) : 0,
            ],
        ]);
    }

    private function parseDate(?string $value, Carbon $fallback): Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return $fallback;
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return $fallback;
        }
    }
}

