<?php

namespace App\Services;

use App\Models\EscalationNotification;
use App\Models\SlaRule;
use App\Models\Ticket;
use App\Repositories\Contracts\SlaBreachRepositoryInterface;
use App\Repositories\Contracts\SlaRuleRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SlaMonitoringService
{
    public function __construct(
        private readonly SlaRuleRepositoryInterface $rules,
        private readonly SlaBreachRepositoryInterface $breaches,
    ) {}

    public function evaluateOpenTickets(int $chunkSize = 200): array
    {
        $activeRules = $this->rules->activeOrdered();
        if ($activeRules->isEmpty()) {
            return ['evaluated' => 0, 'breaches_created' => 0, 'tickets_updated' => 0, 'notifications_queued' => 0];
        }

        $evaluated = 0;
        $breachesCreated = 0;
        $ticketsUpdated = 0;
        $notificationsQueued = 0;

        Ticket::query()
            ->whereNotIn('status', ['closed', 'solved'])
            ->orderBy('id')
            ->chunkById($chunkSize, function ($tickets) use ($activeRules, &$evaluated, &$breachesCreated, &$ticketsUpdated, &$notificationsQueued) {
                foreach ($tickets as $ticket) {
                    $evaluated++;
                    $result = $this->evaluateTicket($ticket, $activeRules->all());
                    $breachesCreated += $result['breaches_created'];
                    $ticketsUpdated += $result['tickets_updated'];
                    $notificationsQueued += $result['notifications_queued'];
                }
            });

        return [
            'evaluated' => $evaluated,
            'breaches_created' => $breachesCreated,
            'tickets_updated' => $ticketsUpdated,
            'notifications_queued' => $notificationsQueued,
        ];
    }

    public function evaluateTicket(Ticket $ticket, array $rules): array
    {
        $ageMinutes = (int) $ticket->created_at->diffInMinutes(now());
        $applicable = array_values(array_filter(
            $rules,
            fn (SlaRule $r) => $r->threshold_minutes !== null && $r->status !== null && $ageMinutes >= $r->threshold_minutes
        ));

        $breachesCreated = 0;
        $ticketsUpdated = 0;
        $notificationsQueued = 0;

        // Check for pre-deadline warnings first (based on sla_deadline)
        if ($ticket->sla_deadline) {
            $notificationsQueued += $this->checkDeadlineWarnings($ticket, $rules);
        }

        if (empty($applicable)) {
            if ($ticket->sla_status !== 'ok') {
                $ticket->forceFill(['sla_status' => 'ok'])->saveQuietly();
                $ticketsUpdated++;
            }

            return [
                'breaches_created' => $breachesCreated,
                'tickets_updated' => $ticketsUpdated,
                'notifications_queued' => $notificationsQueued,
            ];
        }

        $currentRule = end($applicable);
        $currentStatus = (string) $currentRule->status;

        if ($ticket->sla_status !== $currentStatus) {
            $ticket->forceFill(['sla_status' => $currentStatus])->saveQuietly();
            $ticketsUpdated++;
        }

        DB::transaction(function () use ($ticket, $applicable, $currentStatus, &$breachesCreated, &$notificationsQueued) {
            foreach ($applicable as $rule) {
                $breach = $this->breaches->firstOrCreateBreach($ticket, $rule, $currentStatus);
                if ($breach->wasRecentlyCreated) {
                    $breachesCreated++;
                    $notificationsQueued += $this->queueEscalationNotifications($ticket, $rule, 'breach');
                }
            }
        });

        return [
            'breaches_created' => $breachesCreated,
            'tickets_updated' => $ticketsUpdated,
            'notifications_queued' => $notificationsQueued,
        ];
    }

    private function checkDeadlineWarnings(Ticket $ticket, array $rules): int
    {
        $queued = 0;
        $now = now();
        $deadline = $ticket->sla_deadline;
        if (!$deadline) {
            return 0;
        }

        $minutesRemaining = (int) $now->diffInMinutes($deadline, false);

        // Get the most applicable rule
        $activeRule = collect($rules)->sortByDesc('threshold_minutes')->first();
        if (!$activeRule) {
            return 0;
        }

        // Check warning threshold (e.g., 2 hours before deadline)
        if ($activeRule->warning_threshold_hours && $minutesRemaining > 0 && $minutesRemaining <= $activeRule->warning_threshold_hours * 60) {
            $queued += $this->queueEscalationNotifications($ticket, $activeRule, 'warning', $minutesRemaining);
        }

        // Check critical threshold (e.g., 30 minutes before deadline)
        if ($activeRule->critical_threshold_hours && $minutesRemaining > 0 && $minutesRemaining <= $activeRule->critical_threshold_hours * 60) {
            $queued += $this->queueEscalationNotifications($ticket, $activeRule, 'critical', $minutesRemaining);
        }

        return $queued;
    }

    public function closeTicketBreaches(Ticket $ticket): int
    {
        $updated = $this->breaches->resolveBreachesForTicket($ticket);
        if ($ticket->sla_status !== 'ok') {
            $ticket->forceFill(['sla_status' => 'ok'])->saveQuietly();
        }

        return $updated;
    }

    public function slaDashboardSummary(\Carbon\CarbonInterface $from, \Carbon\CarbonInterface $to): array
    {
        $totalClosed = Ticket::query()
            ->whereBetween('closed_at', [$from, $to])
            ->count();

        $closedWithBreach = 0;
        if (Schema::hasTable('sla_breaches')) {
            $closedWithBreach = Ticket::query()
                ->whereBetween('closed_at', [$from, $to])
                ->whereExists(function ($q) {
                    $q->selectRaw(1)
                        ->from('sla_breaches')
                        ->whereColumn('sla_breaches.ticket_id', 'tickets.id');
                })
                ->count();
        }

        $compliance = $totalClosed > 0 ? (int) round((($totalClosed - $closedWithBreach) / $totalClosed) * 100) : 100;
        $breachPercent = $totalClosed > 0 ? (int) round(($closedWithBreach / $totalClosed) * 100) : 0;

        $avgResolutionMinutes = (int) $this->avgTicketResolutionMinutes($from, $to);

        $criticalTickets = Ticket::query()
            ->whereNotIn('status', ['closed', 'solved'])
            ->whereIn('sla_status', ['critical', 'breached'])
            ->count();

        return [
            'sla_compliance_percent' => $compliance,
            'sla_breach_percent' => $breachPercent,
            'avg_resolution_minutes' => $avgResolutionMinutes,
            'critical_open_tickets' => $criticalTickets,
        ];
    }

    private function avgTicketResolutionMinutes(\Carbon\CarbonInterface $from, \Carbon\CarbonInterface $to): int
    {
        $driver = DB::connection()->getDriverName();

        $query = Ticket::query()
            ->whereBetween('closed_at', [$from, $to])
            ->whereNotNull('closed_at');

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $value = $query->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, closed_at)) as avg_minutes')->value('avg_minutes');
            return (int) round((float) ($value ?? 0));
        }

        if ($driver === 'pgsql') {
            $value = $query->selectRaw("AVG(EXTRACT(EPOCH FROM (closed_at - created_at)) / 60) as avg_minutes")->value('avg_minutes');
            return (int) round((float) ($value ?? 0));
        }

        if ($driver === 'sqlite') {
            $value = $query->selectRaw('AVG((julianday(closed_at) - julianday(created_at)) * 24 * 60) as avg_minutes')->value('avg_minutes');
            return (int) round((float) ($value ?? 0));
        }

        $tickets = $query->get(['created_at', 'closed_at']);
        if ($tickets->isEmpty()) {
            return 0;
        }

        $sum = 0;
        $count = 0;
        foreach ($tickets as $t) {
            if (! $t->created_at || ! $t->closed_at) {
                continue;
            }
            $sum += (int) $t->created_at->diffInMinutes($t->closed_at);
            $count++;
        }

        return $count > 0 ? (int) round($sum / $count) : 0;
    }

    private function queueEscalationNotifications(Ticket $ticket, SlaRule $rule, string $notificationType = 'breach', ?int $minutesRemaining = null): int
    {
        $targets = $this->resolveRecipientTargets($ticket);
        $payload = $this->buildEscalationPayload($ticket, $rule, $notificationType, $minutesRemaining);
        $count = 0;

        foreach ($targets as $target) {
            $exists = EscalationNotification::query()
                ->where('ticket_id', $ticket->id)
                ->where('sla_rule_id', $rule->id)
                ->where('notification_type', $notificationType)
                ->where('channel', $target['channel'])
                ->where('target', $target['target'])
                ->exists();
            if ($exists) {
                continue;
            }

            EscalationNotification::create([
                'ticket_id' => $ticket->id,
                'sla_rule_id' => $rule->id,
                'notification_type' => $notificationType,
                'channel' => $target['channel'],
                'target' => $target['target'],
                'recipient_role' => $target['role'],
                'status' => 'pending',
                'attempt' => 0,
                'payload' => $payload,
            ]);
            $count++;
        }

        return $count;
    }

    private function resolveRecipientTargets(Ticket $ticket): array
    {
        $targets = [];

        $technician = $ticket->technician_id ? \App\Models\User::find($ticket->technician_id) : null;
        if ($technician && trim((string) $technician->phone) !== '') {
            $targets[] = ['channel' => 'whatsapp', 'target' => $technician->phone, 'role' => 'technician'];
        }

        $coordinatorUserId = $ticket->coordinator ? $ticket->coordinator->user_id : null;
        if ($coordinatorUserId) {
            $coordinatorUser = \App\Models\User::find($coordinatorUserId);
            if ($coordinatorUser && trim((string) $coordinatorUser->phone) !== '') {
                $targets[] = ['channel' => 'whatsapp', 'target' => $coordinatorUser->phone, 'role' => 'coordinator'];
            }
        }

        $telegramChatId = trim((string) \App\Models\Setting::getValue('telegram_escalation_chat_id', ''));
        if ($telegramChatId !== '') {
            $targets[] = ['channel' => 'telegram', 'target' => $telegramChatId, 'role' => 'noc'];
        }

        $email = trim((string) \App\Models\Setting::getValue('sla_escalation_email', ''));
        if ($email !== '') {
            $targets[] = ['channel' => 'email', 'target' => $email, 'role' => 'administrator'];
        }

        return $targets;
    }

    private function buildEscalationPayload(Ticket $ticket, SlaRule $rule, string $notificationType, ?int $minutesRemaining = null): array
    {
        return [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'notification_type' => $notificationType,
            'minutes_remaining' => $minutesRemaining,
            'sla_deadline' => $ticket->sla_deadline?->toDateTimeString(),
            'sla_rule' => [
                'name' => $rule->name,
                'status' => $rule->status,
                'threshold_minutes' => $rule->threshold_minutes,
                'warning_threshold_hours' => $rule->warning_threshold_hours,
                'critical_threshold_hours' => $rule->critical_threshold_hours,
            ],
            'created_at' => $ticket->created_at?->toDateTimeString(),
        ];
    }
}
