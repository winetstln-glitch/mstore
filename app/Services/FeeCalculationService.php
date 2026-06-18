<?php

namespace App\Services;

use App\Models\FeeProfile;
use App\Models\FeeTier;
use App\Models\FeeLog;
use Illuminate\Support\Facades\Auth;

class FeeCalculationService
{
    public function __construct() {}

    /**
     * Calculate fee for a given transaction type and nominal amount
     */
    public function calculateFee(string $transactionType, float $nominal, string $module = 'atk'): array
    {
        $profile = FeeProfile::getActiveForType($transactionType, $module);

        if (!$profile) {
            return [
                'success' => false,
                'message' => 'No active fee profile found for this transaction type',
                'calculated_fee' => 0,
                'breakdown' => [],
            ];
        }

        $result = $this->calculateByProfile($profile, $nominal);

        return [
            'success' => true,
            'profile_id' => $profile->id,
            'profile_name' => $profile->name,
            'fee_mode' => $profile->fee_mode,
            'calculated_fee' => $result['fee'],
            'breakdown' => $result['breakdown'],
            'allow_override' => $profile->allow_override,
        ];
    }

    /**
     * Calculate fee using a specific profile
     */
    private function calculateByProfile(FeeProfile $profile, float $nominal): array
    {
        $fee = 0;
        $breakdown = [];

        switch ($profile->fee_mode) {
            case 'fixed':
                $fee = $this->calculateFixedFee($profile, $nominal);
                $breakdown = [
                    'type' => 'fixed',
                    'value' => $fee,
                ];
                break;

            case 'percentage':
                $fee = $this->calculatePercentageFee($profile, $nominal);
                $breakdown = [
                    'type' => 'percentage',
                    'value' => $fee,
                    'percentage' => $profile->tiers->first()?->fee_value ?? 0,
                ];
                break;

            case 'fixed_percentage':
                $fee = $this->calculateFixedPercentageFee($profile, $nominal);
                $breakdown = [
                    'type' => 'fixed_percentage',
                    'fixed_fee' => $profile->tiers->first()?->fixed_value ?? 0,
                    'percentage_fee' => $fee - ($profile->tiers->first()?->fixed_value ?? 0),
                    'total_fee' => $fee,
                ];
                break;

            case 'tier':
                $result = $this->calculateTieredFee($profile, $nominal);
                $fee = $result['fee'];
                $breakdown = $result['breakdown'];
                break;

            case 'cost_plus':
                $fee = $this->calculateCostPlusFee($profile, $nominal);
                $breakdown = [
                    'type' => 'cost_plus',
                    'cost_price' => $profile->cost_price ?? $nominal,
                    'markup' => $fee - ($profile->cost_price ?? $nominal),
                    'total_fee' => $fee,
                ];
                break;

            case 'custom':
                $fee = $this->calculateCustomFormula($profile, $nominal);
                $breakdown = [
                    'type' => 'custom',
                    'formula' => $profile->custom_formula,
                    'result' => $fee,
                ];
                break;
        }

        return [
            'fee' => max(0, round($fee, 2)),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Fixed fee calculation
     */
    private function calculateFixedFee(FeeProfile $profile, float $nominal): float
    {
        $tier = $profile->tiers->first();
        return $tier?->fee_value ?? 0;
    }

    /**
     * Percentage fee calculation
     */
    private function calculatePercentageFee(FeeProfile $profile, float $nominal): float
    {
        $tier = $profile->tiers->first();
        if (!$tier) {
            return 0;
        }
        return ($nominal * $tier->fee_value) / 100;
    }

    /**
     * Fixed + Percentage fee calculation
     */
    private function calculateFixedPercentageFee(FeeProfile $profile, float $nominal): float
    {
        $tier = $profile->tiers->first();
        if (!$tier) {
            return 0;
        }
        $fixed = $tier->fixed_value ?? 0;
        $percentage = ($nominal * ($tier->fee_value ?? 0)) / 100;
        return $fixed + $percentage;
    }

    /**
     * Tiered fee calculation
     */
    private function calculateTieredFee(FeeProfile $profile, float $nominal): array
    {
        $fee = 0;
        $breakdown = [
            'type' => 'tier',
            'tiers' => [],
        ];

        foreach ($profile->tiers as $tier) {
            $min = $tier->min_amount;
            $max = $tier->max_amount;

            $inTier = false;
            if ($max === null) {
                $inTier = $nominal >= $min;
            } else {
                $inTier = $nominal >= $min && $nominal <= $max;
            }

            if ($inTier) {
                $breakdown['applied_tier'] = [
                    'min' => $min,
                    'max' => $max,
                    'fee_type' => $tier->fee_type,
                ];

                switch ($tier->fee_type) {
                    case 'fixed':
                        $fee = $tier->fee_value;
                        $breakdown['applied_tier']['fixed_fee'] = $fee;
                        break;
                    case 'percentage':
                        $fee = ($nominal * $tier->fee_value) / 100;
                        $breakdown['applied_tier']['percentage'] = $tier->fee_value;
                        $breakdown['applied_tier']['percentage_fee'] = $fee;
                        break;
                    case 'fixed_percentage':
                        $fixed = $tier->fixed_value ?? 0;
                        $percentage = ($nominal * ($tier->fee_value ?? 0)) / 100;
                        $fee = $fixed + $percentage;
                        $breakdown['applied_tier']['fixed_fee'] = $fixed;
                        $breakdown['applied_tier']['percentage'] = $tier->fee_value;
                        $breakdown['applied_tier']['percentage_fee'] = $percentage;
                        $breakdown['applied_tier']['total_fee'] = $fee;
                        break;
                }
                break;
            }

            $breakdown['tiers'][] = [
                'min' => $min,
                'max' => $max,
                'fee_type' => $tier->fee_type,
            ];
        }

        return ['fee' => $fee, 'breakdown' => $breakdown];
    }

    /**
     * Cost plus markup calculation
     */
    private function calculateCostPlusFee(FeeProfile $profile, float $nominal): float
    {
        $costPrice = $profile->cost_price ?? $nominal;
        $markup = 0;

        if ($profile->markup_type === 'fixed') {
            $markup = $profile->markup_value ?? 0;
        } else {
            $markup = ($costPrice * ($profile->markup_value ?? 0)) / 100;
        }

        return $costPrice + $markup;
    }

    /**
     * Custom formula calculation
     */
    private function calculateCustomFormula(FeeProfile $profile, float $nominal): float
    {
        $formula = $profile->custom_formula;
        if (!$formula) {
            return 0;
        }

        try {
            $safeFormula = preg_replace('/[^0-9+\-*\/().%\s]/', '', $formula);
            $safeFormula = str_replace('amount', (string) $nominal, $safeFormula);
            
            $result = 0;
            eval('$result = ' . $safeFormula . ';');
            return (float) $result;
        } catch (\Exception $e) {
            report($e);
            return 0;
        }
    }

    /**
     * Log a fee calculation
     */
    public function logFee(array $data): FeeLog
    {
        return FeeLog::create([
            'fee_profile_id' => $data['fee_profile_id'] ?? null,
            'transaction_type' => $data['transaction_type'],
            'transaction_id' => $data['transaction_id'] ?? null,
            'nominal' => $data['nominal'],
            'calculated_fee' => $data['calculated_fee'],
            'manual_fee' => $data['manual_fee'] ?? null,
            'final_fee' => $data['final_fee'] ?? $data['calculated_fee'],
            'reason' => $data['reason'] ?? null,
            'module' => $data['module'] ?? 'atk',
            'user_id' => Auth::id(),
        ]);
    }
}
