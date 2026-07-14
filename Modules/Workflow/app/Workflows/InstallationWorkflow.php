<?php

namespace Modules\Workflow\Workflows;

use Modules\Workflow\Contracts\WorkflowInterface;
use Modules\Network\Services\ProvisioningService;
use Modules\Network\Services\SynchronizationService;
use App\Models\Installation;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class InstallationWorkflow implements WorkflowInterface
{
    public function __construct(
        protected Installation $installation,
        protected ProvisioningService $provisioningService,
        protected SynchronizationService $synchronizationService
    ) {}

    public function execute(): void
    {
        try {
            Log::channel('network')->info('[InstallationWorkflow] Starting workflow', ['installation_id' => $this->installation->id]);

            $customer = $this->installation->customer;

            if ($customer) {
                $this->provisioningService->activate($customer);
                $this->synchronizationService->syncCustomer($customer);
            }

            Log::channel('network')->info('[InstallationWorkflow] Completed successfully', ['installation_id' => $this->installation->id]);
        } catch (\Exception $e) {
            Log::channel('network')->error('[InstallationWorkflow] Failed', [
                'installation_id' => $this->installation->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->rollback();
            throw $e;
        }
    }

    public function rollback(): void
    {
        Log::channel('network')->info('[InstallationWorkflow] Starting rollback', ['installation_id' => $this->installation->id]);
    }
}
