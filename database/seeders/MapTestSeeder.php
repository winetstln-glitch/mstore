<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class MapTestSeeder extends Seeder
{
    public function run()
    {
        Customer::create([
            'name' => 'Customer Pusat',
            'address' => 'Jakarta Pusat, Monas',
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'status' => 'active',
            'phone' => '081234567890'
        ]);

        Customer::create([
            'name' => 'Customer Selatan',
            'address' => 'Blok M, Jakarta Selatan',
            'latitude' => -6.244220,
            'longitude' => 106.801648,
            'status' => 'active',
            'phone' => '081234567891'
        ]);
        
        Customer::create([
            'name' => 'Customer Offline',
            'address' => 'Mangga Dua, Jakarta Utara',
            'latitude' => -6.137452,
            'longitude' => 106.829375,
            'status' => 'suspend',
            'phone' => '081234567892'
        ]);
    }
}
