<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportWhatsAppTemplates extends Command
{
    protected $signature = 'whatsapp:import-templates';
    protected $description = 'Import WhatsApp menu templates';

    public function handle()
    {
        $this->info('Importing WhatsApp menu templates...');

        \Artisan::call('db:seed', [
            '--class' => 'WhatsAppMenuSeeder',
        ]);

        $this->info('✅ Templates imported successfully!');
    }
}
