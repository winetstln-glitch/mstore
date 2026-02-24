<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\MixRadiusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CredentialsController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        return view('client.credentials', compact('user'));
    }

    public function update(Request $request, MixRadiusService $mix)
    {
        $data = $request->validate([
            'new_username' => ['required', 'string', 'min:4'],
            'new_password' => ['required', 'string', 'min:6'],
        ], [
            'new_username.required' => 'Username baru wajib diisi.',
            'new_password.required' => 'Password baru wajib diisi.',
        ]);

        $user = Auth::user();
        $ok = $mix->changeCredentials($user, $data['new_username'], $data['new_password']);
        if (! $ok) {
            return back()->withErrors(['new_username' => 'Gagal mengubah kredensial di server RADIUS.']);
        }

        $user->username = $data['new_username'];
        $user->radius_username = $data['new_username'];
        $user->save();

        return redirect()->route('client.credentials.show')->with('status', 'Kredensial berhasil diperbarui.');
    }
}
