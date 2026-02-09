<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    public function showForgotForm()
    {
        return view('auth.forgot');
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string'],
        ]);

        $identifier = $request->identifier;

        $user = User::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if (!$user) {
            return back()->withErrors(['identifier' => __('User tidak ditemukan berdasarkan email atau nomor HP')]);
        }

        $code = random_int(100000, 999999);
        $hashedCode = Hash::make((string) $code);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => $hashedCode,
                'created_at' => now(),
            ]
        );

        try {
            Mail::raw("Kode reset password Anda: {$code}\nKode berlaku 10 menit.", function ($message) use ($user) {
                $message->to($user->email)->subject('Reset Password - Kode OTP');
            });
        } catch (\Throwable $e) {
            // ignore mail failure to allow whatsapp fallback
        }

        if (!empty($user->phone)) {
            try {
                $wa = new WhatsAppService();
                $wa->sendMessage($user->phone, "*KODE RESET PASSWORD*\n\nKode OTP: {$code}\nBerlaku 10 menit.", 'auth');
            } catch (\Throwable $e) {
                // ignore whatsapp failure
            }
        }

        return redirect()->route('password.reset.form', ['email' => $user->email])
            ->with('success', __('Kode OTP telah dikirim ke email dan/atau WhatsApp terdaftar.'));
    }

    public function showResetForm(Request $request)
    {
        $email = $request->query('email');
        return view('auth.reset', compact('email'));
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'min:6', 'max:6'],
            'password' => ['required', 'confirmed'],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return back()->withErrors(['email' => __('Token reset tidak ditemukan untuk email ini.')]);
        }

        $isExpired = now()->diffInMinutes($record->created_at) > 10;
        if ($isExpired) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->withErrors(['code' => __('Kode OTP telah kedaluwarsa. Silakan minta kode baru.')]);
        }

        if (!Hash::check($request->code, $record->token)) {
            return back()->withErrors(['code' => __('Kode OTP tidak sesuai.')]);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors(['email' => __('User tidak ditemukan.')]);
        }

        $user->update([
            'password' => $request->password,
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('success', __('Password berhasil direset. Silakan login kembali.'));
    }
}

