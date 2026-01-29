<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Router;
use App\Models\Voucher;
use App\Models\HotspotProfile;
use App\Services\MikrotikService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HotspotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Router::where('is_active', true);

        // Filter for Coordinator
        if (!$user->hasRole(['admin', 'management']) && $user->coordinator) {
            if ($user->coordinator->router_id) {
                $query->where('id', $user->coordinator->router_id);
            } elseif ($user->coordinator->region_id) {
                $query->where('region_id', $user->coordinator->region_id);
            } else {
                $query->where('id', 0); // No access
            }
        }

        $routers = $query->get();

        // Prioritize Router ID from request, or fallback to first available
        $routerId = $request->query('router_id');
        
        if ($routerId) {
            $router = $routers->where('id', $routerId)->first();
        } else {
            $router = $routers->first();
        }
        
        $hotspotUsers = [];
        $hotspotProfiles = [];
        $hotspotActive = [];
        $mikrotikConnected = false;
        $totalActiveBalance = 0;

        if ($router) {
            try {
                $mikrotik = new MikrotikService($router);
                if ($mikrotik->isConnected()) {
                    $mikrotikConnected = true;
                    $hotspotUsers = $mikrotik->getHotspotUsers();
                    $hotspotProfiles = $mikrotik->getHotspotProfiles();
                    $hotspotActive = $mikrotik->getHotspotActiveList();

                    // Calculate Active Voucher Balance
                    if (!empty($hotspotActive)) {
                        $activeUsernames = array_column($hotspotActive, 'user');
                        $totalActiveBalance = Voucher::whereIn('code', $activeUsernames)->sum('price');
                    }
                }
            } catch (\Exception $e) {
                $mikrotikConnected = false;
                Log::error("Hotspot Index Error: " . $e->getMessage());
            }
        }

        return view('hotspot.index', compact('router', 'routers', 'hotspotUsers', 'hotspotProfiles', 'hotspotActive', 'mikrotikConnected', 'totalActiveBalance'));
    }

    public function generate(Request $request)
    {
        $user = auth()->user();
        $query = Router::where('is_active', true);

        // Filter for Coordinator
        if (!$user->hasRole(['admin', 'management']) && $user->coordinator) {
            if ($user->coordinator->router_id) {
                $query->where('id', $user->coordinator->router_id);
            } elseif ($user->coordinator->region_id) {
                $query->where('region_id', $user->coordinator->region_id);
            } else {
                $query->where('id', 0);
            }
        }

        $routers = $query->get();
        
        $routerId = $request->query('router_id');
        if ($routerId) {
            $router = $routers->where('id', $routerId)->first();
        } else {
            $router = $routers->first();
        }

        $profiles = [];
        if ($router) {
            try {
                $mikrotik = new MikrotikService($router);
                if ($mikrotik->isConnected()) {
                    $profiles = $mikrotik->getHotspotProfiles();
                }
            } catch (\Exception $e) {}
        }

        return view('hotspot.generate', compact('router', 'routers', 'profiles'));
    }

    public function storeGenerate(Request $request)
    {
        $request->validate([
            'router_id' => 'required|exists:routers,id',
            'profile' => 'required|string',
            'quantity' => 'required|integer|min:1|max:500',
            'price' => 'required|numeric',
            'length' => 'required|integer|min:4|max:20',
            'prefix' => 'nullable|string|max:10',
            'time_limit' => 'nullable|string', // e.g. 1h, 1d
        ]);

        $router = Router::find($request->router_id);

        // Access Control
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'management']) && $user->coordinator) {
            $allowed = false;
            if ($user->coordinator->router_id && $user->coordinator->router_id == $router->id) $allowed = true;
            if ($user->coordinator->region_id && $user->coordinator->region_id == $router->region_id) $allowed = true;
            
            if (!$allowed) {
                return back()->with('error', 'Unauthorized access to this router.');
            }
        }

        $mikrotik = new MikrotikService($router);
        
        if (!$mikrotik->isConnected()) {
            return back()->with('error', 'Could not connect to Mikrotik');
        }

        $batchId = (string) Str::uuid();
        
        DB::beginTransaction();
        try {
            $hotspotProfile = HotspotProfile::firstOrCreate(
                ['router_id' => $router->id, 'name' => $request->profile],
                [
                    'price' => $request->price,
                    'shared_users' => 1,
                    'validity_value' => (int) filter_var($request->time_limit, FILTER_SANITIZE_NUMBER_INT) ?: 24,
                    'validity_unit' => 'hours'
                ]
            );
            
            if ($hotspotProfile->price != $request->price) {
                $hotspotProfile->update(['price' => $request->price]);
            }

            for ($i = 0; $i < $request->quantity; $i++) {
                $code = $request->prefix . Str::upper(Str::random($request->length));
                
                $mikrotik->createHotspotUser(
                    $code, 
                    $code, 
                    $request->profile,
                    $request->time_limit,
                    null, 
                    "Generated Batch: " . substr($batchId, 0, 8)
                );

                Voucher::create([
                    'hotspot_profile_id' => $hotspotProfile->id,
                    'code' => $code,
                    'password' => $code,
                    'price' => $request->price,
                    'status' => 'active',
                    'generated_by' => auth()->id(),
                    'batch_id' => $batchId
                ]);
            }

            // Create Transaction for Print Fee if applicable
            $printFee = $request->input('print_fee', 0);
            if ($printFee > 0) {
                $totalPrintFee = $printFee * $request->quantity;
                Transaction::create([
                    'user_id' => auth()->id(),
                    'type' => 'income',
                    'category' => 'Jasa Print Voucher',
                    'amount' => $totalPrintFee,
                    'transaction_date' => now(),
                    'description' => "Jasa cetak voucher batch " . substr($batchId, 0, 8) . " (" . $request->quantity . " pcs @ " . number_format($printFee, 0, ',', '.') . ")",
                    'reference_number' => 'VOUCHER-PRINT-' . substr($batchId, 0, 8),
                ]);
            }
            
            DB::commit();
            return redirect()->route('hotspot.print', ['batch_id' => $batchId]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Voucher Generation Error: " . $e->getMessage());
            return back()->with('error', 'Error generating vouchers: ' . $e->getMessage());
        }
    }
    
    public function print(Request $request)
    {
        $batchId = $request->query('batch_id');
        if (!$batchId) {
            return redirect()->route('hotspot.index')->with('error', 'No batch specified');
        }

        $vouchers = Voucher::where('batch_id', $batchId)->with('profile')->get();
        
        $templates = \App\Models\VoucherTemplate::all();
        $selectedTemplate = $request->query('template_id') 
            ? \App\Models\VoucherTemplate::find($request->query('template_id')) 
            : \App\Models\VoucherTemplate::where('is_default', true)->first();

        // Fallback if no template
        if (!$selectedTemplate && $templates->isNotEmpty()) {
            $selectedTemplate = $templates->first();
        }

        return view('hotspot.print', compact('vouchers', 'templates', 'selectedTemplate'));
    }

    public function createProfile(Request $request)
    {
        $user = auth()->user();
        $query = Router::where('is_active', true);

        // Filter for Coordinator
        if (!$user->hasRole(['admin', 'management']) && $user->coordinator) {
            if ($user->coordinator->router_id) {
                $query->where('id', $user->coordinator->router_id);
            } elseif ($user->coordinator->region_id) {
                $query->where('region_id', $user->coordinator->region_id);
            } else {
                $query->where('id', 0);
            }
        }

        $routers = $query->get();
        
        $routerId = $request->query('router_id');
        if ($routerId) {
            $router = $routers->where('id', $routerId)->first();
        } else {
            $router = $routers->first();
        }

        return view('hotspot.create_profile', compact('router', 'routers'));
    }

    public function storeProfile(Request $request)
    {
        $request->validate([
            'router_id' => 'required|exists:routers,id',
            'name' => 'required|string|max:50',
            'shared_users' => 'required|integer|min:1',
            'rate_limit' => 'nullable|string', // e.g., 1M/1M
            'price' => 'required|numeric|min:0',
            'validity' => 'nullable|string', // e.g. 1d, 30d
        ]);

        $router = Router::find($request->router_id);
        
        // Access Control
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'management']) && $user->coordinator) {
            $allowed = false;
            if ($user->coordinator->router_id && $user->coordinator->router_id == $router->id) $allowed = true;
            if ($user->coordinator->region_id && $user->coordinator->region_id == $router->region_id) $allowed = true;
            
            if (!$allowed) {
                return back()->with('error', 'Unauthorized access to this router.');
            }
        }

        $mikrotik = new MikrotikService($router);

        if (!$mikrotik->isConnected()) {
            return back()->with('error', 'Could not connect to Mikrotik');
        }

        try {
            // Create in Mikrotik
            $success = $mikrotik->createHotspotProfile(
                $request->name, 
                $request->shared_users, 
                $request->rate_limit,
                $request->validity // session-timeout
            );

            if ($success) {
                // Save to Local DB
                HotspotProfile::updateOrCreate(
                    ['router_id' => $router->id, 'name' => $request->name],
                    [
                        'shared_users' => $request->shared_users,
                        'rate_limit' => $request->rate_limit,
                        'price' => $request->price,
                        'validity_value' => (int) filter_var($request->validity, FILTER_SANITIZE_NUMBER_INT) ?: 24,
                        'validity_unit' => 'hours' // Simplified
                    ]
                );

                return redirect()->route('hotspot.index', ['router_id' => $router->id])
                                 ->with('success', 'Profile created successfully');
            } else {
                return back()->with('error', 'Failed to create profile in Mikrotik');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating profile: ' . $e->getMessage());
        }
    }
}
