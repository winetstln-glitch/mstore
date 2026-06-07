<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppMenu;

class WhatsAppAutoReplyService
{
    public function __construct() {}

    public function getReply(string $message): ?string
    {
        $message = strtolower(trim($message));

        if ($this->matchKeyword($message, ['halo', 'hi', 'hello', 'hey'])) {
            return $this->getGreetingReply();
        }

        if ($this->matchKeyword($message, ['bantuan', 'help', 'menu'])) {
            return $this->getHelpReply();
        }

        return null;
    }

    private function matchKeyword(string $message, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        return false;
    }

    private function getGreetingReply(): string
    {
        return "Halo! 👋\n\nSelamat datang di WhatsApp Bot MStore.\n\nKetik *bantuan* untuk melihat menu layanan yang tersedia.";
    }

    private function getHelpReply(): string
    {
        $menuText = "📋 *Menu Layanan MStore*\n\n";
        
        // Add default internal menus first
        $menuText .= "🔹 *Menu Utama*\n";
        $menuText .= "• *halo* - Sapa bot\n";
        $menuText .= "• *bantuan* - Menampilkan menu ini\n\n";
        
        // Add menus from WhatsAppMenu (bot builder)
        $menus = WhatsAppMenu::active()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($menus->isNotEmpty()) {
            $menuText .= "🔹 *Layanan Kami*\n";
            foreach ($menus as $menu) {
                $menuText .= "• *{$menu->keyword}*\n";
            }
            $menuText .= "\n";
        }
        
        $menuText .= "Terima kasih! 🙏";
        
        return $menuText;
    }
}
