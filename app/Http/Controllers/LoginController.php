<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Services\MixRadiusService;
use App\Models\User;
use App\Models\Role;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request, MixRadiusService $mixRadius)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Email atau Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $login = $data['login'];
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [$field => $login, 'password' => $data['password']];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended('dashboard');
        }

        $verify = $mixRadius->verifyCredentials($login, $data['password']);
        if ($verify['ok'] ?? false) {
            $email = filter_var($login, FILTER_VALIDATE_EMAIL) ? $login : ($verify['data']['email'] ?? ($login . '@local.test'));
            $username = filter_var($login, FILTER_VALIDATE_EMAIL) ? ($verify['data']['username'] ?? null) : $login;

            $user = User::query()
                ->when($email, fn($q) => $q->where('email', $email))
                ->when(!$email && $username, fn($q) => $q->orWhere('username', $username))
                ->first();

            if (!$user) {
                $roleId = Role::where('name', 'customer')->value('id');
                $user = User::create([
                    'name' => $verify['data']['name'] ?? ($username ?: $email),
                    'email' => $email,
                    'username' => $username,
                    'radius_username' => $verify['data']['radius_username'] ?? $username,
                    'radius_type' => $verify['data']['radius_type'] ?? null,
                    'password' => bcrypt(Str::random(32)),
                    'role_id' => $roleId,
                    'is_active' => true,
                ]);
            } else {
                $user->fill([
                    'username' => $user->username ?: $username,
                    'radius_username' => $verify['data']['radius_username'] ?? ($user->radius_username ?: $username),
                    'radius_type' => $verify['data']['radius_type'] ?? $user->radius_type,
                ])->save();
            }

            Auth::login($user, true);
            $request->session()->regenerate();
            return redirect()->intended('dashboard');
        }

        throw ValidationException::withMessages(['login' => 'Email/Username atau password salah atau akun tidak ditemukan.']);
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
