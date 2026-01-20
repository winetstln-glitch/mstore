<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\CustomerWebController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GenieACSController;
use App\Http\Controllers\GenieAcsServerController;
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
use Illuminate\Support\Facades\Route;

// Locale Switcher
Route::get('locale/{lang}', function ($lang) {
    if (in_array($lang, ['en', 'id'])) {
        session()->put('locale', $lang);
    }
    return redirect()->back();
})->name('locale.switch');

Route::get('/', [LoginController::class, 'create'])->name('login');
Route::post('/', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');

    Route::get('notifications/{notification}', [NotificationController::class, 'redirect'])->name('notifications.redirect');
    Route::post('notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');

    // Role Management
    Route::resource('roles', RoleController::class);
    
    // User Management
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
    Route::resource('customers', CustomerWebController::class);
    
    Route::put('tickets/{ticket}/complete', [TicketWebController::class, 'complete'])->name('tickets.complete');
    Route::patch('tickets/{ticket}/location', [TicketWebController::class, 'updateLocation'])->name('tickets.updateLocation');
    Route::patch('tickets/{ticket}/customer', [TicketWebController::class, 'updateCustomer'])->name('tickets.updateCustomer');
    Route::resource('tickets', TicketWebController::class);
    
    // Route::resource('installations', InstallationWebController::class); // Removed
    Route::resource('technicians', TechnicianController::class);
    
    // Technician Attendance
    Route::get('attendance/pdf', [TechnicianAttendanceController::class, 'exportPdf'])->name('attendance.pdf');
    Route::get('attendance/excel', [TechnicianAttendanceController::class, 'exportExcel'])->name('attendance.excel');
    Route::post('attendance/recap-finance', [TechnicianAttendanceController::class, 'recapToFinance'])->name('attendance.recap_finance');
    Route::post('attendance/manual', [TechnicianAttendanceController::class, 'storeManual'])->name('attendance.storeManual');
    Route::delete('attendance/bulk-destroy', [TechnicianAttendanceController::class, 'bulkDestroy'])->name('attendance.bulkDestroy');
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

    Route::post('routers/{router}/test-connection', [RouterController::class, 'testConnection'])->name('routers.test-connection');
    Route::get('routers/{router}/sessions', [RouterController::class, 'sessions'])->name('routers.sessions');
    Route::post('routers/{router}/pppoe/disconnect', [RouterController::class, 'disconnectPppoe'])->name('routers.pppoe.disconnect');
    Route::post('routers/{router}/pppoe/toggle-secret', [RouterController::class, 'togglePppoeSecret'])->name('routers.pppoe.toggle-secret');
    Route::post('routers/{router}/hotspot/disconnect', [RouterController::class, 'disconnectHotspot'])->name('routers.hotspot.disconnect');
    Route::resource('routers', RouterController::class);

    // Business & Operations
    Route::get('finance/profit-loss', [FinanceController::class, 'profitLoss'])->name('finance.profit_loss');
    Route::get('finance/profit-loss/pdf', [FinanceController::class, 'downloadProfitLossPdf'])->name('finance.profit_loss.pdf');
    Route::get('finance/profit-loss/excel', [FinanceController::class, 'downloadProfitLossExcel'])->name('finance.profit_loss.excel');
    
    Route::get('finance/income-breakdown/pdf', [FinanceController::class, 'downloadIncomeBreakdownPdf'])->name('finance.income_breakdown.pdf');
    Route::get('finance/investor-share/pdf', [FinanceController::class, 'downloadInvestorSharePdf'])->name('finance.investor_share.pdf');

    Route::get('finance/manager-report', [FinanceController::class, 'managerReport'])->name('finance.manager_report');
    Route::get('finance/manager-report/pdf', [FinanceController::class, 'downloadManagerReportPdf'])->name('finance.manager_report.pdf');
    Route::get('finance/manager-report/excel', [FinanceController::class, 'downloadManagerReportExcel'])->name('finance.manager_report.excel');
    Route::get('finance/coordinator/{coordinator}', [FinanceController::class, 'coordinatorDetail'])->name('finance.coordinator.detail');
    Route::get('finance/coordinator/{coordinator}/pdf', [FinanceController::class, 'downloadCoordinatorPdf'])->name('finance.coordinator.pdf');
    Route::delete('finance/bulk-destroy', [FinanceController::class, 'bulkDestroy'])->name('finance.bulkDestroy');
    Route::resource('finance', FinanceController::class)->parameters(['finance' => 'transaction']);
    Route::resource('map', MapController::class);
    Route::resource('packages', \App\Http\Controllers\PackageController::class)->except(['show']);
    Route::get('odps/next-sequence/{odc}', [\App\Http\Controllers\OdpController::class, 'getNextSequence'])->name('odps.next_sequence');
    Route::resource('odps', \App\Http\Controllers\OdpController::class);
    Route::resource('odcs', \App\Http\Controllers\OdcController::class);
    Route::resource('regions', \App\Http\Controllers\RegionController::class);
    Route::resource('coordinators', \App\Http\Controllers\CoordinatorController::class);
    Route::resource('investors', InvestorController::class);
    Route::resource('chat', ChatController::class);
    
    // Telegram Settings
    Route::get('/telegram', [\App\Http\Controllers\TelegramController::class, 'index'])->name('telegram.index');
    Route::post('/telegram/update', [\App\Http\Controllers\TelegramController::class, 'update'])->name('telegram.update');
    Route::post('/telegram/test', [\App\Http\Controllers\TelegramController::class, 'test'])->name('telegram.test');

    // Inventory
    Route::get('/inventory', [\App\Http\Controllers\InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/item', [\App\Http\Controllers\InventoryController::class, 'storeItem'])->name('inventory.store');
    Route::put('/inventory/item/{item}', [\App\Http\Controllers\InventoryController::class, 'updateItem'])->name('inventory.update');
    Route::delete('/inventory/item/{item}', [\App\Http\Controllers\InventoryController::class, 'destroyItem'])->name('inventory.destroy');
    Route::get('/inventory/pickup', [\App\Http\Controllers\InventoryController::class, 'createPickup'])->name('inventory.pickup');
    Route::post('/inventory/pickup', [\App\Http\Controllers\InventoryController::class, 'storePickup'])->name('inventory.store-pickup');
    Route::put('/inventory/pickup/{transaction}', [\App\Http\Controllers\InventoryController::class, 'updatePickup'])->name('inventory.pickup.update');
    Route::delete('/inventory/pickup/{transaction}', [\App\Http\Controllers\InventoryController::class, 'destroyPickup'])->name('inventory.pickup.destroy');

    // GenieACS / Network Monitor Routes
    Route::prefix('genieacs')->name('genieacs.')->group(function () {
        // Server Management
        Route::resource('servers', GenieAcsServerController::class);
        
        Route::get('/', [GenieACSController::class, 'index'])->name('index');
        Route::get('/device/{id}', [GenieACSController::class, 'show'])->name('show'); // Changed param to avoid conflict if any, though {id} is safe
        Route::post('/device/{id}/refresh', [GenieACSController::class, 'refresh'])->name('refresh');
        Route::post('/device/{id}/reboot', [GenieACSController::class, 'reboot'])->name('reboot');
        Route::post('/device/{id}/ping', [GenieACSController::class, 'ping'])->name('ping');
        Route::post('/device/{id}/alias', [GenieACSController::class, 'updateAlias'])->name('updateAlias');
        Route::post('/device/{id}/wan', [GenieACSController::class, 'updateWan'])->name('updateWan');
        Route::post('/device/{id}/wlan', [GenieACSController::class, 'updateWlan'])->name('updateWlan');
        Route::post('/device/{id}/param', [GenieACSController::class, 'updateParam'])->name('updateParam');
    });
});
