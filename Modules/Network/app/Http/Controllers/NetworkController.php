<?php

namespace Modules\Network\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Closure;
use App\Models\Customer;
use App\Models\FiberPlan;
use App\Models\Htb;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\OLT;
use App\Models\OLTPort;
use App\Models\ONT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Modules\Network\Contracts\NetworkProviderInterface;
use Modules\Network\Services\CapacityService;
use Modules\Network\Services\FiberPlannerService;
use Modules\Network\Services\GisAnalyticsService;
use Modules\Network\Services\MonitoringService;
use Modules\Network\Services\OpticalMonitoringService;
use Modules\Network\Services\TopologyService;

class NetworkController extends Controller
{
    public function __construct(
        private MonitoringService $monitoringService,
        private NetworkProviderInterface $networkProvider,
        private TopologyService $topologyService,
        private CapacityService $capacityService,
        private OpticalMonitoringService $opticalMonitoringService,
        private FiberPlannerService $fiberPlannerService,
        private GisAnalyticsService $gisAnalyticsService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('network::index');
    }

    /**
     * Network health check
     */
    public function health()
    {
        $healthChecks = [];
        $isHealthy = true;
        $startTime = microtime(true);

        // Check Network Provider
        try {
            $providerHealth = $this->networkProvider->health();
            $healthChecks['provider'] = [
                'status' => ($providerHealth['ok'] ?? false) ? 'ok' : 'error',
                'data' => $providerHealth
            ];
            if (!($providerHealth['ok'] ?? false)) {
                $isHealthy = false;
            }
        } catch (\Exception $e) {
            $healthChecks['provider'] = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            $isHealthy = false;
        }

        // Check Database
        try {
            DB::connection()->getPdo();
            $healthChecks['database'] = ['status' => 'ok'];
        } catch (\Exception $e) {
            $healthChecks['database'] = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            $isHealthy = false;
        }

        // Check Cache
        try {
            Cache::put('health_check', true, 10);
            $cacheValue = Cache::get('health_check');
            $healthChecks['cache'] = [
                'status' => $cacheValue === true ? 'ok' : 'error'
            ];
        } catch (\Exception $e) {
            $healthChecks['cache'] = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            $isHealthy = false;
        }

        // Check Queue
        try {
            $queueConnection = Queue::connection();
            $healthChecks['queue'] = ['status' => 'ok'];
        } catch (\Exception $e) {
            $healthChecks['queue'] = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            $isHealthy = false;
        }

        $totalDuration = (int) round((microtime(true) - $startTime) * 1000);

        $response = [
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'duration_ms' => $totalDuration,
            'timestamp' => now()->toIso8601String(),
            'checks' => $healthChecks
        ];

        Log::channel('network')->info('Health check completed', $response);

        return response()->json($response, $isHealthy ? 200 : 503);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('network::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('network::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('network::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}

    /**
     * Get topology summary
     */
    public function topologySummary()
    {
        return response()->json($this->topologyService->getTopologySummary());
    }

    /**
     * Get OLT topology tree
     */
    public function oltTopology(OLT $olt)
    {
        return response()->json($this->topologyService->getOltTopologyTree($olt));
    }

    /**
     * Get ODC topology
     */
    public function odcTopology(Odc $odc)
    {
        return response()->json($this->topologyService->getOdcTopology($odc));
    }

    /**
     * Get ODP topology
     */
    public function odpTopology(Odp $odp)
    {
        return response()->json($this->topologyService->getOdpTopology($odp));
    }

    /**
     * Get HTB topology
     */
    public function htbTopology(Htb $htb)
    {
        return response()->json($this->topologyService->getHtbTopology($htb));
    }

    /**
     * Get Customer topology
     */
    public function customerTopology(Customer $customer)
    {
        return response()->json($this->topologyService->getCustomerTopology($customer));
    }

    /**
     * Get affected customers (fault isolation)
     */
    public function affectedCustomers(Request $request)
    {
        $validated = $request->validate([
            'node_type' => 'required|in:olt,odc,odp,htb',
            'node_id' => 'required|integer',
        ]);

        return response()->json($this->topologyService->getAffectedCustomers(
            $validated['node_type'],
            $validated['node_id']
        ));
    }

    /**
     * Get orphan nodes
     */
    public function orphanNodes()
    {
        return response()->json([
            'orphan_customers' => $this->topologyService->getOrphanCustomers(),
            'orphan_odps' => $this->topologyService->getOrphanOdps(),
            'orphan_htbs' => $this->topologyService->getOrphanHtbs(),
        ]);
    }

    /**
     * Get capacity utilization
     */
    public function capacityUtilization(Request $request)
    {
        $validated = $request->validate([
            'node_type' => 'required|in:olt,olt_port,odc,odp,htb',
            'node_id' => 'required|integer',
        ]);

        return response()->json($this->topologyService->getCapacityUtilization(
            $validated['node_type'],
            $validated['node_id']
        ));
    }

    /**
     * Capacity dashboard
     */
    public function capacityDashboard()
    {
        return response()->json($this->capacityService->getCapacityDashboard());
    }

    /**
     * Get OLT capacity
     */
    public function oltCapacity(OLT $olt)
    {
        return response()->json($this->capacityService->getOltCapacity($olt));
    }

    /**
     * Get all OLT capacity
     */
    public function allOltCapacity()
    {
        return response()->json($this->capacityService->getAllOltCapacity());
    }

    /**
     * Get ODC capacity
     */
    public function odcCapacity(Odc $odc)
    {
        return response()->json($this->capacityService->getOdcCapacity($odc));
    }

    /**
     * Get all ODC capacity
     */
    public function allOdcCapacity()
    {
        return response()->json($this->capacityService->getAllOdcCapacity());
    }

    /**
     * Get ODP capacity
     */
    public function odpCapacity(Odp $odp)
    {
        return response()->json($this->capacityService->getOdpCapacity($odp));
    }

    /**
     * Get HTB capacity
     */
    public function htbCapacity(Htb $htb)
    {
        return response()->json($this->capacityService->getHtbCapacity($htb));
    }

    /**
     * Get Closure capacity
     */
    public function closureCapacity(Closure $closure)
    {
        return response()->json($this->capacityService->getClosureCapacity($closure));
    }

    /**
     * Get warning nodes
     */
    public function warningNodes()
    {
        return response()->json($this->capacityService->getWarningNodes());
    }

    /**
     * Get critical nodes
     */
    public function criticalNodes()
    {
        return response()->json($this->capacityService->getCriticalNodes());
    }

    /**
     * Optical monitoring dashboard
     */
    public function opticalDashboard()
    {
        return response()->json($this->opticalMonitoringService->getOpticalDashboard());
    }

    /**
     * Get optical status for all ONTs
     */
    public function allOntOpticalStatus()
    {
        return response()->json($this->opticalMonitoringService->getAllOntOpticalStatus());
    }

    /**
     * Get optical status for an ONT
     */
    public function ontOpticalStatus(ONT $ont)
    {
        return response()->json($this->opticalMonitoringService->getOntOpticalStatus($ont));
    }

    /**
     * Get optical history for an ONT
     */
    public function ontOpticalHistory(Request $request, ONT $ont)
    {
        $days = $request->input('days', 7);
        return response()->json($this->opticalMonitoringService->getOntOpticalHistory($ont, (int)$days));
    }

    /**
     * Get ONTs with warning status
     */
    public function warningOnts()
    {
        return response()->json($this->opticalMonitoringService->getWarningOnts());
    }

    /**
     * Get ONTs with critical status
     */
    public function criticalOnts()
    {
        return response()->json($this->opticalMonitoringService->getCriticalOnts());
    }

    /**
     * Calculate fiber plan materials
     */
    public function calculateFiberMaterials(Request $request)
    {
        $validated = $request->validate([
            'length_meters' => 'required|numeric|min:1',
            'custom_materials' => 'nullable|array',
        ]);

        $materials = $this->fiberPlannerService->calculateMaterials(
            $validated['length_meters'],
            $validated['custom_materials'] ?? []
        );

        $totalCost = $this->fiberPlannerService->calculateTotalCost($materials);

        return response()->json([
            'materials' => $materials,
            'total_cost' => $totalCost,
        ]);
    }

    /**
     * Create a fiber plan
     */
    public function createFiberPlan(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'region_id' => 'nullable|integer|exists:regions,id',
            'olt_id' => 'nullable|integer|exists:olts,id',
            'path' => 'nullable|array',
            'length_meters' => 'required|numeric|min:1',
            'status' => 'nullable|in:draft,active,archived',
            'materials' => 'nullable|array',
            'materials.*.item_name' => 'required|string|max:255',
            'materials.*.quantity' => 'required|numeric|min:0',
            'materials.*.unit_price' => 'nullable|numeric|min:0',
            'materials.*.total_price' => 'nullable|numeric|min:0',
        ]);

        $plan = $this->fiberPlannerService->createPlan($validated);

        return response()->json([
            'success' => true,
            'plan' => $plan->load('items'),
        ]);
    }

    /**
     * Get all fiber plans
     */
    public function listFiberPlans()
    {
        $plans = FiberPlan::with(['region', 'olt', 'items'])->latest()->paginate(20);
        return response()->json($plans);
    }

    /**
     * Get a single fiber plan
     */
    public function showFiberPlan(FiberPlan $plan)
    {
        $plan->load(['region', 'olt', 'items.inventoryItem']);
        return response()->json($plan);
    }

    /**
     * Generate BOQ for a fiber plan
     */
    public function generateBoq(FiberPlan $plan)
    {
        $boq = $this->fiberPlannerService->generateBoq($plan);
        return response()->json($boq);
    }

    /**
     * GIS Analytics Dashboard
     */
    public function gisDashboard()
    {
        return response()->json($this->gisAnalyticsService->getGisDashboard());
    }

    /**
     * Customer Density
     */
    public function customerDensity()
    {
        return response()->json($this->gisAnalyticsService->getCustomerDensity());
    }

    /**
     * Fiber Coverage
     */
    public function fiberCoverage()
    {
        return response()->json($this->gisAnalyticsService->getFiberCoverage());
    }

    /**
     * Capacity Heatmap
     */
    public function capacityHeatmap()
    {
        return response()->json($this->gisAnalyticsService->getCapacityHeatmap());
    }

    /**
     * Revenue per Region
     */
    public function revenuePerRegion()
    {
        return response()->json($this->gisAnalyticsService->getRevenuePerRegion());
    }

    /**
     * Growth Analytics
     */
    public function growthAnalytics(Request $request)
    {
        $months = $request->input('months', 12);
        return response()->json($this->gisAnalyticsService->getGrowthAnalytics((int)$months));
    }
}
