<?php

namespace App\Services\Network;

use App\Contracts\Network\NetworkProviderInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MonitoringService
{
    public function __construct(
        protected NetworkProviderInterface $provider
    ) {}

    /**
     * Check if network provider is available
     */
    public function isAvailable(): bool
    {
        return Cache::remember('network.provider.available', 30, function () {
            return $this->provider->isAvailable();
        });
    }

    /**
     * Get network health status
     */
    public function health(): array
    {
        try {
            return $this->provider->health();
        } catch (\Exception $e) {
            Log::error('Network health check failed: ' . $e->getMessage());
            return [
                'ok' => false,
                'error' => $e->getMessage(),
                'checked_at' => now()->toIso8601String()
            ];
        }
    }
}