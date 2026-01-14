<?php

namespace App\Http\Controllers;

use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RouterController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:router.view', only: ['index', 'show', 'testConnection']),
            new Middleware('permission:router.create', only: ['create', 'store']),
            new Middleware('permission:router.edit', only: ['edit', 'update']),
            new Middleware('permission:router.delete', only: ['destroy']),
        ];
    }

    /**
     * Test connection to the router.
     */
    public function testConnection(Router $router)
    {
        try {
            $config = new \RouterOS\Config([
                'host' => $router->host,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => (int) $router->port,
                'timeout' => 5,
                'attempts' => 2,
            ]);

            $client = new \RouterOS\Client($config);
            
            // Try to get identity to verify connection
            $response = $client->query('/system/identity/print')->read();
            $identity = $response[0]['name'] ?? 'Unknown';

            return response()->json([
                'success' => true, 
                'message' => __('Connected successfully! Router Identity: :identity', ['identity' => $identity])
            ]);

        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'socket')) {
                $message .= " (Check IP, Port, or if API service is enabled on Mikrotik)";
            }
            
            return response()->json([
                'success' => false, 
                'message' => __('Connection failed: :message', ['message' => $message])
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $routers = Router::latest()->paginate(10);
        return view('routers.index', compact('routers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('routers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // Note: Password encryption is handled by model casting
        Router::create($validated);

        return redirect()->route('routers.index')->with('success', __('Router added successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Router $router)
    {
        return view('routers.show', compact('router'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Router $router)
    {
        return view('routers.edit', compact('router'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Router $router)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string|max:255', // Optional on update
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $router->update($validated);

        return redirect()->route('routers.index')->with('success', __('Router updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Router $router)
    {
        $router->delete();
        return redirect()->route('routers.index')->with('success', __('Router deleted successfully.'));
    }
}
