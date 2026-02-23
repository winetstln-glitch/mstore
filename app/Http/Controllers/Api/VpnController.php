<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VpnAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VpnController extends Controller
{
    public function reportIp(Request $request)
    {
        $token = $request->query('token') ?: $request->input('token');
        $ip = $request->query('ip') ?: $request->input('ip');
        if (!$token || !$ip) {
            return response()->json(['ok' => false], 400);
        }
        $account = VpnAccount::where('token', $token)->first();
        if (!$account) {
            return response()->json(['ok' => false], 404);
        }
        DB::transaction(function () use ($account, $ip) {
            $router = $account->router;
            if ($router) {
                $router->vpn_tunnel_ip = preg_replace('/\\/.*/', '', (string) $ip);
                $router->vpn_status = 'connected';
                $router->save();
            }
        });
        return response()->json(['ok' => true]);
    }
}
