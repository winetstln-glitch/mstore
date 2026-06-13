<?php

namespace App\Services;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppLog;
use Illuminate\Support\Facades\Schema;

class WhatsAppHealthService
{
    public static function checkTables(): array
    {
        return [
            'whatsapp_conversations' => Schema::hasTable('whatsapp_conversations'),
            'whatsapp_logs' => Schema::hasTable('whatsapp_logs'),
            'ai_histories' => Schema::hasTable('ai_histories'),
            'whatsapp_groups' => Schema::hasTable('whatsapp_groups'),
            'whatsapp_sessions' => Schema::hasTable('whatsapp_sessions'),
            'whatsapp_menus' => Schema::hasTable('whatsapp_menus'),
        ];
    }

    public static function getStats(): array
    {
        try {
            $conversationsCount = Schema::hasTable('whatsapp_conversations') ? WhatsAppConversation::count() : 0;
            $logsCount = Schema::hasTable('whatsapp_logs') ? WhatsAppLog::count() : 0;
            $incomingCount = Schema::hasTable('whatsapp_logs') ? WhatsAppLog::where('type', 'incoming')->count() : 0;
            $outgoingCount = Schema::hasTable('whatsapp_logs') ? WhatsAppLog::where('type', 'outgoing')->count() : 0;
            $failedOutgoing = Schema::hasTable('whatsapp_logs') ? WhatsAppLog::where('type', 'outgoing')->where('status', 'failed')->count() : 0;

            return [
                'conversations' => $conversationsCount,
                'total_logs' => $logsCount,
                'incoming' => $incomingCount,
                'outgoing' => $outgoingCount,
                'failed_outgoing' => $failedOutgoing,
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('WhatsAppHealthService stats error: ' . $e->getMessage(), ['exception' => $e]);
            return [
                'conversations' => 0,
                'total_logs' => 0,
                'incoming' => 0,
                'outgoing' => 0,
                'failed_outgoing' => 0,
            ];
        }
    }

    public static function checkAll(): array
    {
        $tables = self::checkTables();
        $allTablesOk = !in_array(false, $tables, true);
        $stats = self::getStats();

        return [
            'tables' => $tables,
            'stats' => $stats,
            'overall_status' => $allTablesOk ? 'healthy' : 'degraded',
        ];
    }
}
