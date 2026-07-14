<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EscalationNotification;
use App\Models\SlaBreach;
use App\Models\SlaRule;
use App\Models\Setting;
use App\Models\Ticket;
use App\Services\SlaMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlaMonitoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_ticket_as_escalated_and_creates_breaches_and_notifications(): void
    {
        Setting::create(['key' => 'telegram_escalation_chat_id', 'value' => '123456', 'group' => 'telegram', 'type' => 'text', 'label' => 'test']);

        SlaRule::query()->delete();
        SlaRule::create(['name' => 'OPEN > 24 JAM', 'threshold_minutes' => 1440, 'status' => 'warning', 'is_active' => true]);
        SlaRule::create(['name' => 'OPEN > 48 JAM', 'threshold_minutes' => 2880, 'status' => 'critical', 'is_active' => true]);
        SlaRule::create(['name' => 'OPEN > 72 JAM', 'threshold_minutes' => 4320, 'status' => 'breached', 'is_active' => true]);

        $customer = Customer::create(['name' => 'Budi Santoso']);
        $ticket = Ticket::create([
            'ticket_number' => 'TKT-TEST01',
            'customer_id' => $customer->id,
            'type' => 'outage',
            'priority' => 'medium',
            'status' => 'open',
            'description' => 'Internet mati sejak pukul 08.00',
        ]);
        $ticket->created_at = now()->subHours(80);
        $ticket->updated_at = now()->subHours(80);
        $ticket->saveQuietly();

        app(SlaMonitoringService::class)->evaluateOpenTickets();

        $ticket->refresh();
        $this->assertSame('breached', $ticket->sla_status);

        $this->assertSame(3, SlaBreach::query()->where('ticket_id', $ticket->id)->count());
        $this->assertGreaterThan(0, EscalationNotification::query()->where('ticket_id', $ticket->id)->count());
    }
}
