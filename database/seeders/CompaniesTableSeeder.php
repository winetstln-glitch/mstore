<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompaniesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::firstOrCreate(
            ['code' => 'PT001'],
            [
                'name' => 'PT Contoh Perusahaan',
                'tax_id' => '01.000.000.0-000.000',
                'currency' => 'IDR',
                'country' => 'ID',
                'address' => 'Jl. Contoh No. 123, Jakarta',
                'is_active' => true,
            ]
        );

        Company::firstOrCreate(
            ['code' => 'CV001'],
            [
                'name' => 'CV Contoh Lainnya',
                'tax_id' => '02.000.000.0-000.000',
                'currency' => 'IDR',
                'country' => 'ID',
                'address' => 'Jl. Contoh Lain No. 456, Bandung',
                'is_active' => true,
            ]
        );
    }
}
