<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class SecurityMonitoringController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:security.monitoring.view', only: ['index']),
        ];
    }

    public function index(Request $request)
    {
        $auditLogs = AuditLog::query()->with('user:id,name')->latest()->limit(50)->get();
        $failedJobs = DB::table('failed_jobs')->orderByDesc('failed_at')->limit(50)->get();
        $failedNotifications = DB::table('notification_logs')->where('status', 'failed')->orderByDesc('updated_at')->limit(50)->get();

        return view('security.monitoring', [
            'auditLogs' => $auditLogs,
            'failedJobs' => $failedJobs,
            'failedNotifications' => $failedNotifications,
        ]);
    }
}

