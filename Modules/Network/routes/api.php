<?php

use Illuminate\Support\Facades\Route;
use Modules\Network\Http\Controllers\NetworkController;

Route::prefix('v1')->group(function () {
    Route::get('health', [NetworkController::class, 'health'])->name('network.health');
});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('networks', NetworkController::class)->names('network');
    
    // Topology endpoints
    Route::get('topology/summary', [NetworkController::class, 'topologySummary'])->name('network.topology.summary');
    Route::get('topology/olt/{olt}', [NetworkController::class, 'oltTopology'])->name('network.topology.olt');
    Route::get('topology/odc/{odc}', [NetworkController::class, 'odcTopology'])->name('network.topology.odc');
    Route::get('topology/odp/{odp}', [NetworkController::class, 'odpTopology'])->name('network.topology.odp');
    Route::get('topology/htb/{htb}', [NetworkController::class, 'htbTopology'])->name('network.topology.htb');
    Route::get('topology/customer/{customer}', [NetworkController::class, 'customerTopology'])->name('network.topology.customer');
    Route::post('topology/affected-customers', [NetworkController::class, 'affectedCustomers'])->name('network.topology.affected-customers');
    Route::get('topology/orphans', [NetworkController::class, 'orphanNodes'])->name('network.topology.orphans');
    Route::post('topology/capacity', [NetworkController::class, 'capacityUtilization'])->name('network.topology.capacity');
    
    // Capacity management endpoints
    Route::get('capacity/dashboard', [NetworkController::class, 'capacityDashboard'])->name('network.capacity.dashboard');
    Route::get('capacity/olts', [NetworkController::class, 'allOltCapacity'])->name('network.capacity.olts');
    Route::get('capacity/olt/{olt}', [NetworkController::class, 'oltCapacity'])->name('network.capacity.olt');
    Route::get('capacity/odcs', [NetworkController::class, 'allOdcCapacity'])->name('network.capacity.odcs');
    Route::get('capacity/odc/{odc}', [NetworkController::class, 'odcCapacity'])->name('network.capacity.odc');
    Route::get('capacity/odp/{odp}', [NetworkController::class, 'odpCapacity'])->name('network.capacity.odp');
    Route::get('capacity/htb/{htb}', [NetworkController::class, 'htbCapacity'])->name('network.capacity.htb');
    Route::get('capacity/closure/{closure}', [NetworkController::class, 'closureCapacity'])->name('network.capacity.closure');
    Route::get('capacity/warnings', [NetworkController::class, 'warningNodes'])->name('network.capacity.warnings');
    Route::get('capacity/criticals', [NetworkController::class, 'criticalNodes'])->name('network.capacity.criticals');
    
    // Optical monitoring endpoints
    Route::get('optical/dashboard', [NetworkController::class, 'opticalDashboard'])->name('network.optical.dashboard');
    Route::get('optical/onts', [NetworkController::class, 'allOntOpticalStatus'])->name('network.optical.onts');
    Route::get('optical/ont/{ont}', [NetworkController::class, 'ontOpticalStatus'])->name('network.optical.ont');
    Route::get('optical/ont/{ont}/history', [NetworkController::class, 'ontOpticalHistory'])->name('network.optical.ont.history');
    Route::get('optical/warnings', [NetworkController::class, 'warningOnts'])->name('network.optical.warnings');
    Route::get('optical/criticals', [NetworkController::class, 'criticalOnts'])->name('network.optical.criticals');

    // Fiber planner endpoints
    Route::post('fiber-planner/calculate', [NetworkController::class, 'calculateFiberMaterials'])->name('network.fiber-planner.calculate');
    Route::post('fiber-planner/plans', [NetworkController::class, 'createFiberPlan'])->name('network.fiber-planner.create');
    Route::get('fiber-planner/plans', [NetworkController::class, 'listFiberPlans'])->name('network.fiber-planner.list');
    Route::get('fiber-planner/plans/{plan}', [NetworkController::class, 'showFiberPlan'])->name('network.fiber-planner.show');
    Route::get('fiber-planner/plans/{plan}/boq', [NetworkController::class, 'generateBoq'])->name('network.fiber-planner.boq');

    // GIS Analytics endpoints
    Route::get('gis/dashboard', [NetworkController::class, 'gisDashboard'])->name('network.gis.dashboard');
    Route::get('gis/customer-density', [NetworkController::class, 'customerDensity'])->name('network.gis.customer-density');
    Route::get('gis/fiber-coverage', [NetworkController::class, 'fiberCoverage'])->name('network.gis.fiber-coverage');
    Route::get('gis/capacity-heatmap', [NetworkController::class, 'capacityHeatmap'])->name('network.gis.capacity-heatmap');
    Route::get('gis/revenue-per-region', [NetworkController::class, 'revenuePerRegion'])->name('network.gis.revenue-per-region');
    Route::get('gis/growth-analytics', [NetworkController::class, 'growthAnalytics'])->name('network.gis.growth-analytics');
});
