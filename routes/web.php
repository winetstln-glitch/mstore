<?php

use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CustomerWebController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GenieACSController;
use App\Http\Controllers\GenieAcsServerController;
use App\Http\Controllers\HotspotController;
use App\Http\Controllers\InstallationWebController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\OLTController;
use App\Http\Controllers\OnuController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\TechnicianAttendanceController;
use App\Http\Controllers\TicketWebController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InvestorController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\VpnServerController;
use App\Models\Router as RouterModel;
use App\Models\VpnAccount;
use App\Services\VpnBridgeService;
use Illuminate\Support\Facades\Route;

// Locale Switcher
Route::get('locale/{lang}', function ($lang) {
    if (in_array($lang, ['en', 'id'])) {
        session()->put('locale', $lang);
    }
    return redirect()->back();
})->name('locale.switch');

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
Route::get('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'showForgotForm'])->name('password.forgot');
Route::post('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'sendOtp'])->name('password.send_otp');
Route::get('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'reset'])->name('password.reset');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/health/mixradius', function (\App\Services\MixRadiusService $mix) {
        return response()->json($mix->health());
    })->name('health.mixradius');
    
    // Client Portal
    Route::prefix('client')->name('client.')->group(function () {
        Route::get('/dashboard', \App\Http\Controllers\Client\DashboardController::class)->name('dashboard');
        Route::get('/portal', [\App\Http\Controllers\Client\MixradiusPortalController::class, 'index'])->name('portal');
            Route::get('/mixradius', function () {
                $url = \App\Models\Setting::getValue('mixradius_base_url', env('MIXRADIUS_BASE_URL', ''));
                abort_if(empty($url), 404);
                // Normalize url to base (no trailing slash)
                $url = rtrim((string)$url, '/');
                return view('client.mixradius_embed', ['mixradiusUrl' => $url]);
            })->name('mixradius');
        Route::get('/connection', [\App\Http\Controllers\Client\ConnectionController::class, 'index'])->name('connection');
        Route::get('/invoices', [\App\Http\Controllers\Client\InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [\App\Http\Controllers\Client\InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/invoices/{invoice}/pay', [\App\Http\Controllers\Client\InvoiceController::class, 'pay'])->name('invoices.pay');
        Route::get('/credentials', [\App\Http\Controllers\Client\CredentialsController::class, 'show'])->name('credentials.show');
        Route::post('/credentials', [\App\Http\Controllers\Client\CredentialsController::class, 'update'])->name('credentials.update');
    });
    
// Payment Webhook
Route::post('/webhooks/midtrans', [\App\Http\Controllers\WebhookController::class, 'midtrans'])->name('webhooks.midtrans');

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
    Route::resource('users', UserController::class);

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

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
    Route::post('customers/{customer}/settings/wan', [CustomerWebController::class, 'updateWan'])->name('customers.settings.wan');
    Route::post('customers/{customer}/settings/wlan', [CustomerWebController::class, 'updateWlan'])->name('customers.settings.wlan');
    Route::delete('customers/bulk-destroy', [CustomerWebController::class, 'bulkDestroy'])->name('customers.bulkDestroy');
    Route::resource('customers', CustomerWebController::class);
    
    Route::put('tickets/{ticket}/complete', [TicketWebController::class, 'complete'])->name('tickets.complete');
    Route::post('tickets/{ticket}/notify', [TicketWebController::class, 'sendNotification'])->name('tickets.notify');
    Route::patch('tickets/{ticket}/location', [TicketWebController::class, 'updateLocation'])->name('tickets.updateLocation');
    Route::patch('tickets/{ticket}/customer', [TicketWebController::class, 'updateCustomer'])->name('tickets.updateCustomer');
    Route::resource('tickets', TicketWebController::class);
    
    Route::resource('installations', InstallationWebController::class);
    Route::resource('technicians', TechnicianController::class);
    
    // Technician Attendance
    Route::post('salary-adjustments', [\App\Http\Controllers\SalaryAdjustmentController::class, 'store'])->name('salary-adjustments.store');
    Route::delete('salary-adjustments/{salaryAdjustment}', [\App\Http\Controllers\SalaryAdjustmentController::class, 'destroy'])->name('salary-adjustments.destroy');
    Route::get('attendance/pdf', [TechnicianAttendanceController::class, 'exportPdf'])->name('attendance.pdf');
    Route::get('attendance/excel', [TechnicianAttendanceController::class, 'exportExcel'])->name('attendance.excel');
    Route::post('attendance/recap-finance', [TechnicianAttendanceController::class, 'recapToFinance'])->name('attendance.recap_finance');
    Route::post('attendance/manual', [TechnicianAttendanceController::class, 'storeManual'])->name('attendance.storeManual');
    Route::delete('attendance/bulk-destroy', [TechnicianAttendanceController::class, 'bulkDestroy'])->name('attendance.bulkDestroy');
    Route::post('attendance/{attendance}/notify', [TechnicianAttendanceController::class, 'sendNotification'])->name('attendance.notify');
    Route::resource('attendance', TechnicianAttendanceController::class)->only(['index', 'create', 'store', 'update', 'destroy']);

    // Schedules & Leaves
    Route::post('schedules/period', [\App\Http\Controllers\TechnicianScheduleController::class, 'updatePeriod'])->name('schedules.updatePeriod');
    Route::resource('schedules', \App\Http\Controllers\TechnicianScheduleController::class)->only(['index', 'store', 'destroy']);
    Route::resource('leave-requests', \App\Http\Controllers\LeaveRequestController::class)->except(['create', 'show', 'edit', 'destroy']);

    // Network & Infrastructure
    Route::post('olt/test-connection', [OLTController::class, 'testConnection'])->name('olt.test_connection');
    Route::get('olt/{olt}/check-status', [OLTController::class, 'checkStatus'])->name('olt.check_status');
    Route::get('olt/{olt}/system-info', [OLTController::class, 'getSystemInfo'])->name('olt.system_info');
    Route::get('olt/{olt}/onus', [OnuController::class, 'index'])->name('olt.onus.index');
    Route::post('olt/{olt}/onus/sync', [OnuController::class, 'sync'])->name('olt.onus.sync');
    Route::resource('olt', OLTController::class);
    // ... kode router lainnya ...
    Route::post('routers/{router}/pppoe/disconnect', [RouterController::class, 'disconnectPppoe'])->name('routers.pppoe.disconnect');
    Route::post('routers/{router}/pppoe/toggle-secret', [RouterController::class, 'togglePppoeSecret'])->name('routers.pppoe.toggle-secret');
    
    // TAMBAHKAN BARIS INI (Route untuk halaman list PPPoE Active)
    Route::get('routers/{router}/pppoe-active', [RouterController::class, 'pppoeActive'])->name('routers.pppoe.active');
    
    Route::post('routers/{router}/hotspot/disconnect', [RouterController::class, 'disconnectHotspot'])->name('routers.hotspot.disconnect');
    Route::get('hotspot/online', [RouterController::class, 'sessions'])->name('hotspot.online');
    Route::get('hotspot', [HotspotController::class, 'index'])->name('hotspot.index');
    Route::get('pppoe', [App\Http\Controllers\PppoeController::class, 'index'])->name('pppoe.index');
    Route::resource('routers', RouterController::class);

    Route::prefix('vpn')->name('vpn.')->group(function () {
        Route::resource('servers', VpnServerController::class)->except(['show']);
    });
    Route::view('/vpn/guide', 'vpn.guide')->name('vpn.guide');

    Route::get('/routers/{router}/vpn/script', function (RouterModel $router) {
        abort_unless($router->vpn_account_id, 404);
        $account = VpnAccount::findOrFail($router->vpn_account_id);
        $protocol = request('protocol', 'l2tp');
        $service = app(VpnBridgeService::class);
        $script = $service->generateScript($account, $protocol);
        return response()->view('routers.vpn_script', compact('router', 'account', 'script', 'protocol'));
    })->name('routers.vpn.script');
    
    Route::post('routers/{router}/test-connection', [RouterController::class, 'testConnection'])->name('routers.test-connection');
    Route::get('routers/{router}/sessions', [RouterController::class, 'sessions'])->name('routers.sessions');
    Route::post('routers/{router}/pppoe/disconnect', [RouterController::class, 'disconnectPppoe'])->name('routers.pppoe.disconnect');
    Route::post('routers/{router}/pppoe/toggle-secret', [RouterController::class, 'togglePppoeSecret'])->name('routers.pppoe.toggle-secret');
    Route::post('routers/{router}/hotspot/disconnect', [RouterController::class, 'disconnectHotspot'])->name('routers.hotspot.disconnect');
    Route::get('hotspot/online', [RouterController::class, 'index'])->name('hotspot.online');
    Route::get('hotspot', [HotspotController::class, 'index'])->name('hotspot.index');
    Route::resource('routers', RouterController::class);

    // Business & Operations
    Route::get('finance/material-report', [FinanceController::class, 'materialReport'])->name('finance.material_report');
    Route::get('finance/export-accounting', [FinanceController::class, 'exportAccounting'])->name('finance.export_accounting');
    Route::get('finance/settings', [FinanceController::class, 'settings'])->name('finance.settings');
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
    
    Route::put('map/location/{type}/{id}', [MapController::class, 'updateLocation'])->name('map.update_location');
    Route::put('map/path/{type}/{id}', [MapController::class, 'updatePath'])->name('map.update_path');
    Route::post('map/connections/save', [\App\Http\Controllers\MapConnectionController::class, 'save'])->name('map.connections.save');
    Route::get('map/connections', [\App\Http\Controllers\MapConnectionController::class, 'index'])->name('map.connections.index');
    Route::resource('map', MapController::class);
    
    // Tools
    Route::get('/calculator/pon', [CalculatorController::class, 'index'])->name('calculator.pon');
    Route::resource('packages', \App\Http\Controllers\PackageController::class)->except(['show']);
    Route::get('odps/next-sequence/{odc}', [\App\Http\Controllers\OdpController::class, 'getNextSequence'])->name('odps.next_sequence');
    Route::get('odps/export/excel', [\App\Http\Controllers\OdpController::class, 'exportExcel'])->name('odps.export.excel');
    Route::resource('odps', \App\Http\Controllers\OdpController::class);
    Route::resource('htbs', \App\Http\Controllers\HtbController::class);
    Route::get('odcs/export/excel', [\App\Http\Controllers\OdcController::class, 'exportExcel'])->name('odcs.export.excel');
    Route::resource('odcs', \App\Http\Controllers\OdcController::class);
    Route::resource('closures', \App\Http\Controllers\ClosureController::class);
    Route::resource('regions', \App\Http\Controllers\RegionController::class);
    Route::resource('coordinators', \App\Http\Controllers\CoordinatorController::class);
    Route::get('investors/export/pdf', [InvestorController::class, 'exportPdf'])->name('investors.export.pdf');
    Route::get('investors/export/excel', [InvestorController::class, 'exportExcel'])->name('investors.export.excel');
    Route::resource('investors', InvestorController::class);
    Route::resource('chat', ChatController::class);
    
    // Telegram Settings
    Route::get('/telegram', [\App\Http\Controllers\TelegramController::class, 'index'])->name('telegram.index');
    Route::post('/telegram/update', [\App\Http\Controllers\TelegramController::class, 'update'])->name('telegram.update');
    Route::post('/telegram/test', [\App\Http\Controllers\TelegramController::class, 'test'])->name('telegram.test');

    // WhatsApp Settings
    Route::get('/whatsapp', [\App\Http\Controllers\WhatsAppController::class, 'index'])->name('whatsapp.index');
    Route::post('/whatsapp/update', [\App\Http\Controllers\WhatsAppController::class, 'update'])->name('whatsapp.update');
    Route::post('/whatsapp/test', [\App\Http\Controllers\WhatsAppController::class, 'test'])->name('whatsapp.test');

    Route::post('wash/transactions/{transaction}/whatsapp-receipt', [\App\Http\Controllers\WashTransactionController::class, 'whatsappReceipt'])->name('wash.transactions.whatsapp_receipt');
    Route::post('atk/transactions/{transaction}/whatsapp-receipt', [\App\Http\Controllers\AtkTransactionController::class, 'whatsappReceipt'])->name('atk.transactions.whatsapp_receipt');


    // Inventory
    Route::get('/inventory/export/pdf', [\App\Http\Controllers\InventoryController::class, 'exportPdf'])->name('inventory.export.pdf');
    Route::get('/inventory/export/excel', [\App\Http\Controllers\InventoryController::class, 'exportExcel'])->name('inventory.export.excel');
    Route::get('/inventory/import/template', [\App\Http\Controllers\InventoryController::class, 'downloadTemplate'])->name('inventory.import.template');
    Route::post('/inventory/import', [\App\Http\Controllers\InventoryController::class, 'importExcel'])->name('inventory.import');
    Route::get('/inventory', [\App\Http\Controllers\InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/item', [\App\Http\Controllers\InventoryController::class, 'storeItem'])->name('inventory.store');
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

    Route::prefix('accounting')->name('accounting.')->group(function () {
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
        Route::get('/dashboard', [\App\Http\Controllers\WashTransactionController::class, 'dashboard'])->name('dashboard');
        Route::get('/pos', [\App\Http\Controllers\WashTransactionController::class, 'pos'])->name('pos');
        Route::post('/transactions', [\App\Http\Controllers\WashTransactionController::class, 'store'])->name('transactions.store');
        Route::get('/transactions/{transaction}/receipt', [\App\Http\Controllers\WashTransactionController::class, 'receipt'])->name('transactions.receipt');
        Route::get('/reports', [\App\Http\Controllers\WashReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/pdf', [\App\Http\Controllers\WashReportController::class, 'pdf'])->name('reports.pdf');
        Route::get('/reports/excel', [\App\Http\Controllers\WashReportController::class, 'excel'])->name('reports.excel');
        Route::get('/expenses', [\App\Http\Controllers\WashExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/expenses/create', [\App\Http\Controllers\WashExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [\App\Http\Controllers\WashExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/customer/check', [\App\Http\Controllers\WashTransactionController::class, 'checkCustomer'])->name('customer.check');
        Route::get('/transactions/export/pdf', [\App\Http\Controllers\WashTransactionController::class, 'exportPdf'])->name('transactions.export.pdf');
        Route::get('/transactions/export/excel', [\App\Http\Controllers\WashTransactionController::class, 'exportExcel'])->name('transactions.export.excel');
        Route::resource('transactions', \App\Http\Controllers\WashTransactionController::class)->only(['index', 'show']);
    });

    // Wash Services (Auto Wash)
    Route::resource('wash/services', \App\Http\Controllers\WashController::class)->names([
        'index' => 'wash.services.index',
        'create' => 'wash.services.create',
        'store' => 'wash.services.store',
        'edit' => 'wash.services.edit',
        'update' => 'wash.services.update',
        'destroy' => 'wash.services.destroy',
    ])->except(['show']);
    Route::resource('wash/employees', \App\Http\Controllers\WashEmployeeController::class)->names('wash.employees');
    
    // ATK Store Routes
    Route::prefix('atk')->name('atk.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\AtkTransactionController::class, 'dashboard'])->name('dashboard');
        Route::get('/pos', [\App\Http\Controllers\AtkTransactionController::class, 'pos'])->name('pos');
        Route::post('/transactions', [\App\Http\Controllers\AtkTransactionController::class, 'store'])->name('transactions.store');
        Route::get('/reports', [\App\Http\Controllers\AtkReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/pdf', [\App\Http\Controllers\AtkReportController::class, 'pdf'])->name('reports.pdf');
        Route::get('/reports/excel', [\App\Http\Controllers\AtkReportController::class, 'excel'])->name('reports.excel');
        Route::get('/expenses', [\App\Http\Controllers\AtkExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/expenses/create', [\App\Http\Controllers\AtkExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [\App\Http\Controllers\AtkExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/transactions/export/pdf', [\App\Http\Controllers\AtkTransactionController::class, 'exportPdf'])->name('transactions.export.pdf');
        Route::get('/transactions/export/excel', [\App\Http\Controllers\AtkTransactionController::class, 'exportExcel'])->name('transactions.export.excel');
        Route::get('/transactions/{transaction}/receipt', [\App\Http\Controllers\AtkTransactionController::class, 'receipt'])->name('transactions.receipt');
        Route::get('products/export', [\App\Http\Controllers\AtkProductController::class, 'export'])->name('products.export');
        Route::post('products/import', [\App\Http\Controllers\AtkProductController::class, 'import'])->name('products.import');
        Route::delete('products/bulk-destroy', [\App\Http\Controllers\AtkProductController::class, 'bulkDestroy'])->name('products.bulk_destroy');
        Route::resource('products', \App\Http\Controllers\AtkProductController::class);
        Route::resource('transactions', \App\Http\Controllers\AtkTransactionController::class)->only(['index', 'show']);
    });
});
