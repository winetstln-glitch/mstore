<?php

namespace App\Http\Controllers;

use App\Models\RadAcct;

class OnlineUserController extends Controller
{
    /**
     * Tampilkan daftar pengguna yang sedang online (session aktif di RADIUS).
     */
    public function index()
    {
        $onlineUsers = RadAcct::whereNull('acctstoptime')
            ->orderByDesc('acctstarttime')
            ->paginate(50);

        if (request()->expectsJson()) {
            return response()->json($onlineUsers);
        }

        return view('online_users.index', compact('onlineUsers'));
    }
}
