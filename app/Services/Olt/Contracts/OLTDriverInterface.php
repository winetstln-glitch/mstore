<?php
// app/Services/OLT/Contracts/OLTDriverInterface.php

namespace App\Services\OLT\Contracts;

interface OLTDriverInterface
{
    public function connect($olt, int $timeout = 30): void;
    public function disconnect(): void;
    public function getDeviceInfo(): array;
    public function getPorts(): array;
    public function getOnts(string $portName): array;
    public function getOnus(): array;
    public function getOntDetail(string $ontId): array;
    public function getOntOpticalInfo(string $ontId): array;
    public function getOntTraffic(string $ontId): array;
    public function rebootOnt(string $ontId): bool;
    public function getSystemResources(): array;
    public function getAlarms(): array;
    public function testConnection(): bool;
}
