<?php

namespace Modules\Network\Services;

use App\Models\ONT;
use App\Models\OntOpticalHistory;
use Illuminate\Support\Carbon;

class OpticalMonitoringService
{
    public const STATUS_NORMAL = 'normal';
    public const STATUS_WARNING = 'warning';
    public const STATUS_CRITICAL = 'critical';

    /**
     * Get optical status for an ONT
     */
    public function getOntOpticalStatus(ONT $ont): array
    {
        $ont->load(['opticalHistory' => function ($query) {
            $query->latest()->limit(50);
        }]);

        $rxPower = $ont->rx_power;
        $txPower = $ont->tx_power;
        $temperature = $ont->temperature;
        $voltage = $ont->voltage;
        $distance = $ont->distance;

        return [
            'ont_id' => $ont->id,
            'ont_name' => $ont->name,
            'serial_number' => $ont->serial_number,
            'rx_power' => $rxPower,
            'rx_power_status' => $this->getRxPowerStatus($rxPower),
            'tx_power' => $txPower,
            'tx_power_status' => $this->getTxPowerStatus($txPower),
            'temperature' => $temperature,
            'temperature_status' => $this->getTemperatureStatus($temperature),
            'voltage' => $voltage,
            'voltage_status' => $this->getVoltageStatus($voltage),
            'distance' => $distance,
            'last_polled_at' => $ont->last_polled_at,
            'oper_status' => $ont->oper_status,
            'history' => $ont->opticalHistory->map(function (OntOpticalHistory $history) {
                return [
                    'rx_power' => $history->rx_power,
                    'tx_power' => $history->tx_power,
                    'voltage' => $history->voltage,
                    'temperature' => $history->temperature,
                    'recorded_at' => $history->recorded_at->toIso8601String(),
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Get optical status for all ONTs
     */
    public function getAllOntOpticalStatus(): array
    {
        $onts = ONT::with(['opticalHistory' => function ($query) {
            $query->latest()->limit(10);
        }])->get();

        return $onts->map(function (ONT $ont) {
            return $this->getOntOpticalStatus($ont);
        })->values()->toArray();
    }

    /**
     * Get optical history for an ONT
     */
    public function getOntOpticalHistory(ONT $ont, int $days = 7): array
    {
        $history = OntOpticalHistory::where('ont_id', $ont->id)
            ->where('recorded_at', '>=', Carbon::now()->subDays($days))
            ->latest()
            ->get();

        return $history->map(function (OntOpticalHistory $history) {
            return [
                'rx_power' => $history->rx_power,
                'tx_power' => $history->tx_power,
                'voltage' => $history->voltage,
                'temperature' => $history->temperature,
                'recorded_at' => $history->recorded_at->toIso8601String(),
            ];
        })->values()->toArray();
    }

    /**
     * Get ONTs with warning optical status
     */
    public function getWarningOnts(): array
    {
        $onts = ONT::all();
        return $onts->filter(function (ONT $ont) {
            $statuses = [
                $this->getRxPowerStatus($ont->rx_power),
                $this->getTxPowerStatus($ont->tx_power),
                $this->getTemperatureStatus($ont->temperature),
                $this->getVoltageStatus($ont->voltage),
            ];
            return in_array(self::STATUS_WARNING, $statuses);
        })->map(function (ONT $ont) {
            return $this->getOntOpticalStatus($ont);
        })->values()->toArray();
    }

    /**
     * Get ONTs with critical optical status
     */
    public function getCriticalOnts(): array
    {
        $onts = ONT::all();
        return $onts->filter(function (ONT $ont) {
            $statuses = [
                $this->getRxPowerStatus($ont->rx_power),
                $this->getTxPowerStatus($ont->tx_power),
                $this->getTemperatureStatus($ont->temperature),
                $this->getVoltageStatus($ont->voltage),
            ];
            return in_array(self::STATUS_CRITICAL, $statuses);
        })->map(function (ONT $ont) {
            return $this->getOntOpticalStatus($ont);
        })->values()->toArray();
    }

    /**
     * Get optical monitoring dashboard
     */
    public function getOpticalDashboard(): array
    {
        $allOnts = $this->getAllOntOpticalStatus();
        $warningOnts = $this->getWarningOnts();
        $criticalOnts = $this->getCriticalOnts();

        return [
            'total_onts' => count($allOnts),
            'normal_onts' => count($allOnts) - count($warningOnts) - count($criticalOnts),
            'warning_onts' => $warningOnts,
            'warning_count' => count($warningOnts),
            'critical_onts' => $criticalOnts,
            'critical_count' => count($criticalOnts),
        ];
    }

    /**
     * Get RX Power status
     */
    private function getRxPowerStatus(?float $rxPower): string
    {
        if ($rxPower === null) {
            return self::STATUS_NORMAL;
        }
        
        if ($rxPower >= -20 && $rxPower <= -8) {
            return self::STATUS_NORMAL;
        } elseif (($rxPower >= -27 && $rxPower < -20) || ($rxPower > -8 && $rxPower <= 0)) {
            return self::STATUS_WARNING;
        }
        return self::STATUS_CRITICAL;
    }

    /**
     * Get TX Power status
     */
    private function getTxPowerStatus(?float $txPower): string
    {
        if ($txPower === null) {
            return self::STATUS_NORMAL;
        }
        
        if ($txPower >= 1 && $txPower <= 5) {
            return self::STATUS_NORMAL;
        } elseif (($txPower >= -2 && $txPower < 1) || ($txPower > 5 && $txPower <= 10)) {
            return self::STATUS_WARNING;
        }
        return self::STATUS_CRITICAL;
    }

    /**
     * Get Temperature status
     */
    private function getTemperatureStatus(?float $temperature): string
    {
        if ($temperature === null) {
            return self::STATUS_NORMAL;
        }
        
        if ($temperature >= 0 && $temperature <= 60) {
            return self::STATUS_NORMAL;
        } elseif (($temperature >= -10 && $temperature < 0) || ($temperature > 60 && $temperature <= 75)) {
            return self::STATUS_WARNING;
        }
        return self::STATUS_CRITICAL;
    }

    /**
     * Get Voltage status
     */
    private function getVoltageStatus(?float $voltage): string
    {
        if ($voltage === null) {
            return self::STATUS_NORMAL;
        }
        
        if ($voltage >= 3.0 && $voltage <= 3.6) {
            return self::STATUS_NORMAL;
        } elseif (($voltage >= 2.5 && $voltage < 3.0) || ($voltage > 3.6 && $voltage <= 4.0)) {
            return self::STATUS_WARNING;
        }
        return self::STATUS_CRITICAL;
    }
}
