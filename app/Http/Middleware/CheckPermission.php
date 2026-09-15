<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    protected function getSuperAdminRoles(): array
    {
        return Role::superAdminRoleNames();
    }

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        foreach ($this->getSuperAdminRoles() as $superRole) {
            if (method_exists($user, 'hasRole') && $user->hasRole($superRole)) {
                return $next($request);
            }
        }

        $permissions = explode('|', $permission);

        foreach ($permissions as $perm) {
            if (method_exists($user, 'hasPermission') && $user->hasPermission($perm)) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized action.');
    }
}
