<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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

    public function createCustomer()
    {
        $waNumber = config('services.whatsapp.admin_number', null);
        if (! $waNumber) {
            $waNumber = Setting::getValue('whatsapp_number', '6281234567890');
        }
        $waNumber = preg_replace('/[^0-9]/', '', (string) $waNumber);
        if ($waNumber === '') {
            $waNumber = '6281234567890';
        }
        if (str_starts_with($waNumber, '0')) {
            $waNumber = '62'.substr($waNumber, 1);
        }

        return view('auth.login-customer', [
            'waNumber' => $waNumber,
        ]);
    }

    public function store(Request $request)
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
        $mode = $request->input('mode') === 'customer' ? 'customer' : 'admin';
        $isEmailInput = (bool) filter_var($login, FILTER_VALIDATE_EMAIL);
        $legacyEmailUser = null;
        $backRoute = $mode === 'customer' ? route('login.customer') : route('login');

        if ($isEmailInput) {
            $legacyEmailUser = User::query()
                ->whereRaw('LOWER(email) = ?', [Str::lower($login)])
                ->first();

            if (! $legacyEmailUser) {
                throw ValidationException::withMessages(['login' => 'Email tidak ditemukan.'])->redirectTo($backRoute);
            }

            if (trim((string) $legacyEmailUser->username) !== '') {
                throw ValidationException::withMessages(['login' => 'Gunakan username untuk login.'])->redirectTo($backRoute);
            }
        }

        $localUser = $this->resolveLocalUserByLogin($login, $isEmailInput, $legacyEmailUser);

        $attempted = false;

        if ($isEmailInput && $legacyEmailUser) {
            $attempted = Auth::attempt(['email' => $legacyEmailUser->email, 'password' => $password, 'is_active' => true], $remember);
        } else {
            $attempted = Auth::attempt(['username' => $login, 'password' => $password, 'is_active' => true], $remember);
            if (! $attempted) {
                $legacyUser = User::query()
                    ->whereNull('username')
                    ->where('email', 'like', $login.'@%')
                    ->first();
                if ($legacyUser) {
                    $attempted = Auth::attempt(['email' => $legacyUser->email, 'password' => $password, 'is_active' => true], $remember);
                }
            }
        }

        if ($attempted) {
            $request->session()->regenerate();
            $user = Auth::user();
            $fallback = $user ? $this->defaultRedirectForUser($user) : route('dashboard');
            if ($mode === 'customer' && $user && ! $user->hasRole('customer')) {
                $request->session()->flash('warning', 'Akun Anda bukan akun pelanggan. Diarahkan ke dashboard Admin.');
            }
            return redirect()->intended($fallback);
        }

        $customerPortalRedirect = $this->attemptCustomerPortalLogin($request, $login, $password, $remember);
        if ($customerPortalRedirect !== null) {
            return $customerPortalRedirect;
        }

        throw ValidationException::withMessages(['login' => $this->buildInvalidLoginMessage($localUser)])->redirectTo($backRoute);
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }



    protected function attemptCustomerPortalLogin(Request $request, string $login, string $password, bool $remember): ?RedirectResponse
    {
        $customer = $this->resolveCustomerPortalIdentity($login);
        if (! $customer) {
            return null;
        }

        if (($customer->status ?? 'active') !== 'active') {
            return null;
        }

        $customerPassword = (string) ($customer->pppoe_password ?? '');
        if ($customerPassword === '' || ! hash_equals($customerPassword, $password)) {
            return null;
        }

        $user = $this->ensurePortalUserForCustomer($customer, $password);
        if (! $user) {
            return null;
        }

        if (! $user->is_active) {
            return null;
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('client.onu-wifi.show'));
    }

    protected function resolveCustomerPortalIdentity(string $login): ?Customer
    {
        $login = trim($login);
        if ($login === '') {
            return null;
        }

        if (ctype_digit($login)) {
            return Customer::find((int) $login);
        }

        return Customer::where('pppoe_user', $login)->first();
    }

    protected function ensurePortalUserForCustomer(Customer $customer, string $rawPassword): ?User
    {
        $user = null;
        if ($customer->user_id) {
            $user = User::find($customer->user_id);
        }

        $username = trim((string) ($customer->pppoe_user ?? ''));
        $email = 'customer'.$customer->id.'@local.test';
        $defaultCompanyId = Company::where('is_active', true)->orderBy('id')->value('id');

        if (! $user && $username !== '') {
            $user = User::where('username', $username)->first();
        }
        if (! $user) {
            $user = User::where('email', $email)->first();
        }

        $role = Role::firstOrCreate(
            ['name' => 'customer'],
            ['label' => 'Customer']
        );

        if (! $user) {
            $emailBase = 'customer'.$customer->id;
            $emailCandidate = $email;
            $suffix = 1;
            while (User::where('email', $emailCandidate)->exists()) {
                $emailCandidate = $emailBase.'.'.$suffix.'@local.test';
                $suffix++;
            }

            $user = User::create([
                'name' => $customer->name ?: ('Customer '.$customer->id),
                'email' => $emailCandidate,
                'username' => $username !== '' ? User::generateUniqueUsername($username, $emailCandidate) : User::generateUniqueUsername('customer_'.$customer->id, $emailCandidate),
                'radius_username' => $username !== '' ? $username : null,
                'radius_type' => $username !== '' ? 'pppoe' : null,
                'phone' => $customer->phone,
                'password' => $rawPassword,
                'role_id' => $role->id,
                'company_id' => $defaultCompanyId,
                'is_active' => ($customer->status ?? 'active') === 'active',
            ]);
        } else {
            $updates = [
                'name' => $user->name ?: ($customer->name ?: ('Customer '.$customer->id)),
                'phone' => $user->phone ?: $customer->phone,
                'is_active' => ($customer->status ?? 'active') === 'active',
                'password' => $rawPassword,
            ];
            if ($username !== '') {
                $updates['radius_username'] = $user->radius_username ?: $username;
                $updates['radius_type'] = $user->radius_type ?: 'pppoe';
            }
            if (! $user->role_id) {
                $updates['role_id'] = $role->id;
            }
            if (! $user->company_id && $defaultCompanyId) {
                $updates['company_id'] = $defaultCompanyId;
            }
            $user->fill($updates)->save();
        }

        if ($customer->user_id !== $user->id) {
            $customer->user_id = $user->id;
            $customer->save();
        }

        return $user;
    }

    protected function defaultRedirectForUser(User $user): string
    {
        if ($user->hasRole('customer')) {
            return route('client.onu-wifi.show');
        }

        if ($user->hasRole('karyawan-wash') || $user->hasRole('kasir-wash')) {
            return route('attendance.create');
        }

        return route('dashboard');
    }

    protected function resolveLocalUserByLogin(string $login, bool $isEmailInput, ?User $legacyEmailUser): ?User
    {
        if ($isEmailInput) {
            return $legacyEmailUser;
        }

        $user = User::query()->where('username', $login)->first();
        if ($user) {
            return $user;
        }

        return User::query()
            ->whereNull('username')
            ->where('email', 'like', $login.'@%')
            ->first();
    }

    protected function buildInvalidLoginMessage(?User $localUser): string
    {
        if (! $localUser) {
            return 'Username tidak ditemukan.';
        }

        if (! $localUser->is_active) {
            return 'Akun tidak aktif. Hubungi admin untuk aktivasi ulang.';
        }

        return 'Password salah.';
    }
}
