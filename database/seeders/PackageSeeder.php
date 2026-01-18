<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Home 10 Mbps',
                'price' => 150000,
                'speed' => '10 Mbps',
                'description' => 'Paket hemat untuk penggunaan rumahan ringan.',
                'is_active' => true,
            ],
            [
                'name' => 'Home 20 Mbps',
                'price' => 250000,
                'speed' => '20 Mbps',
                'description' => 'Paket standar untuk streaming dan browsing lancar.',
                'is_active' => true,
            ],
            [
                'name' => 'Gamer 50 Mbps',
                'price' => 450000,
                'speed' => '50 Mbps',
                'description' => 'Paket ngebut untuk gaming dan download cepat.',
                'is_active' => true,
            ],
            [
                'name' => 'Sultan 100 Mbps',
                'price' => 750000,
                'speed' => '100 Mbps',
                'description' => 'Kecepatan maksimal tanpa batas.',
                'is_active' => true,
            ],
        ];

        foreach ($packages as $pkg) {
            Package::firstOrCreate(['name' => $pkg['name']], $pkg);
        }
    }
}
