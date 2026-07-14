<?php

namespace App\Services\Network;

use App\Contracts\Network\NetworkProviderInterface;
use Illuminate\Support\Facades\Log;

class AuthenticationService
{
    public function __construct(
        protected NetworkProviderInterface $provider
    ) {}

    /**
     * Verify customer credentials
     */
    public function verify(string $username, string $password): array
    {
        try {
            return $this->provider->verifyCredentials($username, $password);
        } catch (\Exception $e) {
            Log::error('Network authentication failed: ' . $e->getMessage());
            return [
                'ok' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}