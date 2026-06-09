<?php

namespace App\Services;

use App\Models\AreaOutage;
use App\Models\GenieDeviceStatus;
use App\Models\Router;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\NocMetricSnapshotRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class NocMetricsService
{
    public function __construct(
        private readonly NocMetricSnapshotRepositoryInterface $snapshots,
    ) {}

    public function latestCached(): ?array
    {
        $cached = Cache::get('noc.metrics.latest');
        if (is_array($cached)) {
            return $cached;
        }

        $latest = $this->snapshots->latest();
        if (! $latest) {
            return null;
        }

        $payload = $latest->toArray();
        Cache::put('noc.metrics.latest', $payload, 120);

        return $payload;
    }

    public function capture(): array
    {
        $onu = $this->computeOnuMetrics();
        $pppoe = $this->computePppoeMetrics();
        $outage = $this->computeOutageMetrics();
        $tickets = $this->computeTicketMetrics();
        $tech = $this->computeTechnicianMetrics();

        $health = $this->computeNetworkHealthScore(
            onuOffline: (int) $onu['onu_offline'],
            onuLos: (int) $onu['onu_los'],
            ticketOpen: (int) $tickets['ticket_open'],
            outageActive: (int) $outage['outage_active']
        );

        $record = $this->snapshots->create([
            'captured_at' => now(),
            ...$onu,
            ...$pppoe,
            ...$outage,
            ...$tickets,
            ...$tech,
            'network_health_score' => $health,
            'meta' => [
                'router_count' => (int) Router::query()->count(),
            ],
        ]);

        $payload = $record->toArray();
        Cache::put('noc.metrics.latest', $payload, 120);

        return $payload;
    }

    private function computeOnuMetrics(): array
    {
        $thresholdMinutes = (int) \App\Models\Setting::getValue('genieacs_online_threshold_minutes', 15);
        $freshSince = now()->subMinutes($thresholdMinutes);

        $base = GenieDeviceStatus::query()
            ->whereNotNull('customer_id')
            ->where('updated_at', '>=', $freshSince);

        $online = (clone $base)->where('is_online', true)->count();
        $offline = (clone $base)->where('is_online', false)->count();

        $los = (clone $base)
            ->where('is_online', false)
            ->where(function ($q) {
                $q->where('last_reason', 'like', '%LOS%')
                    ->orWhere('last_reason', 'like', '%Loss of Signal%');
            })->count();

        $dyingGasp = (clone $base)
            ->where('is_online', false)
            ->where('last_reason', 'like', '%Dying Gasp%')
            ->count();

        $weakSignal = (clone $base)
            ->whereNotNull('rx_power')
            ->get(['rx_power'])
            ->filter(function ($row) {
                $rx = $this->parseDbm($row->rx_power);
                return $rx !== null && $rx <= -27.0;
            })
            ->count();

        return [
            'onu_online' => $online,
            'onu_offline' => $offline,
            'onu_los' => $los,
            'onu_dying_gasp' => $dyingGasp,
            'onu_weak_signal' => $weakSignal,
        ];
    }

    private function computePppoeMetrics(): array
    {
        $cached = Cache::get('noc.metrics.pppoe');
        if (is_array($cached)) {
            return [
                'pppoe_online' => (int) ($cached['pppoe_online'] ?? 0),
                'pppoe_offline' => (int) ($cached['pppoe_offline'] ?? 0),
                'pppoe_active_sessions' => (int) ($cached['pppoe_active_sessions'] ?? 0),
                'pppoe_total_users' => (int) ($cached['pppoe_total_users'] ?? 0),
            ];
        }

        $routers = Router::query()->where('is_active', true)->get();
        $activeSessions = 0;
        $totalUsers = 0;
        $routerOnline = 0;
        $routerOffline = 0;

        foreach ($routers as $router) {
            $service = new MikrotikService($router);
            if (! $service->isConnected()) {
                $routerOffline++;
                continue;
            }
            $routerOnline++;
            $activeSessions += $service->getPppoeActiveCount();
            $totalUsers += count($service->getSecrets());
        }

        $result = [
            'pppoe_online' => $routerOnline,
            'pppoe_offline' => $routerOffline,
            'pppoe_active_sessions' => $activeSessions,
            'pppoe_total_users' => $totalUsers,
        ];

        Cache::put('noc.metrics.pppoe', $result, 180);

        return $result;
    }

    private function computeOutageMetrics(): array
    {
        $active = AreaOutage::query()->where('status', 'active');

        $countActive = (clone $active)->count();
        $maintenance = (clone $active)->where('type', 'maintenance')->count();
        $fiberCut = (clone $active)->where('type', 'fiber_cut')->count();
        $oltDown = (clone $active)->where('type', 'olt_down')->count();

        return [
            'outage_active' => $countActive,
            'outage_maintenance' => $maintenance,
            'outage_fiber_cut' => $fiberCut,
            'outage_olt_down' => $oltDown,
        ];
    }

    private function computeTicketMetrics(): array
    {
        $base = Ticket::query();

        $open = (clone $base)->where('status', 'open')->count();
        $inProgress = (clone $base)->where('status', 'in_progress')->count();
        $pending = (clone $base)->where('status', 'pending')->count();
        $closed = (clone $base)->whereIn('status', ['closed', 'solved'])->count();

        return [
            'ticket_open' => $open,
            'ticket_in_progress' => $inProgress,
            'ticket_pending' => $pending,
            'ticket_closed' => $closed,
        ];
    }

    private function computeTechnicianMetrics(): array
    {
        $onlineThreshold = now()->subMinutes(2);
        $technicians = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'technician'))
            ->where('is_active', true);

        $online = (clone $technicians)->where('last_seen_at', '>=', $onlineThreshold)->count();
        $offline = (clone $technicians)->where(function ($q) use ($onlineThreshold) {
            $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', $onlineThreshold);
        })->count();

        $handling = Ticket::query()
            ->whereIn('status', ['assigned', 'in_progress'])
            ->whereNotNull('technician_id')
            ->distinct('technician_id')
            ->count('technician_id');

        $available = max(0, $online - $handling);

        return [
            'technician_online' => $online,
            'technician_offline' => $offline,
            'technician_handling_ticket' => $handling,
            'technician_available' => $available,
        ];
    }

    private function computeNetworkHealthScore(int $onuOffline, int $onuLos, int $ticketOpen, int $outageActive): int
    {
        $score = 100;
        $score -= min(40, (int) round($onuOffline / 10));
        $score -= min(25, $onuLos * 2);
        $score -= min(25, (int) round($ticketOpen / 5));
        $score -= min(30, $outageActive * 10);

        return max(0, min(100, $score));
    }

    private function parseDbm(?string $value): ?float
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        $normalized = str_replace(['dBm', 'DBM', 'dbm'], '', $value);
        $normalized = trim($normalized);
        if (! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }
}

