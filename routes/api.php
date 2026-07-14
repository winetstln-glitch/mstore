<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\HotspotPortalController;
use App\Http\Controllers\Api\InstallationController;
use App\Http\Controllers\Api\TechnicianController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\VpnController;
use App\Http\Controllers\Api\OLTController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/payment/create', [HotspotPortalController::class, 'createPayment'])->middleware('throttle:10,1');
Route::get('/payment/status', [HotspotPortalController::class, 'paymentStatus'])->middleware('throttle:30,1');
Route::get('/voucher/status', [HotspotPortalController::class, 'voucherStatus'])->middleware('throttle:30,1');
Route::get('/billing/monthly', [HotspotPortalController::class, 'billingMonthly'])->middleware('throttle:30,1');
Route::get('/products/ads', [HotspotPortalController::class, 'productAds'])->middleware('throttle:60,1');
Route::get('/hotspot/health', [HotspotPortalController::class, 'health'])->middleware('throttle:60,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Customers
    Route::apiResource('customers', CustomerController::class)->names('api.customers');

    // Tickets
    Route::apiResource('tickets', TicketController::class)->names('api.tickets');

    // Installations
    Route::apiResource('installations', InstallationController::class)->names('api.installations');

    // Technician Module
    Route::get('/technician/dashboard', [TechnicianController::class, 'dashboard']);
    Route::get('/technician/history', [TechnicianController::class, 'history']);

    // Network Map
    Route::get('/network/online-paths', [\App\Http\Controllers\MapController::class, 'onlinePaths'])->name('api.network.online-paths');
    Route::post('/network/node-detail', [\App\Http\Controllers\MapController::class, 'getNodeDetail'])->name('api.network.node-detail');

    // Module Manager
    Route::prefix('modules')->group(function () {
        Route::get('/', [\App\Http\Controllers\ModuleManagerController::class, 'index'])->name('api.modules.index');
        Route::get('/{alias}', [\App\Http\Controllers\ModuleManagerController::class, 'show'])->name('api.modules.show');
        Route::post('/{alias}/enable', [\App\Http\Controllers\ModuleManagerController::class, 'enable'])->name('api.modules.enable');
        Route::post('/{alias}/disable', [\App\Http\Controllers\ModuleManagerController::class, 'disable'])->name('api.modules.disable');
    });
});

// External Integration API (Protected by API Key in query param)
Route::get('/integration', [\App\Http\Controllers\Api\IntegrationController::class, 'handle'])->middleware('throttle:30,1');

Route::match(['GET', 'POST'], '/vpn/report-ip', [VpnController::class, 'reportIp'])
    ->middleware('throttle:60,1')
    ->name('api.vpn.report-ip');

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Dashboard
    Route::get('dashboard', [OLTController::class, 'dashboard']);
    
    // OLT Management
    Route::get('olts', [OLTController::class, 'index']);
    Route::post('olts', [OLTController::class, 'store']);
    Route::get('olts/{olt}', [OLTController::class, 'show']);
    Route::post('olts/{olt}/poll', [OLTController::class, 'poll']);
    Route::post('olts/connect-test', [OLTController::class, 'connectTest']);
    
    // ONTs
    Route::get('olts/{olt}/onts', [OLTController::class, 'ontList']);
    Route::get('olts/{olt}/onts/{ont}', [OLTController::class, 'ontShow']);
});

Route::prefix('v1/ai')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Phase 11: Enterprise Intelligence
    Route::post('/customer/analyze', [\App\Http\Controllers\AiController::class, 'analyzeCustomer']);
    Route::post('/root-cause', [\App\Http\Controllers\AiController::class, 'rootCauseAnalysis']);
    Route::get('/predictive-maintenance', [\App\Http\Controllers\AiController::class, 'predictiveMaintenance']);
    Route::get('/capacity-prediction', [\App\Http\Controllers\AiController::class, 'capacityPrediction']);
});

// WhatsApp Webhook
Route::prefix('whatsapp')->group(function () {
    Route::match(['GET', 'POST'], '/webhook', [\App\Http\Controllers\WhatsAppWebhookController::class, 'handle'])
        ->middleware('throttle:120,1')
        ->name('api.whatsapp.webhook');
});
