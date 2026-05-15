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

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
Route::get('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'showForgotForm'])->name('password.forgot');
Route::post('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'sendOtp'])->name('password.send_otp');
Route::get('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'reset'])->name('password.reset');

Route::middleware('auth')->group(function () {
    Route::get('/customers/register', [CustomerPublicRegisterController::class, 'create'])->name('customers.public.register.create');
    Route::post('/customers/register', [CustomerPublicRegisterController::class, 'store'])->name('customers.public.register.store');
    
    Route::get('modem-data', [ModemDataController::class, 'index'])->name('modem-data.index')->middleware('permission:modem-data.view');
    Route::post('modem-data', [ModemDataController::class, 'store'])->name('modem-data.store')->middleware('permission:modem-data.create|modem-data.view');
    
    // AI Center
    Route::get('/ai-center', [\App\Http\Controllers\AiController::class, 'index'])->name('ai.index');
    Route::post('/ai-center/chat', [\App\Http\Controllers\AiController::class, 'chat'])
        ->middleware('throttle:30,1')
        ->name('ai.chat');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/presence/ping', [PresenceController::class, 'ping'])->name('presence.ping');
    Route::get('/dashboard/monitor-logs', [DashboardController::class, 'monitorLogs'])->name('dashboard.monitor_logs');
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
    Route::resource('customers', CustomerWebController::class);

    Route::put('tickets/{ticket}/complete', [TicketWebController::class, 'complete'])->name('tickets.complete');
    Route::get('tickets/{ticket}/sop-pdf', [TicketWebController::class, 'sopPdf'])->name('tickets.sop.pdf');
    Route::post('tickets/{ticket}/notify', [TicketWebController::class, 'sendNotification'])->name('tickets.notify');
    Route::patch('tickets/{ticket}/location', [TicketWebController::class, 'updateLocation'])->name('tickets.updateLocation');
    Route::patch('tickets/{ticket}/customer', [TicketWebController::class, 'updateCustomer'])->name('tickets.updateCustomer');
    Route::resource('tickets', TicketWebController::class);

    Route::resource('installations', InstallationWebController::class);
    Route::permanentRedirect('technicians', 'employees');
    Route::any('technicians/{any}', fn () => redirect()->route('employees.index', [], 301))
        ->where('any', '.*');

    // Technician Attendance & Kasbon
    Route::get('technicians/kasbon', [\App\Http\Controllers\SalaryAdjustmentController::class, 'index'])->name('technicians.kasbon.index');
    Route::post('salary-adjustments', [\App\Http\Controllers\SalaryAdjustmentController::class, 'store'])->name('salary-adjustments.store');
    Route::delete('salary-adjustments/{salaryAdjustment}', [\App\Http\Controllers\SalaryAdjustmentController::class, 'destroy'])->name('salary-adjustments.destroy');
    Route::get('attendance/daily', [TechnicianAttendanceController::class, 'daily'])->name('attendance.daily');
    Route::get('attendance/payslip', [TechnicianAttendanceController::class, 'payslip'])->name('attendance.payslip');
    Route::get('attendance/excel', [TechnicianAttendanceController::class, 'exportExcel'])->name('attendance.excel');
    Route::post('attendance/recap-finance', [TechnicianAttendanceController::class, 'recapToFinance'])->name('attendance.recap_finance');
    Route::post('attendance/manual', [TechnicianAttendanceController::class, 'storeManual'])->name('attendance.storeManual');
    Route::delete('attendance/bulk-destroy', [TechnicianAttendanceController::class, 'bulkDestroy'])->name('attendance.bulkDestroy');
    Route::post('attendance/{attendance}/notify', [TechnicianAttendanceController::class, 'sendNotification'])->name('attendance.notify');
    Route::get('attendance/kiosk', [TechnicianAttendanceController::class, 'kiosk'])->name('attendance.kiosk');
    Route::post('attendance/kiosk/scan', [TechnicianAttendanceController::class, 'kioskScan'])->name('attendance.kiosk.scan');
    Route::post('landing/attendance/clock-in', [TechnicianAttendanceController::class, 'store'])->name('landing.attendance.store');
    Route::put('landing/attendance/{attendance}/clock-out', [TechnicianAttendanceController::class, 'update'])->name('landing.attendance.update');
    Route::resource('attendance', TechnicianAttendanceController::class)->only(['index', 'create', 'store', 'update', 'destroy']);

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
    Route::resource('leave-requests', \App\Http\Controllers\LeaveRequestController::class)->except(['create', 'show', 'edit', 'destroy']);

    // Network & Infrastructure
    Route::post('olt/test-connection', [OLTController::class, 'testConnection'])->name('olt.test_connection');
    Route::get('olt/{olt}/check-status', [OLTController::class, 'checkStatus'])->name('olt.check_status');
    Route::get('olt/{olt}/system-info', [OLTController::class, 'getSystemInfo'])->name('olt.system_info');
    Route::get('olt/{olt}/onus', [OnuController::class, 'index'])->name('olt.onus.index');
    Route::post('olt/{olt}/onus/sync', [OnuController::class, 'sync'])->name('olt.onus.sync');
    Route::put('olt/onu/{onu}', [OnuController::class, 'update'])->name('olt.onu.update');
    Route::post('olt/onu/{onu}/reboot', [OnuController::class, 'reboot'])->name('olt.onu.reboot');
    Route::resource('olt', OLTController::class);
    // ... kode router lainnya ...
    Route::post('routers/{router}/pppoe/disconnect', [RouterController::class, 'disconnectPppoe'])->name('routers.pppoe.disconnect');

    // Route untuk test koneksi router (Explicitly defined)
    Route::post('routers/{router}/test-connection', [RouterController::class, 'testConnection'])->name('routers.test-connection');

    Route::post('routers/{router}/pppoe/toggle-secret', [RouterController::class, 'togglePppoeSecret'])->name('routers.pppoe.toggle-secret');

    // Route for sessions view
    Route::get('routers/{router}/sessions', [RouterController::class, 'sessions'])->name('routers.sessions');

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

    Route::put('map/location/{type}/{id}', [MapController::class, 'updateLocation'])->name('map.update_location');
    Route::put('map/path/{type}/{id}', [MapController::class, 'updatePath'])->name('map.update_path');
    Route::post('map/connections/save', [\App\Http\Controllers\MapConnectionController::class, 'save'])->name('map.connections.save');
    Route::get('map/connections', [\App\Http\Controllers\MapConnectionController::class, 'index'])->name('map.connections.index');
    Route::get('map/wlan-status/{customer}', [MapController::class, 'getCustomerWlanStatus'])->name('map.wlan_status');
    Route::post('map/wlan-update/{customer}', [MapController::class, 'updateCustomerWlan'])->name('map.wlan_update');
    Route::post('map/ping', [MapController::class, 'ping'])->name('map.ping');
    Route::resource('map', MapController::class);

    // Tools
    Route::get('/calculator/pon', [CalculatorController::class, 'index'])->name('calculator.pon');
    Route::get('/network/analyzer', [NetworkAnalyzerController::class, 'index'])->name('network.analyzer');
    Route::get('/network/analyzer/ping', [NetworkAnalyzerController::class, 'ping'])->name('network.analyzer.ping');
    Route::get('/network/analyzer/info', [NetworkAnalyzerController::class, 'networkInfo'])->name('network.analyzer.info');
    Route::get('/network/analyzer/speed/download', [NetworkAnalyzerController::class, 'speedDownload'])->name('network.analyzer.speed.download');
    Route::post('/network/analyzer/speed/upload', [NetworkAnalyzerController::class, 'speedUpload'])->name('network.analyzer.speed.upload');
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
    Route::post('/voucher/template', [VoucherController::class, 'storeTemplate'])->name('vouchers.templates.store');
    Route::delete('/voucher/template/{voucherTemplate}', [VoucherController::class, 'deleteTemplate'])->name('vouchers.templates.delete');
    Route::get('/voucher/export/csv', [VoucherController::class, 'exportCsv'])->name('vouchers.export.csv');
    Route::get('/voucher/export/excel', [VoucherController::class, 'exportExcel'])->name('vouchers.export.excel');
    Route::get('/voucher/export/pdf', [VoucherController::class, 'exportPdf'])->name('vouchers.export.pdf');
    Route::get('investors/export/pdf', [InvestorController::class, 'exportPdf'])->name('investors.export.pdf');
    Route::get('investors/export/excel', [InvestorController::class, 'exportExcel'])->name('investors.export.excel');
    Route::resource('investors', InvestorController::class);
    Route::post('chat/start', [ChatController::class, 'start'])->name('chat.start');
    Route::get('chat/{chat}/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::get('chat/{chat}/presence', [ChatController::class, 'presence'])->name('chat.presence');
    Route::post('chat/{chat}/typing', [ChatController::class, 'typing'])->name('chat.typing');
    Route::post('chat/{chat}/read', [ChatController::class, 'markRead'])->name('chat.read');
    Route::get('chat/messages/{message}/download', [ChatController::class, 'downloadAttachment'])->name('chat.attachments.download');
    Route::resource('chat', ChatController::class)->only(['index', 'show', 'store']);

    // Telegram Settings
    Route::get('/telegram', [\App\Http\Controllers\TelegramController::class, 'index'])->name('telegram.index');
    Route::post('/telegram/update', [\App\Http\Controllers\TelegramController::class, 'update'])->name('telegram.update');
    Route::post('/telegram/test', [\App\Http\Controllers\TelegramController::class, 'test'])->name('telegram.test');
    Route::post('/telegram/test-ip-down', [\App\Http\Controllers\TelegramController::class, 'testIpDown'])->name('telegram.test_ip_down');
    Route::post('/telegram/test-ip-up', [\App\Http\Controllers\TelegramController::class, 'testIpUp'])->name('telegram.test_ip_up');
    Route::post('/telegram/preview-ip-down', [\App\Http\Controllers\TelegramController::class, 'previewIpDown'])->name('telegram.preview_ip_down');
    Route::post('/telegram/preview-ip-up', [\App\Http\Controllers\TelegramController::class, 'previewIpUp'])->name('telegram.preview_ip_up');

    // WhatsApp Settings
    Route::get('/whatsapp', [\App\Http\Controllers\WhatsAppController::class, 'index'])->name('whatsapp.index');
    Route::post('/whatsapp/update', [\App\Http\Controllers\WhatsAppController::class, 'update'])->name('whatsapp.update');
    Route::post('/whatsapp/test', [\App\Http\Controllers\WhatsAppController::class, 'test'])->name('whatsapp.test');
    Route::post('/whatsapp/check-status', [\App\Http\Controllers\WhatsAppController::class, 'checkStatus'])->name('whatsapp.check-status');

    Route::post('wash/transactions/{transaction}/whatsapp-receipt', [\App\Http\Controllers\WashTransactionController::class, 'whatsappReceipt'])->name('wash.transactions.whatsapp_receipt');
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
        Route::post('/expenses/stock-out', [\App\Http\Controllers\WashExpenseController::class, 'stockOut'])->name('expenses.stock_out');
        Route::get('/expenses/{expense}/edit', [\App\Http\Controllers\WashExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('/expenses/{expense}', [\App\Http\Controllers\WashExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [\App\Http\Controllers\WashExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::get('/customer/check', [\App\Http\Controllers\WashTransactionController::class, 'checkCustomer'])->name('customer.check');
        Route::get('/transactions/export/pdf', [\App\Http\Controllers\WashTransactionController::class, 'exportPdf'])->name('transactions.export.pdf');
        Route::get('/transactions/export/excel', [\App\Http\Controllers\WashTransactionController::class, 'exportExcel'])->name('transactions.export.excel');
        Route::delete('/transactions/bulk-destroy', [\App\Http\Controllers\WashTransactionController::class, 'bulkDestroy'])->name('transactions.bulkDestroy');
        Route::resource('transactions', \App\Http\Controllers\WashTransactionController::class)->only(['index', 'show', 'update', 'destroy']);
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
    Route::permanentRedirect('wash/employees', 'employees');
    Route::any('wash/employees/{any}', fn () => redirect()->route('employees.index', [], 301))
        ->where('any', '.*');

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
    });
});
