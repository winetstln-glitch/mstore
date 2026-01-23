<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AssetCategory;
use App\Models\Brand;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Router', 'description' => 'Perangkat Router (Active)'],
            ['name' => 'Switch', 'description' => 'Perangkat Switch/Hub (Active)'],
            ['name' => 'OLT', 'description' => 'Optical Line Terminal (Active)'],
            ['name' => 'ODP', 'description' => 'Optical Distribution Point (Passive)'],
            ['name' => 'ONT', 'description' => 'Optical Network Terminal / Modem (Active)'],
            ['name' => 'Tools', 'description' => 'Peralatan Kerja (Splicer, OPM, Tang)'],
            ['name' => 'Kendaraan', 'description' => 'Motor/Mobil Operasional'],
            ['name' => 'Material', 'description' => 'Kabel, Konektor, Patch Cord (Consumable)'],
        ];

        foreach ($categories as $cat) {
            AssetCategory::firstOrCreate(['name' => $cat['name']], $cat);
        }

        $brands = [
            'ZTE', 'Huawei', 'Mikrotik', 'TP-Link', 'Ubiquiti', 'Tenda', 'Totolink', 'Ruijie',
            'Tumtec', 'Fujikura', 'Sumitomo', 'Inno', 'Joinwit',
            'Honda', 'Toyota', 'Suzuki', 'Yamaha'
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(['name' => $brand]);
        }
    }
}
