<?php

namespace App\Http\Controllers;

use App\Models\OLT;
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

    public function index(OLT $olt)
    {
        return redirect()->route('olt.show', $olt);
    }

    public function update(Request $request, Onu $onu)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $olt = $onu->olt;
            $driver = $this->oltService->getDriver($olt);
            $driver->connect($olt);

            $driver->setOnuName($onu->onu_index ?? $onu->interface, $validated['name']);

            $driver->disconnect();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to update ONU name on OLT: " . $e->getMessage());
        }

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
            $driver = $this->oltService->getDriver($olt);
            $driver->connect($olt);

            if ($olt->brand === 'hsgq') {
                $driver->rebootOnu($onu->interface);
            }

            $driver->disconnect();

            return response()->json([
                'success' => true,
                'message' => __('Reboot command sent successfully.')
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to reboot ONU: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Reboot failed: :message', ['message' => $e->getMessage()])
            ]);
        }
    }

    public function sync(OLT $olt, Request $request)
    {
        $startTime = microtime(true);
        
        try {
            $service = new \App\Services\Olt\OltService();
            $driver = $service->getDriver($olt);
            $driver->connect($olt, 30);
            $onuDataList = $driver->getOnus();
            $driver->disconnect();

            $count = 0;
            $foundInterfaces = [];
            if (!empty($onuDataList)) {
                foreach ($onuDataList as $data) {
                    $olt->onus()->updateOrCreate(
                        ['interface' => $data['interface']],
                        array_merge($data, ['last_updated' => now()])
                    );
                    $foundInterfaces[] = $data['interface'];
                    $count++;
                }
            }

            if (!empty($foundInterfaces)) {
                $deletedCount = $olt->onus()->whereNotIn('interface', $foundInterfaces)->delete();
            }

            $olt->update(['last_synced_at' => now()]);
            $duration = round((microtime(true) - $startTime) * 1000);

            if ($request->wantsJson() || $request->is('api/*') || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'success' => true,
                    'message' => __('Sinkronisasi selesai. Berhasil :count ONU ditemukan.', ['count' => $count]),
                    'count' => $count,
                    'duration' => $duration
                ]);
            }

            return redirect()->route('olt.show', $olt->id)->with('success', __('Sinkronisasi selesai. Berhasil :count ONU ditemukan.', ['count' => $count]));

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000);
            $errors = [];
            $debug = [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
            
            if ($request->wantsJson() || $request->is('api/*') || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'success' => false,
                    'message' => __('Gagal sinkronisasi: :message', ['message' => $e->getMessage()]),
                    'errors' => $errors,
                    'debug' => $debug,
                    'duration' => $duration
                ], 500);
            }
            
            return redirect()->route('olt.show', $olt->id)->with('error', __('Gagal sinkronisasi: :message', ['message' => $e->getMessage()]));
        }
    }

    public function destroy(Request $request, Onu $onu)
    {
        try {
            $onu->delete();

            if ($request->wantsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'success' => true,
                    'message' => __('ONU deleted successfully')
                ]);
            }

            return redirect()->back()->with('success', __('ONU deleted successfully'));
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'success' => false,
                    'message' => __('Error deleting ONU: :message', ['message' => $e->getMessage()])
                ], 500);
            }

            return redirect()->back()->with('error', __('Error deleting ONU: :message', ['message' => $e->getMessage()]));
        }
    }
}
