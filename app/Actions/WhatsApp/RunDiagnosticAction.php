<?php

namespace App\Actions\WhatsApp;

use App\Models\Customer;
use App\Models\WhatsAppSession;
use App\Services\NetworkDiagnosticService;
use App\Services\TechnicianAssignmentService;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;

class RunDiagnosticAction
{
    protected $diagnosticService;
    protected $assignmentService;

    public function __construct(NetworkDiagnosticService $diagnosticService, TechnicianAssignmentService $assignmentService)
    {
        $this->diagnosticService = $diagnosticService;
        $this->assignmentService = $assignmentService;
    }

    public function execute(Customer $customer, WhatsAppSession $session = null, string $initialMessage = ''): array
    {
        Log::info("Starting diagnostic for customer {$customer->id}");

        $diagnostic = $this->diagnosticService->runDiagnostic($customer, $initialMessage);

        $response = $this->buildResponse($diagnostic, $customer);

        return $response;
    }

    protected function buildResponse($diagnostic, Customer $customer): array
    {
        $text = "🔍 Hasil Diagnosis Otomatis:\n\n";

        $text .= $diagnostic->summary . "\n\n";

        if (!empty($diagnostic->recommendations)) {
            $text .= "💡 Saran:\n";
            foreach ($diagnostic->recommendations as $idx => $rec) {
                $text .= ($idx + 1) . ". " . $rec . "\n";
            }
        }

        if ($diagnostic->ticket_needed) {
            $text .= "\nSilakan balas \"LANJUT\" untuk membuat tiket otomatis.";
        } else {
            $text .= "\nJika masih bermasalah, silakan balas untuk bantuan lebih lanjut.";
        }

        return [
            'type' => 'text',
            'text' => $text,
            'diagnostic' => $diagnostic,
        ];
    }

    public function createTicketFromDiagnostic($diagnostic, Customer $customer, string $description = ''): ?Ticket
    {
        if (!$diagnostic->ticket_needed) {
            return null;
        }

        $ticket = Ticket::create([
            'customer_id' => $customer->id,
            'ticket_number' => 'TCK-' . date('Ymd') . '-' . strtoupper(str()->random(4)),
            'title' => 'Laporan Gangguan - ' . $diagnostic->summary,
            'description' => $description ?: $diagnostic->summary,
            'status' => 'open',
            'priority' => $diagnostic->priority,
            'category' => $this->mapCategory($diagnostic),
            'diagnostic_id' => $diagnostic->id,
        ]);

        $diagnostic->update(['ticket_id' => $ticket->id]);

        // Auto assign technician
        try {
            $this->assignmentService->autoAssign($ticket);
        } catch (\Exception $e) {
            Log::error("Failed to auto assign technician: " . $e->getMessage());
        }

        return $ticket;
    }

    protected function mapCategory($diagnostic): string
    {
        $map = [
            'onu_los' => 'ONU',
            'onu_offline' => 'ONU',
            'pppoe_issue' => 'PPPoE',
            'wifi' => 'Hotspot',
        ];

        $checks = $diagnostic->checks;
        if (isset($checks['onu']['issue_type'])) {
            return $map[$checks['onu']['issue_type']] ?? 'Other';
        }

        return 'Other';
    }
}
