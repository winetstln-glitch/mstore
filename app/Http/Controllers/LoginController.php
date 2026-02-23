<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
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

        $login = trim($data['login']);
        $password = $data['password'];
        $enforceCustomer = (bool) env('MIXRADIUS_ENFORCE_CUSTOMER_LOGIN', false);

        $isEmail = (bool) filter_var($login, FILTER_VALIDATE_EMAIL);
        if (!$isEmail) {
            $identity = $login;
            try {
                if (ctype_digit($login)) {
                    $customer = \App\Models\Customer::with('user')->find((int)$login);
                    if ($customer) {
                        if (!empty($customer->pppoe_user)) {
                            $identity = $customer->pppoe_user;
                        } elseif ($customer->user && !empty($customer->user->username)) {
                            $identity = $customer->user->username;
                        } elseif ($customer->user && !empty($customer->user->email)) {
                            $identity = $customer->user->email;
                        }
                    }
                } else {
                    $cByPppoe = \App\Models\Customer::where('pppoe_user', $login)->first();
                    if ($cByPppoe) {
                        $identity = $cByPppoe->pppoe_user;
                    }
                    // If not found locally and numeric-like ID was entered, try resolve from MixRADIUS
                    if ($identity === $login && ctype_digit($login)) {
                        $resolved = $mixRadius->resolveUsernameById($login);
                        if (is_string($resolved) && $resolved !== '') {
                            $identity = $resolved;
                        }
                    }
                }
            } catch (\Throwable $e) {}
            if ($identity === $login && ctype_digit($login)) {
                $resolved = $mixRadius->resolveUsernameById($login);
                if (is_string($resolved) && $resolved !== '') {
                    $identity = $resolved;
                }
            }

            try {
                $verify = $mixRadius->verifyCredentials($identity, $password);
                if ($verify['ok'] ?? false) {
                    $email = filter_var($identity, FILTER_VALIDATE_EMAIL) ? $identity : ($verify['data']['email'] ?? ($identity . '@local.test'));
                    $username = filter_var($identity, FILTER_VALIDATE_EMAIL) ? ($verify['data']['username'] ?? null) : $identity;

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
                        try {
                            $cust = null;
                            if (ctype_digit($login)) {
                                $cust = \App\Models\Customer::find((int)$login);
                            }
                            if (!$cust && !empty($username)) {
                                $cust = \App\Models\Customer::where('pppoe_user', $username)->first();
                            }
                            if ($cust && empty($cust->user_id)) {
                                $cust->user_id = $user->id;
                                $cust->save();
                            }
                        } catch (\Throwable $e) {}
                    } else {
                        $user->fill([
                            'username' => $user->username ?: $username,
                            'radius_username' => $verify['data']['radius_username'] ?? ($user->radius_username ?: $username),
                            'radius_type' => $verify['data']['radius_type'] ?? $user->radius_type,
                        ])->save();
                    }

                    Auth::login($user, true);
                    $request->session()->regenerate();
                    $fallback = $user && $user->hasRole('customer') ? route('client.dashboard') : route('dashboard');
                    return redirect()->intended($fallback);
                }
            } catch (\Throwable $e) {
                $errId = (string) Str::uuid();
                $reqId = $request->headers->get('X-Request-Id');
                Log::error('Login customer failed', [
                    'error_id' => $errId,
                    'request_id' => $reqId,
                    'login' => $login,
                    'identity' => $identity,
                    'message' => $e->getMessage(),
                ]);
                throw ValidationException::withMessages(['login' => 'Terjadi kesalahan pada server. Silakan coba beberapa saat lagi.']);
            }

            throw ValidationException::withMessages(['login' => 'Username/ID atau password salah, atau tidak terdaftar di MixRADIUS.']);
        }

        $field = 'email';
        $credentials = [$field => $login, 'password' => $password];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            if ($enforceCustomer && $user && $user->hasRole('customer')) {
                // Build identity mapping for MixRADIUS check
                $identity = $login;
                try {
                    if (ctype_digit($login)) {
                        $customer = \App\Models\Customer::with('user')->find((int)$login);
                        if ($customer) {
                            if (!empty($customer->pppoe_user)) {
                                $identity = $customer->pppoe_user;
                            } elseif ($customer->user && !empty($customer->user->username)) {
                                $identity = $customer->user->username;
                            } elseif ($customer->user && !empty($customer->user->email)) {
                                $identity = $customer->user->email;
                            }
                        }
                    } else {
                        $cByPppoe = \App\Models\Customer::where('pppoe_user', $login)->first();
                        if ($cByPppoe) {
                            $identity = $cByPppoe->pppoe_user;
                        }
                    }
                } catch (\Throwable $e) {}
                
                // If MixRADIUS available, enforce verification; otherwise allow local login
                if ($mixRadius->isAvailable()) {
                    $verify = $mixRadius->verifyCredentials($identity, $password);
                    if (!($verify['ok'] ?? false)) {
                        Auth::logout();
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'login' => 'Akun pelanggan wajib diverifikasi MixRADIUS. Periksa username/ID dan password.',
                        ]);
                    }
                }
            }
            $fallback = $user && $user->hasRole('customer') ? route('client.dashboard') : route('dashboard');
            return redirect()->intended($fallback);
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
