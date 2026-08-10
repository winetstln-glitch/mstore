<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && ! $user->company_id) {
            // Jangan auto-assign ke company pertama — ini celah keamanan multi-tenant.
            // Arahkan user ke halaman error atau onboarding.
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'Akun belum memiliki perusahaan yang dikonfigurasi.',
                    'message' => 'Hubungi administrator untuk mengaitkan akun Anda ke perusahaan.',
                ], 403);
            }

            abort(403, 'Akun Anda belum dikonfigurasi ke perusahaan manapun. Hubungi administrator.');
        }

        return $next($request);
    }
}