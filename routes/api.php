<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\HotspotPortalController;
use App\Http\Controllers\Api\InstallationController;
use App\Http\Controllers\Api\TechnicianController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\VpnController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/payment/create', [HotspotPortalController::class, 'createPayment']);
Route::get('/payment/status', [HotspotPortalController::class, 'paymentStatus']);
Route::get('/voucher/status', [HotspotPortalController::class, 'voucherStatus']);
Route::get('/billing/monthly', [HotspotPortalController::class, 'billingMonthly']);
Route::get('/products/ads', [HotspotPortalController::class, 'productAds']);
Route::get('/hotspot/health', [HotspotPortalController::class, 'health']);

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

    Route::get('/network/online-paths', [\App\Http\Controllers\MapController::class, 'onlinePaths'])->name('api.network.online-paths');
});

// External Integration API (Protected by API Key in query param)
Route::get('/integration', [\App\Http\Controllers\Api\IntegrationController::class, 'handle']);

Route::match(['GET', 'POST'], '/vpn/report-ip', [VpnController::class, 'reportIp'])->name('api.vpn.report-ip');
