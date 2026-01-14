<?php

namespace App\Http\Controllers;

use App\Models\GenieAcsServer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class GenieAcsServerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:genieacs_server.view', only: ['index']),
            new Middleware('permission:genieacs_server.create', only: ['create', 'store']),
            new Middleware('permission:genieacs_server.edit', only: ['edit', 'update']),
            new Middleware('permission:genieacs_server.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $servers = GenieAcsServer::all();
        return view('genieacs.servers.index', compact('servers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('genieacs.servers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'is_active' => 'boolean',
        ]);

        // Ensure only one server is active if needed, or allow multiple.
        // For now, simple creation.
        
        GenieAcsServer::create($validated);

        return redirect()->route('genieacs.servers.index')
            ->with('success', 'GenieACS Server added successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GenieAcsServer $server)
    {
        return view('genieacs.servers.edit', compact('server'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GenieAcsServer $server)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'is_active' => 'boolean',
        ]);

        $server->update($validated);

        return redirect()->route('genieacs.servers.index')
            ->with('success', __('GenieACS Server updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GenieAcsServer $server)
    {
        $server->delete();

        return redirect()->route('genieacs.servers.index')
            ->with('success', __('GenieACS Server deleted successfully.'));
    }
}
