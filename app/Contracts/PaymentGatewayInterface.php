<?php

namespace App\Contracts;

use App\Models\Transaction;

interface PaymentGatewayInterface
{
    /**
     * Get the gateway name.
     */
    public function getName(): string;

    /**
     * Get the gateway identifier.
     */
    public function getIdentifier(): string;

    /**
     * Get keys for this gateway's configuration.
     */
    public function getConfigKeys(): array;

    /**
     * Test connection to the gateway provider.
     */
    public function testConnection(): array;

    /**
     * Create a payment transaction.
     */
    public function createTransaction(array $payload): array;

    /**
     * Verify webhook signature.
     */
    public function verifySignature(array $payload, string $signature): bool;

    /**
     * Handle payment notification/callback.
     */
    public function handleNotification(array $payload): array;

    /**
     * Check transaction status.
     */
    public function checkStatus(string $referenceId): array;

    /**
     * Get available payment methods.
     */
    public function getPaymentMethods(): array;
}
