<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['1001', 'Kas', 'asset'],
            ['1002', 'Bank', 'asset'],
            ['1101', 'Piutang Usaha', 'asset'],
            ['1201', 'Persediaan ATK', 'asset'],
            ['1301', 'Peralatan Jaringan', 'asset'],
            ['1302', 'Kendaraan', 'asset'],
            ['1401', 'Deposit Agen Bank', 'asset'],
            ['2001', 'Hutang Supplier', 'liability'],
            ['2002', 'Hutang Gaji', 'liability'],
            ['2101', 'Pendapatan Diterima Dimuka', 'liability'],
            ['3001', 'Modal', 'equity'],
            ['3101', 'Laba Ditahan', 'equity'],
            ['3201', 'Laba Berjalan', 'equity'],
            ['4001', 'Pendapatan ISP', 'revenue'],
            ['4002', 'Pendapatan Instalasi', 'revenue'],
            ['4003', 'Pendapatan ATK', 'revenue'],
            ['4004', 'Pendapatan Jasa Transfer Bank', 'revenue'],
            ['4005', 'Pendapatan Car Wash', 'revenue'],
            ['5001', 'HPP ATK', 'expense'],
            ['6001', 'Beban Bandwidth', 'expense'],
            ['6002', 'Beban Listrik', 'expense'],
            ['6003', 'Beban Gaji', 'expense'],
            ['6004', 'Beban ATK Internal', 'expense'],
            ['6005', 'Beban Maintenance', 'expense'],
            ['6006', 'Beban Transport', 'expense'],
            ['6007', 'Beban Bank/Payment', 'expense'],
        ];
        foreach ($rows as $r) {
            Account::firstOrCreate(['code' => $r[0]], ['name' => $r[1], 'type' => $r[2]]);
        }
    }
}
