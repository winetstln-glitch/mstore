<?php

use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CustomerPublicRegisterController;
use App\Http\Controllers\CustomerWebController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GenieACSController;
use App\Http\Controllers\GenieAcsServerController;
use App\Http\Controllers\HotspotController;
use App\Http\Controllers\PppoeController;
use App\Http\Controllers\InstallationWebController;
use App\Http\Controllers\InvestorController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ModemDataController;
use App\Http\Controllers\NetworkAnalyzerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OLTController;
use App\Http\Controllers\OnuController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TechnicianAttendanceController;
use App\Http\Controllers\TicketWebController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\VpnServerController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ConsolidationController;
use App\Models\Router as RouterModel;
use App\Models\VpnAccount;
use App\Services\VpnBridgeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Locale Switcher
Route::get('locale/{lang}', function ($lang) {
    if (in_array($lang, ['en', 'id'])) {
        session()->put('locale', $lang);
    }

    return redirect()->back();
})->name('locale.switch');

// DEBUG: Sync Missing Permissions
if (app()->environment('local')) {
    Route::get('/debug-sync-permissions', function () {
        echo "<h1>🔄 Syncing Missing Permissions</h1>";
        
        $permissions = [
            // Car Wash - Full List
            ['name' => 'wash.view', 'label' => 'View Wash Dashboard', 'group' => 'Car Wash'],
            ['name' => 'wash.pos', 'label' => 'Access Wash POS', 'group' => 'Car Wash'],
            ['name' => 'wash.manage', 'label' => 'Manage Wash Services', 'group' => 'Car Wash'],
            ['name' => 'wash.report', 'label' => 'View Wash Reports', 'group' => 'Car Wash'],
            ['name' => 'wash.member.view', 'label' => 'View Wash Members', 'group' => 'Car Wash'],
            ['name' => 'wash.member.manage', 'label' => 'Manage Wash Members', 'group' => 'Car Wash'],
            ['name' => 'wash.loyalty.view', 'label' => 'View Wash Loyalty', 'group' => 'Car Wash'],
            ['name' => 'wash.loyalty.manage', 'label' => 'Manage Wash Loyalty', 'group' => 'Car Wash'],
            ['name' => 'wash.reward.view', 'label' => 'View Wash Reward Vouchers', 'group' => 'Car Wash'],
            ['name' => 'wash.reward.manage', 'label' => 'Manage Wash Reward Vouchers', 'group' => 'Car Wash'],
            ['name' => 'wash.transaction.view', 'label' => 'View Wash Transactions', 'group' => 'Car Wash'],
            ['name' => 'wash.transaction.create', 'label' => 'Create Wash Transactions', 'group' => 'Car Wash'],
            ['name' => 'wash.transaction.update', 'label' => 'Update Wash Transactions', 'group' => 'Car Wash'],
            ['name' => 'wash.transaction.delete', 'label' => 'Delete Wash Transactions', 'group' => 'Car Wash'],
            ['name' => 'wash.expense.view', 'label' => 'View Wash Expenses', 'group' => 'Car Wash'],
            ['name' => 'wash.expense.create', 'label' => 'Create Wash Expenses', 'group' => 'Car Wash'],
            ['name' => 'wash.expense.update', 'label' => 'Update Wash Expenses', 'group' => 'Car Wash'],
            ['name' => 'wash.expense.delete', 'label' => 'Delete Wash Expenses', 'group' => 'Car Wash'],
            ['name' => 'wash.expense.approve', 'label' => 'Approve Wash Expenses', 'group' => 'Car Wash'],
            ['name' => 'wash.shift.view', 'label' => 'View Wash Shifts', 'group' => 'Car Wash'],
            ['name' => 'wash.shift.open', 'label' => 'Open Wash Shift', 'group' => 'Car Wash'],
            ['name' => 'wash.shift.close', 'label' => 'Close Wash Shift', 'group' => 'Car Wash'],
            ['name' => 'wash.shift.manage', 'label' => 'Manage Wash Shifts', 'group' => 'Car Wash'],
            ['name' => 'wash.cash.view', 'label' => 'View Wash Cash Registers', 'group' => 'Car Wash'],
            ['name' => 'wash.cash.manage', 'label' => 'Manage Wash Cash Registers', 'group' => 'Car Wash'],
            ['name' => 'wash.closing.view', 'label' => 'View Wash Daily Closings', 'group' => 'Car Wash'],
            ['name' => 'wash.closing.create', 'label' => 'Create Wash Daily Closing', 'group' => 'Car Wash'],
            ['name' => 'wash.closing.approve', 'label' => 'Approve Wash Daily Closing', 'group' => 'Car Wash'],
            ['name' => 'wash.supplier.view', 'label' => 'View Wash Suppliers', 'group' => 'Car Wash'],
            ['name' => 'wash.supplier.manage', 'label' => 'Manage Wash Suppliers', 'group' => 'Car Wash'],
            ['name' => 'wash.stock.view', 'label' => 'View Wash Stock', 'group' => 'Car Wash'],
            ['name' => 'wash.stock.manage', 'label' => 'Manage Wash Stock', 'group' => 'Car Wash'],
            
            // Wedding & Event
            ['name' => 'wedding.view', 'label' => 'View Wedding & Event', 'group' => 'Wedding & Event'],
            ['name' => 'wedding.manage', 'label' => 'Manage Wedding Packages', 'group' => 'Wedding & Event'],
            ['name' => 'wedding.booking', 'label' => 'Manage Wedding Bookings', 'group' => 'Wedding & Event'],
            ['name' => 'wedding.payment', 'label' => 'Manage Wedding Payments', 'group' => 'Wedding & Event'],
            ['name' => 'wedding.report', 'label' => 'View Wedding Reports', 'group' => 'Wedding & Event'],
            
            // CCTV Installation
            ['name' => 'cctv.view', 'label' => 'View CCTV Installation', 'group' => 'CCTV Installation'],
            ['name' => 'cctv.manage', 'label' => 'Manage CCTV Packages', 'group' => 'CCTV Installation'],
            ['name' => 'cctv.booking', 'label' => 'Manage CCTV Bookings', 'group' => 'CCTV Installation'],
            ['name' => 'cctv.payment', 'label' => 'Manage CCTV Payments', 'group' => 'CCTV Installation'],
            ['name' => 'cctv.report', 'label' => 'View CCTV Reports', 'group' => 'CCTV Installation'],
            
            // Company & Consolidation
            ['name' => 'company.view', 'label' => 'View Companies', 'group' => 'Company & Consolidation'],
            ['name' => 'company.manage', 'label' => 'Manage Companies', 'group' => 'Company & Consolidation'],
            ['name' => 'consolidation.view', 'label' => 'View Consolidation Reports', 'group' => 'Company & Consolidation'],
        ];

        $inserted = 0;
        $updated = 0;
        echo "<ul>";
        foreach ($permissions as $p) {
            $existing = \App\Models\Permission::where('name', $p['name'])->first();
            if (!$existing) {
                \App\Models\Permission::create($p);
                $inserted++;
                echo "<li style='color: green;'>✅ Inserted: " . htmlspecialchars($p['name']) . " (" . htmlspecialchars($p['group']) . "</li>";
            } else {
                $existing->update(['label' => $p['label'], 'group' => $p['group']]);
                $updated++;
            }
        }
        echo "</ul>";

        echo "<h2>Results</h2>";
        echo "<p>Inserted: <strong>" . $inserted . "</strong> permissions</p>";
        echo "<p>Updated: <strong>" . $updated . "</strong> permissions</p>";
        echo "<p>Total in DB: <strong>" . \App\Models\Permission::count() . "</strong></p>";
        echo "<p><a href='/roles' style='font-size: 18px; color: blue;'>➡️ Go to Role Management</a></p>";
    });
}

// Voucher Payment Routes (Public)
Route::get('/voucher-payment', [\App\Http\Controllers\VoucherPaymentController::class, 'index'])->name('voucher.payment.index');
Route::post('/voucher-payment/select-payment', [\App\Http\Controllers\VoucherPaymentController::class, 'selectPaymentMethod'])->name('voucher.payment.select_payment');
Route::post('/voucher-payment/create', [\App\Http\Controllers\VoucherPaymentController::class, 'createPayment'])->name('voucher.payment.create');
Route::post('/voucher-payment/callback', [\App\Http\Controllers\VoucherPaymentController::class, 'callback'])->name('voucher.payment.callback');
Route::get('/voucher-payment/return', [\App\Http\Controllers\VoucherPaymentController::class, 'return'])->name('voucher.payment.return');
Route::get('/voucher-payment/{referenceId}', [\App\Http\Controllers\VoucherPaymentController::class, 'show'])->name('voucher.payment.show');
Route::get('/voucher-payment/{referenceId}/check-status', [\App\Http\Controllers\VoucherPaymentController::class, 'checkStatus'])->name('voucher.payment.check_status');

// Debug Route for Duitku Credentials (development only)
if (app()->environment('local')) {
Route::get('/debug-duitku', function () {
    echo "<h1>ðŸ” Duitku Debug</h1>";
    
    $merchantCode = \App\Models\Setting::getValue('duitku_merchant_code', config('services.duitku.merchant_code'));
    $apiKey = \App\Models\Setting::getValue('duitku_api_key', config('services.duitku.api_key'));
    $sandbox = \App\Models\Setting::getValue('duitku_sandbox', config('services.duitku.sandbox', true));
    
    echo "<h2>1. Credentials:</h2>";
    $maskedMerchant = is_string($merchantCode) && strlen($merchantCode) > 4
        ? substr($merchantCode, 0, 2) . str_repeat('*', max(0, strlen($merchantCode) - 4)) . substr($merchantCode, -2)
        : '****';
    $maskedApiKey = is_string($apiKey) && strlen($apiKey) > 8
        ? substr($apiKey, 0, 4) . '****' . substr($apiKey, -2)
        : '****';
    echo "<p><strong>Merchant Code:</strong> " . htmlspecialchars($maskedMerchant) . "</p>";
    echo "<p><strong>API Key:</strong> " . htmlspecialchars($maskedApiKey) . "</p>";
    echo "<p><strong>Sandbox Mode:</strong> " . ($sandbox ? "ON" : "OFF") . "</p>";
    
    echo "<h2>2. Config Class Parameters (swap check):</h2>";
    try {
        $config1 = new \Duitku\Config($apiKey, $merchantCode);
        $config1->setSandboxMode($sandbox);
        $config1->setDuitkuLogs(false); // Disable logs to avoid permission errors!
        $config1->setSanitizedMode(false);
        echo "<p><strong>Config 1 (correct order - apiKey first):</strong><br>";
        echo "- getMerchantCode(): " . htmlspecialchars($config1->getMerchantCode()) . "<br>";
        echo "- getApiKey(): " . htmlspecialchars(substr($config1->getApiKey(), 0, 8) . "****") . "</p>";
        
        // Test getPaymentMethod via our Service
        echo "<h2>3. Test getPaymentMethod via Service:</h2>";
        try {
            $paymentManager = app(\App\Services\Payment\PaymentManager::class);
            $duitku = $paymentManager->gateway('duitku');
            $result = $duitku->getPaymentMethods();
            if (isset($result['success']) && !$result['success']) {
                echo "<p style='color: red; font-weight: bold;'>âŒ ERROR: " . htmlspecialchars($result['message']) . "</p>";
            } else {
                echo "<p style='color: green; font-weight: bold;'>âœ… SUCCESS!</p>";
                echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . "</pre>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red; font-weight: bold;'>âŒ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red; font-weight: bold;'>âŒ Config Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<hr><p>Generated at: " . now() . "</p>";
    echo "<p><a href='/voucher-payment'>Test Voucher Payment</a></p>";
});
}

// Test Route for Duitku (Hanya untuk development!)
if (app()->environment('local')) {
    Route::get('/test-duitku', function () {
        // Cek apakah sudah ada template voucher
        $template = \App\Models\VoucherTemplate::first();
        if (! $template) {
            return 'Silakan buat template voucher terlebih dahulu di halaman voucher!';
        }

        // Buat test payment
        $referenceId = 'TEST-' . time();
        $payment = \App\Models\VoucherPayment::create([
            'reference_id' => $referenceId,
            'voucher_template_id' => $template->id,
            'amount' => 10000,
            'phone_number' => '6281234567890', // Ganti dengan nomor WhatsApp Anda untuk testing
            'customer_name' => 'Test Customer',
            'status' => 'pending',
        ]);

        // Hitung signature (mirip Duitku)
        $merchantCode = \App\Models\Setting::getValue('duitku_merchant_code', config('services.duitku.merchant_code'));
        $apiKey = \App\Models\Setting::getValue('duitku_api_key', config('services.duitku.api_key'));
        $signature = md5($merchantCode . $referenceId . '00' . $apiKey);

        // Simulasikan callback success
        $callbackData = [
            'merchantCode' => $merchantCode,
            'merchantOrderId' => $referenceId,
            'statusCode' => '00',
            'statusMessage' => 'Success',
            'amount' => '10000',
            'paymentCode' => 'QRIS',
            'reference' => 'D1234567890',
            'signature' => $signature,
        ];

        // Panggil method callback secara manual
        $controller = new \App\Http\Controllers\VoucherPaymentController(
            app(\App\Services\Payment\PaymentManager::class),
            app(\App\Services\VoucherService::class),
            app(\App\Services\WhatsAppService::class)
        );

        $request = new \Illuminate\Http\Request($callbackData);
        $response = $controller->callback($request);

        return response()->json([
            'message' => 'Test callback berhasil dijalankan!',
            'payment' => $payment->refresh(),
            'response' => $response->getContent(),
        ]);
    });
}

Route::get('/test', function () {
    return view('test');
});

Route::get('/test-livewire', function () {
    return \Livewire\Livewire::mount('isp.pppoe-user.index');
});
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/internet', [LandingController::class, 'showService'])
    ->defaults('service', 'internet')
    ->name('landing.services.internet');
Route::get('/wedding-event', [LandingController::class, 'showService'])
    ->defaults('service', 'wedding-event')
    ->name('landing.services.wedding');
Route::get('/cctv', [LandingController::class, 'showService'])
    ->defaults('service', 'cctv')
    ->name('landing.services.cctv');
Route::get('/gt-wash', [LandingController::class, 'showService'])
    ->defaults('service', 'gt-wash')
    ->name('landing.services.wash');
Route::get('/atk-store', [LandingController::class, 'showService'])
    ->defaults('service', 'atk-store')
    ->name('landing.services.atk');
Route::post('/leads', [LandingController::class, 'storeLead'])
    ->middleware('throttle:20,1')
    ->name('landing.leads.store');

Route::get('/customers/register', [CustomerPublicRegisterController::class, 'create'])->name('customers.public.register.create');
Route::post('/customers/register', [CustomerPublicRegisterController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('customers.public.register.store');

Route::post('/ai-public/chat', [\App\Http\Controllers\AiController::class, 'publicChat'])
    ->middleware('throttle:30,1')
    ->name('ai.public.chat');

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
Route::get('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'showForgotForm'])->name('password.forgot');
Route::post('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'sendOtp'])
    ->middleware('throttle:5,1')
    ->name('password.send_otp');
Route::get('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'reset'])
    ->middleware('throttle:5,1')
    ->name('password.reset');

Route::middleware('auth')->group(function () {
        // PPPoE Users Routes
        Route::prefix('isp/pppoe-users')->name('isp.pppoe-users.')->group(function () {
            Route::get('/', \App\Livewire\ISP\PPPoEUser\Index::class)->name('index');
            Route::get('/create', \App\Livewire\ISP\PPPoEUser\Create::class)->name('create');
            Route::get('/{customer}/edit', \App\Livewire\ISP\PPPoEUser\Edit::class)->name('edit');
            Route::get('/{customer}', \App\Livewire\ISP\PPPoEUser\Show::class)->name('show');
        });

        Route::get('admin/dashboard', [\App\Http\Controllers\AdminDashboardController::class, 'index'])
            ->middleware('permission:admin.dashboard.view')
            ->name('admin.dashboard');
        Route::get('admin/audit-trail', [\App\Http\Controllers\AdminDashboardController::class, 'auditTrail'])
            ->middleware('permission:dashboard.view')
            ->name('admin.audit-trail');
    
    Route::get('modem-data', [ModemDataController::class, 'index'])->name('modem-data.index')->middleware('permission:modem-data.view');
    Route::post('modem-data', [ModemDataController::class, 'store'])->name('modem-data.store')->middleware('permission:modem-data.create|modem-data.view');
    
    // AI Center
    Route::get('/ai-center', [\App\Http\Controllers\AiController::class, 'index'])
        ->middleware('permission:ai.view')
        ->name('ai.index');
    Route::post('/ai-center/chat', [\App\Http\Controllers\AiController::class, 'chat'])
        ->middleware('permission:ai.view')
        ->middleware('throttle:30,1')
        ->name('ai.chat');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');
    Route::post('/presence/ping', [PresenceController::class, 'ping'])->name('presence.ping');
    Route::get('/dashboard/monitor-logs', [DashboardController::class, 'monitorLogs'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard.monitor_logs');
    Route::get('/health/network', function (\Modules\Network\Services\MonitoringService $monitoring) {
        return response()->json($monitoring->health());
    })->name('health.network');
    Route::get('/health/mixradius', function (\Modules\Network\Services\MonitoringService $monitoring) {
        return response()->json($monitoring->health());
    })->name('health.mixradius');

    Route::get('/noc/dashboard', [\App\Http\Controllers\NocDashboardController::class, 'index'])
        ->middleware('permission:noc.dashboard.view')
        ->name('noc.dashboard');
    Route::get('/noc/dashboard/data', [\App\Http\Controllers\NocDashboardController::class, 'data'])
        ->middleware('permission:noc.dashboard.view')
        ->name('noc.dashboard.data');
    Route::prefix('noc/operasional')->name('noc.operational.')->group(function () {
        Route::get('/area-outage', [\App\Http\Controllers\NocOperationalController::class, 'areaOutage'])
            ->middleware('permission:noc.operational.view')
            ->name('area_outage');
        Route::get('/network-incident', [\App\Http\Controllers\NocOperationalController::class, 'incidents'])
            ->middleware('permission:noc.operational.view')
            ->name('network_incident');
        Route::get('/network-diagnostic', [\App\Http\Controllers\NocOperationalController::class, 'diagnostics'])
            ->middleware('permission:noc.operational.view')
            ->name('network_diagnostic');
        Route::get('/diagnostic-logs', [\App\Http\Controllers\NocOperationalController::class, 'diagnosticLogs'])
            ->middleware('permission:noc.diagnostic_logs.view')
            ->name('diagnostic_logs');
        Route::get('/olt-monitoring', [\App\Http\Controllers\NocOperationalController::class, 'oltMonitoring'])
            ->middleware('permission:noc.olt_monitoring.view')
            ->name('olt_monitoring');
        Route::get('/fiber-monitoring', [\App\Http\Controllers\NocOperationalController::class, 'fiberMonitoring'])
            ->middleware('permission:noc.fiber_monitoring.view')
            ->name('fiber_monitoring');
    });

    Route::get('/whatsapp/analytics', [\App\Http\Controllers\WhatsAppAnalyticsController::class, 'index'])
        ->middleware('permission:whatsapp.analytics.view')
        ->name('whatsapp.analytics');
    Route::get('/whatsapp/analytics/data', [\App\Http\Controllers\WhatsAppAnalyticsController::class, 'data'])
        ->middleware('permission:whatsapp.analytics.view')
        ->name('whatsapp.analytics.data');

    Route::prefix('whatsapp/ai-knowledge-base')->name('whatsapp.kb.')->group(function () {
        Route::get('/', [\App\Http\Controllers\KnowledgeBaseAdminController::class, 'index'])
            ->middleware('permission:whatsapp.kb.manage')
            ->name('index');
        Route::get('/create', [\App\Http\Controllers\KnowledgeBaseAdminController::class, 'create'])
            ->middleware('permission:whatsapp.kb.manage')
            ->name('create');
        Route::post('/', [\App\Http\Controllers\KnowledgeBaseAdminController::class, 'store'])
            ->middleware('permission:whatsapp.kb.manage')
            ->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\KnowledgeBaseAdminController::class, 'edit'])
            ->middleware('permission:whatsapp.kb.manage')
            ->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\KnowledgeBaseAdminController::class, 'update'])
            ->middleware('permission:whatsapp.kb.manage')
            ->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\KnowledgeBaseAdminController::class, 'destroy'])
            ->middleware('permission:whatsapp.kb.manage')
            ->name('destroy');
    });

    Route::get('/tickets/sla-monitoring', [\App\Http\Controllers\SlaMonitoringController::class, 'index'])
        ->middleware('permission:sla.monitoring.view')
        ->name('sla.monitoring');
    Route::get('/tickets/sla-monitoring/data', [\App\Http\Controllers\SlaMonitoringController::class, 'data'])
        ->middleware('permission:sla.monitoring.view')
        ->name('sla.monitoring.data');
    Route::get('/tickets/escalation-queue', [\App\Http\Controllers\EscalationQueueController::class, 'index'])
        ->middleware('permission:sla.escalation.view')
        ->name('sla.escalation-queue');

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/noc', [\App\Http\Controllers\ReportingCenterController::class, 'noc'])
            ->middleware('permission:report.noc.export')
            ->name('noc');
        Route::get('/noc/pdf', [\App\Http\Controllers\ReportingCenterController::class, 'nocPdf'])
            ->middleware('permission:report.noc.export')
            ->name('noc.pdf');
        Route::get('/noc/excel', [\App\Http\Controllers\ReportingCenterController::class, 'nocExcel'])
            ->middleware('permission:report.noc.export')
            ->name('noc.excel');

        Route::get('/whatsapp', [\App\Http\Controllers\ReportingCenterController::class, 'whatsapp'])
            ->middleware('permission:report.whatsapp.export')
            ->name('whatsapp');
        Route::get('/whatsapp/pdf', [\App\Http\Controllers\ReportingCenterController::class, 'whatsappPdf'])
            ->middleware('permission:report.whatsapp.export')
            ->name('whatsapp.pdf');
        Route::get('/whatsapp/excel', [\App\Http\Controllers\ReportingCenterController::class, 'whatsappExcel'])
            ->middleware('permission:report.whatsapp.export')
            ->name('whatsapp.excel');

        Route::get('/sla', [\App\Http\Controllers\ReportingCenterController::class, 'sla'])
            ->middleware('permission:report.sla.export')
            ->name('sla');
        Route::get('/sla/pdf', [\App\Http\Controllers\ReportingCenterController::class, 'slaPdf'])
            ->middleware('permission:report.sla.export')
            ->name('sla.pdf');
        Route::get('/sla/excel', [\App\Http\Controllers\ReportingCenterController::class, 'slaExcel'])
            ->middleware('permission:report.sla.export')
            ->name('sla.excel');

        Route::get('/wedding', [\App\Http\Controllers\ReportingCenterController::class, 'wedding'])
            ->middleware('permission:report.wedding.export')
            ->name('wedding');
        Route::get('/wedding/pdf', [\App\Http\Controllers\ReportingCenterController::class, 'weddingPdf'])
            ->middleware('permission:report.wedding.export')
            ->name('wedding.pdf');
        Route::get('/wedding/excel', [\App\Http\Controllers\ReportingCenterController::class, 'weddingExcel'])
            ->middleware('permission:report.wedding.export')
            ->name('wedding.excel');

        Route::get('/cctv', [\App\Http\Controllers\ReportingCenterController::class, 'cctv'])
            ->middleware('permission:report.cctv.export')
            ->name('cctv');
        Route::get('/cctv/pdf', [\App\Http\Controllers\ReportingCenterController::class, 'cctvPdf'])
            ->middleware('permission:report.cctv.export')
            ->name('cctv.pdf');
        Route::get('/cctv/excel', [\App\Http\Controllers\ReportingCenterController::class, 'cctvExcel'])
            ->middleware('permission:report.cctv.export')
            ->name('cctv.excel');
    });

    Route::get('/security/monitoring', [\App\Http\Controllers\SecurityMonitoringController::class, 'index'])
        ->middleware('permission:security.monitoring.view')
        ->name('security.monitoring');

    // Client Portal
    Route::prefix('client')->name('client.')->group(function () {
        Route::get('/dashboard', \App\Http\Controllers\Client\DashboardController::class)->name('dashboard');
        Route::get('/portal', [\App\Http\Controllers\Client\MixradiusPortalController::class, 'index'])->name('portal');
        Route::get('/mixradius', function () {
            $url = \App\Models\Setting::getValue('mixradius_base_url', env('MIXRADIUS_BASE_URL', ''));
            abort_if(empty($url), 404);
            // Normalize url to base (no trailing slash)
            $url = rtrim((string) $url, '/');

            return view('client.mixradius_embed', ['mixradiusUrl' => $url]);
        })->name('mixradius');
        Route::get('/connection', [\App\Http\Controllers\Client\ConnectionController::class, 'index'])->name('connection');
        Route::get('/invoices', [\App\Http\Controllers\Client\InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [\App\Http\Controllers\Client\InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/invoices/{invoice}/pay', [\App\Http\Controllers\Client\InvoiceController::class, 'pay'])->name('invoices.pay');
        Route::get('/credentials', [\App\Http\Controllers\Client\CredentialsController::class, 'show'])->name('credentials.show');
        Route::post('/credentials', [\App\Http\Controllers\Client\CredentialsController::class, 'update'])->name('credentials.update');
    });

    // WhatsApp Webhook
    Route::get('/webhooks/whatsapp', [\App\Http\Controllers\WhatsApp\WhatsAppWebhookController::class, 'verify'])->name('webhooks.whatsapp.verify');
    Route::post('/webhooks/whatsapp', [\App\Http\Controllers\WhatsApp\WhatsAppWebhookController::class, 'handle'])
        ->middleware(\App\Http\Middleware\VerifyWhatsAppWebhook::class)
        ->name('webhooks.whatsapp.handle');

    // Payment Webhooks
Route::post('/webhooks/midtrans', [\App\Http\Controllers\WebhookController::class, 'midtrans'])->name('webhooks.midtrans');
Route::post('/webhooks/payment', [\App\Http\Controllers\PaymentController::class, 'callback'])->name('webhooks.payment.callback');
Route::get('/webhooks/payment/return', [\App\Http\Controllers\PaymentController::class, 'return'])->name('webhooks.payment.return');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    Route::get('/profile/id-card', [ProfileController::class, 'idCard'])->name('profile.id_card');
    Route::get('/profile/id-card/download', [ProfileController::class, 'idCardDownload'])->name('profile.id_card.download');

    Route::get('notifications/{notification}', [NotificationController::class, 'redirect'])->name('notifications.redirect');
    Route::post('notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');

    // Role Management
    Route::resource('roles', RoleController::class);

    // User Management
    Route::get('users/export', [UserController::class, 'export'])->name('users.export');
    Route::get('users/{user}/id-card', [UserController::class, 'idCard'])->name('users.id-card');
    Route::post('users/{user}/send-whatsapp-account', [UserController::class, 'sendWhatsAppAccount'])->name('users.send-whatsapp-account');
    Route::resource('users', UserController::class);

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::get('/settings/attendance', [SettingController::class, 'attendance'])->name('settings.attendance.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/settings/backup', [SettingController::class, 'backupDatabase'])->name('settings.backup');
    Route::get('/settings/atk', [SettingController::class, 'atk'])->name('settings.atk.index');
    Route::post('/settings/atk', [SettingController::class, 'update'])->name('settings.atk.update');
    Route::get('/settings/wash', [SettingController::class, 'wash'])->name('settings.wash.index');
    Route::post('/settings/wash', [SettingController::class, 'update'])->name('settings.wash.update');

    // Payment Gateway Management
    Route::middleware(['permission:payment.view'])->prefix('payment-gateway')->group(function () {
        Route::get('/', [\App\Http\Controllers\PaymentGatewayController::class, 'dashboard'])->name('payment.dashboard');
        Route::get('/{gateway}', [\App\Http\Controllers\PaymentGatewayController::class, 'gateway'])->name('payment.gateway');
        Route::post('/{gateway}/update', [\App\Http\Controllers\PaymentGatewayController::class, 'update'])
            ->middleware('permission:payment.edit')
            ->name('payment.gateway.update');
        Route::post('/{gateway}/test', [\App\Http\Controllers\PaymentGatewayController::class, 'testConnection'])
            ->middleware('permission:payment.test')
            ->name('payment.gateway.test');
    });

    // API Keys Management
    Route::get('settings/apikeys', [\App\Http\Controllers\ApiKeyController::class, 'index'])->name('apikeys.index');
    Route::post('settings/apikeys', [\App\Http\Controllers\ApiKeyController::class, 'store'])->name('apikeys.store');
    Route::delete('settings/apikeys/{apiKey}', [\App\Http\Controllers\ApiKeyController::class, 'destroy'])->name('apikeys.destroy');
    Route::post('settings/apikeys/{apiKey}/toggle', [\App\Http\Controllers\ApiKeyController::class, 'toggle'])->name('apikeys.toggle');

    Route::get('customers/export', [CustomerWebController::class, 'export'])->name('customers.export');
    Route::post('customers/import-file', [CustomerWebController::class, 'importFile'])->name('customers.importFile');
    Route::get('customers/import', [CustomerWebController::class, 'import'])->name('customers.import');
    Route::get('customers/genie-device', [CustomerWebController::class, 'getGenieDevice'])->name('customers.genie_device');
    Route::get('customers/{customer}/settings', [CustomerWebController::class, 'settings'])->name('customers.settings');
    Route::post('customers/{customer}/notify-status', [CustomerWebController::class, 'notifyStatus'])->name('customers.notify_status');
    Route::post('customers/{customer}/settings/wan', [CustomerWebController::class, 'updateWan'])->name('customers.settings.wan');
    Route::post('customers/{customer}/settings/wlan', [CustomerWebController::class, 'updateWlan'])->name('customers.settings.wlan');
    Route::delete('customers/bulk-destroy', [CustomerWebController::class, 'bulkDestroy'])->name('customers.bulkDestroy');
    Route::resource('companies', CompanyController::class);
    Route::get('consolidation', [ConsolidationController::class, 'index'])->name('consolidation.index');
    Route::post('consolidation/generate', [ConsolidationController::class, 'generate'])->name('consolidation.generate');
    Route::resource('customers', CustomerWebController::class);

    Route::put('tickets/{ticket}/complete', [TicketWebController::class, 'complete'])->name('tickets.complete');
    Route::get('tickets/{ticket}/sop-pdf', [TicketWebController::class, 'sopPdf'])->name('tickets.sop.pdf');
    Route::post('tickets/{ticket}/notify', [TicketWebController::class, 'sendNotification'])->name('tickets.notify');
    Route::patch('tickets/{ticket}/location', [TicketWebController::class, 'updateLocation'])->name('tickets.updateLocation');
    Route::patch('tickets/{ticket}/customer', [TicketWebController::class, 'updateCustomer'])->name('tickets.updateCustomer');
    Route::resource('tickets', TicketWebController::class);

    Route::resource('installations', InstallationWebController::class);
    
    // Technician Attendance & Kasbon (PLACE BEFORE CATCH-ALL)
    Route::get('kasbon', [\App\Http\Controllers\SalaryAdjustmentController::class, 'index'])->name('technicians.kasbon.index');
    Route::post('salary-adjustments', [\App\Http\Controllers\SalaryAdjustmentController::class, 'store'])->name('salary-adjustments.store');
    Route::put('salary-adjustments/{salaryAdjustment}', [\App\Http\Controllers\SalaryAdjustmentController::class, 'update'])->name('salary-adjustments.update');
    Route::delete('salary-adjustments/{salaryAdjustment}', [\App\Http\Controllers\SalaryAdjustmentController::class, 'destroy'])->name('salary-adjustments.destroy');
    
    // Kasbon Loans
    Route::post('kasbon-loans', [\App\Http\Controllers\KasbonLoanController::class, 'store'])->name('kasbon-loans.store');
    Route::put('kasbon-loans/{kasbonLoan}', [\App\Http\Controllers\KasbonLoanController::class, 'update'])->name('kasbon-loans.update');
    Route::delete('kasbon-loans/{kasbonLoan}', [\App\Http\Controllers\KasbonLoanController::class, 'destroy'])->name('kasbon-loans.destroy');
    Route::post('kasbon-loans/{kasbonLoan}/installments', [\App\Http\Controllers\KasbonLoanController::class, 'storeInstallment'])->name('kasbon-loans.installments.store');
    Route::delete('kasbon-loans/{kasbonLoan}/installments/{installment}', [\App\Http\Controllers\KasbonLoanController::class, 'destroyInstallment'])->name('kasbon-loans.installments.destroy');
    
    Route::permanentRedirect('technicians', 'employees');
    Route::any('technicians/{any}', fn () => redirect()->route('employees.index', [], 301))
        ->where('any', '.*');
    Route::resource('attendance', TechnicianAttendanceController::class)->only(['index', 'create', 'destroy']);
    Route::post('attendance', [TechnicianAttendanceController::class, 'store'])->name('attendance.store')->middleware('throttle:10,1');
    Route::put('attendance/{attendance}', [TechnicianAttendanceController::class, 'update'])->name('attendance.update')->middleware('throttle:10,1');
    Route::get('attendance/daily', [TechnicianAttendanceController::class, 'daily'])->name('attendance.daily');
    Route::get('attendance/payslip', [TechnicianAttendanceController::class, 'payslip'])->name('attendance.payslip');
    Route::get('attendance/excel', [TechnicianAttendanceController::class, 'exportExcel'])->name('attendance.excel');
    Route::post('attendance/recap-finance', [TechnicianAttendanceController::class, 'recapToFinance'])->name('attendance.recap_finance');
    Route::post('attendance/manual', [TechnicianAttendanceController::class, 'storeManual'])->name('attendance.storeManual')->middleware('throttle:5,1');
    Route::put('attendance/manual/{attendance}', [TechnicianAttendanceController::class, 'updateManual'])->name('attendance.updateManual')->middleware('throttle:5,1');
    Route::delete('attendance/bulk-destroy', [TechnicianAttendanceController::class, 'bulkDestroy'])->name('attendance.bulkDestroy');
    Route::post('attendance/{attendance}/notify', [TechnicianAttendanceController::class, 'sendNotification'])->name('attendance.notify');
    Route::get('attendance/kiosk', [TechnicianAttendanceController::class, 'kiosk'])->name('attendance.kiosk');
    Route::post('attendance/kiosk/scan', [TechnicianAttendanceController::class, 'kioskScan'])->name('attendance.kiosk.scan')->middleware('throttle:30,1');
    Route::post('landing/attendance/clock-in', [TechnicianAttendanceController::class, 'store'])->name('landing.attendance.store')->middleware('throttle:10,1');
    Route::put('landing/attendance/{attendance}/clock-out', [TechnicianAttendanceController::class, 'update'])->name('landing.attendance.update')->middleware('throttle:10,1');

    // Schedules & Leaves
    Route::post('schedules/period', [\App\Http\Controllers\TechnicianScheduleController::class, 'updatePeriod'])->name('schedules.updatePeriod');
    Route::post('schedules/auto-generate', [\App\Http\Controllers\TechnicianScheduleController::class, 'autoGenerate'])->name('schedules.autoGenerate');
    Route::post('schedules/bulk', [\App\Http\Controllers\TechnicianScheduleController::class, 'bulkStore'])->name('schedules.bulkStore');
    Route::post('schedules/daily', [\App\Http\Controllers\TechnicianScheduleController::class, 'dailyStore'])->name('schedules.daily.store');
    Route::post('schedules/daily/bulk', [\App\Http\Controllers\TechnicianScheduleController::class, 'dailyBulkStore'])->name('schedules.daily.bulkStore');
    Route::post('schedules/daily/auto-generate', [\App\Http\Controllers\TechnicianScheduleController::class, 'dailyAutoGenerate'])->name('schedules.daily.autoGenerate');
    Route::post('schedules/import/excel', [\App\Http\Controllers\TechnicianScheduleController::class, 'importExcel'])->name('schedules.import.excel');
    Route::get('schedules/export/pdf', [\App\Http\Controllers\TechnicianScheduleController::class, 'exportPdf'])->name('schedules.export.pdf');
    Route::get('schedules/export/excel', [\App\Http\Controllers\TechnicianScheduleController::class, 'exportExcel'])->name('schedules.export.excel');
    Route::resource('schedules', \App\Http\Controllers\TechnicianScheduleController::class)->only(['index', 'store', 'destroy']);
    Route::get('employee/leave-requests', [\App\Http\Controllers\LeaveRequestController::class, 'employee'])->name('employee.leave-requests');
    Route::get('admin/leave-requests', [\App\Http\Controllers\LeaveRequestController::class, 'admin'])->name('admin.leave-requests');
    Route::get('leave-requests/{leaveRequest}/edit', [\App\Http\Controllers\LeaveRequestController::class, 'edit'])->name('leave-requests.edit');
    Route::put('leave-requests/{leaveRequest}/update-request', [\App\Http\Controllers\LeaveRequestController::class, 'updateRequest'])->name('leave-requests.update-request');
    Route::resource('leave-requests', \App\Http\Controllers\LeaveRequestController::class)->except(['create', 'show', 'edit', 'destroy', 'index']);

    // Network & Infrastructure
   Route::prefix('olt')->name('olt.')->group(function () {

    // ===== CRUD =====
    Route::get('/', [OLTController::class, 'index'])->middleware('permission:olt.view')->name('index');
    Route::get('/create', [OLTController::class, 'create'])->name('create');
    Route::post('/', [OLTController::class, 'store'])->name('store');
    Route::post('/{olt}/poll', [OltController::class, 'poll'])->name('poll');
    Route::get('/{olt}', [OLTController::class, 'show'])->name('show');
    Route::get('/{olt}/edit', [OLTController::class, 'edit'])->name('edit');
    Route::put('/{olt}', [OLTController::class, 'update'])->name('update');
    Route::delete('/{olt}', [OLTController::class, 'destroy'])->name('destroy');

    // ===== Status & Connection =====
    Route::post('/batch-check-status', [OLTController::class, 'batchCheckStatus'])->name('batch_check_status');
    Route::post('/test-connection', [OLTController::class, 'testConnection'])->name('test_connection');
    Route::get('/{olt}/check-status', [OLTController::class, 'checkStatus'])->name('check_status');
    Route::get('/{olt}/system-info', [OLTController::class, 'systemInfo'])->name('system_info');
    Route::post('/{olt}/poll', [OLTController::class, 'poll'])->name('poll');

    // ===== ONU Operations =====
    Route::get('/{olt}/onus', [OLTController::class, 'filterOnus'])->name('onts');
    Route::get('/{olt}/onus/filter', [OLTController::class, 'filterOnus'])->name('onus.filter');
    Route::post('/{olt}/onus/sync', [OLTController::class, 'syncOnus'])->name('onus.sync');
    Route::put('/onu/{onu}', [OLTController::class, 'updateOnu'])->name('onu.update');
    Route::delete('/onu/{onu}', [OLTController::class, 'destroyOnu'])->name('onu.destroy');
    Route::get('/onu/{onu}/detail', function($onu) {
        $ont = \App\Models\ONT::with(['olt', 'port'])->findOrFail($onu);
        return view('olt.onus.show', compact('ont'));
    })->name('onu.detail');
    Route::get('/ont/{onu}', function($onu) {
        $ont = \App\Models\ONT::findOrFail($onu);
        return redirect()->route('olt.show', $ont->olt_id);
    })->name('ont.detail');
    Route::post('/ont/{onu}/reboot', function($onu) {
        return response()->json(['success' => true, 'message' => 'Reboot ONT not implemented yet']);
    })->name('ont.reboot');
});
    // ... kode router lainnya ...
    Route::post('routers/{router}/pppoe/disconnect', [RouterController::class, 'disconnectPppoe'])->name('routers.pppoe.disconnect');

    // Route untuk test koneksi router (Explicitly defined)
    Route::post('routers/{router}/test-connection', [RouterController::class, 'testConnection'])->name('routers.test-connection');

    Route::post('routers/{router}/pppoe/toggle-secret', [RouterController::class, 'togglePppoeSecret'])->name('routers.pppoe.toggle-secret');

    // Route for sessions view
    Route::get('routers/{router}/sessions', [RouterController::class, 'sessions'])->name('routers.sessions');

    Route::post('routers/{router}/hotspot/disconnect', [RouterController::class, 'disconnectHotspot'])->name('routers.hotspot.disconnect');

    // Simple Queue Management Routes
    Route::post('routers/{router}/simple-queues', [RouterController::class, 'createSimpleQueue'])->name('routers.simple-queues.store');
    Route::put('routers/{router}/simple-queues', [RouterController::class, 'updateSimpleQueue'])->name('routers.simple-queues.update');
    Route::delete('routers/{router}/simple-queues', [RouterController::class, 'deleteSimpleQueue'])->name('routers.simple-queues.destroy');
    Route::post('routers/{router}/simple-queues/toggle', [RouterController::class, 'toggleSimpleQueue'])->name('routers.simple-queues.toggle');
    Route::post('routers/{router}/simple-queues/move', [RouterController::class, 'moveSimpleQueue'])->name('routers.simple-queues.move');
    Route::get('hotspot/online', [RouterController::class, 'sessions'])->name('hotspot.online');
    Route::get('hotspot', [HotspotController::class, 'index'])->name('hotspot.index');

    // Hotspot Profiles (Voucher & Rumahan packages)
    Route::prefix('hotspot')->name('hotspot.')->group(function () {
        Route::resource('profiles', \App\Http\Controllers\HotspotProfileController::class)->except(['show']);
    });

    // Wash Member Packages (Member subscriptions)
    Route::prefix('wash')->name('wash.')->group(function () {
        Route::resource('member-packages', \App\Http\Controllers\WashMemberPackageController::class)->except(['show']);
    });

    Route::get('pppoe', [PppoeController::class, 'index'])->middleware('permission:pppoe.view')->name('pppoe.index');
    Route::post('pppoe/{router}/disconnect', [PppoeController::class, 'disconnect'])->name('pppoe.disconnect');
    Route::resource('routers', RouterController::class);

    Route::prefix('vpn')->name('vpn.')->group(function () {
        Route::resource('servers', VpnServerController::class)->except(['show']);
    });
    Route::view('/vpn/guide', 'vpn.guide')->middleware('permission:router.view')->name('vpn.guide');

    Route::get('/routers/{router}/vpn/script', function (RouterModel $router) {
        abort_unless($router->vpn_account_id, 404);
        $account = VpnAccount::findOrFail($router->vpn_account_id);
        $protocol = request('protocol', 'l2tp');
        $service = app(VpnBridgeService::class);
        $script = $service->generateScript($account, $protocol);

        return response()->view('routers.vpn_script', compact('router', 'account', 'script', 'protocol'));
    })->name('routers.vpn.script');

    // Business & Operations
    Route::get('finance/material-report', [FinanceController::class, 'materialReport'])->name('finance.material_report');
    Route::get('finance/export-accounting', [FinanceController::class, 'exportAccounting'])->name('finance.export_accounting');
    Route::get('finance/profit-loss', [FinanceController::class, 'profitLoss'])->name('finance.profit_loss');
    Route::get('finance/profit-loss/pdf', [FinanceController::class, 'downloadProfitLossPdf'])->name('finance.profit_loss.pdf');
    Route::get('finance/profit-loss/excel', [FinanceController::class, 'downloadProfitLossExcel'])->name('finance.profit_loss.excel');

    Route::get('finance/income-breakdown/pdf', [FinanceController::class, 'downloadIncomeBreakdownPdf'])->name('finance.income_breakdown.pdf');
    Route::get('finance/investor-share/pdf', [FinanceController::class, 'downloadInvestorSharePdf'])->name('finance.investor_share.pdf');
    Route::get('finance/investor-report', [FinanceController::class, 'investorReport'])->name('finance.investor_report');
    Route::get('finance/investor-report/pdf', [FinanceController::class, 'downloadInvestorReportPdf'])->name('finance.investor_report.pdf');

    Route::get('finance/manager-report', [FinanceController::class, 'managerReport'])->name('finance.manager_report');
    Route::get('finance/manager-report/pdf', [FinanceController::class, 'downloadManagerReportPdf'])->name('finance.manager_report.pdf');
    Route::get('finance/manager-report/excel', [FinanceController::class, 'downloadManagerReportExcel'])->name('finance.manager_report.excel');
    Route::get('finance/coordinator/{coordinator}', [FinanceController::class, 'coordinatorDetail'])->name('finance.coordinator.detail');
    Route::get('finance/coordinator/{coordinator}/pdf', [FinanceController::class, 'downloadCoordinatorPdf'])->name('finance.coordinator.pdf');
    Route::delete('finance/bulk-destroy', [FinanceController::class, 'bulkDestroy'])->name('finance.bulkDestroy');
    Route::resource('finance', FinanceController::class)->parameters(['finance' => 'transaction']);

    Route::put('map/location/{type}/{id}', [MapController::class, 'updateLocation'])
        ->middleware('permission:map.view')
        ->name('map.update_location');
    Route::put('map/path/{type}/{id}', [MapController::class, 'updatePath'])
        ->middleware('permission:map.view')
        ->name('map.update_path');
    Route::post('map/connections/save', [\App\Http\Controllers\MapConnectionController::class, 'save'])
        ->middleware('permission:map.view')
        ->name('map.connections.save');
    Route::get('map/connections', [\App\Http\Controllers\MapConnectionController::class, 'index'])
        ->middleware('permission:map.view')
        ->name('map.connections.index');
    Route::get('map/wlan-status/{customer}', [MapController::class, 'getCustomerWlanStatus'])
        ->middleware('permission:map.view')
        ->name('map.wlan_status');
    Route::post('map/wlan-update/{customer}', [MapController::class, 'updateCustomerWlan'])
        ->middleware('permission:map.view')
        ->name('map.wlan_update');
    Route::post('map/ping', [MapController::class, 'ping'])
        ->middleware('permission:map.view')
        ->name('map.ping');
    Route::get('dashboard/consolidated', [\App\Http\Controllers\ConsolidatedDashboardController::class, 'index'])
        ->middleware('permission:finance.view')
        ->name('dashboard.consolidated');
    Route::post('dashboard/consolidated/generate', [\App\Http\Controllers\ConsolidatedDashboardController::class, 'generateSummary'])
        ->middleware('permission:finance.view')
        ->name('dashboard.consolidated.generate');
    Route::resource('map', MapController::class);

    // Tools
    Route::get('/calculator/pon', [CalculatorController::class, 'index'])
        ->middleware('permission:calculator.view')
        ->name('calculator.pon');
    Route::get('/network/analyzer', [NetworkAnalyzerController::class, 'index'])
        ->middleware('permission:router.view')
        ->name('network.analyzer');
    Route::get('/network/analyzer/ping', [NetworkAnalyzerController::class, 'ping'])
        ->middleware('permission:router.view')
        ->name('network.analyzer.ping');
    Route::get('/network/analyzer/info', [NetworkAnalyzerController::class, 'networkInfo'])
        ->middleware('permission:router.view')
        ->name('network.analyzer.info');
    Route::get('/network/analyzer/speed/download', [NetworkAnalyzerController::class, 'speedDownload'])
        ->middleware('permission:router.view')
        ->name('network.analyzer.speed.download');
    Route::post('/network/analyzer/speed/upload', [NetworkAnalyzerController::class, 'speedUpload'])
        ->middleware('permission:router.view')
        ->name('network.analyzer.speed.upload');
    // [DEPRECATED] Ganti Paket Internet (HotspotProfile) — route packages lama dinonaktifkan
    // Route::resource('packages', \App\Http\Controllers\PackageController::class)->except(['show']);
    Route::get('odps/next-sequence/{odc}', [\App\Http\Controllers\OdpController::class, 'getNextSequence'])->name('odps.next_sequence');
    Route::get('odps/export/excel', [\App\Http\Controllers\OdpController::class, 'exportExcel'])->name('odps.export.excel');
    Route::resource('odps', \App\Http\Controllers\OdpController::class);
    Route::resource('htbs', \App\Http\Controllers\HtbController::class);
    Route::get('odcs/export/excel', [\App\Http\Controllers\OdcController::class, 'exportExcel'])->name('odcs.export.excel');
    Route::resource('odcs', \App\Http\Controllers\OdcController::class);
    Route::resource('closures', \App\Http\Controllers\ClosureController::class);
    Route::resource('regions', \App\Http\Controllers\RegionController::class);
    Route::resource('coordinators', \App\Http\Controllers\CoordinatorController::class);
    Route::get('employees-export/csv', [EmployeeController::class, 'exportCsv'])->name('employees.export.csv');
    Route::get('employees-export/pdf', [EmployeeController::class, 'exportPdf'])->name('employees.export.pdf');
    Route::get('employees-export/excel', [EmployeeController::class, 'exportExcel'])->name('employees.export.excel');
    Route::get('employees-print/id-cards', [EmployeeController::class, 'printCards'])->name('employees.print.cards');
    Route::post('employees-sync', [EmployeeController::class, 'syncExisting'])->name('employees.sync');
    Route::get('employees/{employee}/id-card', [EmployeeController::class, 'idCard'])->name('employees.id-card');
    Route::resource('employees', EmployeeController::class)->except(['show']);

    Route::get('/voucher/list', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::post('/voucher/generate', [VoucherController::class, 'generate'])->name('vouchers.generate');
    Route::post('/voucher/disconnect', [VoucherController::class, 'disconnect'])->name('vouchers.disconnect');
    // [DEPRECATED] Master Paket Voucher pindah ke HotspotProfile Paket Internet tab Voucher
    // Route::post('/voucher/template', [VoucherController::class, 'storeTemplate'])->name('vouchers.templates.store');
    // Route::get('/voucher/template/{voucherTemplate}/edit', [VoucherController::class, 'editTemplate'])->name('vouchers.templates.edit');
    // Route::put('/voucher/template/{voucherTemplate}', [VoucherController::class, 'updateTemplate'])->name('vouchers.templates.update');
    // Route::delete('/voucher/template/{voucherTemplate}', [VoucherController::class, 'deleteTemplate'])->name('vouchers.templates.delete');
    Route::get('/voucher/export/csv', [VoucherController::class, 'exportCsv'])->name('vouchers.export.csv');
    Route::get('/voucher/export/excel', [VoucherController::class, 'exportExcel'])->name('vouchers.export.excel');
    Route::get('/voucher/export/pdf', [VoucherController::class, 'exportPdf'])->name('vouchers.export.pdf');
    Route::get('investors/export/pdf', [InvestorController::class, 'exportPdf'])->name('investors.export.pdf');
    Route::get('investors/export/excel', [InvestorController::class, 'exportExcel'])->name('investors.export.excel');
    Route::resource('investors', InvestorController::class);
    Route::post('chat/start', [ChatController::class, 'start'])
        ->middleware('permission:chat.view')
        ->name('chat.start');
    Route::get('chat/{chat}/messages', [ChatController::class, 'messages'])
        ->middleware('permission:chat.view')
        ->name('chat.messages');
    Route::get('chat/{chat}/presence', [ChatController::class, 'presence'])
        ->middleware('permission:chat.view')
        ->name('chat.presence');
    Route::post('chat/{chat}/typing', [ChatController::class, 'typing'])
        ->middleware('permission:chat.view')
        ->name('chat.typing');
    Route::post('chat/{chat}/read', [ChatController::class, 'markRead'])
        ->middleware('permission:chat.view')
        ->name('chat.read');
    Route::get('chat/messages/{message}/download', [ChatController::class, 'downloadAttachment'])
        ->middleware('permission:chat.view')
        ->name('chat.attachments.download');
    Route::resource('chat', ChatController::class)
        ->middleware('permission:chat.view')
        ->only(['index', 'show', 'store']);

    // Telegram Settings
    Route::get('/telegram', [\App\Http\Controllers\TelegramController::class, 'index'])->name('telegram.index');
    Route::post('/telegram/update', [\App\Http\Controllers\TelegramController::class, 'update'])->name('telegram.update');
    Route::post('/telegram/test', [\App\Http\Controllers\TelegramController::class, 'test'])->name('telegram.test');
    Route::post('/telegram/test-ip-down', [\App\Http\Controllers\TelegramController::class, 'testIpDown'])->name('telegram.test_ip_down');
    Route::post('/telegram/test-ip-up', [\App\Http\Controllers\TelegramController::class, 'testIpUp'])->name('telegram.test_ip_up');
    Route::post('/telegram/preview-ip-down', [\App\Http\Controllers\TelegramController::class, 'previewIpDown'])->name('telegram.preview_ip_down');
    Route::post('/telegram/preview-ip-up', [\App\Http\Controllers\TelegramController::class, 'previewIpUp'])->name('telegram.preview_ip_up');

    // WhatsApp Settings
    Route::get('/whatsapp', [\App\Http\Controllers\WhatsAppController::class, 'index'])
        ->middleware('permission:chat.view')
        ->name('whatsapp.index');
    Route::get('/whatsapp/logs', [\App\Http\Controllers\WhatsAppController::class, 'logs'])
        ->middleware('permission:chat.view')
        ->name('whatsapp.logs');
    Route::post('/whatsapp/update', [\App\Http\Controllers\WhatsAppController::class, 'update'])
        ->middleware('permission:chat.manage')
        ->name('whatsapp.update');
    Route::post('/whatsapp/test', [\App\Http\Controllers\WhatsAppController::class, 'test'])
        ->middleware('permission:chat.manage')
        ->name('whatsapp.test');
    Route::post('/whatsapp/check-status', [\App\Http\Controllers\WhatsAppController::class, 'checkStatus'])
        ->middleware('permission:chat.manage')
        ->name('whatsapp.check-status');

    // WhatsApp Bot Builder
    Route::post('whatsapp-builder/import-templates', [\App\Http\Controllers\WhatsAppBotBuilderController::class, 'importTemplates'])
        ->middleware('permission:chat.manage')
        ->name('whatsapp.builder.import-templates');
    Route::resource('whatsapp-builder', \App\Http\Controllers\WhatsAppBotBuilderController::class)
        ->middleware('permission:chat.manage')
        ->names([
        'index' => 'whatsapp.builder.index',
        'create' => 'whatsapp.builder.create',
        'store' => 'whatsapp.builder.store',
        'edit' => 'whatsapp.builder.edit',
        'update' => 'whatsapp.builder.update',
        'destroy' => 'whatsapp.builder.destroy',
    ])->parameters(['whatsapp-builder' => 'menu']);

    Route::post('wash/transactions/{transaction}/whatsapp-receipt', [\App\Http\Controllers\WashTransactionController::class, 'whatsappReceipt'])
        ->middleware('permission:wash.report')
        ->name('wash.transactions.whatsapp_receipt');
    Route::post('atk/transactions/{transaction}/whatsapp-receipt', [\App\Http\Controllers\AtkTransactionController::class, 'whatsappReceipt'])->name('atk.transactions.whatsapp_receipt');

    // Inventory
    Route::get('/inventory/export/pdf', [\App\Http\Controllers\InventoryController::class, 'exportPdf'])->name('inventory.export.pdf');
    Route::get('/inventory/export/excel', [\App\Http\Controllers\InventoryController::class, 'exportExcel'])->name('inventory.export.excel');
    Route::get('/inventory/movements/export/excel', [\App\Http\Controllers\InventoryController::class, 'exportMovementExcel'])->name('inventory.movements.export.excel');
    Route::get('/inventory/import/template', [\App\Http\Controllers\InventoryController::class, 'downloadTemplate'])->name('inventory.import.template');
    Route::post('/inventory/import', [\App\Http\Controllers\InventoryController::class, 'importExcel'])->name('inventory.import');
    Route::get('/inventory', [\App\Http\Controllers\InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/item', [\App\Http\Controllers\InventoryController::class, 'storeItem'])->name('inventory.store');
    Route::post('/inventory/stock-in', [\App\Http\Controllers\InventoryController::class, 'storeStockIn'])->name('inventory.stock-in.store');
    Route::put('/inventory/item/{item}', [\App\Http\Controllers\InventoryController::class, 'updateItem'])->name('inventory.update');
    Route::delete('/inventory/item/{item}', [\App\Http\Controllers\InventoryController::class, 'destroyItem'])->name('inventory.destroy');
    Route::get('/inventory/pickup', [\App\Http\Controllers\InventoryController::class, 'createPickup'])->name('inventory.pickup');
    Route::post('/inventory/pickup', [\App\Http\Controllers\InventoryController::class, 'storePickup'])->name('inventory.store-pickup');
    Route::put('/inventory/pickup/{transaction}', [\App\Http\Controllers\InventoryController::class, 'updatePickup'])->name('inventory.pickup.update');
    Route::delete('/inventory/pickup/{transaction}', [\App\Http\Controllers\InventoryController::class, 'destroyPickup'])->name('inventory.pickup.destroy');

    // Inventory Assets
    Route::get('/my-assets', [\App\Http\Controllers\AssetController::class, 'myAssets'])->name('inventory.my_assets');
    Route::get('/inventory/item/{item}/assets', [\App\Http\Controllers\AssetController::class, 'index'])->name('inventory.assets.index');
    Route::post('/inventory/item/{item}/assets', [\App\Http\Controllers\AssetController::class, 'store'])->name('inventory.assets.store');
    Route::put('/inventory/assets/{asset}', [\App\Http\Controllers\AssetController::class, 'update'])->name('inventory.assets.update');
    Route::post('/inventory/assets/{asset}/return', [\App\Http\Controllers\AssetController::class, 'returnAsset'])->name('inventory.assets.return');
    Route::delete('/inventory/assets/{asset}', [\App\Http\Controllers\AssetController::class, 'destroy'])->name('inventory.assets.destroy');
    Route::get('/inventory/assets/{asset}/assign', [\App\Http\Controllers\AssetController::class, 'assign'])->name('inventory.assets.assign'); // GET for form
    Route::post('/inventory/assets/{asset}/assign', [\App\Http\Controllers\AssetController::class, 'processAssignment'])->name('inventory.assets.process_assignment'); // POST for submit
    Route::post('/inventory/assets/{asset}/return', [\App\Http\Controllers\AssetController::class, 'returnAsset'])->name('inventory.assets.return');

    Route::prefix('accounting')->name('accounting.')->middleware('permission:accounting.view')->group(function () {
        Route::get('/trial-balance', [\App\Http\Controllers\AccountingReportController::class, 'trialBalance'])->name('trial_balance');
        Route::get('/income-statement', [\App\Http\Controllers\AccountingReportController::class, 'incomeStatement'])->name('income_statement');
        Route::get('/balance-sheet', [\App\Http\Controllers\AccountingReportController::class, 'balanceSheet'])->name('balance_sheet');
        Route::get('/ledger', [\App\Http\Controllers\AccountingReportController::class, 'ledger'])->name('ledger');
        Route::get('/cash-flow', [\App\Http\Controllers\AccountingReportController::class, 'cashFlow'])->name('cash_flow');
        Route::get('/trial-balance/pdf', [\App\Http\Controllers\AccountingReportController::class, 'exportTrialBalancePdf'])->name('trial_balance.pdf');
        Route::get('/trial-balance/excel', [\App\Http\Controllers\AccountingReportController::class, 'exportTrialBalanceExcel'])->name('trial_balance.excel');
        Route::get('/income-statement/pdf', [\App\Http\Controllers\AccountingReportController::class, 'exportIncomeStatementPdf'])->name('income_statement.pdf');
        Route::get('/income-statement/excel', [\App\Http\Controllers\AccountingReportController::class, 'exportIncomeStatementExcel'])->name('income_statement.excel');
        Route::get('/balance-sheet/pdf', [\App\Http\Controllers\AccountingReportController::class, 'exportBalanceSheetPdf'])->name('balance_sheet.pdf');
        Route::get('/balance-sheet/excel', [\App\Http\Controllers\AccountingReportController::class, 'exportBalanceSheetExcel'])->name('balance_sheet.excel');
        Route::get('/ledger/pdf', [\App\Http\Controllers\AccountingReportController::class, 'exportLedgerPdf'])->name('ledger.pdf');
        Route::get('/ledger/excel', [\App\Http\Controllers\AccountingReportController::class, 'exportLedgerExcel'])->name('ledger.excel');
        Route::get('/cash-flow/pdf', [\App\Http\Controllers\AccountingReportController::class, 'exportCashFlowPdf'])->name('cash_flow.pdf');
        Route::get('/cash-flow/excel', [\App\Http\Controllers\AccountingReportController::class, 'exportCashFlowExcel'])->name('cash_flow.excel');

        // Period management
        Route::get('/periods', [\App\Http\Controllers\PeriodController::class, 'index'])->name('periods.index');
        Route::get('/periods/create', [\App\Http\Controllers\PeriodController::class, 'create'])->name('periods.create');
        Route::post('/periods', [\App\Http\Controllers\PeriodController::class, 'store'])->name('periods.store');
        Route::get('/periods/{period}/opening', [\App\Http\Controllers\PeriodController::class, 'openingForm'])->name('periods.opening');
        Route::post('/periods/{period}/opening', [\App\Http\Controllers\PeriodController::class, 'postOpening'])->name('periods.opening.post');
        Route::post('/periods/{period}/close', [\App\Http\Controllers\PeriodController::class, 'close'])->name('periods.close');
    });

    // GenieACS / Network Monitor Routes
    Route::prefix('genieacs')->name('genieacs.')->group(function () {
        // Server Management
        Route::resource('servers', GenieAcsServerController::class);

        Route::get('/', [GenieACSController::class, 'index'])->name('index');
        Route::get('/device/{id}', [GenieACSController::class, 'show'])->name('show'); // Changed param to avoid conflict if any, though {id} is safe
        Route::post('/assign-odp', [GenieACSController::class, 'assignOdp'])->name('assign_odp');
        Route::post('/device/{id}/refresh', [GenieACSController::class, 'refresh'])->name('refresh');
        Route::post('/device/{id}/reboot', [GenieACSController::class, 'reboot'])->name('reboot');
        Route::post('/device/{id}/ping', [GenieACSController::class, 'ping'])->name('ping');
        Route::post('/device/{id}/alias', [GenieACSController::class, 'updateAlias'])->name('updateAlias');
        Route::post('/device/{id}/wan', [GenieACSController::class, 'updateWan'])->name('updateWan');
        Route::post('/device/{id}/wlan', [GenieACSController::class, 'updateWlan'])->name('updateWlan');
        Route::post('/device/{id}/param', [GenieACSController::class, 'updateParam'])->name('updateParam');
    });

    // Wash Service Routes
    Route::prefix('wash')->name('wash.')->group(function () {
        Route::get('/guide', function () {
            return view('wash.guide');
        })->middleware('permission:wash.view')->name('guide');
        Route::get('/dashboard', [\App\Http\Controllers\WashTransactionController::class, 'dashboard'])
            ->middleware('permission:wash.view')
            ->name('dashboard');
        Route::get('/pos', [\App\Http\Controllers\WashTransactionController::class, 'pos'])
            ->middleware('permission:wash.pos')
            ->name('pos');
        Route::get('/members', [\App\Http\Controllers\WashMemberController::class, 'index'])
            ->middleware('permission:wash.member.view')
            ->name('members.index');
        Route::get('/members/levels', [\App\Http\Controllers\WashMemberController::class, 'levels'])
            ->middleware('permission:wash.member.view')
            ->name('members.levels');
        Route::get('/members/{member}', [\App\Http\Controllers\WashMemberController::class, 'show'])
            ->middleware('permission:wash.member.view')
            ->name('members.show');
        Route::put('/members/{member}', [\App\Http\Controllers\WashMemberController::class, 'update'])
            ->middleware('permission:wash.member.manage')
            ->name('members.update');
        Route::get('/members/{member}/card', [\App\Http\Controllers\WashMemberController::class, 'cardPdf'])
            ->middleware('permission:wash.member.view')
            ->name('members.card');
        Route::get('/loyalty', [\App\Http\Controllers\WashLoyaltyController::class, 'index'])
            ->middleware('permission:wash.loyalty.view')
            ->name('loyalty.index');
        Route::get('/loyalty/{counter}/edit', [\App\Http\Controllers\WashLoyaltyController::class, 'editCounter'])
            ->middleware('permission:wash.loyalty.manage')
            ->name('loyalty.edit');
        Route::put('/loyalty/{counter}', [\App\Http\Controllers\WashLoyaltyController::class, 'updateCounter'])
            ->middleware('permission:wash.loyalty.manage')
            ->name('loyalty.update');
        Route::get('/loyalty/vouchers', [\App\Http\Controllers\WashLoyaltyController::class, 'vouchers'])
            ->middleware('permission:wash.reward.view')
            ->name('loyalty.vouchers');
        Route::get('/loyalty/vouchers/{voucher}/edit', [\App\Http\Controllers\WashLoyaltyController::class, 'editVoucher'])
            ->middleware('permission:wash.reward.manage')
            ->name('loyalty.vouchers.edit');
        Route::put('/loyalty/vouchers/{voucher}', [\App\Http\Controllers\WashLoyaltyController::class, 'updateVoucher'])
            ->middleware('permission:wash.reward.manage')
            ->name('loyalty.vouchers.update');
        Route::get('/loyalty/redemptions', [\App\Http\Controllers\WashLoyaltyController::class, 'redemptions'])
            ->middleware('permission:wash.reward.view')
            ->name('loyalty.redemptions');
        Route::get('/loyalty/report', [\App\Http\Controllers\WashLoyaltyController::class, 'report'])
            ->middleware('permission:wash.reward.view')
            ->name('loyalty.report');
        Route::post('/transactions', [\App\Http\Controllers\WashTransactionController::class, 'store'])
            ->middleware('permission:wash.pos')
            ->name('transactions.store');
        Route::post('/transactions/{transaction}/loyalty/rollback', [\App\Http\Controllers\WashTransactionController::class, 'loyaltyRollback'])
            ->middleware('permission:wash.manage')
            ->name('transactions.loyalty.rollback');
        Route::post('/transactions/{transaction}/loyalty/manual-voucher', [\App\Http\Controllers\WashTransactionController::class, 'loyaltyManualVoucher'])
            ->middleware('permission:wash.manage')
            ->name('transactions.loyalty.manual_voucher');
        Route::post('/transactions/{transaction}/loyalty/retroactive', [\App\Http\Controllers\WashTransactionController::class, 'loyaltyRetroactive'])
            ->middleware('permission:wash.manage')
            ->name('transactions.loyalty.retroactive');
        Route::get('/transactions/{transaction}/receipt', [\App\Http\Controllers\WashTransactionController::class, 'receipt'])
            ->middleware('permission:wash.report')
            ->name('transactions.receipt');
        Route::get('/reports', [\App\Http\Controllers\WashReportController::class, 'index'])
            ->middleware('permission:wash.report')
            ->name('reports.index');
        Route::get('/reports/pdf', [\App\Http\Controllers\WashReportController::class, 'pdf'])->name('reports.pdf');
        Route::get('/reports/excel', [\App\Http\Controllers\WashReportController::class, 'excel'])->name('reports.excel');
        Route::get('/expenses', [\App\Http\Controllers\WashExpenseController::class, 'index'])
            ->middleware('permission:wash.report')
            ->name('expenses.index');
        Route::get('/expenses/create', [\App\Http\Controllers\WashExpenseController::class, 'create'])
            ->middleware('permission:wash.manage')
            ->name('expenses.create');
        Route::post('/expenses', [\App\Http\Controllers\WashExpenseController::class, 'store'])
            ->middleware('permission:wash.manage')
            ->name('expenses.store');
        Route::post('/expenses/stock-out', [\App\Http\Controllers\WashExpenseController::class, 'stockOut'])
            ->middleware('permission:wash.manage')
            ->name('expenses.stock_out');
        Route::get('/expenses/{expense}/edit', [\App\Http\Controllers\WashExpenseController::class, 'edit'])
            ->middleware('permission:wash.manage')
            ->name('expenses.edit');
        Route::put('/expenses/{expense}', [\App\Http\Controllers\WashExpenseController::class, 'update'])
            ->middleware('permission:wash.manage')
            ->name('expenses.update');
        Route::delete('/expenses/{expense}', [\App\Http\Controllers\WashExpenseController::class, 'destroy'])
            ->middleware('permission:wash.manage')
            ->name('expenses.destroy');
        Route::get('/customer/check', [\App\Http\Controllers\WashTransactionController::class, 'checkCustomer'])
            ->middleware('permission:wash.pos')
            ->name('customer.check');
        Route::get('/transactions/export/pdf', [\App\Http\Controllers\WashTransactionController::class, 'exportPdf'])
            ->middleware('permission:wash.report')
            ->name('transactions.export.pdf');
        Route::get('/transactions/export/excel', [\App\Http\Controllers\WashTransactionController::class, 'exportExcel'])
            ->middleware('permission:wash.report')
            ->name('transactions.export.excel');
        Route::delete('/transactions/bulk-destroy', [\App\Http\Controllers\WashTransactionController::class, 'bulkDestroy'])
            ->middleware('permission:wash.manage')
            ->name('transactions.bulkDestroy');
        Route::resource('transactions', \App\Http\Controllers\WashTransactionController::class)
            ->only(['index', 'show', 'update', 'destroy'])
            ->middleware('permission:wash.report');
    });
    // Wash Services (Auto Wash)
    Route::resource('wash/services', \App\Http\Controllers\WashController::class)
        ->middleware('permission:wash.manage')
        ->names([
        'index' => 'wash.services.index',
        'create' => 'wash.services.create',
        'store' => 'wash.services.store',
        'edit' => 'wash.services.edit',
        'update' => 'wash.services.update',
        'destroy' => 'wash.services.destroy',
    ])->except(['show']);
    
    // Wash Stock
    Route::prefix('wash/stock')->name('wash.stock.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WashStockController::class, 'index'])
            ->middleware('permission:wash.view')
            ->name('index');
        Route::get('/create', [\App\Http\Controllers\WashStockController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\WashStockController::class, 'store'])->name('store');
        Route::get('/{stockItem}', [\App\Http\Controllers\WashStockController::class, 'show'])->name('show');
        Route::get('/{stockItem}/edit', [\App\Http\Controllers\WashStockController::class, 'edit'])->name('edit');
        Route::put('/{stockItem}', [\App\Http\Controllers\WashStockController::class, 'update'])->name('update');
        Route::delete('/{stockItem}', [\App\Http\Controllers\WashStockController::class, 'destroy'])->name('destroy');
        Route::get('/{stockItem}/stock-in', [\App\Http\Controllers\WashStockController::class, 'stockIn'])->name('stock-in');
        Route::post('/{stockItem}/stock-in', [\App\Http\Controllers\WashStockController::class, 'storeStockIn'])->name('stock-in.store');
    });
    // Test Wash ERP
    Route::get('/test-wash', function () {
        return view('test-wash');
    });

    // Wash Suppliers
    Route::prefix('wash/suppliers')->name('wash.suppliers.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WashSupplierController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\WashSupplierController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\WashSupplierController::class, 'store'])->name('store');
        Route::get('/{supplier}', [\App\Http\Controllers\WashSupplierController::class, 'show'])->name('show');
        Route::get('/{supplier}/edit', [\App\Http\Controllers\WashSupplierController::class, 'edit'])->name('edit');
        Route::put('/{supplier}', [\App\Http\Controllers\WashSupplierController::class, 'update'])->name('update');
        Route::delete('/{supplier}', [\App\Http\Controllers\WashSupplierController::class, 'destroy'])->name('destroy');
    });

    // Wash Shifts
    Route::prefix('wash/shifts')->name('wash.shifts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WashShiftController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\WashShiftController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\WashShiftController::class, 'store'])->name('store');
        Route::get('/{shift}', [\App\Http\Controllers\WashShiftController::class, 'show'])->name('show');
        Route::get('/{shift}/edit', [\App\Http\Controllers\WashShiftController::class, 'edit'])->name('edit');
        Route::put('/{shift}', [\App\Http\Controllers\WashShiftController::class, 'update'])->name('update');
        Route::delete('/{shift}', [\App\Http\Controllers\WashShiftController::class, 'destroy'])->name('destroy');
    });

    // Wash Shift Sessions
    Route::prefix('wash/shift-sessions')->name('wash.shift-sessions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WashShiftSessionController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\WashShiftSessionController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\WashShiftSessionController::class, 'store'])->name('store');
        Route::get('/{session}', [\App\Http\Controllers\WashShiftSessionController::class, 'show'])->name('show');
        Route::get('/{session}/edit', [\App\Http\Controllers\WashShiftSessionController::class, 'edit'])->name('edit');
        Route::put('/{session}', [\App\Http\Controllers\WashShiftSessionController::class, 'update'])->name('update');
    });

    // Wash Cash Registers
    Route::prefix('wash/cash-registers')->name('wash.cash-registers.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WashCashRegisterController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\WashCashRegisterController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\WashCashRegisterController::class, 'store'])->name('store');
        Route::get('/{register}', [\App\Http\Controllers\WashCashRegisterController::class, 'show'])->name('show');
        Route::get('/{register}/edit', [\App\Http\Controllers\WashCashRegisterController::class, 'edit'])->name('edit');
        Route::put('/{register}', [\App\Http\Controllers\WashCashRegisterController::class, 'update'])->name('update');
        Route::delete('/{register}', [\App\Http\Controllers\WashCashRegisterController::class, 'destroy'])->name('destroy');
    });

    // Wash Cash Movements
    Route::prefix('wash/cash-movements')->name('wash.cash-movements.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WashCashMovementController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\WashCashMovementController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\WashCashMovementController::class, 'store'])->name('store');
        Route::get('/{movement}', [\App\Http\Controllers\WashCashMovementController::class, 'show'])->name('show');
    });

    // Wash Daily Closings
    Route::prefix('wash/daily-closings')->name('wash.daily-closings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WashDailyClosingController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\WashDailyClosingController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\WashDailyClosingController::class, 'store'])->name('store');
        Route::get('/{closing}', [\App\Http\Controllers\WashDailyClosingController::class, 'show'])->name('show');
        Route::post('/{closing}/approve', [\App\Http\Controllers\WashDailyClosingController::class, 'approve'])->name('approve');
    });

    Route::permanentRedirect('wash/employees', 'employees');
    Route::any('wash/employees/{any}', fn () => redirect()->route('employees.index', [], 301))
        ->where('any', '.*');

    // ATK Store Routes
    Route::prefix('atk')->name('atk.')->group(function () {
        // Debug route untuk melihat nilai variabel di Mutasi Kas Utama
        Route::get('/cash-movements/debug', function () {
            $request = request();
            $cash = \App\Models\Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);
            $baseQuery = \App\Models\AtkCashMovement::with(['creator', 'atkTransaction.items'])->whereNull('reversed_at');
            $query = (clone $baseQuery)->orderBy('created_at', 'asc');
            $startDate = $request->filled('start_date') ? $request->start_date : null;
            $endDate = $request->filled('end_date') ? $request->end_date : null;
            
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }
            if ($request->filled('movement_type')) {
                $query->where('movement_type', $request->movement_type);
            }
            if ($request->filled('created_by')) {
                $query->where('created_by', $request->created_by);
            }
            
            $movementsCollection = $query->get();
            $initialBalance = 0;
            if ($startDate) {
                $lastMovementBefore = (clone $baseQuery)
                    ->whereDate('created_at', '<', $startDate)
                    ->orderBy('created_at', 'desc')
                    ->first();
                $initialBalance = $lastMovementBefore ? $lastMovementBefore->balance_after : 0;
            } else {
                $totalIncomingInList = $movementsCollection->where('direction', 'in')->sum('amount');
                $totalOutgoingInList = $movementsCollection->where('direction', 'out')->sum('amount');
                $initialBalance = $cash->balance - $totalIncomingInList + $totalOutgoingInList;
            }
            
            return response()->json([
                'cash_balance' => $cash->balance,
                'total_in_list' => $totalIncomingInList ?? null,
                'total_out_list' => $totalOutgoingInList ?? null,
                'initial_balance' => $initialBalance,
                'movements_count' => $movementsCollection->count(),
                'first_movement' => $movementsCollection->first()?->toArray()
            ]);
        })->middleware('auth')->name('cash-movements.debug');
        
        Route::get('/guide', function () {
            return view('atk.guide');
        })->middleware('permission:atk.view')->name('guide');
        Route::get('/dashboard', [\App\Http\Controllers\AtkTransactionController::class, 'dashboard'])->name('dashboard');
        Route::get('/pos', [\App\Http\Controllers\AtkTransactionController::class, 'pos'])->name('pos');
        Route::post('/transactions', [\App\Http\Controllers\AtkTransactionController::class, 'store'])->name('transactions.store');
        Route::get('/reports', [\App\Http\Controllers\AtkReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/pdf', [\App\Http\Controllers\AtkReportController::class, 'pdf'])->name('reports.pdf');
        Route::get('/reports/excel', [\App\Http\Controllers\AtkReportController::class, 'excel'])->name('reports.excel');
        
        // Reports
        Route::get('/reports/cash', [\App\Http\Controllers\AtkCashReportController::class, 'index'])->name('reports.cash');
        Route::get('/reports/cash/pdf', [\App\Http\Controllers\AtkCashReportController::class, 'pdf'])->name('reports.cash.pdf');
        Route::get('/reports/cash/excel', [\App\Http\Controllers\AtkCashReportController::class, 'excel'])->name('reports.cash.excel');
        Route::get('/reports/float', [\App\Http\Controllers\AtkFloatReportController::class, 'index'])->name('reports.float');
        Route::get('/reports/float/pdf', [\App\Http\Controllers\AtkFloatReportController::class, 'pdf'])->name('reports.float.pdf');
        Route::get('/reports/float/excel', [\App\Http\Controllers\AtkFloatReportController::class, 'excel'])->name('reports.float.excel');
        Route::get('/reports/owner-funds', [\App\Http\Controllers\AtkOwnerFundReportController::class, 'index'])->name('reports.owner-funds');
        Route::get('/reports/owner-funds/pdf', [\App\Http\Controllers\AtkOwnerFundReportController::class, 'pdf'])->name('reports.owner-funds.pdf');
        Route::get('/reports/owner-funds/excel', [\App\Http\Controllers\AtkOwnerFundReportController::class, 'excel'])->name('reports.owner-funds.excel');
        
        Route::get('/expenses', [\App\Http\Controllers\AtkExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/expenses/create', [\App\Http\Controllers\AtkExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [\App\Http\Controllers\AtkExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/expenses/{expense}/edit', [\App\Http\Controllers\AtkExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('/expenses/{expense}', [\App\Http\Controllers\AtkExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [\App\Http\Controllers\AtkExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::get('/transactions/export/pdf', [\App\Http\Controllers\AtkTransactionController::class, 'exportPdf'])->name('transactions.export.pdf');
        Route::get('/transactions/export/excel', [\App\Http\Controllers\AtkTransactionController::class, 'exportExcel'])->name('transactions.export.excel');
        Route::get('/transactions/{transaction}/receipt', [\App\Http\Controllers\AtkTransactionController::class, 'receipt'])->name('transactions.receipt');
        Route::delete('/transactions/bulk-destroy', [\App\Http\Controllers\AtkTransactionController::class, 'bulkDestroy'])->name('transactions.bulkDestroy');
        Route::get('products/export', [\App\Http\Controllers\AtkProductController::class, 'export'])->name('products.export');
        Route::get('products/barcodes', [\App\Http\Controllers\AtkProductController::class, 'barcodes'])->name('products.barcodes');
        Route::get('products/barcodes/pdf', [\App\Http\Controllers\AtkProductController::class, 'barcodesPdf'])->name('products.barcodes.pdf');
        Route::post('products/import', [\App\Http\Controllers\AtkProductController::class, 'import'])->name('products.import');
        Route::delete('products/bulk-destroy', [\App\Http\Controllers\AtkProductController::class, 'bulkDestroy'])->name('products.bulk_destroy');
        Route::resource('products', \App\Http\Controllers\AtkProductController::class);
        Route::resource('transactions', \App\Http\Controllers\AtkTransactionController::class)->only(['index', 'show', 'destroy']);
        
        // Cash Register Routes
        Route::get('/cash-registers', [\App\Http\Controllers\AtkCashRegisterController::class, 'index'])->name('cash-registers.index');
        Route::get('/cash-registers/create', [\App\Http\Controllers\AtkCashRegisterController::class, 'create'])->name('cash-registers.create');
        Route::post('/cash-registers', [\App\Http\Controllers\AtkCashRegisterController::class, 'store'])->name('cash-registers.store');
        Route::get('/cash-registers/{register}/edit', [\App\Http\Controllers\AtkCashRegisterController::class, 'edit'])->name('cash-registers.edit');
        Route::put('/cash-registers/{register}', [\App\Http\Controllers\AtkCashRegisterController::class, 'update'])->name('cash-registers.update');
        Route::delete('/cash-registers/{register}', [\App\Http\Controllers\AtkCashRegisterController::class, 'destroy'])->name('cash-registers.destroy');
        Route::get('/cash-registers/{register}', [\App\Http\Controllers\AtkCashRegisterController::class, 'show'])->name('cash-registers.show');
        Route::post('/cash-registers/{register}/close', [\App\Http\Controllers\AtkCashRegisterController::class, 'close'])->name('cash-registers.close');
        
        // Cash Movements Routes
        Route::get('/cash-movements', [\App\Http\Controllers\AtkCashMovementController::class, 'index'])->name('cash-movements.index');
        Route::get('/cash-movements/create', [\App\Http\Controllers\AtkCashMovementController::class, 'create'])->name('cash-movements.create');
        Route::post('/cash-movements', [\App\Http\Controllers\AtkCashMovementController::class, 'store'])->name('cash-movements.store');
        
        // Float Account Routes
        Route::resource('float-accounts', \App\Http\Controllers\AtkFloatAccountController::class)->parameters(['float-accounts' => 'account']);
        Route::get('float-accounts/{account}/create-transaction', [\App\Http\Controllers\AtkFloatAccountController::class, 'createTransaction'])->name('float-accounts.create-transaction');
        Route::post('float-accounts/{account}/transaction', [\App\Http\Controllers\AtkFloatAccountController::class, 'storeTransaction'])->name('float-accounts.store-transaction');
        
        // Owner Fund Routes
        Route::resource('owner-funds', \App\Http\Controllers\AtkOwnerFundController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
        
        // Fee Management Routes
        Route::prefix('fee')->name('fee.')->group(function () {
            Route::get('/', [\App\Http\Controllers\FeeController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\FeeController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\FeeController::class, 'store'])->name('store');
            Route::get('/{id}', [\App\Http\Controllers\FeeController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\App\Http\Controllers\FeeController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\FeeController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\FeeController::class, 'destroy'])->name('destroy');
            Route::post('/calculate', [\App\Http\Controllers\FeeController::class, 'calculate'])->name('calculate');
        });
    });

    Route::prefix('wedding')->name('wedding.')->group(function () {
        Route::get('/dashboard', \App\Http\Controllers\WeddingDashboardController::class)
            ->middleware('permission:wedding.view')
            ->name('dashboard');

        Route::get('/packages', [\App\Http\Controllers\WeddingPackageController::class, 'index'])
            ->middleware('permission:wedding.view')
            ->name('packages.index');
        Route::get('/packages/create', [\App\Http\Controllers\WeddingPackageController::class, 'create'])
            ->middleware('permission:wedding.manage')
            ->name('packages.create');
        Route::post('/packages', [\App\Http\Controllers\WeddingPackageController::class, 'store'])
            ->middleware('permission:wedding.manage')
            ->name('packages.store');
        Route::get('/packages/{package}/edit', [\App\Http\Controllers\WeddingPackageController::class, 'edit'])
            ->middleware('permission:wedding.manage')
            ->name('packages.edit');
        Route::put('/packages/{package}', [\App\Http\Controllers\WeddingPackageController::class, 'update'])
            ->middleware('permission:wedding.manage')
            ->name('packages.update');
        Route::delete('/packages/{package}', [\App\Http\Controllers\WeddingPackageController::class, 'destroy'])
            ->middleware('permission:wedding.manage')
            ->name('packages.destroy');

        Route::get('/gallery', [\App\Http\Controllers\WeddingGalleryController::class, 'index'])
            ->middleware('permission:wedding.view')
            ->name('gallery.index');
        Route::get('/gallery/create', [\App\Http\Controllers\WeddingGalleryController::class, 'create'])
            ->middleware('permission:wedding.manage')
            ->name('gallery.create');
        Route::post('/gallery', [\App\Http\Controllers\WeddingGalleryController::class, 'store'])
            ->middleware('permission:wedding.manage')
            ->name('gallery.store');
        Route::get('/gallery/{item}/edit', [\App\Http\Controllers\WeddingGalleryController::class, 'edit'])
            ->middleware('permission:wedding.manage')
            ->name('gallery.edit');
        Route::put('/gallery/{item}', [\App\Http\Controllers\WeddingGalleryController::class, 'update'])
            ->middleware('permission:wedding.manage')
            ->name('gallery.update');
        Route::delete('/gallery/{item}', [\App\Http\Controllers\WeddingGalleryController::class, 'destroy'])
            ->middleware('permission:wedding.manage')
            ->name('gallery.destroy');

        Route::resource('bookings', \App\Http\Controllers\WeddingBookingController::class)
            ->middleware('permission:wedding.booking');

        Route::get('/schedule', [\App\Http\Controllers\WeddingScheduleController::class, 'index'])
            ->middleware('permission:wedding.view')
            ->name('schedule.index');

        Route::get('/payments', [\App\Http\Controllers\WeddingPaymentController::class, 'index'])
            ->middleware('permission:wedding.payment')
            ->name('payments.index');
        Route::get('/bookings/{booking}/payments/create', [\App\Http\Controllers\WeddingPaymentController::class, 'create'])
            ->middleware('permission:wedding.payment')
            ->name('payments.create');
        Route::post('/bookings/{booking}/payments', [\App\Http\Controllers\WeddingPaymentController::class, 'store'])
            ->middleware('permission:wedding.payment')
            ->name('payments.store');
        Route::get('/payments/{payment}', [\App\Http\Controllers\WeddingPaymentController::class, 'show'])
            ->middleware('permission:wedding.payment')
            ->name('payments.show');
    });

    Route::prefix('cctv')->name('cctv.')->group(function () {
        Route::get('/dashboard', \App\Http\Controllers\CctvDashboardController::class)
            ->middleware('permission:cctv.view')
            ->name('dashboard');

        Route::get('/packages', [\App\Http\Controllers\CctvPackageController::class, 'index'])
            ->middleware('permission:cctv.view')
            ->name('packages.index');
        Route::get('/packages/create', [\App\Http\Controllers\CctvPackageController::class, 'create'])
            ->middleware('permission:cctv.manage')
            ->name('packages.create');
        Route::post('/packages', [\App\Http\Controllers\CctvPackageController::class, 'store'])
            ->middleware('permission:cctv.manage')
            ->name('packages.store');
        Route::get('/packages/{package}/edit', [\App\Http\Controllers\CctvPackageController::class, 'edit'])
            ->middleware('permission:cctv.manage')
            ->name('packages.edit');
        Route::put('/packages/{package}', [\App\Http\Controllers\CctvPackageController::class, 'update'])
            ->middleware('permission:cctv.manage')
            ->name('packages.update');
        Route::delete('/packages/{package}', [\App\Http\Controllers\CctvPackageController::class, 'destroy'])
            ->middleware('permission:cctv.manage')
            ->name('packages.destroy');

        Route::resource('bookings', \App\Http\Controllers\CctvBookingController::class)
            ->middleware('permission:cctv.booking');

        Route::get('/schedule', [\App\Http\Controllers\CctvScheduleController::class, 'index'])
            ->middleware('permission:cctv.view')
            ->name('schedule.index');

        Route::get('/bookings/{booking}/surveys/create', [\App\Http\Controllers\CctvSurveyController::class, 'create'])
            ->middleware('permission:cctv.booking')
            ->name('surveys.create');
        Route::post('/bookings/{booking}/surveys', [\App\Http\Controllers\CctvSurveyController::class, 'store'])
            ->middleware('permission:cctv.booking')
            ->name('surveys.store');
        Route::get('/surveys/{survey}/edit', [\App\Http\Controllers\CctvSurveyController::class, 'edit'])
            ->middleware('permission:cctv.booking')
            ->name('surveys.edit');
        Route::put('/surveys/{survey}', [\App\Http\Controllers\CctvSurveyController::class, 'update'])
            ->middleware('permission:cctv.booking')
            ->name('surveys.update');

        Route::get('/bookings/{booking}/installations/create', [\App\Http\Controllers\CctvInstallationController::class, 'create'])
            ->middleware('permission:cctv.booking')
            ->name('installations.create');
        Route::post('/bookings/{booking}/installations', [\App\Http\Controllers\CctvInstallationController::class, 'store'])
            ->middleware('permission:cctv.booking')
            ->name('installations.store');
        Route::get('/installations/{installation}/edit', [\App\Http\Controllers\CctvInstallationController::class, 'edit'])
            ->middleware('permission:cctv.booking')
            ->name('installations.edit');
        Route::put('/installations/{installation}', [\App\Http\Controllers\CctvInstallationController::class, 'update'])
            ->middleware('permission:cctv.booking')
            ->name('installations.update');

        Route::get('/payments', [\App\Http\Controllers\CctvPaymentController::class, 'index'])
            ->middleware('permission:cctv.payment')
            ->name('payments.index');
        Route::get('/bookings/{booking}/payments/create', [\App\Http\Controllers\CctvPaymentController::class, 'create'])
            ->middleware('permission:cctv.payment')
            ->name('payments.create');
        Route::post('/bookings/{booking}/payments', [\App\Http\Controllers\CctvPaymentController::class, 'store'])
            ->middleware('permission:cctv.payment')
            ->name('payments.store');
        Route::get('/payments/{payment}', [\App\Http\Controllers\CctvPaymentController::class, 'show'])
            ->middleware('permission:cctv.payment')
            ->name('payments.show');
    });
});

Route::get('/wash/member/verify/{token}', [\App\Http\Controllers\WashMemberController::class, 'verify'])
    ->name('wash.members.verify');
