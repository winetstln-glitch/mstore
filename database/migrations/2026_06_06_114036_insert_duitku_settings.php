<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaults = [
            [
                'key' => 'duitku_merchant_code',
                'value' => '',
                'group' => 'payment',
                'type' => 'text',
                'label' => 'Duitku Merchant Code',
            ],
            [
                'key' => 'duitku_api_key',
                'value' => '',
                'group' => 'payment',
                'type' => 'text',
                'label' => 'Duitku API Key',
            ],
            [
                'key' => 'duitku_sandbox',
                'value' => '1',
                'group' => 'payment',
                'type' => 'boolean',
                'label' => 'Duitku Sandbox Mode',
            ],
        ];

        foreach ($defaults as $setting) {
            \App\Models\Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        // Clear setting cache
        \App\Models\Setting::forgetCache();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\Setting::where('key', 'like', 'duitku_%')->delete();
        \App\Models\Setting::forgetCache();
    }
};
