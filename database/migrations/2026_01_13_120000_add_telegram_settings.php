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

        Setting::create([
            'key' => 'telegram_technician_group_chat_id',
            'value' => env('TELEGRAM_TECHNICIAN_GROUP_CHAT_ID', ''),
            'group' => 'telegram',
            'type' => 'text',
            'label' => 'Telegram Technician Group Chat ID',
        ]);

        Setting::create([
            'key' => 'telegram_ticket_template',
            'value' => null,
            'group' => 'telegram',
            'type' => 'textarea',
            'label' => 'Template Notifikasi Tiket Baru',
        ]);

        Setting::create([
            'key' => 'telegram_ticket_solved_template',
            'value' => null,
            'group' => 'telegram',
            'type' => 'textarea',
            'label' => 'Template Notifikasi Tiket Selesai',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::whereIn('key', [
            'telegram_bot_token',
            'telegram_technician_group_chat_id',
            'telegram_ticket_template',
            'telegram_ticket_solved_template'
        ])->delete();
    }
};
