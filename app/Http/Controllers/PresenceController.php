<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PresenceController extends Controller
{
    public function ping(Request $request): JsonResponse
    {
        $user = Auth::user();
        abort_if(! $user, 401);

        $now = now();
        $shouldUpdate = ! $user->last_seen_at || $user->last_seen_at->diffInSeconds($now) >= 15;
        if ($shouldUpdate) {
            $user->forceFill([
                'last_seen_at' => $now,
                'last_seen_ip' => (string) ($request->ip() ?? ''),
                'last_seen_user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            ])->saveQuietly();
        }

        return response()->json(['ok' => true, 'at' => $now->toDateTimeString()]);
    }
}

