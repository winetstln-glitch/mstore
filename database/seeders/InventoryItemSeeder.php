<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use Illuminate\Database\Seeder;

class InventoryItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'name' => 'ONU Fiberhome',
                'description' => 'Perangkat ONU/ONT untuk pelanggan FTTH.',
                'unit' => 'pcs',
                'stock' => 0,
                'price' => 250000,
            ],
            [
                'name' => 'Router WiFi',
                'description' => 'Router WiFi rumahan untuk pelanggan.',
                'unit' => 'pcs',
                'stock' => 0,
                'price' => 300000,
            ],
            [
                'name' => 'Kabel FO Dropcore 1 Core',
                'description' => 'Kabel fiber optik dropcore 1 core per meter.',
                'unit' => 'meter',
                'stock' => 0,
                'price' => 2500,
            ],
            [
                'name' => 'Konektor SC/UPC',
                'description' => 'Konektor SC/UPC untuk terminasi FO.',
                'unit' => 'pcs',
                'stock' => 0,
                'price' => 5000,
            ],
        ];

        foreach ($items as $data) {
            InventoryItem::firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}

