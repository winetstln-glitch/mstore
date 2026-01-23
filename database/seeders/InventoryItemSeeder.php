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
            // DEVICE
            [
                'name' => 'ONU Fiberhome',
                'category' => 'device',
                'type' => 'onu',
                'brand' => 'Fiberhome',
                'model' => 'AN5506',
                'description' => 'Perangkat ONU/ONT untuk pelanggan FTTH.',
                'unit' => 'pcs',
                'stock' => 0,
                'price' => 250000,
            ],
            [
                'name' => 'Router WiFi ZTE',
                'category' => 'device',
                'type' => 'router',
                'brand' => 'ZTE',
                'model' => 'F609',
                'description' => 'Router WiFi rumahan untuk pelanggan.',
                'unit' => 'pcs',
                'stock' => 0,
                'price' => 150000,
            ],
            // FIBER
            [
                'name' => 'Kabel FO Dropcore 1 Core',
                'category' => 'fiber',
                'type' => 'cable',
                'brand' => 'Generic',
                'model' => '1 Core',
                'description' => 'Kabel fiber optik dropcore 1 core per meter.',
                'unit' => 'meter',
                'stock' => 0,
                'price' => 1500,
            ],
            [
                'name' => 'Konektor SC/UPC',
                'category' => 'fiber',
                'type' => 'connector',
                'brand' => 'Generic',
                'model' => 'SC/UPC Blue',
                'description' => 'Konektor SC/UPC untuk terminasi FO.',
                'unit' => 'pcs',
                'stock' => 0,
                'price' => 2500,
            ],
            // TOOLS (Alat Kerja)
            [
                'name' => 'Splicer Tumtec V9',
                'category' => 'tool',
                'type' => 'splicer',
                'brand' => 'Tumtec',
                'model' => 'V9',
                'description' => 'Mesin splicing core alignment.',
                'unit' => 'unit',
                'stock' => 1,
                'price' => 15000000,
            ],
            [
                'name' => 'Tangga Teleskopik 5M',
                'category' => 'tool',
                'type' => 'ladder',
                'brand' => 'Generic',
                'model' => '5 Meter',
                'description' => 'Tangga aluminium teleskopik untuk instalasi tiang.',
                'unit' => 'unit',
                'stock' => 2,
                'price' => 1200000,
            ],
            [
                'name' => 'Tang Potong',
                'category' => 'tool',
                'type' => 'hand_tool',
                'brand' => 'Tekiro',
                'model' => 'Standard',
                'description' => 'Tang potong untuk kabel dropcore.',
                'unit' => 'pcs',
                'stock' => 5,
                'price' => 35000,
            ],
            // VEHICLE (Kendaraan)
            [
                'name' => 'Grand Max Blind Van',
                'category' => 'vehicle',
                'type' => 'car',
                'brand' => 'Daihatsu',
                'model' => 'Grand Max',
                'description' => 'Mobil operasional teknisi.',
                'unit' => 'unit',
                'stock' => 1,
                'price' => 150000000,
            ]
        ];

        foreach ($items as $data) {
            InventoryItem::updateOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}

