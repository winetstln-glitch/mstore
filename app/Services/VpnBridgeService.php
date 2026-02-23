<?php

namespace App\Services;

use App\Models\Router;
use App\Models\User;
use App\Models\VpnAccount;
use App\Models\VpnServer;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class VpnBridgeService
{
    public function provisionForRouter(Router $router, ?User $owner = null): ?VpnAccount
    {
        $q = VpnServer::query()->where('status', 'active')->withCount('vpnAccounts');
        if ($router->location) {
            $loc = str_contains(strtolower($router->location), 'sg') ? 'sg' : (str_contains(strtolower($router->location), 'id') ? 'id' : null);
            if ($loc) {
                $q->where('location', 'like', strtoupper($loc) . '%');
            }
        }
        $server = $q->orderBy('vpn_accounts_count')->orderByRaw('COALESCE(last_latency_ms, 999999) asc')->first();

        if (!$server) {
            return null;
        }

        $username = 'rt_' . Str::lower(Str::random(6));
        $password = Str::random(10);
        $token = Str::random(40);

        return DB::transaction(function () use ($router, $owner, $server, $username, $password, $token) {
            $account = VpnAccount::create([
                'user_id' => $owner?->id,
                'router_id' => $router->id,
                'vpn_server_id' => $server->id,
                'username' => $username,
                'password' => $password,
                'token' => $token,
                'status' => 'active',
            ]);

            $radius = new RadiusService();
            $radius->addUser($username, $password);

            $router->vpn_account_id = $account->id;
            $router->save();

            return $account;
        });
    }

    public function generateScript(VpnAccount $account, string $protocol = 'l2tp', ?string $profileName = 'vpn-billing-bridge'): string
    {
        $server = $account->server;
        $host = $server->ip_public;
        $pn = $profileName ?: 'vpn-billing-bridge';
        $u = $account->username;
        $p = $account->password;
        $base = rtrim(config('app.url') ?? url('/'), '/');
        $reportUrl = $base . '/api/vpn/report-ip?token=' . urlencode($account->token) . '&ip=$[/ip address get [find where interface=' . $pn . '] address]';

        $sched = "/system scheduler\nadd name=auto-report-ip on-event=\"/tool fetch url=\\\"{$reportUrl}\\\" keep-result=no\" interval=5m\n";
        if ($protocol === 'pptp') {
            return "/interface pptp-client\nadd name={$pn} connect-to={$host} user=\"{$u}\" password=\"{$p}\" profile=default-encryption disabled=no\n/ip firewall nat\nadd chain=srcnat out-interface={$pn} action=masquerade\n/tool fetch url=\"{$reportUrl}\" keep-result=no\n{$sched}";
        }
        if ($protocol === 'sstp') {
            return "/interface sstp-client\nadd name={$pn} connect-to={$host} user=\"{$u}\" password=\"{$p}\" verify-server-certificate=no disabled=no\n/ip firewall nat\nadd chain=srcnat out-interface={$pn} action=masquerade\n/tool fetch url=\"{$reportUrl}\" keep-result=no\n{$sched}";
        }
        if ($protocol === 'openvpn') {
            return "/interface ovpn-client\nadd name={$pn} connect-to={$host} user=\"{$u}\" password=\"{$p}\" add-default-route=no disabled=no\n/ip firewall nat\nadd chain=srcnat out-interface={$pn} action=masquerade\n/tool fetch url=\"{$reportUrl}\" keep-result=no\n{$sched}";
        }
        return "/interface l2tp-client\nadd name={$pn} connect-to={$host} user=\"{$u}\" password=\"{$p}\" profile=default-encryption disabled=no\n/ip firewall nat\nadd chain=srcnat out-interface={$pn} action=masquerade\n/tool fetch url=\"{$reportUrl}\" keep-result=no\n{$sched}";
    }
}
