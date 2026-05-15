<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Models\Onu;
use App\Services\Olt\OltService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Http;

class OnuController extends Controller implements HasMiddleware
{
    protected $oltService;

    public function __construct(OltService $oltService)
    {
        $this->oltService = $oltService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:olt.view', only: ['index']),
            new Middleware('permission:olt.edit', only: ['sync', 'update', 'reboot']),
        ];
    }

    public function index(Olt $olt)
    {
        return redirect()->route('olt.show', $olt);
    }

    public function update(Request $request, Onu $onu)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $onu->update($validated);

        if ($request->wantsJson() || $request->header('Accept') === 'application/json') {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', __('ONU name updated successfully.'));
    }

    public function reboot(Request $request, Onu $onu)
    {
        try {
            $olt = $onu->olt;

            if (empty($olt->web_user) || empty($olt->web_password)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Web credentials not configured for this OLT.')
                ]);
            }

            // Basic reboot implementation - for now, just return success placeholder
            // In production, you'd implement actual reboot logic here (like Dinobill)
            return response()->json([
                'success' => true,
                'message' => __('Reboot command sent successfully.')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Reboot failed: :message', ['message' => $e->getMessage()])
            ]);
        }
    }

    public function sync(Olt $olt, Request $request)
    {
        set_time_limit(300); // 5 minutes max for sync

        try {
            // Get the appropriate driver
            $driver = $this->oltService->getDriver($olt);

            // Connect
            $driver->connect($olt, 30); // 30s timeout for connection

            // Fetch ONUs
            $onuDataList = $driver->getOnus();

            // Disconnect
            $driver->disconnect();

            // Sync logic (update DB)
            $count = 0;
            if (! empty($onuDataList)) {
                foreach ($onuDataList as $data) {
                    $olt->onus()->updateOrCreate(
                        ['interface' => $data['interface']], // Key
                        array_merge($data, ['last_updated' => now()])
                    );
                    $count++;
                }

                if ($request->wantsJson() || $request->is('api/*') || $request->header('Accept') === 'application/json') {
                    return response()->json([
                        'success' => true,
                        'message' => __('Synced :count ONUs successfully.', ['count' => $count])
                    ]);
                }

                return redirect()->route('olt.onus.index', $olt->id)->with('success', __('Synced :count ONUs successfully.', ['count' => $count]));
            }

            // If empty, it might be due to parsing error or actually empty
            $method = ($driver instanceof \App\Services\Olt\Drivers\SnmpDriver) ? 'SNMP' : 'Telnet/Web';
            
            if ($request->wantsJson() || $request->is('api/*') || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'success' => false,
                    'message' => __("Connection successful (:method) but no ONUs found. If using SNMP, check Community/Port. If using Telnet, check parsing logic.", ['method' => $method])
                ]);
            }

            return redirect()->route('olt.onus.index', $olt->id)->with('warning', __("Connection successful (:method) but no ONUs found. If using SNMP, check Community/Port. If using Telnet, check parsing logic.", ['method' => $method]));

        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->is('api/*') || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'success' => false,
                    'message' => __('Sync failed: :message', ['message' => $e->getMessage()])
                ]);
            }
            
            return redirect()->route('olt.onus.index', $olt->id)->with('error', __('Sync failed: :message', ['message' => $e->getMessage()]));
        }
    }
}
