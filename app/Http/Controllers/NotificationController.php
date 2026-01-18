<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
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
