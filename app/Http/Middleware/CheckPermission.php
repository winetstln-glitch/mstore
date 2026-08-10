<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Daftar nama role yang memiliki akses ke semua fitur (super-admin).
     * Gunakan config agar mudah diubah tanpa deploy ulang.
     *
     * @var array<string>
     */
    protected function getSuperAdminRoles(): array
    {
        return config('auth.super_admin_roles', ['admin', 'direktur', 'hrd-manager']);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        // Super-admin roles bypass semua permission check.
        // Nama role dibaca dari config/auth.php agar mudah diubah tanpa modifikasi kode.
        foreach ($this->getSuperAdminRoles() as $superRole) {
            if ($user->hasRole($superRole)) {
                return $next($request);
            }
        }

        $permissions = explode('|', $permission);

        foreach ($permissions as $perm) {
            if ($user->hasPermission($perm)) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized action.');
    }
}
