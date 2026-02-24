<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Setting::create([
            'key' => 'telegram_bot_token',
            'value' => env('TELEGRAM_BOT_TOKEN', ''),
            'group' => 'telegram',
            'type' => 'text',
            'label' => 'Telegram Bot Token',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::where('key', 'telegram_bot_token')->delete();
    }
};
