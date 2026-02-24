<?php

namespace App\Console\Commands;

use App\Services\MixRadiusService;
use Illuminate\Console\Command;

class MixRadiusProbe extends Command
{
    protected $signature = 'mixradius:probe 
        {--username= : Username PPPoE/Hotspot untuk uji autentikasi} 
        {--password= : Password untuk uji autentikasi} 
        {--id= : ID pelanggan untuk uji lookup username}';

    protected $description = 'Menguji koneksi ke MixRADIUS untuk menemukan endpoint AUTH dan USER INFO yang valid';

    public function handle(MixRadiusService $service): int
    {
        $username = (string) $this->option('username');
        $password = (string) $this->option('password');
        $id = (string) $this->option('id');

        $this->info('=== MixRADIUS Probe ===');

        if ($id !== '') {
            $this->line('-> Mencoba lookup username dari ID: '.$id);
            $res = $service->resolveUsernameByIdWithMeta($id);
            if (is_array($res) && ($res['username'] ?? '') !== '') {
                $this->info('OK USERINFO');
                $this->table(['Field', 'Value'], [
                    ['username', $res['username']],
                    ['endpoint', $res['endpoint'] ?? '-'],
                ]);
            } else {
                $this->error('Gagal USERINFO: belum menemukan endpoint valid. Cek laravel.log untuk percobaan yang dilakukan.');
            }
        }

        if ($username !== '' && $password !== '') {
            $this->line('-> Mencoba autentikasi username: '.$username);
            $res = $service->verifyCredentials($username, $password);
            if (($res['ok'] ?? false) === true) {
                $this->info('OK AUTH');
                $this->table(['Field', 'Value'], [
                    ['endpoint', $res['endpoint'] ?? '-'],
                    ['user_field', $res['meta']['user_field'] ?? '-'],
                    ['as_form', ($res['meta']['as_form'] ?? false) ? 'true' : 'false'],
                    ['auth_header', ($res['meta']['auth_header'] ?? false) ? 'true' : 'false'],
                ]);
            } else {
                $this->error('Gagal AUTH: belum menemukan endpoint valid. Cek laravel.log untuk percobaan yang dilakukan.');
            }
        }

        if ($id === '' && ($username === '' || $password === '')) {
            $this->warn('Tidak ada opsi yang diberikan. Gunakan salah satu:');
            $this->line('  php artisan mixradius:probe --id=262222155560');
            $this->line('  php artisan mixradius:probe --username=pppoe_user --password=secret');
        }

        return self::SUCCESS;
    }
}
