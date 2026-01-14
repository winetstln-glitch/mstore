<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Models\Onu;
use App\Services\Olt\OltService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

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
            new Middleware('permission:olt.edit', only: ['sync']),
        ];
    }

    public function index(Olt $olt)
    {
        $onus = $olt->onus()->orderBy('interface')->paginate(10);
        return view('olt.onus.index', compact('olt', 'onus'));
    }

    public function sync(Olt $olt)
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
            if (!empty($onuDataList)) {
                // Determine existing ONUs to potentially deactivate missing ones
                // For now, just update/create
                
                foreach ($onuDataList as $data) {
                    $olt->onus()->updateOrCreate(
                        ['interface' => $data['interface']], // Key
                        $data
                    );
                    $count++;
                }
                return redirect()->route('olt.onus.index', $olt->id)->with('success', __('Synced :count ONUs successfully.', ['count' => $count]));
            }
            
            // If empty, it might be due to parsing error or actually empty
            // Fallback to simulation if simulated data is requested or just show warning
            // For now, we return warning
            return redirect()->route('olt.onus.index', $olt->id)->with('warning', __('Connection successful but no ONUs found. If using Web Mode, check laravel.log for parsing details/errors.'));

        } catch (\Exception $e) {
            return redirect()->route('olt.onus.index', $olt->id)->with('error', __('Sync failed: :message', ['message' => $e->getMessage()]));
        }
    }
}
