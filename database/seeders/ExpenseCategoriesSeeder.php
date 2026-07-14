<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'OPER', 'name' => 'Operasional', 'description' => 'Biaya operasional sehari-hari'],
            ['code' => 'GAJI', 'name' => 'Gaji Karyawan', 'description' => 'Biaya gaji dan upah karyawan'],
            ['code' => 'LISTRIK', 'name' => 'Listrik & Air', 'description' => 'Biaya listrik dan air'],
            ['code' => 'PROMO', 'name' => 'Promosi & Iklan', 'description' => 'Biaya promosi dan iklan'],
            ['code' => 'PERL', 'name' => 'Perlengkapan', 'description' => 'Biaya perlengkapan kantor'],
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::firstOrCreate(['code' => $cat['code']], $cat);
        }
    }
}
