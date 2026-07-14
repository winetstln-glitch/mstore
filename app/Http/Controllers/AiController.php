<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AiController extends Controller
{
    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index()
    {
        // Cache each data type separately with appropriate TTLs
        $data = [
            'restockSuggestions' => Cache::remember('ai_restock', 60, fn () => $this->aiService->getRestockSuggestions()),
            'salesForecast' => Cache::remember('ai_forecast', 60, fn () => $this->aiService->getSalesForecast()),
            'networkInsights' => Cache::remember('ai_network', 5, fn () => $this->aiService->getNetworkInsights()),
            'businessInsights' => Cache::remember('ai_business', 30, fn () => $this->aiService->getBusinessInsights()),
            'systemOverview' => Cache::remember('ai_system_overview', 15, fn () => $this->aiService->getSystemOverview()),
        ];

        return view('ai.index', $data);
    }

    /**
     * Handle AI Chat requests.
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:255',
        ]);

        $response = $this->aiService->processChat($request->input('message'));

        return response()->json([
            'response' => $response,
        ]);
    }

    public function publicChat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:255',
        ]);

        $reply = $this->aiService->processChat($request->input('message'));

        return response()->json([
            'reply' => $reply,
            'response' => $reply,
        ]);
    }

    /**
     * Phase 11.1: AI NOC - Analyze customer offline
     */
    public function analyzeCustomer(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer'
        ]);
        return response()->json($this->aiService->analyzeCustomerOffline($request->customer_id));
    }

    /**
     * Phase 11.2: Root Cause Analysis
     */
    public function rootCauseAnalysis(Request $request)
    {
        $request->validate([
            'node_type' => 'required|in:olt,odc,odp,htb',
            'node_id' => 'required|integer'
        ]);
        return response()->json($this->aiService->performRootCauseAnalysis($request->node_type, $request->node_id));
    }

    /**
     * Phase 11.3: Predictive Maintenance
     */
    public function predictiveMaintenance()
    {
        return response()->json($this->aiService->getPredictiveMaintenanceAlerts());
    }

    /**
     * Phase 11.4: Capacity Prediction
     */
    public function capacityPrediction()
    {
        return response()->json($this->aiService->getCapacityPredictions());
    }
}
