<?php

namespace App\Http\Controllers;

use App\Models\VpnServer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class VpnServerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:router.view', only: ['index']),
            new Middleware('permission:router.create', only: ['create', 'store']),
            new Middleware('permission:router.edit', only: ['edit', 'update']),
            new Middleware('permission:router.delete', only: ['destroy']),
        ];
    }

    public function index()
    {
        $servers = VpnServer::orderBy('location')->orderBy('name')->paginate(20);
        return view('vpn.servers.index', compact('servers'));
    }

    public function create()
    {
        return view('vpn.servers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'location' => 'nullable|string|max:50',
            'ip_public' => 'required|string|max:100',
            'port' => 'required|integer',
            'protocol' => 'required|in:l2tp,pptp,sstp,openvpn',
            'status' => 'required|in:active,maintenance',
        ]);
        VpnServer::create($data);
        return redirect()->route('vpn.servers.index')->with('success', 'Server VPN ditambahkan');
    }

    public function edit(VpnServer $server)
    {
        return view('vpn.servers.edit', compact('server'));
    }

    public function update(Request $request, VpnServer $server)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'location' => 'nullable|string|max:50',
            'ip_public' => 'required|string|max:100',
            'port' => 'required|integer',
            'protocol' => 'required|in:l2tp,pptp,sstp,openvpn',
            'status' => 'required|in:active,maintenance',
        ]);
        $server->update($data);
        return redirect()->route('vpn.servers.index')->with('success', 'Server VPN diperbarui');
    }

    public function destroy(VpnServer $server)
    {
        $server->delete();
        return redirect()->route('vpn.servers.index')->with('success', 'Server VPN dihapus');
    }
}
