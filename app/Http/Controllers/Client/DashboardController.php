<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        // Status koneksi sederhana (stub): aktif jika user aktif
        $connectionStatus = $user->is_active ? 'Active' : 'Inactive';
        return view('client.dashboard', compact('user', 'connectionStatus'));
    }
}

