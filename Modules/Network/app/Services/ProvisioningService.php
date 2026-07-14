<?php

namespace Modules\Network\Services;

use Modules\Network\Contracts\NetworkProviderInterface;
use App\Models\Customer;
use Modules\Network\Events\Domain\CustomerActivated;
use Modules\Network\Events\Domain\CustomerSuspended;
use Modules\Network\Events\Domain\CustomerUnsuspended;
use Modules\Network\Events\ProvisioningRequested;
use Modules\Network\Events\ProvisioningCompleted;
use Modules\Network\Events\ProvisioningFailed;
use Modules\Network\Exceptions\ProvisioningFailedException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProvisioningService
{
    public function __construct(protected NetworkProviderInterface $networkProvider)
    {
    }

    /**
     * Activate a customer (idempotent)
     */
    public function activate(Customer $customer): bool
    {
        return DB::transaction(function () use ($customer) {
            try {
                Log::channel('network')->info('[Provisioning] Starting activation', [
                    'customer_id' => $customer->id,
                    'pppoe_username' => $customer->pppoe_user,
                    'provider' => get_class($this->networkProvider),
                ]);

                event(new ProvisioningRequested($customer, 'activate'));

                // Idempotency: Check if customer is already active
                if ($customer->status === 'active' && $this->networkProvider->customerExists($customer)) {
                    Log::channel('network')->info('[Provisioning] Already active', [
                        'customer_id' => $customer->id,
                    ]);
                    event(new ProvisioningCompleted($customer, 'activate'));
                    return true;
                }

                $result = $this->networkProvider->activateCustomer($customer);

                if (!$result) {
                    throw new ProvisioningFailedException('Failed to activate customer');
                }

                $customer->update(['status' => 'active']);

                event(new CustomerActivated($customer));
                event(new ProvisioningCompleted($customer, 'activate'));

                Log::channel('network')->info('[Provisioning] Activation completed', [
                    'customer_id' => $customer->id,
                    'pppoe_username' => $customer->pppoe_user,
                ]);

                return true;
            } catch (\Exception $e) {
                Log::channel('network')->error('[Provisioning] Activation failed', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                event(new ProvisioningFailed($customer, 'activate', $e));
                throw $e;
            }
        });
    }

    /**
     * Suspend a customer (idempotent)
     */
    public function suspend(Customer $customer): bool
    {
        return DB::transaction(function () use ($customer) {
            try {
                Log::channel('network')->info('[Provisioning] Starting suspension', [
                    'customer_id' => $customer->id,
                    'pppoe_username' => $customer->pppoe_user,
                    'provider' => get_class($this->networkProvider),
                ]);

                event(new ProvisioningRequested($customer, 'suspend'));

                // Idempotency: Check if customer is already suspended
                if ($customer->status === 'suspend') {
                    Log::channel('network')->info('[Provisioning] Already suspended', [
                        'customer_id' => $customer->id,
                    ]);
                    event(new ProvisioningCompleted($customer, 'suspend'));
                    return true;
                }

                $result = $this->networkProvider->suspendCustomer($customer);

                if (!$result) {
                    throw new ProvisioningFailedException('Failed to suspend customer');
                }

                $customer->update(['status' => 'suspend']);

                event(new CustomerSuspended($customer));
                event(new ProvisioningCompleted($customer, 'suspend'));

                Log::channel('network')->info('[Provisioning] Suspension completed', [
                    'customer_id' => $customer->id,
                ]);

                return true;
            } catch (\Exception $e) {
                Log::channel('network')->error('[Provisioning] Suspension failed', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
                event(new ProvisioningFailed($customer, 'suspend', $e));
                throw $e;
            }
        });
    }

    /**
     * Unsuspend a customer (idempotent)
     */
    public function unsuspend(Customer $customer): bool
    {
        return DB::transaction(function () use ($customer) {
            try {
                Log::channel('network')->info('[Provisioning] Starting unsuspension', [
                    'customer_id' => $customer->id,
                    'pppoe_username' => $customer->pppoe_user,
                    'provider' => get_class($this->networkProvider),
                ]);

                event(new ProvisioningRequested($customer, 'unsuspend'));

                // Idempotency: Check if customer is already active
                if ($customer->status === 'active') {
                    Log::channel('network')->info('[Provisioning] Already active', [
                        'customer_id' => $customer->id,
                    ]);
                    event(new ProvisioningCompleted($customer, 'unsuspend'));
                    return true;
                }

                $result = $this->networkProvider->unsuspendCustomer($customer);

                if (!$result) {
                    throw new ProvisioningFailedException('Failed to unsuspend customer');
                }

                $customer->update(['status' => 'active']);

                event(new CustomerUnsuspended($customer));
                event(new ProvisioningCompleted($customer, 'unsuspend'));

                Log::channel('network')->info('[Provisioning] Unsuspension completed', [
                    'customer_id' => $customer->id,
                ]);

                return true;
            } catch (\Exception $e) {
                Log::channel('network')->error('[Provisioning] Unsuspension failed', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
                event(new ProvisioningFailed($customer, 'unsuspend', $e));
                throw $e;
            }
        });
    }

    /**
     * Disconnect customer
     */
    public function disconnect(Customer $customer): bool
    {
        try {
            Log::channel('network')->info('[Provisioning] Disconnecting customer', [
                'customer_id' => $customer->id,
                'pppoe_username' => $customer->pppoe_user,
            ]);

            return $this->networkProvider->disconnectCustomer($customer);
        } catch (\Exception $e) {
            Log::channel('network')->error('[Provisioning] Disconnect failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Change customer profile
     */
    public function changeProfile(Customer $customer, string $newProfile): bool
    {
        return DB::transaction(function () use ($customer, $newProfile) {
            try {
                Log::channel('network')->info('[Provisioning] Changing profile', [
                    'customer_id' => $customer->id,
                    'old_profile' => $customer->package,
                    'new_profile' => $newProfile,
                ]);

                $result = $this->networkProvider->changeProfile($customer, $newProfile);

                if (!$result) {
                    throw new ProvisioningFailedException('Failed to change profile');
                }

                $customer->update(['package' => $newProfile]);

                Log::channel('network')->info('[Provisioning] Profile changed successfully', [
                    'customer_id' => $customer->id,
                    'new_profile' => $newProfile,
                ]);

                return true;
            } catch (\Exception $e) {
                Log::channel('network')->error('[Provisioning] Change profile failed', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }
}

