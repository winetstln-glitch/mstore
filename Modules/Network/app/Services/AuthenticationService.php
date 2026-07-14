<?php

namespace Modules\Network\Services;

use Modules\Network\Contracts\NetworkProviderInterface;
use Modules\Network\Adapters\FreeRadiusAdapter;
use Modules\Network\Events\PasswordChanged;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class AuthenticationService
{
    public function __construct(
        protected NetworkProviderInterface $networkProvider,
        protected FreeRadiusAdapter $freeRadiusAdapter
    ) {}

    public function changePassword(Customer $customer, string $newPassword): bool
    {
        try {
            Log::channel('network')->info('[AuthenticationService] Changing password', [
                'customer_id' => $customer->id,
            ]);

            $result = $this->networkProvider->changePassword($customer, $newPassword);

            if ($result) {
                event(new PasswordChanged($customer, $newPassword));
            }

            return $result;
        } catch (\Exception $e) {
            Log::channel('network')->error('[AuthenticationService] Failed to change password', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function changeUsername(Customer $customer, string $newUsername): bool
    {
        try {
            Log::channel('network')->info('[AuthenticationService] Changing username', [
                'customer_id' => $customer->id,
            ]);

            return $this->networkProvider->changeUsername($customer, $newUsername);
        } catch (\Exception $e) {
            Log::channel('network')->error('[AuthenticationService] Failed to change username', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function verifyCredentials(string $username, string $password): array
    {
        try {
            Log::channel('network')->info('[AuthenticationService] Verifying credentials', [
                'username' => $username,
            ]);

            return $this->freeRadiusAdapter->verifyCredentials($username, $password);
        } catch (\Exception $e) {
            Log::channel('network')->error('[AuthenticationService] Failed to verify credentials', [
                'username' => $username,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function resolveUsernameByIdWithMeta(string $id): array
    {
        try {
            Log::channel('network')->info('[AuthenticationService] Resolving username by ID', [
                'id' => $id,
            ]);

            return $this->freeRadiusAdapter->resolveUsernameByIdWithMeta($id);
        } catch (\Exception $e) {
            Log::channel('network')->error('[AuthenticationService] Failed to resolve username by ID', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'username' => '',
                'endpoint' => '',
            ];
        }
    }
}

