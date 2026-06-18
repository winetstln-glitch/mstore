<?php

namespace Database\Seeders;

use App\Models\FeeProfile;
use App\Models\FeeTier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeeProfileSeeder extends Seeder
{
    public function run(): void
    {
        // Bank Transfer Fee - Fixed 2,000
        $bankProfile = FeeProfile::create([
            'name' => 'Biaya Transfer Bank',
            'transaction_type' => 'bank',
            'fee_mode' => 'fixed',
            'is_active' => true,
            'allow_override' => true,
            'module' => 'atk',
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        FeeTier::create([
            'fee_profile_id' => $bankProfile->id,
            'min_amount' => 0,
            'max_amount' => null,
            'fee_type' => 'fixed',
            'fee_value' => 2000,
            'sort_order' => 0,
        ]);

        // Cash Out Fee - 1% + 500
        $cashOutProfile = FeeProfile::create([
            'name' => 'Biaya Tarik Tunai',
            'transaction_type' => 'cash_out',
            'fee_mode' => 'fixed_percentage',
            'is_active' => true,
            'allow_override' => true,
            'module' => 'atk',
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        FeeTier::create([
            'fee_profile_id' => $cashOutProfile->id,
            'min_amount' => 0,
            'max_amount' => null,
            'fee_type' => 'fixed_percentage',
            'fee_value' => 1, // 1%
            'fixed_value' => 500,
            'sort_order' => 0,
        ]);

        // Top Up Fee - Tiered
        $topUpProfile = FeeProfile::create([
            'name' => 'Biaya Top Up',
            'transaction_type' => 'top_up',
            'fee_mode' => 'tier',
            'is_active' => true,
            'allow_override' => true,
            'module' => 'atk',
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        FeeTier::create([
            'fee_profile_id' => $topUpProfile->id,
            'min_amount' => 0,
            'max_amount' => 1000000,
            'fee_type' => 'fixed',
            'fee_value' => 1000,
            'sort_order' => 0,
        ]);
        
        FeeTier::create([
            'fee_profile_id' => $topUpProfile->id,
            'min_amount' => 1000001,
            'max_amount' => 5000000,
            'fee_type' => 'fixed',
            'fee_value' => 2000,
            'sort_order' => 1,
        ]);

        // PPOB Fee - 0.5%
        $ppobProfile = FeeProfile::create([
            'name' => 'Biaya PPOB',
            'transaction_type' => 'ppob',
            'fee_mode' => 'percentage',
            'is_active' => true,
            'allow_override' => true,
            'module' => 'atk',
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        FeeTier::create([
            'fee_profile_id' => $ppobProfile->id,
            'min_amount' => 0,
            'max_amount' => null,
            'fee_type' => 'percentage',
            'fee_value' => 0.5, // 0.5%
            'sort_order' => 0,
        ]);
    }
}
