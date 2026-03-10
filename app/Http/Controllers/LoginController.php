<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\MixRadiusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
            'login.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $login = trim($data['login']);
        $password = $data['password'];
        $remember = $request->boolean('remember');
        $enforceRaw = Setting::getValue('mixradius_enforce_customer_login', env('MIXRADIUS_ENFORCE_CUSTOMER_LOGIN', false) ? '1' : '0');
        $enforceCustomer = in_array(strtolower((string) $enforceRaw), ['1', 'true', 'yes', 'on'], true);
        $isEmailInput = (bool) filter_var($login, FILTER_VALIDATE_EMAIL);
        $legacyEmailUser = null;

        if ($isEmailInput) {
            $legacyEmailUser = User::query()
                ->whereNull('username')
                ->whereRaw('LOWER(email) = ?', [Str::lower($login)])
                ->first();

            if (! $legacyEmailUser) {
                throw ValidationException::withMessages(['login' => 'Login gunakan username. Email hanya alternatif untuk akun lama yang belum punya username.']);
            }
        }

        $identity = $this->resolveIdentity($login, $mixRadius);

        $attempted = false;

        if ($isEmailInput && $legacyEmailUser) {
            $attempted = Auth::attempt(['email' => $legacyEmailUser->email, 'password' => $password], $remember);
        } else {
            $attempted = Auth::attempt(['username' => $login, 'password' => $password], $remember);
            if (! $attempted) {
                $legacyUser = User::query()
                    ->whereNull('username')
                    ->where('email', 'like', $login.'@%')
                    ->first();
                if ($legacyUser) {
                    $attempted = Auth::attempt(['email' => $legacyUser->email, 'password' => $password], $remember);
                }
            }
        }

        if ($attempted) {
            $request->session()->regenerate();
            $user = Auth::user();
            if ($enforceCustomer && $user && $user->hasRole('customer')) {
                if ($mixRadius->isAvailable()) {
                    $verify = $mixRadius->verifyCredentials($identity, $password);
                    if (! ($verify['ok'] ?? false)) {
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

        try {
            $verify = $mixRadius->verifyCredentials($identity, $password);
            if ($verify['ok'] ?? false) {
                $resolvedUsername = trim((string) ($verify['data']['username'] ?? $verify['data']['radius_username'] ?? $identity));
                $resolvedUsername = $resolvedUsername !== '' ? $resolvedUsername : $login;
                $resolvedEmail = trim((string) ($verify['data']['email'] ?? ''));

                if ($resolvedEmail === '') {
                    $emailPrefix = Str::slug($resolvedUsername, '.');
                    if ($emailPrefix === '') {
                        $emailPrefix = 'user.'.Str::lower(Str::random(8));
                    }
                    $resolvedEmail = $emailPrefix.'@local.test';
                }

                $user = User::query()
                    ->where('username', $resolvedUsername)
                    ->orWhere('radius_username', $resolvedUsername)
                    ->orWhere('email', $resolvedEmail)
                    ->first();

                if (! $user) {
                    $roleId = Role::where('name', 'customer')->value('id');
                    $user = User::create([
                        'name' => $verify['data']['name'] ?? $resolvedUsername,
                        'email' => $resolvedEmail,
                        'username' => $resolvedUsername,
                        'radius_username' => $verify['data']['radius_username'] ?? $resolvedUsername,
                        'radius_type' => $verify['data']['radius_type'] ?? null,
                        'password' => bcrypt(Str::random(32)),
                        'role_id' => $roleId,
                        'is_active' => true,
                    ]);
                } else {
                    $user->fill([
                        'username' => $user->username ?: $resolvedUsername,
                        'radius_username' => $verify['data']['radius_username'] ?? ($user->radius_username ?: $resolvedUsername),
                        'radius_type' => $verify['data']['radius_type'] ?? $user->radius_type,
                    ])->save();
                }

                try {
                    $cust = null;
                    if (ctype_digit($login)) {
                        $cust = \App\Models\Customer::find((int) $login);
                    }
                    if (! $cust && ! empty($resolvedUsername)) {
                        $cust = \App\Models\Customer::where('pppoe_user', $resolvedUsername)->first();
                    }
                    if ($cust && empty($cust->user_id)) {
                        $cust->user_id = $user->id;
                        $cust->save();
                    }
                } catch (\Throwable $e) {
                }

                Auth::login($user, $remember);
                $request->session()->regenerate();
                $fallback = $user->hasRole('customer') ? route('client.dashboard') : route('dashboard');

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

        throw ValidationException::withMessages(['login' => 'Username atau password salah, atau akun tidak ditemukan.']);
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    protected function resolveIdentity(string $login, MixRadiusService $mixRadius): string
    {
        $identity = $login;

        try {
            if (ctype_digit($login)) {
                $customer = \App\Models\Customer::with('user')->find((int) $login);
                if ($customer) {
                    if (! empty($customer->pppoe_user)) {
                        $identity = $customer->pppoe_user;
                    } elseif ($customer->user && ! empty($customer->user->username)) {
                        $identity = $customer->user->username;
                    }
                }
            } else {
                $customer = \App\Models\Customer::where('pppoe_user', $login)->first();
                if ($customer && ! empty($customer->pppoe_user)) {
                    $identity = $customer->pppoe_user;
                }
            }
        } catch (\Throwable $e) {
        }

        if ($identity === $login && ctype_digit($login)) {
            $resolved = $mixRadius->resolveUsernameById($login);
            if (is_string($resolved) && $resolved !== '') {
                $identity = $resolved;
            }
        }

        return $identity;
    }
}
