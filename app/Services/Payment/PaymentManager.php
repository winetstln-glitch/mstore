<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use InvalidArgumentException;

class PaymentManager
{
    protected array $gateways = [];

    public function __construct()
    {
        $this->registerDefaultGateways();
    }

    protected function registerDefaultGateways(): void
    {
        $this->register(new DuitkuGateway());
        $this->register(new MidtransGateway());
    }

    public function register(PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$gateway->getIdentifier()] = $gateway;
    }

    public function gateway(string $identifier): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$identifier])) {
            throw new InvalidArgumentException("Payment gateway [{$identifier}] is not registered.");
        }

        return $this->gateways[$identifier];
    }

    public function all(): array
    {
        return $this->gateways;
    }
}
