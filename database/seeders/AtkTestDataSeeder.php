<?php

namespace Database\Seeders;

use App\Models\AtkFloatAccount;
use App\Models\AtkProduct;
use App\Models\FeeProfile;
use Illuminate\Database\Seeder;

class AtkTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Produk ATK (gunakan kolom 'category' string)
        $products = [
            // ATK Umum
            ['name' => 'Pulpen Gel', 'code' => 'PULPEN-001', 'category' => 'ATK Umum', 'price' => 3000, 'cost_price' => 1500, 'stock' => 100, 'unit' => 'pcs'],
            ['name' => 'Buku Tulis 50 Halaman', 'code' => 'BUKU-001', 'category' => 'ATK Umum', 'price' => 5000, 'cost_price' => 2500, 'stock' => 50, 'unit' => 'pcs'],
            ['name' => 'Kertas HVS A4', 'code' => 'KERTAS-A4', 'category' => 'ATK Umum', 'price' => 500, 'cost_price' => 250, 'stock' => 1000, 'unit' => 'lembar'],
            // Jasa Fotokopi
            ['name' => 'Fotokopi Hitam Putih', 'code' => 'FOTO-HP', 'category' => 'JASA POTOCOPY', 'price' => 200, 'cost_price' => 100, 'stock' => 0, 'unit' => 'lembar'],
            ['name' => 'Fotokopi Warna', 'code' => 'FOTO-WARNA', 'category' => 'JASA POTOCOPY', 'price' => 1000, 'cost_price' => 500, 'stock' => 0, 'unit' => 'lembar'],
            // Jasa Transfer Bank (produk dummy untuk trigger jenis transaksi)
            ['name' => 'Transfer Bank', 'code' => 'TRF-BANK', 'category' => 'JASA TRANSFER BANK', 'price' => 0, 'cost_price' => 0, 'stock' => 0, 'unit' => 'transaksi'],
        ];
        foreach ($products as $prod) {
            AtkProduct::firstOrCreate(['code' => $prod['code']], $prod);
        }

        // 2. Buat Akun Float
        $floatAccounts = [
            ['code' => 'FLOAT-DANA', 'name' => 'Akun Float DANA', 'account_type' => 'e-wallet', 'current_balance' => 1000000, 'status' => 'active', 'description' => 'Akun float untuk transaksi DANA'],
            ['code' => 'FLOAT-OVO', 'name' => 'Akun Float OVO', 'account_type' => 'e-wallet', 'current_balance' => 1000000, 'status' => 'active', 'description' => 'Akun float untuk transaksi OVO'],
            ['code' => 'FLOAT-PPOB', 'name' => 'Akun Float PPOB', 'account_type' => 'ppob_deposit', 'current_balance' => 5000000, 'status' => 'active', 'description' => 'Akun float untuk transaksi PPOB'],
            ['code' => 'FLOAT-TRANSFER', 'name' => 'Akun Float Transfer', 'account_type' => 'bank', 'current_balance' => 10000000, 'status' => 'active', 'description' => 'Akun float untuk jasa transfer bank'],
        ];
        foreach ($floatAccounts as $acc) {
            AtkFloatAccount::firstOrCreate(['code' => $acc['code']], $acc);
        }

        // 3. Buat Fee Profiles beserta Fee Tiers
        $feeProfilesData = [
            [
                'name' => 'Fee Top Up',
                'transaction_type' => 'top_up',
                'fee_mode' => 'tier',
                'is_active' => true,
                'module' => 'atk',
                'tiers' => [
                    ['min_amount' => 10000, 'max_amount' => 1000000, 'fee_type' => 'fixed', 'fee_value' => 1000]
                ]
            ],
            [
                'name' => 'Fee PPOB',
                'transaction_type' => 'ppob',
                'fee_mode' => 'tier',
                'is_active' => true,
                'module' => 'atk',
                'tiers' => [
                    ['min_amount' => 10000, 'max_amount' => 500000, 'fee_type' => 'percentage', 'fee_value' => 3]
                ]
            ],
            [
                'name' => 'Fee Cash Out',
                'transaction_type' => 'cash_out',
                'fee_mode' => 'tier',
                'is_active' => true,
                'module' => 'atk',
                'tiers' => [
                    ['min_amount' => 50000, 'max_amount' => 5000000, 'fee_type' => 'percentage', 'fee_value' => 1]
                ]
            ],
            [
                'name' => 'Fee Transfer Bank',
                'transaction_type' => 'bank',
                'fee_mode' => 'tier',
                'is_active' => true,
                'module' => 'atk',
                'tiers' => [
                    ['min_amount' => 10000, 'max_amount' => 100000000, 'fee_type' => 'fixed', 'fee_value' => 5000]
                ]
            ]
        ];

        foreach ($feeProfilesData as $fpData) {
            $tiers = $fpData['tiers'];
            unset($fpData['tiers']);
            
            // Cari atau buat FeeProfile
            $feeProfile = FeeProfile::firstOrCreate(['name' => $fpData['name']], $fpData);
            
            // Buat tiers jika belum ada
            foreach ($tiers as $tierData) {
                $tierData['fee_profile_id'] = $feeProfile->id;
                \App\Models\FeeTier::firstOrCreate(
                    [
                        'fee_profile_id' => $tierData['fee_profile_id'],
                        'min_amount' => $tierData['min_amount'],
                        'max_amount' => $tierData['max_amount']
                    ],
                    $tierData
                );
            }
        }
    }
}
