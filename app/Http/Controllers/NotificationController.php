<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class NotificationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:notification.view', only: ['redirect']),
            new Middleware('permission:notification.manage', only: ['markAllAsRead']),
        ];
    }

    public function redirect(string $notification)
    {
        $user = Auth::user();
        $model = $user->notifications()->find($notification);
        if (!$model) {
            return redirect()->route('dashboard');
        }

        $data = $model->data ?? [];
        $url = $data['url'] ?? null;
        if (isset($data['ticket_id'])) {
            $url = route('tickets.show', $data['ticket_id']);
        }
        if (!$url) {
            $url = url('/');
        }

        $model->delete();

        return redirect($url);
    }

    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        $user->unreadNotifications()->delete();

        if ($request->expectsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return back();
    }
}
