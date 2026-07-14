<?php

namespace App\DTO;

class CashImpactData
{
    public function __construct(
        public readonly int|float $cashReceivedFromCustomer = 0,
        public readonly int|float $cashPaidToCustomer = 0,
        public readonly int|float $feeCollectedInCash = 0,
        public readonly array $movementLines = []
    ) {}

    public function netCashChange(): int|float
    {
        return $this->cashReceivedFromCustomer - $this->cashPaidToCustomer;
    }
}
