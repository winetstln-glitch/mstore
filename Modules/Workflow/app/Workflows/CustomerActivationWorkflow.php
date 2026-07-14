<?php

namespace Modules\Workflow\Workflows;

use Modules\Workflow\Contracts\WorkflowInterface;
use Modules\Network\Services\ProvisioningService;
use Modules\Network\Services\SynchronizationService;
use App\Services\BillingService;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class CustomerActivationWorkflow implements WorkflowInterface
{
    public function __construct(
        protected Customer $customer,
        protected ProvisioningService $provisioningService,
        protected SynchronizationService $synchronizationService,
        protected BillingService $billingService
    ) {}

    public function execute(): void
    {
        try {
            Log::channel('network')->info('[CustomerActivationWorkflow] Starting workflow', ['customer_id' => $this->customer->id]);

            // Step 1: Activate customer via Provisioning
            $this->provisioningService->activate($this->customer);

            // Step 2: Sync customer
            $this->synchronizationService->syncCustomer($this->customer);

            // Step 3: Generate invoice
            $this->billingService->generateInvoice($this->customer);

            Log::channel('network')->info('[CustomerActivationWorkflow] Completed successfully', ['customer_id' => $this->customer->id]);
        } catch (\Exception $e) {
            Log::channel('network')->error('[CustomerActivationWorkflow] Failed', [
                'customer_id' => $this->customer->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->rollback();
            throw $e;
        }
    }

    public function rollback(): void
    {
        try {
            Log::channel('network')->info('[CustomerActivationWorkflow] Starting rollback', ['customer_id' => $this->customer->id]);
            $this->customer->update(['status' => 'suspend']);
        } catch (\Exception $e) {
            Log::channel('network')->error('[CustomerActivationWorkflow] Rollback failed', [
                'customer_id' => $this->customer->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
