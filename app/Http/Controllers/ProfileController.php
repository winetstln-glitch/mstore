<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;

class ProfileController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:profile.view', only: ['edit', 'idCard', 'idCardDownload']),
            new Middleware('permission:profile.update', only: ['update']),
        ];
    }

    /**
     * Display the user's profile form.
     */
    public function edit()
    {
        $user = Auth::user();
        $customer = \App\Models\Customer::where('user_id', $user->id)->first();
        $currentInvoice = $user->invoices()->latest()->first();
        $devicesCount = $customer ? \App\Models\Device::where('customer_id', $customer->id)->count() : 0;
        $statusText = $currentInvoice && $currentInvoice->status === 'paid' ? 'LUNAS' : ($currentInvoice ? strtoupper($currentInvoice->status) : '-');
        $idPelanggan = $user->username ?: (string) $user->id;
        $serviceText = $devicesCount > 0 ? ($devicesCount.' Device') : '-';
        $paymentType = 'POSTPAID';
        $registeredAt = $customer?->created_at;
        $updatedAt = $user->updated_at;
        $dueDate = $currentInvoice?->due_date;

        return view('profile.edit', compact(
            'user',
            'customer',
            'currentInvoice',
            'devicesCount',
            'statusText',
            'idPelanggan',
            'serviceText',
            'paymentType',
            'registeredAt',
            'updatedAt',
            'dueDate'
        ));
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Check if we have a base64 avatar
        $hasBase64 = $request->filled('avatar_base64');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'avatar_base64' => ['nullable', 'string'],
        ];

        // Only apply image validation if NO base64 provided AND a file IS uploaded
        if (! $hasBase64 && $request->hasFile('avatar')) {
            $rules['avatar'] = ['nullable', 'image', 'max:2048'];
        } else {
            $rules['avatar'] = ['nullable']; // Allow empty/string if base64 is present
        }

        $validated = $request->validate($rules);

        // Handle Base64 Upload (from Cropper)
        if ($request->filled('avatar_base64')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $base64_image = $request->input('avatar_base64');

            // Extract the base64 data (remove "data:image/jpeg;base64," part)
            if (preg_match('/^data:image\/(\w+);base64,/', $base64_image, $type)) {
                $base64_image = substr($base64_image, strpos($base64_image, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif

                if (! in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                    return back()->withErrors(['avatar' => 'Invalid image type.']);
                }

                $base64_image = base64_decode($base64_image);

                if ($base64_image === false) {
                    return back()->withErrors(['avatar' => 'Base64 decode failed.']);
                }

                $filename = 'avatars/'.uniqid().'.'.$type;
                Storage::disk('public')->put($filename, $base64_image);

                $validated['avatar'] = $filename;
                // Remove avatar_base64 from validated array as it's not a column
                unset($validated['avatar_base64']);
            }
        }
        // Handle Standard File Upload (Fallback)
        elseif ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        } else {
            unset($validated['avatar_base64']);
        }

        $user->update($validated);

        return back()->with('success', __('Profile updated successfully.'));
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        return back()->with('success', __('Password updated successfully.'));
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request)
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function idCard()
    {
        $user = Auth::user()->load(['role', 'employee']);
        $idCardCode = $this->userIdCardCode($user);
        [$brandName, $logoUrl, $brandSlogan] = $this->resolveUserBrand($user);

        return view('profile.id_card', compact('user', 'idCardCode', 'logoUrl', 'brandName', 'brandSlogan'));
    }

    public function idCardDownload()
    {
        $user = Auth::user()->load(['role', 'employee']);
        $idCardCode = $this->userIdCardCode($user);
        [$brandName, $logoUrl, $brandSlogan] = $this->resolveUserBrand($user);
        $viewData = [
            'user' => $user,
            'idCardCode' => $idCardCode,
            'logoUrl' => $logoUrl,
            'brandName' => $brandName,
            'brandSlogan' => $brandSlogan,
            'isPdf' => true,
        ];
        $pdf = Pdf::loadView('profile.id_card', $viewData)
            ->setPaper('a6', 'portrait');
        // Enable remote assets for images (logo, avatar, QR)
        if (method_exists($pdf, 'setOptions')) {
            $pdf->setOptions(['isRemoteEnabled' => true]);
        }
        $filename = 'ID-'.preg_replace('/[^A-Za-z0-9\-]+/', '-', $user->name).'.pdf';

        return $pdf->download($filename);
    }

    protected function userIdCardCode($user): string
    {
        $code = trim((string) ($user->attendance_card_code ?? ''));
        if ($code !== '') {
            return $code;
        }

        return \App\Models\User::defaultAttendanceCardCodeById((int) $user->id);
    }

    protected function resolveUserBrand($user): array
    {
        $scope = strtolower(trim((string) ($user->role?->label ?: $user->role?->name ?: '')));
        $defaultLogo = (string) (Setting::getValue('store_logo') ?: '');
        $defaultSlogan = 'Solusi Digital Cepat dan Terpercaya';

        if (str_contains($scope, 'wash')) {
            $name = (string) (Setting::getValue('brand_gtwash_name') ?: 'GTWASH');
            $logo = (string) (Setting::getValue('brand_gtwash_logo') ?: $defaultLogo);
            $slogan = (string) (Setting::getValue('brand_gtwash_slogan') ?: $defaultSlogan);

            return [strtoupper($name), $this->brandLogoUrl($logo), $slogan];
        }

        if (str_contains($scope, 'net') || str_contains($scope, 'network') || str_contains($scope, 'internet')) {
            $name = (string) (Setting::getValue('brand_mstorenet_name') ?: 'MSTORE.NET');
            $logo = (string) (Setting::getValue('brand_mstorenet_logo') ?: $defaultLogo);
            $slogan = (string) (Setting::getValue('brand_mstorenet_slogan') ?: $defaultSlogan);

            return [strtoupper($name), $this->brandLogoUrl($logo), $slogan];
        }

        $name = (string) (Setting::getValue('brand_mstore_name') ?: Setting::getValue('store_name') ?: 'MSTORE');
        $logo = (string) (Setting::getValue('brand_mstore_logo') ?: $defaultLogo);
        $slogan = (string) (Setting::getValue('brand_mstore_slogan') ?: $defaultSlogan);

        return [strtoupper($name), $this->brandLogoUrl($logo), $slogan];
    }

    protected function brandLogoUrl(string $logo): string
    {
        if (! $logo) {
            return asset('img/logo.png');
        }

        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            return $logo;
        }

        return asset($logo);
    }

    protected function buildQrPayload($user): string
    {
        $company = config('app.name', 'MStore');
        $code = trim((string) ($user->attendance_card_code ?? ''));
        if ($code === '') {
            $code = \App\Models\User::defaultAttendanceCardCodeById((int) $user->id);
        }
        $role = $user->role?->label ?? $user->role?->name ?? 'Staff';
        $vcard = "BEGIN:VCARD\nVERSION:3.0\nN:{$user->name}\nEMAIL:{$user->email}\nORG:{$company}\nTITLE:{$role}\nNOTE:{$code}\nEND:VCARD";

        return $vcard;
    }

    protected function buildQrUrl(string $text, int $size = 260, string $hexColor = '1d4ed8'): string
    {
        $data = rawurlencode($text);
        $sizeParam = $size.'x'.$size;
        $color = strtolower(ltrim($hexColor, '#'));

        // api.qrserver.com supports &color=RRGGBB and &bgcolor=RRGGBB
        return "https://api.qrserver.com/v1/create-qr-code/?size={$sizeParam}&margin=2&data={$data}&color={$color}&bgcolor=ffffff";
    }
}
