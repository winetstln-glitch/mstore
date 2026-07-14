<?php

namespace Database\Seeders;

use App\Models\ReceiptTemplate;
use Illuminate\Database\Seeder;

class ReceiptTemplateSeeder extends Seeder
{
    public function run()
    {
        $templates = [
            [
                'name' => 'Template POS ATK',
                'transaction_type' => 'pos',
                'size' => '80mm',
                'orientation' => 'portrait',
                'header' => 'MStore ATK',
                'footer' => 'Terima kasih telah berbelanja',
                'show_logo' => true,
                'show_qr' => true,
                'show_barcode' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Template Transfer Bank',
                'transaction_type' => 'bank',
                'size' => '80mm',
                'orientation' => 'portrait',
                'header' => 'MStore ATK - Transfer Bank',
                'footer' => 'Terima kasih',
                'show_logo' => true,
                'show_qr' => true,
                'show_barcode' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Template Tarik Tunai',
                'transaction_type' => 'cash_out',
                'size' => '80mm',
                'orientation' => 'portrait',
                'header' => 'MStore ATK - Tarik Tunai',
                'footer' => 'Terima kasih',
                'show_logo' => true,
                'show_qr' => true,
                'show_barcode' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Template Top Up',
                'transaction_type' => 'top_up',
                'size' => '80mm',
                'orientation' => 'portrait',
                'header' => 'MStore ATK - Top Up',
                'footer' => 'Terima kasih',
                'show_logo' => true,
                'show_qr' => true,
                'show_barcode' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Template PPOB',
                'transaction_type' => 'ppob',
                'size' => '80mm',
                'orientation' => 'portrait',
                'header' => 'MStore ATK - PPOB',
                'footer' => 'Terima kasih',
                'show_logo' => true,
                'show_qr' => true,
                'show_barcode' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Template QRIS',
                'transaction_type' => 'qris',
                'size' => '80mm',
                'orientation' => 'portrait',
                'header' => 'MStore ATK - QRIS',
                'footer' => 'Terima kasih',
                'show_logo' => true,
                'show_qr' => true,
                'show_barcode' => true,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            ReceiptTemplate::create($template);
        }
    }
}
