<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:profile.view', only: ['edit']),
            new Middleware('permission:profile.update', only: ['update']),
        ];
    }

    /**
     * Display the user's profile form.
     */
    public function edit()
    {
        return view('profile.edit', [
            'user' => Auth::user(),
        ]);
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'avatar_base64' => ['nullable', 'string'],
        ];

        // Only apply image validation if NO base64 provided AND a file IS uploaded
        if (!$hasBase64 && $request->hasFile('avatar')) {
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

                if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                    return back()->withErrors(['avatar' => 'Invalid image type.']);
                }

                $base64_image = base64_decode($base64_image);

                if ($base64_image === false) {
                     return back()->withErrors(['avatar' => 'Base64 decode failed.']);
                }

                $filename = 'avatars/' . uniqid() . '.' . $type;
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
            'password' => Hash::make($validated['password']),
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
}
