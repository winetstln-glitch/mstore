<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['code', 'name', 'type', 'parent_id', 'is_active'];

    public const DEFAULT_CHART = [
        ['code' => '1001', 'name' => 'Kas', 'type' => 'asset'],
        ['code' => '1002', 'name' => 'Bank', 'type' => 'asset'],
        ['code' => '1101', 'name' => 'Piutang Usaha', 'type' => 'asset'],
        ['code' => '1201', 'name' => 'Persediaan ATK', 'type' => 'asset'],
        ['code' => '1301', 'name' => 'Peralatan Jaringan', 'type' => 'asset'],
        ['code' => '1302', 'name' => 'Kendaraan', 'type' => 'asset'],
        ['code' => '1401', 'name' => 'Deposit Agen Bank', 'type' => 'asset'],
        ['code' => '2001', 'name' => 'Hutang Supplier', 'type' => 'liability'],
        ['code' => '2002', 'name' => 'Hutang Gaji', 'type' => 'liability'],
        ['code' => '2101', 'name' => 'Pendapatan Diterima Dimuka', 'type' => 'liability'],
        ['code' => '3001', 'name' => 'Modal', 'type' => 'equity'],
        ['code' => '3101', 'name' => 'Laba Ditahan', 'type' => 'equity'],
        ['code' => '3201', 'name' => 'Laba Berjalan', 'type' => 'equity'],
        ['code' => '4001', 'name' => 'Pendapatan ISP', 'type' => 'revenue'],
        ['code' => '4002', 'name' => 'Pendapatan Instalasi', 'type' => 'revenue'],
        ['code' => '4003', 'name' => 'Pendapatan ATK', 'type' => 'revenue'],
        ['code' => '4004', 'name' => 'Pendapatan Jasa Transfer Bank', 'type' => 'revenue'],
        ['code' => '4005', 'name' => 'Pendapatan Car Wash', 'type' => 'revenue'],
        ['code' => '4006', 'name' => 'Pendapatan Lain-lain', 'type' => 'revenue'],
        ['code' => '5001', 'name' => 'HPP ATK', 'type' => 'expense'],
        ['code' => '6001', 'name' => 'Beban Bandwidth', 'type' => 'expense'],
        ['code' => '6002', 'name' => 'Beban Listrik', 'type' => 'expense'],
        ['code' => '6003', 'name' => 'Beban Gaji', 'type' => 'expense'],
        ['code' => '6004', 'name' => 'Beban ATK Internal', 'type' => 'expense'],
        ['code' => '6005', 'name' => 'Beban Maintenance', 'type' => 'expense'],
        ['code' => '6006', 'name' => 'Beban Transport', 'type' => 'expense'],
        ['code' => '6007', 'name' => 'Beban Bank/Payment', 'type' => 'expense'],
        ['code' => '6008', 'name' => 'Beban Komisi Koordinator', 'type' => 'expense'],
        ['code' => '6009', 'name' => 'Beban Bagi Hasil Investor', 'type' => 'expense'],
        ['code' => '6010', 'name' => 'Beban Pengurus', 'type' => 'expense'],
        ['code' => '6011', 'name' => 'Beban Konsumsi', 'type' => 'expense'],
        ['code' => '6012', 'name' => 'Beban Operasional Umum', 'type' => 'expense'],
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public static function ensureDefaultChart(): void
    {
        foreach (self::DEFAULT_CHART as $row) {
            self::query()->firstOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'type' => $row['type'], 'is_active' => true]
            );
        }
    }
}
