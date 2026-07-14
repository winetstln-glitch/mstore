<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use Illuminate\Database\Seeder;

class BusinessUnitsSeeder extends Seeder
{
    public function run(): void
    {
        $businessUnits = [
            [
                'code' => 'ISP',
                'name' => 'ISP Internet Provider',
                'type' => 'ISP',
                'is_active' => true,
            ],
            [
                'code' => 'ATK',
                'name' => 'Toko ATK',
                'type' => 'RETAIL',
                'is_active' => true,
            ],
            [
                'code' => 'WASH',
                'name' => 'Wash & Detailing',
                'type' => 'SERVICE',
                'is_active' => true,
            ],
            [
                'code' => 'CCTV',
                'name' => 'CCTV Installation',
                'type' => 'SERVICE',
                'is_active' => true,
            ],
            [
                'code' => 'WEDDING',
                'name' => 'Wedding Organizer',
                'type' => 'SERVICE',
                'is_active' => true,
            ],
        ];

        foreach ($businessUnits as $bu) {
            BusinessUnit::firstOrCreate(['code' => $bu['code']], $bu);
        }
    }
}
