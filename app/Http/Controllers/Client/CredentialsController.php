<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Network\Services\AuthenticationService;

class CredentialsController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        return view('client.credentials', compact('user'));
    }

    public function update(Request $request, AuthenticationService $authService)
    {
        $data = $request->validate([
            'new_username' => ['required', 'string', 'min:4'],
            'new_password' => ['required', 'string', 'min:6'],
        ], [
            'new_username.required' => 'Username baru wajib diisi.',
            'new_password.required' => 'Password baru wajib diisi.',
        ]);

        $user = Auth::user();
        $customer = $user->customer;
        
        if ($customer) {
            $authService->changePassword($customer, $data['new_password']);
            $authService->changeUsername($customer, $data['new_username']);
        }

        $user->username = $data['new_username'];
        $user->radius_username = $data['new_username'];
        $user->save();

        return redirect()->route('client.credentials.show')->with('status', 'Kredensial berhasil diperbarui.');
    }
}
