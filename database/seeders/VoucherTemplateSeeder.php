<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VoucherTemplate;

class VoucherTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultHtml = '
<div style="width: 200px; border: 1px solid #000; padding: 10px; margin: 5px; text-align: center; font-family: monospace; display: inline-block;">
    <div style="font-weight: bold; border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 5px;">MStore Hotspot</div>
    <div>Price: %price%</div>
    <div style="font-size: 18px; font-weight: bold; margin: 10px 0; background: #eee; padding: 5px;">%code%</div>
    <div style="font-size: 12px;">
        Profile: %profile%<br>
        Valid: %validity%
    </div>
    <div style="font-size: 10px; margin-top: 5px;">Login at: http://mstore.net</div>
</div>';

        VoucherTemplate::create([
            'name' => 'Default Simple',
            'html_content' => $defaultHtml,
            'is_default' => true,
        ]);
        
        $thermalHtml = '
<div style="width: 58mm; padding: 2mm; margin: 0 auto; text-align: center; font-family: sans-serif;">
    <div style="font-weight: bold; font-size: 14px;">MSTORE WIFI</div>
    <div style="font-size: 12px;">================</div>
    <div style="font-size: 12px;">Package: %profile%</div>
    <div style="font-size: 12px;">Price: %price%</div>
    <div style="margin: 5px 0; font-size: 20px; font-weight: bold;">%code%</div>
    <div style="font-size: 10px;">Validity: %validity%</div>
    <div style="font-size: 12px;">================</div>
    <div style="font-size: 10px;">Thank You</div>
</div>';

        VoucherTemplate::create([
            'name' => 'Thermal 58mm',
            'html_content' => $thermalHtml,
            'is_default' => false,
        ]);
    }
}
