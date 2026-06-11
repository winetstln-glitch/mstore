<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

abstract class BaseGateway implements PaymentGatewayInterface
{
    protected array $config = [];

    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * Load encrypted configuration from settings.
     */
    protected function loadConfig(): void
    {
        $prefix = $this->getIdentifier() . '_';
        $keys = $this->getConfigKeys();

        foreach ($keys as $key) {
            $settingKey = $prefix . $key;
            $value = Setting::getValue($settingKey);

            if ($this->isSensitiveKey($key) && !empty($value)) {
                try {
                    $value = Crypt::decryptString($value);
                } catch (\Exception $e) {
                    Log::error("Failed to decrypt payment setting: {$settingKey}");
                }
            }

            $this->config[$key] = $value;
        }
    }

    /**
     * Get keys for this gateway's configuration.
     */
    abstract public function getConfigKeys(): array;

    /**
     * Determine if a key is sensitive and should be encrypted.
     */
    protected function isSensitiveKey(string $key): bool
    {
        $sensitive = ['api_key', 'secret_key', 'server_key', 'client_secret', 'webhook_secret'];
        return in_array($key, $sensitive);
    }

    /**
     * Save configuration securely.
     */
    public function saveConfig(array $data): void
    {
        $prefix = $this->getIdentifier() . '_';

        foreach ($data as $key => $value) {
            if (!in_array($key, $this->getConfigKeys())) {
                continue;
            }

            $settingKey = $prefix . $key;
            $finalValue = $value;

            if ($this->isSensitiveKey($key) && !empty($value)) {
                $finalValue = Crypt::encryptString($value);
            }

            Setting::updateOrCreate(
                ['key' => $settingKey],
                [
                    'value' => $finalValue,
                    'group' => 'payment_gateway',
                    'type' => $this->isSensitiveKey($key) ? 'password' : 'text',
                    'label' => ucwords(str_replace('_', ' ', $key))
                ]
            );
        }

        Setting::forgetCache();
    }
}
